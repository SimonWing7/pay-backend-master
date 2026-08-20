<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Two-Factor Verification - Edfundo Pay</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        * { font-family: 'Plus Jakarta Sans', sans-serif; }
        .btn-gradient {
            background: linear-gradient(135deg, #3d01bd, #00bdff);
            color: white;
            font-weight: 700;
            padding: 12px;
            border-radius: 10px;
            border: none;
            cursor: pointer;
            font-size: 15px;
            width: 100%;
            transition: opacity 0.15s;
        }
        .btn-gradient:hover { opacity: 0.9; }
        .form-input {
            width: 100%;
            padding: 11px 14px;
            border: 1.5px solid #e2e5ef;
            border-radius: 8px;
            font-size: 20px;
            letter-spacing: 0.3em;
            text-align: center;
            color: #111827;
            outline: none;
            transition: border-color 0.15s;
            font-family: 'Plus Jakarta Sans', sans-serif;
        }
        .form-input:focus { border-color: #3d01bd; }
    </style>
</head>
<body style="background-color: #000026; min-height: 100vh; display: flex; align-items: center; justify-content: center;">

    <div style="width: 100%; max-width: 440px; padding: 0 16px;">

        <div style="text-align: center; margin-bottom: 32px;">
            <img src="/images/edfundo-logo-white.png" alt="Edfundo Pay" style="height: 40px; width: auto; margin: 0 auto 16px;">
            <h1 style="font-size: 22px; font-weight: 800; color: #ffffff; margin: 0;">Verification Required</h1>
            <p style="font-size: 14px; color: rgba(255,255,255,0.4); margin-top: 4px;">Enter the code from your authenticator app</p>
        </div>

        <div style="background: white; border-radius: 16px; padding: 36px; box-shadow: 0 4px 40px rgba(0,0,0,0.3);">

            @if($errors->any())
                <div style="background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; padding: 12px 16px; border-radius: 8px; font-size: 14px; margin-bottom: 20px;">
                    @foreach($errors->all() as $error)
                        <p>{{ $error }}</p>
                    @endforeach
                </div>
            @endif

            <form method="POST" action="{{ route('admin.two-factor.challenge.post') }}">
                @csrf

                <div style="margin-bottom: 24px;">
                    <label for="code" style="display: block; font-size: 13px; font-weight: 600; color: #374151; margin-bottom: 6px;">6-digit code</label>
                    <input type="text" name="code" id="code" required autofocus autocomplete="one-time-code"
                        inputmode="numeric" maxlength="12" class="form-input" placeholder="000000">
                    <p style="font-size: 12px; color: #9ca3af; margin-top: 8px;">Lost access to your authenticator? Enter one of your recovery codes instead.</p>
                </div>

                <button type="submit" class="btn-gradient">Verify</button>
            </form>
        </div>

        <p style="text-align: center; font-size: 13px; color: rgba(255,255,255,0.3); margin-top: 24px;">
            Edfundo Pay &mdash; Internal use only
        </p>
    </div>

</body>
</html>
