<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Merchant Login - Edfundo Pay</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        * { font-family: 'Plus Jakarta Sans', sans-serif; }
        .gradient-bg { background: linear-gradient(135deg, #3d01bd 0%, #00bdff 100%); }
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
            font-size: 14px;
            color: #111827;
            outline: none;
            transition: border-color 0.15s;
            font-family: 'Plus Jakarta Sans', sans-serif;
        }
        .form-input:focus { border-color: #3d01bd; }
    </style>
</head>
<body style="background-color: #f5f6fa; min-height: 100vh; display: flex; align-items: center; justify-content: center;">

    <div style="width: 100%; max-width: 440px; padding: 0 16px;">

        {{-- Logo --}}
        <div style="text-align: center; margin-bottom: 32px;">
            <img src="/images/edfundo-logo-colour.png" alt="Edfundo Pay" style="height: 40px; width: auto; margin: 0 auto 16px;">
            <h1 style="font-size: 22px; font-weight: 800; color: #000026; margin: 0;">Merchant Portal</h1>
            <p style="font-size: 14px; color: #9ca3af; margin-top: 4px;">Sign in to your dashboard</p>
        </div>

        {{-- Card --}}
        <div style="background: white; border-radius: 16px; padding: 36px; box-shadow: 0 4px 24px rgba(0,0,38,0.08); border: 1px solid #eef0f5;">

            @if($errors->any())
                <div style="background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; padding: 12px 16px; border-radius: 8px; font-size: 14px; margin-bottom: 20px;">
                    @foreach($errors->all() as $error)
                        <p>{{ $error }}</p>
                    @endforeach
                </div>
            @endif

            <form method="POST" action="{{ route('merchant.login.post') }}">
                @csrf

                <div style="margin-bottom: 18px;">
                    <label for="email" style="display: block; font-size: 13px; font-weight: 600; color: #374151; margin-bottom: 6px;">Email address</label>
                    <input type="email" name="email" id="email" value="{{ old('email') }}" required
                        class="form-input" placeholder="you@example.com">
                </div>

                <div style="margin-bottom: 28px;">
                    <label for="password" style="display: block; font-size: 13px; font-weight: 600; color: #374151; margin-bottom: 6px;">Password</label>
                    <input type="password" name="password" id="password" required
                        class="form-input" placeholder="••••••••">
                </div>

                <button type="submit" class="btn-gradient">Sign in</button>
            </form>
        </div>

        <p style="text-align: center; font-size: 13px; color: #9ca3af; margin-top: 24px;">
            Powered by <strong style="color: #3d01bd;">Edfundo Pay</strong>
        </p>
    </div>

</body>
</html>
