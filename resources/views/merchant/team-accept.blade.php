<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Set your password - Edfundo Pay</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        * { font-family: 'Plus Jakarta Sans', sans-serif; }
        .btn-gradient {
            background: linear-gradient(135deg, #3d01bd, #00bdff);
            color: white; font-weight: 700; padding: 12px;
            border-radius: 10px; border: none; cursor: pointer;
            font-size: 15px; width: 100%; transition: opacity 0.15s;
        }
        .btn-gradient:hover { opacity: 0.9; }
        .form-input {
            width: 100%; padding: 11px 14px;
            border: 1.5px solid #e2e5ef; border-radius: 8px;
            font-size: 14px; color: #111827; outline: none;
            transition: border-color 0.15s;
            font-family: 'Plus Jakarta Sans', sans-serif;
        }
        .form-input:focus { border-color: #3d01bd; }
    </style>
</head>
<body style="background-color: #f5f6fa; min-height: 100vh; display: flex; align-items: center; justify-content: center;">
    <div style="width: 100%; max-width: 440px; padding: 0 16px;">

        <div style="text-align: center; margin-bottom: 32px;">
            <img src="/images/edfundo-logo-colour.png" alt="Edfundo Pay" style="height: 40px; width: auto; margin: 0 auto 16px;">
            <h1 style="font-size: 22px; font-weight: 800; color: #000026; margin: 0;">Set your password</h1>
            @if($member)
            <p style="font-size: 14px; color: #9ca3af; margin-top: 4px;">{{ $member->email }}</p>
            @endif
        </div>

        <div style="background: white; border-radius: 16px; padding: 36px; box-shadow: 0 4px 24px rgba(0,0,38,0.08); border: 1px solid #eef0f5;">

            @if(!$member)
                <p style="font-size: 15px; color: #374151; line-height: 1.6; margin: 0 0 20px;">This link has expired or has already been used.</p>
                <p style="font-size: 14px; color: #6b7280; line-height: 1.6; margin: 0 0 24px;">If you were invited, ask the account owner to resend your invitation. If you already have a password and forgot it, use <strong>Forgot password</strong> on the sign-in page.</p>
                <a href="{{ route('merchant.login') }}" class="btn-gradient" style="display: block; text-align: center; text-decoration: none;">Go to sign in</a>
            @else
                @if($errors->any())
                    <div style="background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; padding: 12px 16px; border-radius: 8px; font-size: 14px; margin-bottom: 20px;">
                        @foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach
                    </div>
                @endif

                <p style="font-size: 14px; color: #6b7280; margin: 0 0 20px;">Hi {{ $member->name }}, choose a password to {{ $member->isPending() ? 'finish setting up your access' : 'reset your access' }}.</p>

                <form method="POST" action="{{ route('merchant.team.accept.post', $token) }}">
                    @csrf

                    <div style="margin-bottom: 18px;">
                        <label for="password" style="display: block; font-size: 13px; font-weight: 600; color: #374151; margin-bottom: 6px;">Password</label>
                        <input type="password" name="password" id="password" required minlength="8" autocomplete="new-password"
                            class="form-input" placeholder="Minimum 8 characters">
                    </div>

                    <div style="margin-bottom: 28px;">
                        <label for="password_confirmation" style="display: block; font-size: 13px; font-weight: 600; color: #374151; margin-bottom: 6px;">Confirm password</label>
                        <input type="password" name="password_confirmation" id="password_confirmation" required autocomplete="new-password"
                            class="form-input" placeholder="Repeat your password">
                    </div>

                    <button type="submit" class="btn-gradient">Set Password</button>
                </form>
            @endif
        </div>

        <p style="text-align: center; font-size: 13px; color: #9ca3af; margin-top: 24px;">
            Powered by <strong style="color: #3d01bd;">Edfundo Pay</strong>
        </p>
    </div>
</body>
</html>
