@extends('layouts.app')

@section('title', 'Traitement de fin de mois')

@section('content')
<div class="page-header">
    <h1><i class="bi bi-calendar-month"></i> Traitement de fin de mois</h1>
</div>

@if(request('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="bi bi-check-circle"></i> {{ request('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

@if(request('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="bi bi-x-circle"></i> {{ request('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <i class="bi bi-send"></i> Lancer le traitement
            </div>
            <div class="card-body">
                <form action="{{ route('fin-mois.process') }}" method="POST" id="processForm">
                    @csrf
                    <input type="hidden" id="process_token" name="process_token" value="">
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="periode_debut" class="form-label">Date de début <span class="text-danger">*</span></label>
                            <input type="date" 
                                   class="form-control form-control-sm" 
                                   id="periode_debut" 
                                   name="periode_debut" 
                                   value="{{ old('periode_debut', now()->startOfMonth()->format('Y-m-d')) }}"
                                   required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="periode_fin" class="form-label">Date de fin <span class="text-danger">*</span></label>
                            <input type="date" 
                                   class="form-control form-control-sm" 
                                   id="periode_fin" 
                                   name="periode_fin" 
                                   value="{{ old('periode_fin', now()->endOfMonth()->format('Y-m-d')) }}"
                                   required>
                        </div>
                    </div>
                    <small class="text-muted">Sélectionnez l'intervalle de dates pour lequel générer les récapitulatifs</small>
                    
                    <div class="mb-3">
                        <label for="membre_id" class="form-label">Membre spécifique (optionnel)</label>
                        <select class="form-select form-select-sm" id="membre_id" name="membre_id">
                            <option value="">Tous les membres actifs</option>
                            @foreach(\App\Models\Membre::where('statut', 'actif')->orderBy('nom')->get() as $membre)
                                <option value="{{ $membre->id }}" {{ old('membre_id') == $membre->id ? 'selected' : '' }}>
                                    {{ $membre->prenom }} {{ $membre->nom }} 
                                    @if($membre->email)
                                        ({{ $membre->email }})
                                    @else
                                        <span class="text-warning">(pas d'email)</span>
                                    @endif
                                </option>
                            @endforeach
                        </select>
                        <small class="text-muted">Laissez vide pour traiter tous les membres actifs</small>
                    </div>
                    
                    <div class="alert alert-info" style="font-size: 0.75rem; font-family: 'Ubuntu', sans-serif;">
                        <i class="bi bi-info-circle"></i> 
                        <strong>Information :</strong> Le système va générer un récapitulatif des paiements et engagements pour chaque membre et leur envoyer un email. Les membres qui n'ont pas d'email ne recevront pas le récapitulatif.
                    </div>
                    
                    <!-- Barre de progression (masquée par défaut) -->
                    <div id="progressContainer" style="display: none; margin-top: 1rem;">
                        <div class="mb-2 d-flex justify-content-between align-items-center">
                            <span id="progressText" style="font-size: 0.75rem; font-family: 'Ubuntu', sans-serif;">Traitement en cours...</span>
                            <span id="progressPercent" style="font-size: 0.75rem; font-family: 'Ubuntu', sans-serif; font-weight: 500;">0%</span>
                        </div>
                        <div class="progress" style="height: 20px;">
                            <div id="progressBar" class="progress-bar progress-bar-striped progress-bar-animated" 
                                 role="progressbar" 
                                 style="width: 0%; background-color: var(--primary-dark-blue);" 
                                 aria-valuenow="0" 
                                 aria-valuemin="0" 
                                 aria-valuemax="100">
                            </div>
                        </div>
                    </div>
                    
                    <div class="d-flex justify-content-between">
                        <a href="{{ route('dashboard') }}" class="btn btn-secondary btn-sm">
                            <i class="bi bi-arrow-left"></i> Retour
                        </a>
                        <div>
                            <button type="button" class="btn btn-outline-primary btn-sm me-2" id="previewBtn">
                                <i class="bi bi-eye"></i> Aperçu PDF
                            </button>
                            <button type="submit" class="btn btn-primary btn-sm" id="processBtn">
                                <i class="bi bi-send"></i> Lancer le traitement
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <i class="bi bi-info-circle"></i> À propos
            </div>
            <div class="card-body" style="font-size: 0.75rem; font-family: 'Ubuntu', sans-serif;">
                <p><strong>Le traitement de fin de mois</strong> permet de :</p>
                <ul>
                    <li>Synthétiser tous les paiements d'un membre pour une période</li>
                    <li>Inclure les engagements en cours avec leur statut</li>
                    <li>Envoyer automatiquement un récapitulatif par email</li>
                    <li>Conserver un journal de tous les envois effectués</li>
                </ul>
                
                <p class="mt-3"><strong>Le récapitulatif contient :</strong></p>
                <ul>
                    <li>Nombre total de paiements</li>
                    <li>Montant total payé</li>
                    <li>Détail de chaque paiement (date, cotisation, montant)</li>
                    <li>État des engagements en cours (reste à payer)</li>
                </ul>
                
                <p class="mt-3">
                    <a href="{{ route('fin-mois.journal') }}" class="btn btn-sm btn-outline-primary w-100">
                        <i class="bi bi-journal-text"></i> Voir le journal d'envoi
                    </a>
                </p>
            </div>
        </div>
    </div>
</div>

<script>
    // Gestion du bouton Aperçu
    document.getElementById('previewBtn').addEventListener('click', function(e) {
        e.preventDefault();
        
        const periodeDebut = document.getElementById('periode_debut').value;
        const periodeFin = document.getElementById('periode_fin').value;
        const membreId = document.getElementById('membre_id').value;
        
        if (!periodeDebut || !periodeFin) {
            showAlert('Veuillez sélectionner une période (date de début et date de fin)', 'warning');
            return;
        }
        
        if (!membreId) {
            showAlert('Veuillez sélectionner un membre pour l\'aperçu', 'warning');
            return;
        }
        
        // Construire l'URL de l'aperçu avec cache-busting
        const previewUrl = '{{ route("fin-mois.preview") }}?periode_debut=' + encodeURIComponent(periodeDebut) + 
                          '&periode_fin=' + encodeURIComponent(periodeFin) + 
                          '&membre_id=' + encodeURIComponent(membreId) +
                          '&v=' + Date.now();
        
        // Ouvrir l'aperçu dans un nouvel onglet
        window.open(previewUrl, '_blank');
    });
    
    document.getElementById('processForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const btn = document.getElementById('processBtn');
        const progressContainer = document.getElementById('progressContainer');
        const progressBar = document.getElementById('progressBar');
        const progressPercent = document.getElementById('progressPercent');
        const progressText = document.getElementById('progressText');
        const form = this;
        
        // Désactiver le bouton
        btn.disabled = true;
        btn.innerHTML = '<i class="bi bi-hourglass-split"></i> Traitement en cours...';
        
        // Afficher la barre de progression
        progressContainer.style.display = 'block';
        progressBar.style.width = '0%';
        progressPercent.textContent = '0%';
        progressText.textContent = 'Démarrage du traitement...';
        
        // Générer un token unique pour suivre la progression
        const processToken = 'process_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
        document.getElementById('process_token').value = processToken;
        
        // Préparer les données du formulaire
        const formData = new FormData(form);
        
        // Démarrer le traitement en AJAX
        fetch('{{ route("fin-mois.process") }}', {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Démarrer le polling pour suivre la progression
                let pollCount = 0;
                const maxPolls = 300; // Maximum 5 minutes (300 * 1 seconde)
                
                const pollInterval = setInterval(function() {
                    pollCount++;
                    
                    fetch('{{ route("fin-mois.progress") }}?token=' + processToken)
                        .then(response => response.json())
                        .then(progressData => {
                            if (progressData.progress !== undefined) {
                                const progress = Math.min(progressData.progress, 100);
                                progressBar.style.width = progress + '%';
                                progressBar.setAttribute('aria-valuenow', Math.round(progress));
                                progressPercent.textContent = Math.round(progress) + '%';
                                
                                if (progressData.message) {
                                    progressText.textContent = progressData.message;
                                }
                                
                                // Si terminé
                                if (progressData.completed) {
                                    clearInterval(pollInterval);
                                    progressBar.style.width = '100%';
                                    progressPercent.textContent = '100%';
                                    progressText.textContent = progressData.message || 'Traitement terminé !';
                                    
                                    // Rediriger après 2 secondes avec message de succès
                                    setTimeout(function() {
                                        const message = progressData.message || 'Traitement terminé avec succès.';
                                        const url = '{{ route("fin-mois.index") }}';
                                        // Sécurité : vérifier que l'URL est bien interne avant redirection
                                        if (!url.startsWith('/') && !url.startsWith(window.location.origin)) {
                                            console.error('Redirection non autorisée bloquée');
                                            return;
                                        }
                                        if (progressData.error) {
                                            window.location.href = url + '?error=' + encodeURIComponent(message);
                                        } else {
                                            window.location.href = url + '?success=' + encodeURIComponent(message);
                                        }
                                    }, 2000);
                                }
                            }
                        })
                        .catch(error => {
                            console.error('Erreur polling:', error);
                        });
                    
                    // Arrêter après maxPolls
                    if (pollCount >= maxPolls) {
                        clearInterval(pollInterval);
                    }
                }, 1000); // Poll toutes les secondes
            } else {
                showAlert('Erreur: ' + (data.message || 'Erreur inconnue'), 'error');
                btn.disabled = false;
                btn.innerHTML = '<i class="bi bi-send"></i> Lancer le traitement';
                progressContainer.style.display = 'none';
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            showAlert('Erreur lors du démarrage du traitement', 'error');
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-send"></i> Lancer le traitement';
            progressContainer.style.display = 'none';
        });
    });
</script>
@endsection
