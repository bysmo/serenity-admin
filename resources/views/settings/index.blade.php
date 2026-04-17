@extends('layouts.app')

@section('title', 'Paramètres Généraux')

@section('content')
<div class="page-header">
    <h1><i class="bi bi-sliders"></i> Paramètres Généraux</h1>
</div>

<!-- Statut du Scheduler -->
<div class="row">
    <div class="col-md-8">
        <div class="card mb-3">
            <div class="card-header" style="background-color: var(--primary-dark-blue); color: white; font-weight: 300; font-family: 'Ubuntu', sans-serif;">
                <i class="bi bi-clock-history"></i> Statut du Scheduler Cron
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="d-flex align-items-center mb-3">
                            <span class="me-2"><strong>Statut :</strong></span>
                            @if($schedulerStatus['configured'])
                                <span class="badge bg-success">
                                    <i class="bi bi-check-circle"></i> Configuré
                                </span>
                            @else
                                <span class="badge bg-warning">
                                    <i class="bi bi-exclamation-triangle"></i> Non configuré
                                </span>
                            @endif
                        </div>
                        
                        @if($schedulerStatus['last_run'])
                            <div class="mb-2">
                                <small class="text-muted">
                                    <i class="bi bi-calendar-event"></i> 
                                    Dernière exécution : {{ $schedulerStatus['last_run'] }}
                                </small>
                            </div>
                        @endif
                    </div>
                    <div class="col-md-6">
                        <div class="alert alert-info mb-0" style="font-size: 0.75rem; font-family: 'Ubuntu', sans-serif;">
                            <strong><i class="bi bi-info-circle"></i> Configuration cPanel :</strong><br>
                            <small>Pour configurer le scheduler sur votre serveur cPanel, ajoutez cette commande dans le Cron Jobs de cPanel :</small><br>
                            <code style="font-size: 0.7rem; background: #f8f9fa; padding: 0.25rem 0.5rem; border-radius: 3px; display: block; margin-top: 0.5rem; word-break: break-all;">
                                * * * * * cd {{ base_path() }} && php artisan schedule:run >> /dev/null 2>&1
                            </code>
                            <small class="d-block mt-2">
                                <strong>Étapes :</strong><br>
                                1. Connectez-vous à votre cPanel<br>
                                2. Allez dans "Cron Jobs" (Tâches planifiées)<br>
                                3. Sélectionnez "Toutes les minutes" dans "Common Settings"<br>
                                4. Collez la commande ci-dessus dans le champ "Command"<br>
                                5. Cliquez sur "Ajouter une nouvelle tâche Cron"
                            </small>
                        </div>
                    </div>
                </div>
                
                @if(!$schedulerStatus['configured'])
                    <div class="alert alert-warning mt-3" style="font-size: 0.75rem; font-family: 'Ubuntu', sans-serif;">
                        <i class="bi bi-exclamation-triangle"></i> 
                        <strong>Important :</strong> Sans le scheduler configuré, les rappels automatiques (paiements en retard, alertes de comptes, engagements à échéance) ne fonctionneront pas automatiquement.
                    </div>
                @endif
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <!-- Section À propos -->
        <div class="card mb-3">
            <div class="card-header">
                <i class="bi bi-info-circle"></i> À propos
            </div>
            <div class="card-body">
                <h6 class="mb-3" style="font-weight: 300; font-family: 'Ubuntu', sans-serif; color: var(--primary-dark-blue);">
                    <i class="bi bi-clock-history"></i> Scheduler Cron
                </h6>
                <p style="font-size: 0.75rem; line-height: 1.5; font-weight: 300; font-family: 'Ubuntu', sans-serif; color: #666;">
                    Le scheduler Laravel permet d'exécuter automatiquement des tâches planifiées telles que les rappels de paiements, les alertes de comptes et les notifications d'engagements.
                </p>
                
                <h6 class="mt-4 mb-3" style="font-weight: 300; font-family: 'Ubuntu', sans-serif; color: var(--primary-dark-blue);">
                    <i class="bi bi-robot"></i> Tâches automatiques
                </h6>
                <ul style="font-size: 0.75rem; line-height: 1.8; font-weight: 300; font-family: 'Ubuntu', sans-serif; color: #666; padding-left: 1.2rem;">
                    <li><strong>Rappels paiements :</strong> Envoyés quotidiennement à 9h</li>
                    <li><strong>Alertes comptes :</strong> Vérification des soldes faibles</li>
                    <li><strong>Notifications :</strong> Engagements arrivant à échéance</li>
                </ul>
                
                <h6 class="mt-4 mb-3" style="font-weight: 300; font-family: 'Ubuntu', sans-serif; color: var(--primary-dark-blue);">
                    <i class="bi bi-shield-check"></i> Configuration requise
                </h6>
                <p style="font-size: 0.75rem; line-height: 1.5; font-weight: 300; font-family: 'Ubuntu', sans-serif; color: #666;">
                    Configurez le cron job dans cPanel pour activer toutes les fonctionnalités automatiques. Sans cette configuration, les notifications doivent être déclenchées manuellement.
                </p>
            </div>
        </div>
    </div>
