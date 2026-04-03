@extends('layouts.app')

@section('title', 'Créer un plan de tontine')

@section('content')
<div class="page-header">
    <h1><i class="bi bi-plus-circle"></i> Nouveau plan de tontine</h1>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <i class="bi bi-info-circle"></i> Informations du plan
            </div>
            <div class="card-body">
                <form action="{{ route('epargne-plans.store') }}" method="POST">
                    @csrf

                    <div class="mb-3">
                        <label for="nom" class="form-label">Nom du plan <span class="text-danger">*</span></label>
                        <input type="text"
                               class="form-control @error('nom') is-invalid @enderror"
                               id="nom" name="nom" value="{{ old('nom') }}" required
                               placeholder="Ex: Épargne Libre, Épargne 10k">
                        @error('nom')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control @error('description') is-invalid @enderror"
                                  id="description" name="description" rows="3"
                                  placeholder="Texte affiché aux membres...">{{ old('description') }}</textarea>
                        @error('description')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="montant_min" class="form-label">Montant minimum par versement (XOF) <span class="text-danger">*</span></label>
                            <input type="number" step="1" min="0"
                                   class="form-control @error('montant_min') is-invalid @enderror"
                                   id="montant_min" name="montant_min" value="{{ old('montant_min', 0) }}" required>
                            @error('montant_min')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="montant_max" class="form-label">Montant maximum (XOF)</label>
                            <input type="number" step="1" min="0"
                                   class="form-control @error('montant_max') is-invalid @enderror"
                                   id="montant_max" name="montant_max" value="{{ old('montant_max') }}"
                                   placeholder="Optionnel">
                            @error('montant_max')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="form-text text-muted">Laisser vide = illimité</small>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="frequence" class="form-label">Fréquence <span class="text-danger">*</span></label>
                        <select class="form-select @error('frequence') is-invalid @enderror" id="frequence" name="frequence" required>
                            <option value="">Choisir...</option>
                            <option value="hebdomadaire" {{ old('frequence') === 'hebdomadaire' ? 'selected' : '' }}>Hebdomadaire</option>
                            <option value="mensuel" {{ old('frequence') === 'mensuel' ? 'selected' : '' }}>Mensuel</option>
                            <option value="trimestriel" {{ old('frequence') === 'trimestriel' ? 'selected' : '' }}>Trimestriel</option>
                        </select>
                        @error('frequence')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="taux_remuneration" class="form-label">Taux de rémunération (%) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" min="0" max="100"
                                   class="form-control @error('taux_remuneration') is-invalid @enderror"
                                   id="taux_remuneration" name="taux_remuneration" value="{{ old('taux_remuneration', 0) }}" required
                                   placeholder="Ex: 3.5">
                            @error('taux_remuneration')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="form-text text-muted">Taux annuel appliqué en fin de plan (intérêt simple).</small>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="duree_mois" class="form-label">Durée du plan (mois) <span class="text-danger">*</span></label>
                            <input type="number" min="1" max="360"
                                   class="form-control @error('duree_mois') is-invalid @enderror"
                                   id="duree_mois" name="duree_mois" value="{{ old('duree_mois', 12) }}" required
                                   placeholder="Ex: 12">
                            @error('duree_mois')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="form-text text-muted">Ex: 12 = 1 an, 24 = 2 ans.</small>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="caisse_id" class="form-label">Caisse associée</label>
                        <select class="form-select @error('caisse_id') is-invalid @enderror" id="caisse_id" name="caisse_id">
                            <option value="">— Aucune —</option>
                            @foreach($caisses as $caisse)
                                <option value="{{ $caisse->id }}" {{ old('caisse_id') == $caisse->id ? 'selected' : '' }}>{{ $caisse->nom }}</option>
                            @endforeach
                        </select>
                        @error('caisse_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="form-text text-muted">Les versements seront enregistrés dans cette caisse.</small>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="ordre" class="form-label">Ordre d'affichage</label>
                            <input type="number" min="0" class="form-control" id="ordre" name="ordre" value="{{ old('ordre', 0) }}">
                        </div>
                        <div class="col-md-6 mb-3 d-flex align-items-end">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="actif" id="actif" value="1" {{ old('actif', true) ? 'checked' : '' }}>
                                <label class="form-check-label" for="actif">Plan actif (proposé aux membres)</label>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between mt-3">
                        <a href="{{ route('epargne-plans.index') }}" class="btn btn-secondary">
                            <i class="bi bi-arrow-left"></i> Retour
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-circle"></i> Enregistrer
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <i class="bi bi-info-circle"></i> À propos des plans de tontine
            </div>
            <div class="card-body">
                <h6 class="mb-3" style="font-weight: 300; font-family: 'Ubuntu', sans-serif; color: var(--primary-dark-blue);">
                    <i class="bi bi-piggy-bank"></i> Qu'est-ce qu'un plan ?
                </h6>
                <p style="font-size: 0.75rem; line-height: 1.5; font-weight: 300; font-family: 'Ubuntu', sans-serif; color: #666;">
                    Un plan définit les conditions de tontine proposées aux membres : montant min/max par versement, fréquence (hebdomadaire, mensuel, trimestriel) et la caisse dans laquelle les versements sont enregistrés.
                </p>
                <ul style="font-size: 0.75rem; line-height: 1.8; font-weight: 300; font-family: 'Ubuntu', sans-serif; color: #666; padding-left: 1.2rem;">
                    <li>Seuls les plans <strong>actifs</strong> sont visibles par les membres.</li>
                    <li>L'ordre permet de trier l'affichage sur l'espace membre.</li>
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection
