@extends('layouts.app')

@section('title', 'Configuration BCEAO Pi-SPI')

@section('content')
<div class="page-header">
    <h1><i class="bi bi-bank"></i> Configuration BCEAO Pi-SPI</h1>
</div>

@if($errors->any())
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-triangle"></i> 
        <ul class="mb-0">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

<div class="row">
    <div class="col-md-8">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3">
                <h5 class="card-title mb-0 text-primary"><i class="bi bi-gear-fill me-2"></i>Paramètres de Connexion API</h5>
            </div>
            <div class="card-body p-4">
                <form action="{{ route('pispi.update') }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="row g-3 mb-4 align-items-center">
                        <div class="col-md-6">
                            <div class="form-check form-switch custom-switch">
                                <input class="form-check-input" type="checkbox" id="enabled" name="enabled" value="1"
                                       {{ old('enabled', $config->enabled ?? false) ? 'checked' : '' }}>
                                <label class="form-check-label fw-bold ms-2" for="enabled">
                                    Activer les paiements Pi-SPI
                                </label>
                            </div>
                        </div>
                        <div class="col-md-6 text-end">
                            <span class="badge {{ $config->mode === 'live' ? 'bg-danger' : 'bg-warning' }} px-3 py-2 text-uppercase">
                                Mode Actuel : {{ $config->mode }}
                            </span>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="mode" class="form-label fw-bold">Environnement <span class="text-danger">*</span></label>
                            <select class="form-select border-primary-light" id="mode" name="mode" required>
                                <option value="sandbox" {{ old('mode', $config->mode ?? 'sandbox') === 'sandbox' ? 'selected' : '' }}>Sandbox (Simulateur / Test)</option>
                                <option value="live" {{ old('mode', $config->mode ?? 'sandbox') === 'live' ? 'selected' : '' }}>Production (Live BCEAO)</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="paye_alias" class="form-label fw-bold">ID Marchand (Business Alias) <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="paye_alias" name="paye_alias" 
                                   value="{{ old('paye_alias', $config->paye_alias ?? 'SERENITY_BIZ') }}" required>
                            <small class="text-muted">Votre identifiant unique sur le système Pi-SPI</small>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="client_id" class="form-label fw-bold">Client ID <span class="text-danger">*</span></label>
                        <input type="text" class="form-control font-monospace" id="client_id" name="client_id" 
                               value="{{ old('client_id', $config->client_id ?? '') }}" placeholder="Ex: 40n5lsi9q4cv..." required>
                    </div>

                    <div class="mb-3">
                        <label for="client_secret" class="form-label fw-bold">Client Secret <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="password" class="form-control font-monospace" id="client_secret" name="client_secret" 
                                   value="{{ old('client_secret', $config->client_secret ?? '') }}" required>
                            <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('client_secret')">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label for="api_key" class="form-label fw-bold">API Key (x-api-key) <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="password" class="form-control font-monospace" id="api_key" name="api_key" 
                                   value="{{ old('api_key', $config->api_key ?? '') }}" required>
                            <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('api_key')">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                        <small class="text-muted">Clé d'API nécessaire pour chaque requête HTTP</small>
                    </div>

                    <hr class="my-4">

                    <div class="d-flex justify-content-between align-items-center">
                        <div class="text-muted small">
                            <i class="bi bi-shield-lock-fill me-1"></i> Vos identifiants sont stockés de manière sécurisée.
                        </div>
                        <div>
                            <a href="{{ route('payment-methods.index') }}" class="btn btn-outline-secondary me-2">
                                <i class="bi bi-arrow-left me-1"></i> Annuler
                            </a>
                            <button type="submit" class="btn btn-primary px-4">
                                <i class="bi bi-check-circle me-1"></i> Enregistrer
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card bg-light border-0 shadow-sm mb-4">
            <div class="card-body p-4">
                <h5 class="card-title text-primary mb-3"><i class="bi bi-info-circle-fill me-2"></i>Documentation Pi-SPI</h5>
                <p class="small text-muted mb-4">
                    Pi-SPI (Plateforme Interopérable du Système de Paiement Instantané) est l'infrastructure officielle de la BCEAO pour les paiements en temps réel dans l'espace UEMOA.
                </p>
                
                <h6 class="fw-bold small mb-2">Processus d'intégration :</h6>
                <ol class="small text-muted ps-3">
                    <li class="mb-2">Obtenez vos accès sur le <a href="https://developer.pispi.bceao.int/" target="_blank">Portail Développeur BCEAO</a>.</li>
                    <li class="mb-2">Configurez vos credentials sandbox ci-contre.</li>
                    <li class="mb-2">L'application gère automatiquement l'authentification OAuth2 (Cognito).</li>
                    <li class="mb-2">Les paiements sont initiés via le flux "Request to Pay" (RTP).</li>
                </ol>

                <div class="alert alert-info border-0 py-2 small mt-3">
                    <i class="bi bi-lightbulb me-2"></i> <strong>Note :</strong> Dans cet environnement de test, nous utilisons le endpoint <code>no-mtls</code> pour simplifier la communication sans certificats clients.
                </div>
            </div>
        </div>

        <div class="card bg-dark text-white border-0 shadow-sm">
            <div class="card-body p-4">
                <h6 class="fw-bold mb-3"><i class="bi bi-terminal me-2"></i>Statut du Token</h6>
                @php
                    $token = \Illuminate\Support\Facades\Cache::get('pispi_access_token');
                @endphp
                @if($token)
                    <div class="d-flex align-items-center text-success mb-2">
                        <i class="bi bi-check-circle-fill me-2"></i>
                        <span class="small fw-bold">Token Actif en Cache</span>
                    </div>
                    <code class="d-block text-truncate text-white-50 bg-black p-2 rounded mb-2" style="font-size: 0.65rem;">
                        {{ \Illuminate\Support\Str::limit($token, 50) }}
                    </code>
                @else
                    <div class="d-flex align-items-center text-warning opacity-75 mb-0">
                        <i class="bi bi-dash-circle-fill me-2"></i>
                        <span class="small">Aucun token actif</span>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<script>
function togglePassword(id) {
    const input = document.getElementById(id);
    const icon = event.currentTarget.querySelector('i');
    if (input.type === "password") {
        input.type = "text";
        icon.classList.replace('bi-eye', 'bi-eye-slash');
    } else {
        input.type = "password";
        icon.classList.replace('bi-eye-slash', 'bi-eye');
    }
}
</script>

<style>
.form-control:focus, .form-select:focus {
    border-color: #0d6efd;
    box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.1);
}
.custom-switch .form-check-input {
    width: 3em;
    height: 1.5em;
    cursor: pointer;
}
.border-primary-light {
    border-color: #dee2e6;
}
</style>
@endsection
