<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Feedback Request</title>
    <style>
        body {
            font-family: 'Inter', Helvetica, Arial, sans-serif;
            background-color: #f4f4f7;
            color: #51545e;
            margin: 0;
            padding: 0;
            width: 100%;
        }
        .email-wrapper {
            width: 100%;
            margin: 0;
            padding: 0;
            background-color: #f4f4f7;
        }
        .email-content {
            width: 100%;
            max-width: 570px;
            margin: 0 auto;
            padding: 20px;
        }
        .email-body {
            background-color: #ffffff;
            padding: 45px;
            border-radius: 20px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        }
        .logo {
            text-align: center;
            margin-bottom: 30px;
        }
        .logo-box {
            display: inline-block;
            background-color: #008080;
            color: #ffffff;
            padding: 10px 15px;
            border-radius: 10px;
            font-weight: 800;
            font-size: 20px;
            text-decoration: none;
        }
        h1 {
            color: #333333;
            font-size: 24px;
            font-weight: 800;
            margin-top: 0;
            text-align: center;
            letter-spacing: -0.5px;
        }
        p {
            font-size: 16px;
            line-height: 1.6;
            margin-bottom: 25px;
        }
        .button {
            display: block;
            width: 200px;
            margin: 30px auto;
            padding: 15px;
            background-color: #008080;
            color: #ffffff !important;
            text-align: center;
            text-decoration: none;
            border-radius: 12px;
            font-weight: 700;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 1px;
            box-shadow: 0 10px 20px rgba(0, 128, 128, 0.2);
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            font-size: 12px;
            color: #b0adc5;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .survey-info {
            background-color: #f8fafc;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 25px;
            border-left: 4px solid #008080;
        }
        .survey-info p {
            margin: 0;
            font-size: 14px;
            font-weight: 600;
            color: #475569;
        }
    </style>
</head>
<body>
    <div class="email-wrapper">
        <div class="email-content">
            <div class="logo">
                <a href="{{ config('app.url') }}" class="logo-box">Smart Survey</a>
            </div>
            <div class="email-body">
                <h1>Thank You, {{ $survey->user->name }}!</h1>
                <p>We are pleased to inform you that your land surveying project has been successfully completed.</p>
                
                <div class="survey-info">
                    <p>Project ID: #{{ $survey->id }}</p>
                    <p>Location: {{ $survey->village }}</p>
                    <p>Status: Completed</p>
                </div>

                <p>At <strong>Smart Survey</strong>, we strive for precision and excellence in everything we do. Your feedback is incredibly valuable to us and helps us improve our services for everyone.</p>
                
                <p>Could you spare a minute to share your experience with us?</p>
                
                <a href="{{ route('feedback.index') }}" class="button">Give Feedback</a>

                <p>Thank you for choosing Smart Survey for your engineering needs.</p>
                
                <p>Best regards,<br>The Smart Survey Team</p>
            </div>
            <div class="footer">
                &copy; {{ date('Y') }} Smart Survey Systems • Precision First
            </div>
        </div>
    </div>
</body>
</html>
