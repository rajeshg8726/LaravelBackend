<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Welcome to RGJobs — Career Intelligence</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;900&display=swap');
        body { font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif; background-color: #0f172a; margin: 0; padding: 40px 0; -webkit-font-smoothing: antialiased; }
        .wrapper { width: 100%; table-layout: fixed; background-color: #0f172a; }
        .webkit { max-width: 600px; background-color: #1e293b; margin: 0 auto; border-radius: 16px; border: 1px solid #334155; overflow: hidden; }
        .header { background: linear-gradient(135deg, #1e3a8a 0%, #312e81 100%); padding: 40px 30px; text-align: center; border-bottom: 1px solid #334155; }
        .logo { color: #ffffff; font-size: 28px; font-weight: 900; letter-spacing: -1px; margin: 0; text-transform: uppercase; }
        .logo span { color: #60a5fa; }
        .subtitle { color: #94a3b8; font-size: 13px; font-weight: 600; text-transform: uppercase; letter-spacing: 2px; margin-top: 8px; }
        .content { padding: 40px 40px; color: #cbd5e1; line-height: 1.6; font-size: 15px; }
        .content h2 { color: #ffffff; margin-top: 0; font-size: 22px; font-weight: 700; letter-spacing: -0.5px; }
        .callout { background-color: #0f172a; border: 1px solid #334155; border-radius: 12px; padding: 24px; margin: 30px 0; border-left: 4px solid #3b82f6; }
        .callout-title { color: #ffffff; font-weight: 600; margin-bottom: 8px; display: block; font-size: 16px; }
        .button { display: inline-block; background-color: #2563eb; color: #ffffff; padding: 14px 28px; text-decoration: none; border-radius: 8px; font-weight: 600; font-size: 15px; margin-top: 10px; transition: background-color 0.2s; }
        .button:hover { background-color: #1d4ed8; }
        .footer { background-color: #0f172a; padding: 30px 40px; text-align: center; font-size: 12px; color: #64748b; border-top: 1px solid #334155; }
        .footer-logo { font-weight: 700; color: #94a3b8; margin-bottom: 10px; display: block; }
        .divider { height: 1px; background-color: #334155; margin: 30px 0; }
        @media screen and (max-width: 600px) {
            .webkit { width: 100% !important; border-radius: 0; }
            .content { padding: 30px 20px; }
        }
    </style>
</head>
<body>
    <center class="wrapper">
        <div class="webkit">
            <div class="header">
                <div class="logo">RG<span>Jobs</span></div>
                <div class="subtitle">Career Intelligence Platform</div>
            </div>
            <div class="content">
                <h2>Welcome, {{ explode(' ', $user->full_name)[0] }}.</h2>
                <p>You have successfully activated your account on the <strong>RGJobs Career Intelligence Platform</strong>. Your workspace is now ready.</p>
                
                <p>We do not operate like a traditional job board. Our platform is engineered to give you deep visibility into your ATS performance, optimize your application pipelines, and intelligently map your skills to enterprise technical requirements.</p>

                <div class="callout">
                    <span class="callout-title">Your Technical Pipeline is Initialized</span>
                    To help you calibrate your first application, we have provisioned your account with <strong>3 Complimentary AI Intelligence Credits</strong>. Run your current resume through our parser to uncover structural gaps and ATS rejection risks immediately.
                </div>

                <p>To acquire 3 additional intelligence credits instantly, simply upload your base resume to your dashboard profile.</p>

                <div style="text-align: center; margin: 40px 0;">
                    <a href="{{ env('FRONTEND_URL', 'https://www.rgjobs.in') }}/login" class="button">Access Your Workspace</a>
                </div>

                <div class="divider"></div>

                <p style="margin-bottom: 0; color: #94a3b8;">
                    <strong>Rajesh Gupta</strong><br>
                    Founder & Lead Engineer
                </p>
            </div>
            <div class="footer">
                <span class="footer-logo">RGJobs.in</span>
                &copy; {{ date('Y') }} RGJobs Career Intelligence.<br>
                Security Notice: You are receiving this because an account was provisioned with your email.
            </div>
        </div>
    </center>
</body>
</html>
