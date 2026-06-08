<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Your Password</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            width: 100% !important;
            height: 100% !important;
            background-color: #0b0f19;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }
        table {
            border-collapse: collapse;
            border-spacing: 0;
        }
        img {
            border: 0;
            line-height: 100%;
            outline: none;
            text-decoration: none;
        }
        @media only screen and (max-width: 600px) {
            .email-container {
                width: 100% !important;
                padding: 10px !important;
            }
            .content-card {
                padding: 24px !important;
            }
        }
    </style>
</head>
<body style="margin: 0; padding: 0; background-color: #0b0f19; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;">
    <table width="100%" border="0" cellspacing="0" cellpadding="0" style="background-color: #0b0f19; width: 100%; table-layout: fixed;">
        <tr>
            <td align="center" style="padding: 40px 10px 40px 10px;">
                <!--[if (gte mso 9)|(IE)]>
                <table width="600" align="center" border="0" cellspacing="0" cellpadding="0">
                <tr>
                <td>
                <![endif]-->
                <table class="email-container" width="100%" max-width="600" border="0" cellspacing="0" cellpadding="0" style="max-width: 600px; width: 100%;">
                    
                    <!-- Logo / Header -->
                    <tr>
                        <td align="center" style="padding-bottom: 30px;">
                            <table border="0" cellspacing="0" cellpadding="0">
                                <tr>
                                    <td align="center" style="background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 100%); padding: 14px 28px; border-radius: 12px; border: 1px solid #312e81; box-shadow: 0 4px 20px rgba(79, 70, 229, 0.3);">
                                        <span style="font-size: 22px; font-weight: 800; color: #ffffff; letter-spacing: 2px; text-transform: uppercase; font-family: 'Outfit', -apple-system, BlinkMacSystemFont, sans-serif;">RG<span style="color: #a78bfa;">JOBS</span></span>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Content Card -->
                    <tr>
                        <td>
                            <table class="content-card" width="100%" border="0" cellspacing="0" cellpadding="0" style="background-color: #111827; border: 1px solid #1f2937; border-radius: 16px; padding: 40px; box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);">
                                <!-- Greeting -->
                                <tr>
                                    <td style="padding-bottom: 24px;">
                                        <p style="margin: 0 0 8px 0; font-size: 12px; font-weight: 700; color: #a78bfa; text-transform: uppercase; letter-spacing: 1.5px;">Premium Account Security</p>
                                        <h1 style="margin: 0; font-size: 26px; font-weight: 800; color: #ffffff; line-height: 1.3;">Account Recovery</h1>
                                    </td>
                                </tr>
                                
                                <!-- Introduction -->
                                <tr>
                                    <td style="padding-bottom: 24px;">
                                        <p style="margin: 0 0 12px 0; font-size: 16px; color: #d1d5db; line-height: 1.6;">
                                            Hello <strong>{{ $name }}</strong>,
                                        </p>
                                        <p style="margin: 0; font-size: 15px; color: #9ca3af; line-height: 1.6;">
                                            A security request was initiated to reset access to your premium <strong>RGJobs</strong> account. Your career intelligence data remains protected and no changes have been made yet.
                                        </p>
                                    </td>
                                </tr>

                                <!-- Detail Box -->
                                <tr>
                                    <td style="padding-bottom: 30px;">
                                        <table width="100%" border="0" cellspacing="0" cellpadding="0" style="background-color: #1f2937; border-radius: 10px; border: 1px solid #374151;">
                                            <tr>
                                                <td style="padding: 16px;">
                                                    <table width="100%" border="0" cellspacing="0" cellpadding="0">
                                                        <tr>
                                                            <td style="padding-bottom: 12px;">
                                                                <span style="font-size: 11px; color: #9ca3af; text-transform: uppercase; letter-spacing: 1px; font-weight: 600;">Request Type</span><br>
                                                                <span style="font-size: 15px; color: #ffffff; font-weight: 500; margin-top: 4px; display: block;">Password Reset Authorization</span>
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <td style="padding-top: 12px; border-top: 1px solid #374151;">
                                                                <span style="font-size: 11px; color: #9ca3af; text-transform: uppercase; letter-spacing: 1px; font-weight: 600;">Status</span><br>
                                                                <span style="font-size: 15px; color: #34d399; font-weight: 600; margin-top: 4px; display: block;">Pending User Action</span>
                                                            </td>
                                                        </tr>
                                                    </table>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>

                                <!-- Action Button -->
                                <tr>
                                    <td align="center" style="padding: 10px 0 30px 0;">
                                        <table border="0" cellspacing="0" cellpadding="0">
                                            <tr>
                                                <td align="center" style="border-radius: 8px; background: linear-gradient(135deg, #7c3aed 0%, #4f46e5 100%); box-shadow: 0 4px 15px rgba(99, 102, 241, 0.3);">
                                                    <a href="{{ $url }}" target="_blank" style="display: inline-block; padding: 14px 30px; font-size: 16px; font-weight: 600; color: #ffffff; text-decoration: none; border-radius: 8px; border: 1px solid rgba(255,255,255,0.1); text-align: center; letter-spacing: 0.5px;">
                                                        Authorize Password Reset
                                                    </a>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>

                                <!-- Expiry / Security -->
                                <tr>
                                    <td style="padding-bottom: 24px; border-bottom: 1px solid #1f2937;">
                                        <p style="margin: 0 0 12px 0; font-size: 14px; color: #9ca3af; line-height: 1.5;">
                                            <strong>Security Notice:</strong> This password reset link will expire in 60 minutes.
                                        </p>
                                        <p style="margin: 0; font-size: 14px; color: #6b7280; line-height: 1.5;">
                                            If you did not request a password reset, you can safely ignore this email — your account remains secure.
                                        </p>
                                    </td>
                                </tr>

                                <!-- Direct Link Fallback -->
                                <tr>
                                    <td style="padding-top: 24px;">
                                        <p style="margin: 0 0 8px 0; font-size: 12px; color: #6b7280; line-height: 1.5;">
                                            If you're having trouble clicking the "Reset Account Password" button, copy and paste the URL below into your web browser:
                                        </p>
                                        <p style="margin: 0; font-size: 12px; word-break: break-all; line-height: 1.5;">
                                            <a href="{{ $url }}" target="_blank" style="color: #6366f1; text-decoration: none; word-break: break-all;">{{ $url }}</a>
                                        </p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td align="center" style="padding-top: 30px;">
                            <p style="margin: 0 0 8px 0; font-size: 12px; color: #4b5563; font-weight: 500; letter-spacing: 0.5px;">
                                RGJobs AI Career Intelligence Platform
                            </p>
                            <p style="margin: 0 0 8px 0; font-size: 11px; color: #4b5563;">
                                Secure Transactional Notification
                            </p>
                            <p style="margin: 0; font-size: 11px; color: #374151;">
                                &copy; 2026 RGJobs. All rights reserved.
                            </p>
                        </td>
                    </tr>

                </table>
                <!--[if (gte mso 9)|(IE)]>
                </td>
                </tr>
                </table>
                <![endif]-->
            </td>
        </tr>
    </table>
</body>
</html>
