<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @php
        $appNomComplet = \App\Models\AppSetting::get('app_nom', 'Serenity');
    @endphp
    <title>{{ $appNomComplet }} - Vérification OTP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Ubuntu:wght@300;400;500;700&display=swap" rel="stylesheet">
    <style>
        * { font-family: 'Ubuntu', sans-serif; font-weight: 300; }
        body { background-color: #f5f7fa; min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 2rem; }
        .verify-card { max-width: 400px; width: 100%; background: white; border-radius: 10px; box-shadow: 0 4px 20px rgba(0,0,0,0.08); padding: 1.5rem; }
        .verify-card h2 { color: var(--primary-dark-blue, #1e3a5f); font-size: 1.1rem; margin-bottom: 0.5rem; }
        .form-control { font-size: 1.25rem; letter-spacing: 0.5rem; text-align: center; }
        .btn-primary { background-color: var(--primary-dark-blue, #1e3a5f); border-color: var(--primary-dark-blue, #1e3a5f); }
    </style>
</head>
<body>
    <div class="verify-card">
        <h2><i class="bi bi-shield-lock"></i> Vérification de votre compte</h2>
        <p class="text-muted small mb-3">
            @if(session('email_sent') && isset($phone_masked))
                Un code à 6 chiffres a été envoyé par <strong>SMS</strong> (au numéro se terminant par <strong>{{ $phone_masked }}</strong>) et par <strong>email</strong>.
            @elseif(session('email_sent'))
                Un code à 6 chiffres a été envoyé sur votre adresse <strong>email</strong>.
            @else
                Un code à 6 chiffres a été envoyé par <strong>SMS</strong> au numéro se terminant par <strong>{{ $phone_masked ?? '****' }}</strong>.
            @endif
            Saisissez-le ci-dessous pour activer votre compte.
        </p>
        @if(session('success'))
            <div class="alert alert-success small">{{ session('success') }}</div>
        @endif
        @if($errors->any())
            <div class="alert alert-danger small">
                @foreach($errors->all() as $error){{ $error }}@endforeach
            </div>
        @endif
        <form method="POST" action="{{ route('membre.verify-otp') }}">
            @csrf
            <div class="mb-3">
                <label for="otp" class="form-label">Code OTP</label>
                <input type="text" class="form-control @error('otp') is-invalid @enderror" id="otp" name="otp" maxlength="6" pattern="[0-9]{6}" placeholder="123456" required autofocus>
                @error('otp')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <button type="submit" class="btn btn-primary w-100 mb-2"><i class="bi bi-check-circle"></i> Valider</button>
        </form>
        <form method="POST" action="{{ route('membre.resend-otp') }}" class="d-inline">
            @csrf
            <button type="submit" class="btn btn-link btn-sm p-0 text-muted">Renvoyer le code</button>
        </form>
        <div class="mt-3">
            <a href="{{ route('membre.register') }}" class="text-decoration-none small"><i class="bi bi-arrow-left"></i> Retour à l'inscription</a>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        (function() {
            document.querySelectorAll('.alert').forEach(function(alert) {
                setTimeout(function() {
                    alert.style.transition = 'opacity 0.5s ease';
                    alert.style.opacity = '0';
                    setTimeout(function() { alert.remove(); }, 500);
                }, 5000);
            });
        })();
        document.getElementById('otp').addEventListener('input', function() {
            this.value = this.value.replace(/\D/g, '').slice(0, 6);
        });
    </script>
</body>
</html>