</div>

@if($settings->count() === 0)
    <div class="alert alert-info">
        <i class="bi bi-info-circle"></i> 
        <strong>Aucun paramètre configuré.</strong><br>
        <small>Exécutez le seeder pour créer les paramètres par défaut :</small><br>
        <code>php artisan db:seed --class=AppSettingSeeder</code>
    </div>
@endif

@if($settings->count() > 0)
<form action="{{ route('settings.update') }}" method="POST" enctype="multipart/form-data">
    @csrf
    @method('PUT')
    {{-- 1. Informations de l'entreprise --}}
    @if(isset($settingsByGroup['entreprise']))
        @php
            $settings = $settingsByGroup['entreprise'];
            $entrepriseSettings = $settings->filter(function($s) {
                return $s->cle !== 'entreprise_a_propos';
            });
            // Re-order specific fields if needed
            $orderedKeys = ['entreprise_nom', 'entreprise_siege', 'entreprise_rccm', 'entreprise_ifu', 'entreprise_capital', 'entreprise_email', 'entreprise_contact', 'entreprise_logo'];
            $entrepriseSettings = $entrepriseSettings->sortBy(function($s) use ($orderedKeys) {
                $pos = array_search($s->cle, $orderedKeys);
                return $pos === false ? 99 : $pos;
            });
        @endphp
        
        <div class="row">
            <div class="col-md-8">
                <div class="card mb-4 border-0 shadow-sm">
                    <div class="card-header py-3" style="background-color: var(--primary-dark-blue); color: white; border-radius: 10px 10px 0 0;">
                        <h5 class="card-title mb-0" style="font-weight: 300; font-family: 'Ubuntu', sans-serif;">
                            <i class="bi bi-building me-2"></i> Informations de l'entreprise
                        </h5>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-4">
                            @foreach($entrepriseSettings as $setting)
                                @if($setting->cle === 'entreprise_logo')
                                    <div class="col-12">
                                        <label for="entreprise_logo_upload" class="form-label text-muted small fw-bold uppercase">
                                            {{ $setting->description ?? 'Logo' }}
                                        </label>
                                        <div class="d-flex align-items-center gap-4 p-3 bg-light rounded">
                                            @php
                                                $logoPath = $setting->valeur;
                                                $logoFullPath = $logoPath ? storage_path('app/public/' . $logoPath) : null;
                                                $logoExists = $logoFullPath && \Illuminate\Support\Facades\File::exists($logoFullPath);
                                                $logoUrl = $logoExists ? asset('storage/' . $logoPath) : null;
                                            @endphp
                                            @if($logoUrl)
                                                <div class="text-center">
                                                    <img src="{{ $logoUrl }}" alt="Logo" class="img-thumbnail" style="height: 80px; width: auto; object-fit: contain;">
                                                    <div class="small text-muted mt-1">Logo actuel</div>
                                                </div>
                                            @else
                                                <div class="text-muted small border rounded p-3 d-flex align-items-center justify-content-center bg-white" style="height: 80px; width: 120px;">
                                                    Aucun logo
                                                </div>
                                            @endif
                                            <div class="flex-grow-1">
                                                <input type="file" class="form-control" id="entreprise_logo_upload" name="entreprise_logo_upload" accept="image/*">
                                                <div class="form-text small mt-1">Formats acceptés: JPG, PNG, GIF (max 2MB)</div>
                                            </div>
                                        </div>
                                    </div>
                                @else
                                    <div class="col-md-6">
                                        <label for="setting_{{ $setting->cle }}" class="form-label text-muted small fw-bold uppercase">
                                            {{ $setting->description ?? $setting->cle }}
                                        </label>
                                        <input type="{{ $setting->type === 'integer' ? 'number' : 'text' }}" 
                                               class="form-control form-control-lg" 
                                               id="setting_{{ $setting->cle }}" 
                                               name="{{ $setting->cle }}" 
                                               value="{{ $setting->valeur }}"
                                               style="font-size: 0.9rem;">
                                    </div>
                                @endif
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card mb-4 border-0 shadow-sm secondary-card">
                    <div class="card-header py-3 bg-white border-bottom">
                        <h6 class="card-title mb-0 text-primary">À propos</h6>
                    </div>
                    <div class="card-body">
                        <p class="small text-muted mb-4">
                            Ces informations légales et de contact sont essentielles pour la crédibilité de votre plateforme et pour l'édition de vos documents officiels (PDF, Factures, Rapports).
                        </p>
                        <ul class="list-unstyled small text-muted">
                            <li class="mb-2"><i class="bi bi-file-earmark-pdf text-danger me-2"></i> Automatiquement inséré dans les PDF générés</li>
                            <li class="mb-2"><i class="bi bi-envelope text-primary me-2"></i> Utilisé pour les emails sortants</li>
                            <li class="mb-2"><i class="bi bi-shield-check text-success me-2"></i> Essentiel pour la conformité réglementaire</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- 2. Informations de l'application --}}
    @if(isset($settingsByGroup['general']))
        @php
            $generalSettings = $settingsByGroup['general'];
        @endphp
        <div class="row">
            <div class="col-md-8">
                <div class="card mb-4 border-0 shadow-sm">
                    <div class="card-header py-3" style="background-color: #f8f9fa; border-bottom: 2px solid #eee;">
                        <h5 class="card-title mb-0" style="font-weight: 300; font-family: 'Ubuntu', sans-serif; color: #333;">
                            <i class="bi bi-app-indicator me-2"></i> Configuration de l'application
                        </h5>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-4">
                            @foreach($generalSettings as $setting)
                                @if($setting->type === 'boolean')
                                    <div class="col-12">
                                        <div class="form-check form-switch p-3 bg-light rounded">
                                            <input class="form-check-input" type="checkbox" id="setting_{{ $setting->cle }}" name="{{ $setting->cle }}" value="1" {{ filter_var($setting->valeur, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
                                            <label class="form-check-label fw-bold small ms-2" for="setting_{{ $setting->cle }}">
                                                {{ $setting->description ?? $setting->cle }} (Activé)
                                            </label>
                                        </div>
                                    </div>
                                @else
                                    <div class="col-md-6">
                                        <label for="setting_{{ $setting->cle }}" class="form-label text-muted small fw-bold">
                                            {{ $setting->description ?? $setting->cle }}
                                        </label>
                                        <input type="{{ $setting->type === 'integer' ? 'number' : 'text' }}" 
                                               class="form-control" 
                                               id="setting_{{ $setting->cle }}" 
                                               name="{{ $setting->cle }}" 
                                               value="{{ $setting->valeur }}">
                                    </div>
                                @endif
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card mb-4 border-0 shadow-sm secondary-card">
                    <div class="card-header py-3 bg-white border-bottom">
                        <h6 class="card-title mb-0 text-success">Paramètres de base</h6>
                    </div>
                    <div class="card-body">
                        <p class="small text-muted">
                            Ces paramètres contrôlent le comportement global de l'application, comme le nom affiché dans le navigateur et les codes pays par défaut.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- 3. Autres informations (Notifications, Backup, Affichage, etc.) --}}
    @foreach(['notifications', 'backup', 'affichage'] as $otherGroup)
        @if(isset($settingsByGroup[$otherGroup]))
            <div class="row">
                <div class="col-md-8">
                    <div class="card mb-4 border-0 shadow-sm">
                        <div class="card-header py-3 bg-light">
                            <h6 class="card-title mb-0 text-uppercase" style="font-size: 0.8rem; letter-spacing: 1px; color: #555;">
                                <i class="bi bi-gear me-2"></i> {{ ucfirst($otherGroup) }}
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                @foreach($settingsByGroup[$otherGroup] as $setting)
                                    @if($setting->type === 'boolean')
                                        <div class="col-12">
                                            <div class="form-check p-2">
                                                <input class="form-check-input" type="checkbox" id="setting_{{ $setting->cle }}" name="{{ $setting->cle }}" value="1" {{ filter_var($setting->valeur, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
                                                <label class="form-check-label small ms-2" for="setting_{{ $setting->cle }}">
                                                    {{ $setting->description ?? $setting->cle }}
                                                </label>
                                            </div>
                                        </div>
                                    @else
                                        <div class="col-md-6">
                                            <label for="setting_{{ $setting->cle }}" class="form-label small text-muted">
                                                {{ $setting->description ?? $setting->cle }}
                                            </label>
                                            <input type="{{ $setting->type === 'integer' ? 'number' : 'text' }}" class="form-control form-control-sm" id="setting_{{ $setting->cle }}" name="{{ $setting->cle }}" value="{{ $setting->valeur }}">
                                        </div>
                                    @endif
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    {{-- Colonne vide ou aide contextuelle légère --}}
                </div>
            </div>
        @endif
    @endforeach
    
    <div class="d-flex justify-content-between my-4">
        <a href="{{ route('dashboard') }}" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Retour
        </a>
        <button type="submit" class="btn btn-primary px-4">
            <i class="bi bi-check-circle me-2"></i> Enregistrer les paramètres
        </button>
    </div>
</form>
@endif
@endsection
