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
    <title>{{ $appNomComplet }} - Inscription Membre</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Ubuntu:wght@300;400;500;700&display=swap" rel="stylesheet">
    <style>
        :root { --primary-dark-blue: #1e3a5f; --primary-blue: #2c5282; }
        * { font-family: 'Ubuntu', sans-serif; font-weight: 300; }
        body { background-color: #f5f7fa; min-height: 100vh; padding: 1.5rem 0; }
        .page-header h1 { color: var(--primary-dark-blue); font-weight: 300; font-size: 1.25rem; }
        .card { border: none; border-radius: 4px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); }
        .card-header { background-color: var(--primary-dark-blue); color: white; border-radius: 4px 4px 0 0 !important; padding: 0.4rem 0.6rem; font-size: 0.75rem; }
        .card-body { padding: 0.75rem; font-size: 0.8rem; }
        .form-label { font-size: 0.8rem; color: #333; margin-bottom: 0.35rem; font-family: 'Ubuntu', sans-serif; font-weight: 300; }
        .form-control, .form-select { font-size: 0.8rem; border: 1px solid #ddd; border-radius: 5px; padding: 0.4rem 0.6rem; font-family: 'Ubuntu', sans-serif; font-weight: 300; }
        .form-control:focus, .form-select:focus { border-color: var(--primary-dark-blue); box-shadow: 0 0 0 0.2rem rgba(30, 58, 95, 0.25); }
        .btn-primary { background-color: var(--primary-dark-blue); border-color: var(--primary-dark-blue); font-size: 0.8rem; font-family: 'Ubuntu', sans-serif; font-weight: 300; }
        .btn-primary:hover { background-color: var(--primary-blue); border-color: var(--primary-blue); }
        .register-actions-row .btn { font-size: 0.75rem; padding: 0.25rem 0.5rem; }
        .register-actions-row .login-link-text { font-size: 0.7rem; color: #666; font-family: 'Ubuntu', sans-serif; font-weight: 300; }
        .invalid-feedback { font-size: 0.7rem; }
        .phone-input-group .form-select { max-width: 95px; border-top-right-radius: 0; border-bottom-right-radius: 0; font-family: 'Ubuntu', sans-serif; font-weight: 300; }
        .phone-input-group .form-control { border-top-left-radius: 0; border-bottom-left-radius: 0; border-left: none; font-family: 'Ubuntu', sans-serif; font-weight: 300; }
    </style>
</head>
<body>
    <div class="container">
        <div class="page-header">
            <h1><i class="bi bi-plus-circle"></i> Créer un Nouveau Membre</h1>
        </div>
        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show">{{ session('error') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
        @endif
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
        @endif

        <div class="row">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header"><i class="bi bi-info-circle"></i> Informations du Membre</div>
                    <div class="card-body">
                        <p class="text-muted small mb-3">Un code OTP vous sera envoyé par SMS pour activer votre compte (plus sécurisé qu’un lien par email).</p>
                        <form method="POST" action="{{ route('membre.register') }}">
                            @csrf
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="nom" class="form-label">Nom <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('nom') is-invalid @enderror" id="nom" name="nom" value="{{ old('nom') }}" required>
                                    @error('nom')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="prenom" class="form-label">Prénom <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('prenom') is-invalid @enderror" id="prenom" name="prenom" value="{{ old('prenom') }}" required>
                                    @error('prenom')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                                <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email') }}" required>
                                @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Téléphone <span class="text-danger">*</span></label>
                                <div class="d-flex phone-input-group">
                                    <select class="form-select @error('country_code') is-invalid @enderror" name="country_code" required>
                                        @foreach($countries ?? [] as $code => $data)
                                            <option value="{{ $code }}" {{ (old('country_code', $default_country ?? 'BF')) === $code ? 'selected' : '' }}>+{{ $data['dial'] ?? '' }}</option>
                                        @endforeach
                                    </select>
                                    <input type="tel" class="form-control @error('telephone') is-invalid @enderror" name="telephone" value="{{ old('telephone') }}" placeholder="77 123 45 67" required>
                                </div>
                                <small class="text-muted">L’indicatif pays est détecté selon votre emplacement. Un code OTP sera envoyé à ce numéro.</small>
                                @error('telephone')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label for="sexe" class="form-label">Sexe <span class="text-danger">*</span></label>
                                    <select class="form-select @error('sexe') is-invalid @enderror" id="sexe" name="sexe" required>
                                        <option value="">— Sélectionner —</option>
                                        <option value="M" {{ old('sexe') == 'M' ? 'selected' : '' }}>Masculin</option>
                                        <option value="F" {{ old('sexe') == 'F' ? 'selected' : '' }}>Féminin</option>
                                    </select>
                                    @error('sexe')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-md-8 mb-3">
                                    <label for="adresse" class="form-label">Adresse</label>
                                    <input type="text" class="form-control @error('adresse') is-invalid @enderror" id="adresse" name="adresse" value="{{ old('adresse') }}">
                                    @error('adresse')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="pays" class="form-label">Pays</label>
                                    <input type="text" class="form-control @error('pays') is-invalid @enderror" id="pays" name="pays" value="{{ old('pays', 'Burkina Faso') }}">
                                    @error('pays')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="ville" class="form-label">Ville</label>
                                    <input type="text" class="form-control @error('ville') is-invalid @enderror" id="ville" name="ville" value="{{ old('ville') }}">
                                    @error('ville')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="quartier" class="form-label">Quartier</label>
                                    <input type="text" class="form-control @error('quartier') is-invalid @enderror" id="quartier" name="quartier" value="{{ old('quartier') }}">
                                    @error('quartier')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="secteur" class="form-label">Secteur</label>
                                    <input type="text" class="form-control @error('secteur') is-invalid @enderror" id="secteur" name="secteur" value="{{ old('secteur') }}">
                                    @error('secteur')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="password" class="form-label">Mot de passe <span class="text-danger">*</span></label>
                                    <input type="password" class="form-control @error('password') is-invalid @enderror" id="password" name="password" required>
                                    @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    <small class="text-muted">Minimum 6 caractères</small>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="password_confirmation" class="form-label">Confirmer le mot de passe <span class="text-danger">*</span></label>
                                    <input type="password" class="form-control" id="password_confirmation" name="password_confirmation" required>
                                </div>
                            </div>

                            @php $parrainageConfig = \App\Models\ParrainageConfig::current(); @endphp
                            @if($parrainageConfig->actif)
                            <div class="mb-3">
                                <label for="code_parrainage" class="form-label">
                                    <i class="bi bi-people me-1 text-primary"></i>Code de parrainage
                                    <small class="text-muted">(optionnel)</small>
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-gift text-primary"></i></span>
                                    <input type="text" class="form-control text-uppercase @error('code_parrainage') is-invalid @enderror"
                                           id="code_parrainage" name="code_parrainage"
                                           placeholder="Ex: ABC12345"
                                           maxlength="12"
                                           value="{{ old('code_parrainage', $code_parrainage ?? '') }}">
                                    @error('code_parrainage')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <small class="text-muted">Si un membre vous a parrainé, entrez son code ici</small>
                            </div>
                            @endif
                            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 register-actions-row">
                                <span class="login-link-text">Vous avez déjà un compte ?</span>
                                <div class="d-flex gap-2">
                                    <a href="{{ route('membre.login') }}" class="btn btn-outline-primary btn-sm"><i class="bi bi-box-arrow-in-right"></i> Se connecter</a>
                                    <a href="{{ route('membre.login') }}" class="btn btn-secondary btn-sm"><i class="bi bi-arrow-left"></i> Retour</a>
                                    <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-check-circle"></i> Créer mon compte</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header"><i class="bi bi-info-circle"></i> À propos</div>
                    <div class="card-body">
                        <h6 class="mb-3" style="font-weight: 300; color: var(--primary-dark-blue);"><i class="bi bi-person"></i> Inscription</h6>
                        <p style="font-size: 0.75rem; line-height: 1.5; color: #666;">
                            Un membre peut effectuer des paiements de cotisations. L’indicatif pays est proposé selon votre emplacement. La connexion se fait avec votre numéro de téléphone et votre mot de passe.
                        </p>
                        <h6 class="mt-4 mb-3" style="font-weight: 300; color: var(--primary-dark-blue);"><i class="bi bi-shield-lock"></i> Code OTP</h6>
                        <p style="font-size: 0.75rem; line-height: 1.5; color: #666;">
                            Après l’inscription, un code à 6 chiffres vous sera envoyé par SMS. Entrez ce code pour activer votre compte. Plus sécurisé qu’un lien par email.
                        </p>
                    </div>
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
