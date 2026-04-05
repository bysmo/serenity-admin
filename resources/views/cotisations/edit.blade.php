@extends('layouts.app')

@section('title', 'Modifier une cagnotte')

@section('content')
<div class="page-header">
    <h1><i class="bi bi-pencil"></i> Modifier la cagnotte</h1>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <i class="bi bi-info-circle"></i> Informations de la cagnotte
            </div>
            <div class="card-body">
        <form action="{{ route('cotisations.update', $cotisation) }}" method="POST">
            @csrf
            @method('PUT')
            
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="nom" class="form-label">
                        Nom de la cagnotte <span class="text-danger">*</span>
                    </label>
                    <input type="text" 
                           class="form-control @error('nom') is-invalid @enderror" 
                           id="nom" 
                           name="nom" 
                           value="{{ old('nom', $cotisation->nom) }}" 
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
                            required
                            disabled
                            style="background-color: #e9ecef; cursor: not-allowed;">
                        <option value="{{ $cotisation->caisse_id }}" selected>
                            {{ $cotisation->caisse->nom ?? 'N/A' }} 
                            @if($cotisation->caisse)
                                (Solde: {{ number_format($cotisation->caisse->solde_actuel, 0, ',', ' ') }} XOF)
                            @endif
                        </option>
                    </select>
                    <input type="hidden" name="caisse_id" value="{{ $cotisation->caisse_id }}">
                    @error('caisse_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <small class="text-muted" style="font-size: 0.7rem;">La caisse associée ne peut pas être modifiée après la création de la cagnotte.</small>
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
                        <option value="reguliere" {{ old('type', $cotisation->type) === 'reguliere' ? 'selected' : '' }}>Régulière</option>
                        <option value="ponctuelle" {{ old('type', $cotisation->type) === 'ponctuelle' ? 'selected' : '' }}>Ponctuelle</option>
                        <option value="exceptionnelle" {{ old('type', $cotisation->type) === 'exceptionnelle' ? 'selected' : '' }}>Exceptionnelle</option>
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
                        <option value="mensuelle" {{ old('frequence', $cotisation->frequence) === 'mensuelle' ? 'selected' : '' }}>Mensuelle</option>
                        <option value="trimestrielle" {{ old('frequence', $cotisation->frequence) === 'trimestrielle' ? 'selected' : '' }}>Trimestrielle</option>
                        <option value="semestrielle" {{ old('frequence', $cotisation->frequence) === 'semestrielle' ? 'selected' : '' }}>Semestrielle</option>
                        <option value="annuelle" {{ old('frequence', $cotisation->frequence) === 'annuelle' ? 'selected' : '' }}>Annuelle</option>
                        <option value="unique" {{ old('frequence', $cotisation->frequence) === 'unique' ? 'selected' : '' }}>Unique</option>
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
                        <option value="fixe" {{ old('type_montant', $cotisation->type_montant ?? 'fixe') === 'fixe' ? 'selected' : '' }}>Fixe</option>
                        <option value="libre" {{ old('type_montant', $cotisation->type_montant ?? 'fixe') === 'libre' ? 'selected' : '' }}>Libre</option>
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
                    <input type="text" 
                           class="form-control" 
                           id="montant" 
                           value="{{ number_format(old('montant', $cotisation->montant ?? 0), 0, ',', ' ') }} XOF" 
                           readonly
                           style="background-color: #e9ecef; cursor: not-allowed;">
                    <input type="hidden" name="montant" value="{{ old('montant', $cotisation->montant) }}">
                    <small class="form-text text-muted" style="font-size: 0.7rem;" id="montant-help">
                        Le montant ne peut pas être modifié après la création de la cotisation.
                    </small>
                </div>
            </div>
            
            @if($cotisation->code)
            <div class="mb-3">
                <label class="form-label">Code de partage</label>
                <div class="input-group">
                    <input type="text" class="form-control" value="{{ $cotisation->code }}" readonly style="background-color: #e9ecef;">
                    <span class="input-group-text text-muted small">Les membres recherchent ce code pour adhérer</span>
                </div>
            </div>
            @endif
            
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="tag" class="form-label">Tag</label>
                    <select class="form-select @error('tag') is-invalid @enderror" 
                            id="tag" 
                            name="tag">
                        <option value="">-- Aucun tag --</option>
                        @foreach($tags as $tag)
                            <option value="{{ $tag }}" {{ old('tag', $cotisation->tag) === $tag ? 'selected' : '' }}>{{ $tag }}</option>
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
                        <option value="publique" {{ old('visibilite', $cotisation->visibilite ?? 'publique') === 'publique' ? 'selected' : '' }}>Publique</option>
                        <option value="privee" {{ old('visibilite', $cotisation->visibilite ?? 'publique') === 'privee' ? 'selected' : '' }}>Privée</option>
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
                          rows="3">{{ old('description', $cotisation->description) }}</textarea>
                @error('description')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            
            <div class="mb-3">
                <label for="notes" class="form-label">Notes</label>
                <textarea class="form-control @error('notes') is-invalid @enderror" 
                          id="notes" 
                          name="notes" 
                          rows="2">{{ old('notes', $cotisation->notes) }}</textarea>
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
                       {{ old('actif', $cotisation->actif) ? 'checked' : '' }}>
                <label class="form-check-label" for="actif">
                    Cagnotte active
                </label>
            </div>

            <div class="d-flex justify-content-between">
                <a href="{{ route('cotisations.index') }}" class="btn btn-secondary">
                    <i class="bi bi-arrow-left"></i> Retour
                </a>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-circle"></i> Mettre à jour
                </button>
            </div>
        </form>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <i class="bi bi-info-circle"></i> À propos des Cagnettes
            </div>
            <div class="card-body">
                <h6 class="mb-3" style="font-weight: 300; font-family: 'Ubuntu', sans-serif; color: var(--primary-dark-blue);">
                    <i class="bi bi-pencil-square"></i> Modification d'une cagnotte
                </h6>
                <p style="font-size: 0.75rem; line-height: 1.5; font-weight: 300; font-family: 'Ubuntu', sans-serif; color: #666;">
                    Vous pouvez modifier les informations de la cagnotte, mais notez que certains champs comme la caisse associée et le montant ne peuvent pas être modifiés après la création pour garantir l'intégrité des données financières.
                </p>
                
                <h6 class="mt-4 mb-3" style="font-weight: 300; font-family: 'Ubuntu', sans-serif; color: var(--primary-dark-blue);">
                    <i class="bi bi-lock"></i> Champs verrouillés
                </h6>
                <ul style="font-size: 0.75rem; line-height: 1.8; font-weight: 300; font-family: 'Ubuntu', sans-serif; color: #666; padding-left: 1.2rem;">
                    <li><strong>Caisse :</strong> Ne peut pas être modifiée car elle est liée aux paiements existants</li>
                    <li><strong>Montant :</strong> Ne peut pas être modifié pour préserver la cohérence des transactions</li>
                </ul>
                
                <h6 class="mt-4 mb-3" style="font-weight: 300; font-family: 'Ubuntu', sans-serif; color: var(--primary-dark-blue);">
                    <i class="bi bi-lightbulb"></i> Conseils
                </h6>
                <p style="font-size: 0.75rem; line-height: 1.5; font-weight: 300; font-family: 'Ubuntu', sans-serif; color: #666;">
                    Si vous devez modifier la caisse ou le montant, il est recommandé de créer une nouvelle cagnotte et de désactiver l'ancienne.
                </p>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Le champ montant est en lecture seule, donc pas besoin de gérer le toggle
    // Le script est conservé pour compatibilité mais ne fait rien car le champ est readonly
});
</script>
@endsection
