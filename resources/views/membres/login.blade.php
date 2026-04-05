<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @php
        $appNomComplet = \App\Models\AppSetting::get('app_nom', 'Gestion des Cotisations');
        $logoPath = \App\Models\AppSetting::get('entreprise_logo');
        $faviconUrl = $logoPath && \Illuminate\Support\Facades\File::exists(storage_path('app/public/' . $logoPath)) ? asset('storage/' . $logoPath) : (isset($logoPath) ? route('storage.logo', ['filename' => basename($logoPath)]) : asset('favicon.ico'));
    @endphp
    @if($faviconUrl)<link rel="icon" type="image/png" href="{{ $faviconUrl }}">@else<link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">@endif
    <title>{{ $appNomComplet }} - Connexion Membre</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Ubuntu:wght@300;400;500;700&display=swap" rel="stylesheet">
    <style>
        * { font-family: 'Ubuntu', sans-serif; font-weight: 300; }
        body { margin: 0; padding: 0; min-height: 100vh; background-image: url('{{ asset('images/background.jpg') }}'); background-size: cover; background-position: center; background-attachment: fixed; }
        .login-container { position: relative; z-index: 1; min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 2rem; }
        .login-card { background: white; border-radius: 10px; box-shadow: 0 10px 40px rgba(0,0,0,0.3); max-width: 420px; width: 100%; padding: 1.5rem; }
        .login-header { text-align: center; margin-bottom: 1.25rem; }
        .login-header h2 { color: var(--primary-dark-blue, #1e3a5f); font-weight: 300; font-size: 1.25rem; margin-bottom: 0.25rem; }
        .login-header p { color: #666; font-size: 0.75rem; }
        .form-label { font-size: 0.8rem; color: #333; margin-bottom: 0.35rem; font-family: 'Ubuntu', sans-serif; font-weight: 300; }
        .form-control, .form-select { font-size: 0.8rem; border: 1px solid #ddd; border-radius: 5px; padding: 0.4rem 0.6rem; font-family: 'Ubuntu', sans-serif; font-weight: 300; }
        .form-control:focus, .form-select:focus { border-color: var(--primary-dark-blue, #1e3a5f); box-shadow: 0 0 0 0.2rem rgba(30, 58, 95, 0.25); }
        .login-card .btn { font-size: 0.8rem; font-family: 'Ubuntu', sans-serif; font-weight: 300; }
        .btn-primary { background-color: var(--primary-dark-blue, #1e3a5f); border-color: var(--primary-dark-blue, #1e3a5f); padding: 0.4rem; }
        .btn-primary:hover { background-color: var(--primary-blue, #2c5282); border-color: var(--primary-blue, #2c5282); }
        .alert { font-size: 0.75rem; border-radius: 5px; padding: 0.5rem 0.75rem; }
        .invalid-feedback { font-size: 0.7rem; }
        .form-check-label { font-weight: 300; font-family: 'Ubuntu', sans-serif; font-size: 0.75rem; }
        .phone-input-group .form-select { max-width: 85px; border-top-right-radius: 0; border-bottom-right-radius: 0; font-family: 'Ubuntu', sans-serif; font-weight: 300; }
        .phone-input-group .form-control { border-top-left-radius: 0; border-bottom-left-radius: 0; border-left: none; font-family: 'Ubuntu', sans-serif; font-weight: 300; }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <h2><i class="bi bi-person-circle"></i> Connexion Membre</h2>
                <p>Votre sérénité financière commence ici.</p>
            </div>

            @if($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0" style="font-size: 0.8rem;">
                        @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                    </ul>
                </div>
                @if(session('unverified_phone'))
                    <p class="small text-muted mb-0">Utilisez le lien « Renvoyer le code » sur la page d’inscription si vous n’avez pas reçu le SMS.</p>
                @endif
            @endif
            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif

            <form method="POST" action="{{ route('membre.login') }}">
                @csrf
                <div class="mb-3">
                    <label for="phone_country" class="form-label">Pays / Indicatif</label>
                    <div class="d-flex phone-input-group">
                        <select class="form-select form-select-sm @error('country_code') is-invalid @enderror" id="phone_country" name="country_code" required>
                            @foreach($countries ?? [] as $code => $data)
                                <option value="{{ $code }}" {{ (old('country_code', $default_country ?? 'BF')) === $code ? 'selected' : '' }}>+{{ $data['dial'] ?? '' }}</option>
                            @endforeach
                        </select>
                        <input type="tel" class="form-control @error('telephone') is-invalid @enderror" id="telephone" name="telephone" value="{{ old('telephone') }}" placeholder="77 123 45 67" required autofocus>
                    </div>
                    @error('telephone')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                </div>
                <div class="mb-3">
                    <label for="password" class="form-label">Mot de passe</label>
                    <input type="password" class="form-control @error('password') is-invalid @enderror" id="password" name="password" required>
                    @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="mb-3 form-check">
                    <input type="checkbox" class="form-check-input" id="remember" name="remember">
                    <label class="form-check-label" for="remember">Se souvenir de moi</label>
                </div>
                <button type="submit" class="btn btn-primary w-100"><i class="bi bi-box-arrow-in-right"></i> Se connecter</button>
            </form>

            <div class="text-center mt-3">
                <p class="mb-2" style="font-size: 0.9rem; color: #666;">Vous n'avez pas encore de compte ?</p>
                <a href="{{ route('membre.register') }}" class="btn btn-success w-100"><i class="bi bi-person-plus"></i> Créer un compte</a>
                <div class="mt-3">
                    <a href="{{ route('admin.login') }}" class="text-decoration-none" style="font-size: 0.75rem; color: #666;"><i class="bi bi-shield-check"></i> Accès Administrateur</a>
                </div>
            </div>
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
    </script>
</body>
</html>
