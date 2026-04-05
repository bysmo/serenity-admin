@extends('layouts.app')

@section('title', 'Créer une cagnotte')

@section('content')
<div class="page-header">
    <h1><i class="bi bi-plus-circle"></i> Créer une nouvelle cagnotte</h1>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card">
    <div class="card-header">
        <i class="bi bi-info-circle"></i> Informations de la cagnotte
    </div>
    <div class="card-body">
        <form action="{{ route('cotisations.store') }}" method="POST">
            @csrf
            
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="nom" class="form-label">
                        Nom de la cagnotte <span class="text-danger">*</span>
                    </label>
                    <input type="text"
                           class="form-control @error('nom') is-invalid @enderror"
                           id="nom"
                           name="nom"
                           value="{{ old('nom') }}"
                           placeholder="Ex: Cagnotte mensuelle 2024"
                           required>
                    @error('nom')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                
                <div class="col-md-6 mb-3">
                    <label for="caisse_id" class="form-label">
                        Caisse associée <span class="text-danger">*</span>
                    </label>
                    <select class="form-select @error('caisse_id') is-invalid @enderror" 
                            id="caisse_id" 
                            name="caisse_id" 
                            required>
                        <option value="">Sélectionner une caisse</option>
                        @foreach($caisses as $caisse)
                            <option value="{{ $caisse->id }}" {{ old('caisse_id') == $caisse->id ? 'selected' : '' }}>
                                {{ $caisse->nom }} (Solde: {{ number_format((float) ($caisse->solde_actuel ?? 0), 0, ',', ' ') }} XOF)
                            </option>
                        @endforeach
                    </select>
                    @error('caisse_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label for="type" class="form-label">
                        Type <span class="text-danger">*</span>
                    </label>
                    <select class="form-select @error('type') is-invalid @enderror" 
                            id="type" 
                            name="type" 
                            required>
                        <option value="reguliere" {{ old('type') === 'reguliere' ? 'selected' : '' }}>Régulière</option>
                        <option value="ponctuelle" {{ old('type') === 'ponctuelle' ? 'selected' : '' }}>Ponctuelle</option>
                        <option value="exceptionnelle" {{ old('type') === 'exceptionnelle' ? 'selected' : '' }}>Exceptionnelle</option>
                    </select>
                    @error('type')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                
                <div class="col-md-4 mb-3">
                    <label for="frequence" class="form-label">
                        Fréquence de paiement <span class="text-danger">*</span>
                    </label>
                    <select class="form-select @error('frequence') is-invalid @enderror" 
                            id="frequence" 
                            name="frequence" 
                            required>
                        <option value="mensuelle" {{ old('frequence') === 'mensuelle' ? 'selected' : '' }}>Mensuelle</option>
                        <option value="trimestrielle" {{ old('frequence') === 'trimestrielle' ? 'selected' : '' }}>Trimestrielle</option>
                        <option value="semestrielle" {{ old('frequence') === 'semestrielle' ? 'selected' : '' }}>Semestrielle</option>
                        <option value="annuelle" {{ old('frequence') === 'annuelle' ? 'selected' : '' }}>Annuelle</option>
                        <option value="unique" {{ old('frequence') === 'unique' ? 'selected' : '' }}>Unique</option>
                    </select>
                    @error('frequence')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                
                <div class="col-md-4 mb-3">
                    <label for="type_montant" class="form-label">
                        Type de montant <span class="text-danger">*</span>
                    </label>
                    <select class="form-select @error('type_montant') is-invalid @enderror" 
                            id="type_montant" 
                            name="type_montant" 
                            required>
                        <option value="fixe" {{ old('type_montant', 'fixe') === 'fixe' ? 'selected' : '' }}>Fixe</option>
                        <option value="libre" {{ old('type_montant') === 'libre' ? 'selected' : '' }}>Libre</option>
                    </select>
                    @error('type_montant')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-4 mb-3" id="montant-container">
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
                    <small class="form-text text-muted" style="font-size: 0.7rem;" id="montant-help">
                        Montant fixe payable par tous les membres
                    </small>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="tag" class="form-label">Tag</label>
                    <select class="form-select @error('tag') is-invalid @enderror" 
                            id="tag" 
                            name="tag">
                        <option value="">-- Aucun tag --</option>
                        @foreach($tags as $tag)
                            <option value="{{ $tag }}" {{ old('tag') === $tag ? 'selected' : '' }}>{{ $tag }}</option>
                        @endforeach
                    </select>
                    @error('tag')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <small class="form-text text-muted" style="font-size: 0.7rem;">
                        Permet de catégoriser les cagnettes. 
                        <a href="{{ route('tags.create') }}" target="_blank" class="text-decoration-none">
                            Créer un nouveau tag
                        </a>
                    </small>
                </div>
                
                <div class="col-md-6 mb-3">
                    <label for="visibilite" class="form-label">Type de cagnotte</label>
                    <select class="form-select @error('visibilite') is-invalid @enderror" 
                            id="visibilite" 
                            name="visibilite">
                        <option value="publique" {{ old('visibilite', 'publique') === 'publique' ? 'selected' : '' }}>Publique</option>
                        <option value="privee" {{ old('visibilite') === 'privee' ? 'selected' : '' }}>Privée</option>
                    </select>
                    @error('visibilite')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <small class="form-text text-muted" style="font-size: 0.7rem;">
                        <strong>Publique :</strong> tout membre peut adhérer directement. <strong>Privée :</strong> le membre doit demander l'adhésion, l'admin valide.
                    </small>
                </div>
            </div>
            
            <div class="mb-3">
                <label for="description" class="form-label">Description</label>
                <textarea class="form-control @error('description') is-invalid @enderror" 
                          id="description" 
                          name="description" 
                          rows="3"
                          placeholder="Description de la cagnotte...">{{ old('description') }}</textarea>
                @error('description')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
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
            
            <div class="mb-3 form-check">
                <input type="checkbox" 
                       class="form-check-input" 
                       id="actif" 
                       name="actif" 
                       value="1"
                       {{ old('actif', true) ? 'checked' : '' }}>
                <label class="form-check-label" for="actif">
                    Cagnotte active
                </label>
            </div>

            <div class="d-flex justify-content-between">
                <a href="{{ route('cotisations.index') }}" class="btn btn-secondary">
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
    const typeMontantSelect = document.getElementById('type_montant');
    const montantInput = document.getElementById('montant');
    const montantContainer = document.getElementById('montant-container');
    const montantRequired = document.getElementById('montant-required');
    const montantHelp = document.getElementById('montant-help');
    
    function toggleMontantField() {
        if (typeMontantSelect.value === 'libre') {
            // Montant libre : masquer le champ ou le rendre optionnel
            montantInput.removeAttribute('required');
            montantInput.value = '';
            montantRequired.style.display = 'none';
            montantHelp.textContent = 'Les membres pourront payer le montant de leur choix';
            montantContainer.style.opacity = '0.6';
        } else {
            // Montant fixe : rendre le champ requis
            montantInput.setAttribute('required', 'required');
            montantRequired.style.display = 'inline';
            montantHelp.textContent = 'Montant fixe payable par tous les membres';
            montantContainer.style.opacity = '1';
        }
    }
    
    // Initialiser au chargement
    toggleMontantField();
    
    // Écouter les changements
    typeMontantSelect.addEventListener('change', toggleMontantField);
});
</script>
    </div>
    
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <i class="bi bi-info-circle"></i> À propos des Cagnettes
            </div>
            <div class="card-body">
                <h6 class="mb-3" style="font-weight: 300; font-family: 'Ubuntu', sans-serif; color: var(--primary-dark-blue);">
                    <i class="bi bi-wallet2"></i> Qu'est-ce qu'une cagnotte ?
                </h6>
                <p style="font-size: 0.75rem; line-height: 1.5; font-weight: 300; font-family: 'Ubuntu', sans-serif; color: #666;">
                    Une cagnotte est un modèle de paiement défini avec des caractéristiques (montant, fréquence, type) que les membres peuvent utiliser pour effectuer leurs paiements. Chaque cagnotte est associée à une caisse.
                </p>
                
                <h6 class="mt-4 mb-3" style="font-weight: 300; font-family: 'Ubuntu', sans-serif; color: var(--primary-dark-blue);">
                    <i class="bi bi-lightbulb"></i> Types de montant
                </h6>
                <ul style="font-size: 0.75rem; line-height: 1.8; font-weight: 300; font-family: 'Ubuntu', sans-serif; color: #666; padding-left: 1.2rem;">
                    <li><strong>Fixe :</strong> Montant prédéfini, identique pour tous</li>
                    <li><strong>Libre :</strong> Les membres choisissent le montant</li>
                </ul>
                
                <h6 class="mt-4 mb-3" style="font-weight: 300; font-family: 'Ubuntu', sans-serif; color: var(--primary-dark-blue);">
                    <i class="bi bi-calendar"></i> Fréquences
                </h6>
                <p style="font-size: 0.75rem; line-height: 1.5; font-weight: 300; font-family: 'Ubuntu', sans-serif; color: #666;">
                    Mensuelle, trimestrielle, semestrielle, annuelle ou unique. La fréquence détermine à quelle régularité les membres doivent payer cette cagnotte.
                </p>
            </div>
        </div>
    </div>
</div>
@endsection
