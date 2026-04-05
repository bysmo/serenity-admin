@extends('layouts.app')

@section('title', 'Enregistrer un Paiement')

@section('content')
<div class="page-header">
    <h1><i class="bi bi-plus-circle"></i> Enregistrer un Nouveau Paiement</h1>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card">
    <div class="card-header">
        <i class="bi bi-info-circle"></i> Informations du Paiement
    </div>
    <div class="card-body">
        <form action="{{ route('paiements.store') }}" method="POST" id="paiementForm">
            @csrf
            
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="membre_id" class="form-label">
                        Membre <span class="text-danger">*</span>
                    </label>
                    <select class="form-select @error('membre_id') is-invalid @enderror" 
                            id="membre_id" 
                            name="membre_id" 
                            required>
                        <option value="">Sélectionner un membre</option>
                        @foreach($membres as $membre)
                            <option value="{{ $membre->id }}" {{ old('membre_id') == $membre->id ? 'selected' : '' }}>
                                {{ $membre->nom_complet }} ({{ $membre->numero }})
                            </option>
                        @endforeach
                    </select>
                    @error('membre_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                
                <div class="col-md-6 mb-3">
                    <label for="cotisation_id" class="form-label">
                        Cagnotte <span class="text-danger">*</span>
                    </label>
                    <select class="form-select @error('cotisation_id') is-invalid @enderror" 
                            id="cotisation_id" 
                            name="cotisation_id" 
                            required>
                        <option value="">Sélectionner une cotisation</option>
                        @foreach($cotisations as $cotisation)
                            <option value="{{ $cotisation->id }}" 
                                    data-caisse-id="{{ $cotisation->caisse_id }}"
                                    data-montant="{{ $cotisation->montant }}"
                                    data-type-montant="{{ $cotisation->type_montant ?? 'fixe' }}"
                                    {{ old('cotisation_id') == $cotisation->id ? 'selected' : '' }}>
                                {{ $cotisation->nom }} 
                                @if($cotisation->montant)
                                    - {{ number_format($cotisation->montant, 0, ',', ' ') }} XOF
                                @else
                                    (Montant libre)
                                @endif
                            </option>
                        @endforeach
                    </select>
                    @error('cotisation_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="caisse_nom" class="form-label">
                        Caisse (automatique)
                    </label>
                    <input type="text"
                           class="form-control @error('caisse_id') is-invalid @enderror"
                           id="caisse_nom"
                           value=""
                           readonly>
                    <input type="hidden" id="caisse_id" name="caisse_id" value="{{ old('caisse_id') }}">
                    @error('caisse_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <small class="form-text text-muted" style="font-size: 0.7rem;">La caisse est imposée par la cotisation choisie</small>
                </div>
                
                <div class="col-md-6 mb-3">
                    <label for="montant" class="form-label">
                        Montant (XOF) <span class="text-danger" id="montant-required">*</span>
                    </label>
                    <input type="number" 
                           class="form-control @error('montant') is-invalid @enderror" 
                           id="montant" 
                           name="montant" 
                           value="{{ old('montant') }}" 
                           min="1" 
                           step="1">
                    @error('montant')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <small class="form-text text-muted" style="font-size: 0.7rem;" id="montant-help">Rempli automatiquement selon la cotisation</small>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="date_paiement" class="form-label">
                        Date de paiement <span class="text-danger">*</span>
                    </label>
                    <input type="date" 
                           class="form-control @error('date_paiement') is-invalid @enderror" 
                           id="date_paiement" 
                           name="date_paiement" 
                           value="{{ old('date_paiement', date('Y-m-d')) }}" 
                           required>
                    @error('date_paiement')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                
                <div class="col-md-6 mb-3">
                    <label for="mode_paiement" class="form-label">
                        Mode de paiement <span class="text-danger">*</span>
                    </label>
                    <select class="form-select @error('mode_paiement') is-invalid @enderror" 
                            id="mode_paiement" 
                            name="mode_paiement" 
                            required>
                        <option value="especes" {{ old('mode_paiement') === 'especes' ? 'selected' : '' }}>Espèces</option>
                        <option value="cheque" {{ old('mode_paiement') === 'cheque' ? 'selected' : '' }}>Chèque</option>
                        <option value="virement" {{ old('mode_paiement') === 'virement' ? 'selected' : '' }}>Virement</option>
                        <option value="mobile_money" {{ old('mode_paiement') === 'mobile_money' ? 'selected' : '' }}>Mobile Money</option>
                        <option value="autre" {{ old('mode_paiement') === 'autre' ? 'selected' : '' }}>Autre</option>
                    </select>
                    @error('mode_paiement')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>
            
            <div class="mb-3">
                <label for="notes" class="form-label">Notes</label>
                <textarea class="form-control @error('notes') is-invalid @enderror" 
                          id="notes" 
                          name="notes" 
                          rows="2">{{ old('notes') }}</textarea>
                @error('notes')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            
            <div class="d-flex justify-content-between">
                <a href="{{ route('paiements.index') }}" class="btn btn-secondary">
                    <i class="bi bi-arrow-left"></i> Retour
                </a>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-circle"></i> Enregistrer
                </button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const cotisationSelect = document.getElementById('cotisation_id');
    const caisseIdInput = document.getElementById('caisse_id');
    const caisseNomInput = document.getElementById('caisse_nom');
    const montantInput = document.getElementById('montant');
    
    cotisationSelect.addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        const caisseId = selectedOption.getAttribute('data-caisse-id');
        const montant = selectedOption.getAttribute('data-montant');
        const typeMontant = selectedOption.getAttribute('data-type-montant');
        const montantRequired = document.getElementById('montant-required');
        const montantHelp = document.getElementById('montant-help');
        
        if (caisseId) {
            // Forcer la caisse selon la cotisation
            caisseIdInput.value = caisseId;
            caisseNomInput.value = selectedOption.textContent.trim();
        } else {
            caisseIdInput.value = '';
            caisseNomInput.value = '';
        }
        
        if (typeMontant === 'libre') {
            // Montant libre : rendre le champ optionnel
            montantInput.removeAttribute('required');
            montantInput.value = '';
            montantRequired.style.display = 'none';
            montantHelp.textContent = 'Montant libre - Saisissez le montant payé par le membre';
        } else {
            // Montant fixe : remplir et rendre requis
            montantInput.setAttribute('required', 'required');
            montantRequired.style.display = 'inline';
            montantHelp.textContent = 'Rempli automatiquement selon la cotisation';
            if (montant) {
                montantInput.value = montant;
            }
        }
    });

    // Si une cotisation est déjà sélectionnée (old input), initialiser la caisse/ montant
    if (cotisationSelect.value) {
        cotisationSelect.dispatchEvent(new Event('change'));
    }
});
</script>
    </div>
    
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <i class="bi bi-info-circle"></i> À propos des Paiements
            </div>
            <div class="card-body">
                <h6 class="mb-3" style="font-weight: 300; font-family: 'Ubuntu', sans-serif; color: var(--primary-dark-blue);">
                    <i class="bi bi-cash-coin"></i> Qu'est-ce qu'un paiement ?
                </h6>
                <p style="font-size: 0.75rem; line-height: 1.5; font-weight: 300; font-family: 'Ubuntu', sans-serif; color: #666;">
                    Un paiement enregistre une transaction financière effectuée par un membre pour une cotisation spécifique. Le montant est automatiquement crédité à la caisse associée à la cotisation.
                </p>
                
                <h6 class="mt-4 mb-3" style="font-weight: 300; font-family: 'Ubuntu', sans-serif; color: var(--primary-dark-blue);">
                    <i class="bi bi-lightbulb"></i> Caisse automatique
                </h6>
                <p style="font-size: 0.75rem; line-height: 1.5; font-weight: 300; font-family: 'Ubuntu', sans-serif; color: #666;">
                    La caisse est automatiquement définie selon la cotisation choisie pour éviter les erreurs. Si la cotisation a un montant fixe, celui-ci est pré-rempli automatiquement.
                </p>
                
                <h6 class="mt-4 mb-3" style="font-weight: 300; font-family: 'Ubuntu', sans-serif; color: var(--primary-dark-blue);">
                    <i class="bi bi-envelope-check"></i> Notifications
                </h6>
                <p style="font-size: 0.75rem; line-height: 1.5; font-weight: 300; font-family: 'Ubuntu', sans-serif; color: #666;">
                    Si un template d'email est configuré, une notification de confirmation sera envoyée automatiquement au membre après l'enregistrement du paiement.
                </p>
            </div>
        </div>
    </div>
</div>
@endsection
