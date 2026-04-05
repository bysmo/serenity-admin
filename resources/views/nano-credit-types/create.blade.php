@extends('layouts.app')

@section('title', 'Nouveau type de nano crédit')

@section('content')
<div class="page-header">
    <h1><i class="bi bi-plus-circle"></i> Nouveau type de nano crédit</h1>
</div>

<div class="card">
    <div class="card-header"><i class="bi bi-info-circle"></i> Informations du type</div>
    <div class="card-body">
        <form action="{{ route('nano-credit-types.store') }}" method="POST">
            @csrf

            <div class="mb-3">
                <label for="nom" class="form-label">Nom <span class="text-danger">*</span></label>
                <input type="text" class="form-control @error('nom') is-invalid @enderror" id="nom" name="nom" value="{{ old('nom') }}" required placeholder="Ex: Nano 50k, Crédit express">
                @error('nom')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="mb-3">
                <label for="description" class="form-label">Description</label>
                <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description" rows="2">{{ old('description') }}</textarea>
                @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="montant_min" class="form-label">Montant minimum (XOF) <span class="text-danger">*</span></label>
                    <input type="number" step="1" min="0" class="form-control @error('montant_min') is-invalid @enderror" id="montant_min" name="montant_min" value="{{ old('montant_min', 10000) }}" required>
                    @error('montant_min')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6 mb-3">
                    <label for="montant_max" class="form-label">Montant maximum (XOF)</label>
                    <input type="number" step="1" min="0" class="form-control @error('montant_max') is-invalid @enderror" id="montant_max" name="montant_max" value="{{ old('montant_max') }}" placeholder="Optionnel">
                    @error('montant_max')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="duree_jours" class="form-label">Durée du crédit (jours) <span class="text-danger">*</span></label>
                    <input type="number" min="1" max="30" class="form-control @error('duree_jours') is-invalid @enderror" id="duree_jours" name="duree_jours" value="{{ old('duree_jours', 7) }}" required>
                    @error('duree_jours')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6 mb-3">
                    <label for="taux_interet" class="form-label">Taux d'intérêt annuel (%) <span class="text-danger">*</span></label>
                    <input type="number" step="0.01" min="0" max="100" class="form-control @error('taux_interet') is-invalid @enderror" id="taux_interet" name="taux_interet" value="{{ old('taux_interet', 0) }}" required>
                    @error('taux_interet')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="frequence_remboursement" class="form-label">Fréquence de remboursement <span class="text-danger">*</span></label>
                    <select class="form-select @error('frequence_remboursement') is-invalid @enderror" id="frequence_remboursement" name="frequence_remboursement" required>
                        <option value="journalier" {{ old('frequence_remboursement') === 'journalier' ? 'selected' : '' }}>Journalier</option>
                        <option value="hebdomadaire" {{ old('frequence_remboursement') === 'hebdomadaire' ? 'selected' : '' }}>Hebdomadaire</option>
                        <option value="mensuel" {{ old('frequence_remboursement', 'mensuel') === 'mensuel' ? 'selected' : '' }}>Mensuel</option>
                        <option value="trimestriel" {{ old('frequence_remboursement') === 'trimestriel' ? 'selected' : '' }}>Trimestriel</option>
                    </select>
                    @error('frequence_remboursement')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4 mb-3">
                    <label for="ordre" class="form-label">Ordre d'affichage</label>
                    <input type="number" min="0" class="form-control" id="ordre" name="ordre" value="{{ old('ordre', 0) }}">
                </div>
                <div class="col-md-4 mb-3">
                    <label for="min_epargne_percent" class="form-label">Épargne requise (%) <span class="text-danger">*</span></label>
                    <input type="number" step="0.01" min="0" max="100" class="form-control @error('min_epargne_percent') is-invalid @enderror" id="min_epargne_percent" name="min_epargne_percent" value="{{ old('min_epargne_percent', 85) }}" required>
                    <small class="text-muted">Min. d'épargne par rapport au crédit</small>
                    @error('min_epargne_percent')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>

            <div class="mb-3 form-check">
                <input type="checkbox" class="form-check-input" id="actif" name="actif" value="1" {{ old('actif', true) ? 'checked' : '' }}>
                <label class="form-check-label" for="actif">Type actif (visible pour les membres)</label>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="bi bi-check"></i> Enregistrer</button>
                <a href="{{ route('nano-credit-types.index') }}" class="btn btn-outline-secondary">Annuler</a>
            </div>
        </form>
    </div>
</div>
@endsection
