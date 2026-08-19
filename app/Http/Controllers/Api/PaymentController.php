<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Razorpay\Api\Api;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    private $api;

    public function __construct()
    {
        $this->api = new Api(config('services.razorpay.key'), config('services.razorpay.secret'));
    }

    // 1. Create Order
    public function createOrder(Request $request)
    {
        $user = $request->user();
        
        // Accept package_type: 'PRO' (₹199) or 'TOPUP' (₹49)
        $packageType = $request->input('package_type', 'PRO');
        $amount = ($packageType === 'TOPUP') ? 49 : 199;

        // Create order in Razorpay (amount must be in paise)
        $orderData = [
            'receipt'         => 'rcptid_' . uniqid(),
            'amount'          => $amount * 100, // 4900 or 19900 paise
            'currency'        => 'INR',
            'payment_capture' => 1 // auto capture
        ];

        try {
            $razorpayOrder = $this->api->order->create($orderData);

            // Save pending transaction
            Transaction::create([
                'user_id' => $user->id,
                'razorpay_order_id' => $razorpayOrder['id'],
                'amount' => $amount,
                'status' => 'PENDING',
                'plan_type' => ($packageType === 'TOPUP') ? 'TOPUP' : 'PRO'
            ]);

            return response()->json([
                'order_id' => $razorpayOrder['id'],
                'amount' => $amount * 100
            ]);

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    // 2. Verify Payment (Frontend Callback)
    public function verifyPayment(Request $request)
    {
        $request->validate([
            'razorpay_order_id' => 'required',
            'razorpay_payment_id' => 'required',
            'razorpay_signature' => 'required'
        ]);

        try {
            $attributes = [
                'razorpay_order_id' => $request->razorpay_order_id,
                'razorpay_payment_id' => $request->razorpay_payment_id,
                'razorpay_signature' => $request->razorpay_signature
            ];

            // Verify the signature
            $this->api->utility->verifyPaymentSignature($attributes);

            // Safety mechanism: DB Transaction + Pessimistic Locking
            $upgradedUser = null;
            $savedTransaction = null;

            DB::transaction(function () use ($request, &$upgradedUser, &$savedTransaction) {
                $transaction = Transaction::where('razorpay_order_id', $request->razorpay_order_id)
                    ->lockForUpdate() // Prevent race conditions with webhooks
                    ->firstOrFail();

                // Idempotency: Prevent double upgrades
                if ($transaction->status === 'SUCCESS') {
                    return;
                }

                // Update transaction
                $transaction->update([
                    'status' => 'SUCCESS',
                    'razorpay_payment_id' => $request->razorpay_payment_id
                ]);

                // Apply Plan Benefits: Upgrade to PRO or Increment Credits for TOPUP
                $user = $transaction->user;
                if ($transaction->plan_type === 'TOPUP') {
                    // Add 10 credits for top-up
                    $user->increment('ai_credits', 10);
                } else {
                    // Standard PRO upgrade
                    $user->is_pro = true;
                    $user->pro_expires_at = now()->addMonth(); // 1 month from now
                    $user->save();
                }

                $upgradedUser = $user;
                $savedTransaction = $transaction;
            });

            if ($upgradedUser && $savedTransaction) {
                $this->sendProUpgradeEmail($upgradedUser, $savedTransaction);
            }

            return response()->json(['message' => 'Payment successful and user upgraded.']);

        } catch (\Exception $e) {
            Log::error('Razorpay Signature Verification Failed: ' . $e->getMessage());
            return response()->json(['error' => 'Payment verification failed'], 400);
        }
    }

    // 3. Webhook (Safety Net)
    public function webhook(Request $request)
    {
        $webhookSecret = config('services.razorpay.webhook_secret');
        $webhookSignature = $request->header('X-Razorpay-Signature');
        $payload = $request->getContent();

        try {
            // Verify Webhook Signature
            $this->api->utility->verifyWebhookSignature($payload, $webhookSignature, $webhookSecret);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Invalid webhook signature'], 400);
        }

        $data = json_decode($payload, true);
        $event = $data['event'];

        if ($event === 'payment.captured' || $event === 'payment.failed') {
            $paymentEntity = $data['payload']['payment']['entity'];
            $orderId = $paymentEntity['order_id'];
            $paymentId = $paymentEntity['id'];

            $upgradedUser = null;
            $savedTransaction = null;

            DB::transaction(function () use ($orderId, $paymentId, $event, $data, &$upgradedUser, &$savedTransaction) {
                $transaction = Transaction::where('razorpay_order_id', $orderId)
                    ->lockForUpdate()
                    ->first();

                if (!$transaction || $transaction->status === 'SUCCESS') {
                    return; // Ignore if not found or already processed
                }

                if ($event === 'payment.captured') {
                    $transaction->update([
                        'status' => 'SUCCESS',
                        'razorpay_payment_id' => $paymentId,
                        'gateway_response' => $data
                    ]);

                    $user = $transaction->user;
                    if ($transaction->plan_type === 'TOPUP') {
                        $user->increment('ai_credits', 10);
                    } else {
                        $user->is_pro = true;
                        $user->pro_expires_at = now()->addMonth();
                        $user->save();
                    }

                    $upgradedUser = $user;
                    $savedTransaction = $transaction;
                } 
                elseif ($event === 'payment.failed') {
                    $transaction->update([
                        'status' => 'FAILED',
                        'razorpay_payment_id' => $paymentId,
                        'gateway_response' => $data
                    ]);
                }
            });

            if ($upgradedUser && $savedTransaction) {
                $this->sendProUpgradeEmail($upgradedUser, $savedTransaction);
            }
        }

        return response()->json(['status' => 'ok']);
    }

    // 4. Send Transactional Receipt Email (Self-Healing Safety Net)
    private function sendProUpgradeEmail($user, $transaction)
    {
        try {
            Log::info("Sending payment receipt email to {$user->email} for plan: {$transaction->plan_type}");
            
            $subject = $transaction->plan_type === 'TOPUP' 
                ? "Receipt: 10 AI Match Credits Top-Up - RGJobs ⚡" 
                : "Welcome to RGJobs PRO! 🚀";
                
            $headerTitle = $transaction->plan_type === 'TOPUP'
                ? "Credits Loaded Successfully!"
                : "Your PRO Pass is Active!";
                
            $introText = $transaction->plan_type === 'TOPUP'
                ? "Thank you for purchasing the 10 AI Match Credits Top-Up pack on RGJobs! We appreciate your trust in us to assist with your career search."
                : "Thank you for upgrading to RGJobs PRO! We are extremely excited to back you up with premium, state-of-the-art tools to land your next dream job.";

            $planName = $transaction->plan_type === 'TOPUP'
                ? "10 AI Match Credits Pack"
                : "30-Day Unlimited PRO Pass";

            $totalAmount = "₹" . number_format($transaction->amount, 2);
            $paymentDate = $transaction->created_at ? $transaction->created_at->format('d M Y, h:i A') : now()->format('d M Y, h:i A');
            $paymentId = $transaction->razorpay_payment_id ?? 'N/A';

            $benefitsList = $transaction->plan_type === 'TOPUP'
                ? '<li style="margin-bottom: 8px;"><strong>10 Instant AI Matches:</strong> Premium resume-to-job compatibility checks powered by Gemini/Groq.</li>' .
                  '<li style="margin-bottom: 8px;"><strong>Weekly Auto-Refreshes:</strong> Get +1 credit added every week automatically up to a cap of 6.</li>' .
                  '<li style="margin-bottom: 8px;"><strong>Profile Bonus Eligible:</strong> Complete 80% of your candidate profile to earn an instant +3 bonus credits.</li>'
                : '<li style="margin-bottom: 8px;"><strong>Unlimited AI Matches:</strong> 30 days of infinite, deep-dive compatibility scoring.</li>' .
                  '<li style="margin-bottom: 8px;"><strong>Vector Cover Letters:</strong> Beautiful, tailored cover letters instantly generated for every vacancy.</li>' .
                  '<li style="margin-bottom: 8px;"><strong>Interview Prep Kits:</strong> Custom interview response guides tailored to your exact profile.</li>' .
                  '<li style="margin-bottom: 8px;"><strong>Resume Biography Optimization:</strong> Professional bio suggestions that get you noticed instantly.</li>';

            $htmlContent = <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Receipt</title>
</head>
<body style="margin: 0; padding: 0; background-color: #f8fafc; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; -webkit-font-smoothing: antialiased;">
    <table border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color: #f8fafc; padding: 20px 0;">
        <tr>
            <td align="center">
                <table border="0" cellpadding="0" cellspacing="0" width="100%" style="max-width: 600px; background-color: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03); border: 1px solid #e2e8f0;">
                    <!-- HEADER GRADIENT -->
                    <tr>
                        <td align="center" style="background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%); padding: 40px 20px; text-align: center;">
                            <div style="font-size: 24px; font-weight: 800; color: #ffffff; letter-spacing: 1px; margin-bottom: 8px; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;">RGJOBS</div>
                            <div style="display: inline-block; background-color: rgba(255, 255, 255, 0.15); color: #ffffff; font-size: 12px; font-weight: 600; padding: 6px 16px; border-radius: 9999px; text-transform: uppercase; letter-spacing: 0.5px; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;">Payment Confirmed</div>
                            <h1 style="color: #ffffff; font-size: 26px; font-weight: 700; margin: 20px 0 0 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;">{$headerTitle}</h1>
                        </td>
                    </tr>
                    <!-- CONTENT -->
                    <tr>
                        <td style="padding: 40px 30px;">
                            <p style="font-size: 16px; line-height: 24px; color: #334155; margin-top: 0;">Hi <strong>{$user->full_name}</strong>,</p>
                            <p style="font-size: 16px; line-height: 24px; color: #334155;">{$introText}</p>

                            <!-- TRANSACTION INFO BOX -->
                            <div style="background-color: #f1f5f9; border-radius: 12px; padding: 20px; margin: 30px 0;">
                                <h3 style="font-size: 14px; font-weight: 700; text-transform: uppercase; color: #475569; margin: 0 0 16px 0; letter-spacing: 0.5px; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;">Receipt Details</h3>
                                <table border="0" cellpadding="0" cellspacing="0" width="100%">
                                    <tr>
                                        <td style="font-size: 14px; color: #64748b; padding: 6px 0; text-align: left; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;">Payment ID</td>
                                        <td align="right" style="font-size: 14px; font-weight: 600; color: #1e293b; padding: 6px 0; font-family: monospace;">{$paymentId}</td>
                                    </tr>
                                    <tr>
                                        <td style="font-size: 14px; color: #64748b; padding: 6px 0; text-align: left; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;">Date &amp; Time</td>
                                        <td align="right" style="font-size: 14px; font-weight: 600; color: #1e293b; padding: 6px 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;">{$paymentDate}</td>
                                    </tr>
                                    <tr>
                                        <td style="font-size: 14px; color: #64748b; padding: 6px 0; text-align: left; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;">Billing Plan</td>
                                        <td align="right" style="font-size: 14px; font-weight: 600; color: #1e293b; padding: 6px 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;">{$planName}</td>
                                    </tr>
                                    <tr>
                                        <td style="font-size: 14px; color: #64748b; padding: 6px 0; text-align: left; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;">Billing Type</td>
                                        <td align="right" style="font-size: 14px; font-weight: 600; color: #10b981; padding: 6px 0; display: inline-block; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;"><span style="text-transform: uppercase; font-size: 11px; background-color: #d1fae5; padding: 2px 8px; border-radius: 4px;">Safe One-Time Payment</span></td>
                                    </tr>
                                    <tr>
                                        <td style="font-size: 16px; font-weight: 700; color: #1e293b; padding: 16px 0 0 0; text-align: left; border-top: 1px solid #cbd5e1; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;">Total Paid</td>
                                        <td align="right" style="font-size: 20px; font-weight: 800; color: #4f46e5; padding: 16px 0 0 0; border-top: 1px solid #cbd5e1; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;">{$totalAmount}</td>
                                    </tr>
                                </table>
                            </div>

                            <!-- BENEFIT CARDS -->
                            <h3 style="font-size: 15px; font-weight: 700; color: #1e293b; margin: 0 0 16px 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;">What's Activated On Your Account</h3>
                            <ul style="margin: 0 0 30px 0; padding-left: 20px; font-size: 15px; line-height: 24px; color: #334155; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;">
                                {$benefitsList}
                            </ul>

                            <!-- GUARANTEES / TRUST LOOP -->
                            <table border="0" cellpadding="0" cellspacing="0" width="100%" style="margin-bottom: 30px;">
                                <tr>
                                    <td style="padding: 16px; background-color: #eff6ff; border-left: 4px solid #3b82f6; border-radius: 0 8px 8px 0; text-align: left; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;">
                                        <div style="font-size: 14px; font-weight: 700; color: #1e3a8a; margin-bottom: 4px;">🛡️ Safe One-Time Payment Guarantee</div>
                                        <div style="font-size: 13px; line-height: 18px; color: #1e40af;">
                                            This is a secure, 100% one-time transaction. <strong>We do not save your payment details or establish any automatic subscription mandates or recurring bank auto-debits.</strong> You are in absolute control!
                                        </div>
                                    </td>
                                </tr>
                                <tr><td style="height: 16px;"></td></tr>
                                <tr>
                                    <td style="padding: 16px; background-color: #ecfdf5; border-left: 4px solid #10b981; border-radius: 0 8px 8px 0; text-align: left; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;">
                                        <div style="font-size: 14px; font-weight: 700; color: #064e3b; margin-bottom: 4px;">🤝 100% Aggregator Transparency</div>
                                        <div style="font-size: 13px; line-height: 18px; color: #047857;">
                                            RGJobs is a premium, honest job aggregator. We index vacancies directly from official company sites and route you straight to their official application portals, protecting you from middlemen and data brokers.
                                        </div>
                                    </td>
                                </tr>
                            </table>

                            <!-- CTA BUTTON -->
                            <table border="0" cellpadding="0" cellspacing="0" width="100%">
                                <tr>
                                    <td align="center">
                                        <a href="https://www.rgjobs.in/login" style="display: inline-block; background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%); color: #ffffff; font-size: 16px; font-weight: 700; text-decoration: none; padding: 14px 32px; border-radius: 8px; box-shadow: 0 4px 6px -1px rgba(79, 70, 229, 0.2), 0 2px 4px -1px rgba(79, 70, 229, 0.1); transition: all 0.2s ease; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;">
                                            Go to Dashboard &amp; Start Matching 🚀
                                        </a>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <!-- FOOTER -->
                    <tr>
                        <td align="center" style="background-color: #f8fafc; border-top: 1px solid #e2e8f0; padding: 30px; text-align: center; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;">
                            <p style="font-size: 13px; color: #64748b; margin: 0 0 8px 0;">Thank you for trusting RGJobs with your career journey.</p>
                            <p style="font-size: 12px; color: #94a3b8; margin: 0;">RGJobs Inc., Girik Studio, Sultanpur-Lucknow Road, Lucknow, UP, India</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
HTML;

            \Illuminate\Support\Facades\Mail::html($htmlContent, function ($message) use ($user, $subject) {
                $message->to($user->email)
                    ->subject($subject);
            });
        } catch (\Exception $e) {
            Log::error('Failed to send transactional receipt email: ' . $e->getMessage());
        }
    }


    }
