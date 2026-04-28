<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @php
        $appNomComplet = \App\Models\AppSetting::get('app_nom', 'Serenity');
        $logoPath = \App\Models\AppSetting::get('entreprise_logo');
        $faviconUrl = $logoPath && \Illuminate\Support\Facades\File::exists(storage_path('app/public/' . $logoPath)) ? asset('storage/' . $logoPath) : (isset($logoPath) ? route('storage.logo', ['filename' => basename($logoPath)]) : asset('favicon.ico'));
    @endphp
    @if($faviconUrl)<link rel="icon" type="image/png" href="{{ $faviconUrl }}">@else<link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">@endif
    <title>{{ $appNomComplet }} - Réinitialisation du mot de passe</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Ubuntu:wght@300;400;500;700&display=swap" rel="stylesheet">
    <style>
        * { font-family: 'Ubuntu', sans-serif; font-weight: 300; }
        body { margin: 0; padding: 0; min-height: 100vh; background-image: url('{{ asset('images/bg-clients-login.png.webp') }}'); background-size: cover; background-position: center; background-attachment: fixed; }
        .login-container { position: relative; z-index: 1; min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 2rem; }
        .login-card { background: white; border-radius: 10px; box-shadow: 0 10px 40px rgba(0,0,0,0.3); max-width: 420px; width: 100%; padding: 1.5rem; }
        .login-header { text-align: center; margin-bottom: 1.25rem; }
        .login-header h2 { color: var(--primary-dark-blue, #1e3a5f); font-weight: 300; font-size: 1.25rem; margin-bottom: 0.25rem; }
        .login-header p { color: #666; font-size: 0.75rem; }
        .form-label { font-size: 0.8rem; color: #333; margin-bottom: 0.35rem; font-family: 'Ubuntu', sans-serif; font-weight: 300; }
        .form-control { font-size: 0.8rem; border: 1px solid #ddd; border-radius: 5px; padding: 0.4rem 0.6rem; font-family: 'Ubuntu', sans-serif; font-weight: 300; }
        .form-control:focus { border-color: var(--primary-dark-blue, #1e3a5f); box-shadow: 0 0 0 0.2rem rgba(30, 58, 95, 0.25); }
        .login-card .btn { font-size: 0.8rem; font-family: 'Ubuntu', sans-serif; font-weight: 300; }
        .btn-primary { background-color: var(--primary-dark-blue, #1e3a5f); border-color: var(--primary-dark-blue, #1e3a5f); padding: 0.4rem; }
        .btn-primary:hover { background-color: var(--primary-blue, #2c5282); border-color: var(--primary-blue, #2c5282); }
        .alert { font-size: 0.75rem; border-radius: 5px; padding: 0.5rem 0.75rem; }
        .invalid-feedback { font-size: 0.7rem; }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <h2><i class="bi bi-shield-lock"></i> Nouveau mot de passe</h2>
                <p>Création d'un nouveau mot de passe.</p>
            </div>

            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif

            <form method="POST" action="{{ route('membre.password.update') }}">
                @csrf
                <input type="hidden" name="token" value="{{ $token }}">

                <div class="mb-3">
                    <label for="email" class="form-label">Adresse E-mail</label>
                    <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ $email ?? old('email') }}" required autofocus readonly>
                    @error('email')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="password" class="form-label">Nouveau mot de passe</label>
                    <input type="password" class="form-control @error('password') is-invalid @enderror" id="password" name="password" required>
                    @error('password')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="password_confirmation" class="form-label">Confirmer le mot de passe</label>
                    <input type="password" class="form-control" id="password_confirmation" name="password_confirmation" required>
                </div>

                <button type="submit" class="btn btn-primary w-100"><i class="bi bi-check-circle"></i> Réinitialiser le mot de passe</button>
            </form>
        </div>
    </div>
</body>
</html>
