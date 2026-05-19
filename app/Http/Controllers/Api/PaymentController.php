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
        $amount = 199; // Amount in INR

        // Create order in Razorpay (amount must be in paise)
        $orderData = [
            'receipt'         => 'rcptid_' . uniqid(),
            'amount'          => $amount * 100, // 19900 paise = 199 INR
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
                'plan_type' => 'PRO'
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
            DB::transaction(function () use ($request) {
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

                // Upgrade User to PRO
                $user = $transaction->user;
                $user->is_pro = true;
                $user->pro_expires_at = now()->addMonth(); // 1 month from now
                $user->save();
            });

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

            DB::transaction(function () use ($orderId, $paymentId, $event, $data) {
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
                    $user->is_pro = true;
                    $user->pro_expires_at = now()->addMonth();
                    $user->save();
                } 
                elseif ($event === 'payment.failed') {
                    $transaction->update([
                        'status' => 'FAILED',
                        'razorpay_payment_id' => $paymentId,
                        'gateway_response' => $data
                    ]);
                }
            });
        }

        return response()->json(['status' => 'ok']);
    }
}
