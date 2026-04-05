@extends('layouts.app')

@section('title', 'Modifier Palier — Serenity')

@section('content')
<div class="page-header mb-4">
    <div class="d-flex align-items-center gap-2 mb-1">
        <a href="{{ route('nano-credit-paliers.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left"></i>
        </a>
        <h1 class="page-title mb-0">
            <i class="bi bi-pencil me-2"></i>Modifier — {{ $palier->nom }}
        </h1>
    </div>
</div>

<form action="{{ route('nano-credit-paliers.update', $palier) }}" method="POST">
    @csrf @method('PUT')

    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    <div class="row g-4">

        {{-- Informations générales --}}
        <div class="col-12">
            <div class="card">
                <div class="card-header py-2"><strong><i class="bi bi-info-circle me-2"></i>Informations générales</strong></div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-2">
                            <label class="form-label">Numéro <span class="text-danger">*</span></label>
                            <input type="number" name="numero" class="form-control form-control-sm @error('numero') is-invalid @enderror"
                                   value="{{ old('numero', $palier->numero) }}" min="1" required>
                            @error('numero')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Nom <span class="text-danger">*</span></label>
                            <input type="text" name="nom" class="form-control form-control-sm @error('nom') is-invalid @enderror"
                                   value="{{ old('nom', $palier->nom) }}" required>
                            @error('nom')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Statut</label>
                            <div class="form-check mt-2">
                                <input class="form-check-input" type="checkbox" name="actif" id="actif" value="1"
                                       {{ old('actif', $palier->actif) ? 'checked' : '' }}>
                                <label class="form-check-label" for="actif">Palier actif</label>
                            </div>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Description</label>
                            <textarea name="description" class="form-control form-control-sm" rows="2">{{ old('description', $palier->description) }}</textarea>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Paramètres crédit --}}
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header py-2"><strong><i class="bi bi-cash-coin me-2"></i>Paramètres du crédit</strong></div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">Montant plafond (FCFA) <span class="text-danger">*</span></label>
                            <input type="number" name="montant_plafond" class="form-control form-control-sm"
                                   value="{{ old('montant_plafond', (int)(float)$palier->montant_plafond) }}" min="1" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label">Durée max (jours) <span class="text-danger">*</span></label>
                            <input type="number" name="duree_jours" class="form-control form-control-sm"
                                   value="{{ old('duree_jours', $palier->duree_jours) }}" min="1" max="3650" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label">Nombre de garants</label>
                            <input type="number" name="nombre_garants" class="form-control form-control-sm"
                                   value="{{ old('nombre_garants', $palier->nombre_garants) }}" min="0" max="10">
                        </div>
                        <div class="col-6">
                            <label class="form-label">Taux intérêt annuel (%) <span class="text-danger">*</span></label>
                            <input type="number" name="taux_interet" class="form-control form-control-sm"
                                   value="{{ old('taux_interet', $palier->taux_interet) }}" min="0" max="100" step="0.01" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label">Fréquence remboursement</label>
                            <select name="frequence_remboursement" class="form-select form-select-sm">
                                @foreach(['journalier' => 'Journalier','hebdomadaire' => 'Hebdomadaire','mensuel' => 'Mensuel','trimestriel' => 'Trimestriel'] as $val => $label)
                                    <option value="{{ $val }}" {{ old('frequence_remboursement', $palier->frequence_remboursement) === $val ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label">Qualité min. garants <span class="text-danger">*</span></label>
                            <input type="number" name="min_garant_qualite" class="form-control form-control-sm @error('min_garant_qualite') is-invalid @enderror"
                                   value="{{ old('min_garant_qualite', $palier->min_garant_qualite) }}" min="0" required>
                            <small class="text-muted">Qualité minimale pour être garant à ce palier</small>
                            @error('min_garant_qualite')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-6">
                            <label class="form-label">Partage bénéfices garants (%) <span class="text-danger">*</span></label>
                            <input type="number" name="pourcentage_partage_garant" class="form-control form-control-sm @error('pourcentage_partage_garant') is-invalid @enderror"
                                   value="{{ old('pourcentage_partage_garant', $palier->pourcentage_partage_garant) }}" min="0" max="100" step="0.01" required>
                            <small class="text-muted">% des intérêts redistribués aux garants</small>
                            @error('pourcentage_partage_garant')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Conditions d'accession --}}
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header py-2"><strong><i class="bi bi-arrow-up-circle me-2 text-success"></i>Conditions d'accession</strong></div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">Crédits remboursés (min)</label>
                            <input type="number" name="min_credits_rembourses" class="form-control form-control-sm"
                                   value="{{ old('min_credits_rembourses', $palier->min_credits_rembourses) }}" min="0">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Montant total remboursé (FCFA, min)</label>
                            <input type="number" name="min_montant_total_rembourse" class="form-control form-control-sm"
                                   value="{{ old('min_montant_total_rembourse', (int)(float)$palier->min_montant_total_rembourse) }}" min="0">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Épargne cumulée (FCFA, min)</label>
                            <input type="number" name="min_epargne_cumulee" class="form-control form-control-sm"
                                   value="{{ old('min_epargne_cumulee', (int)(float)$palier->min_epargne_cumulee) }}" min="0">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Pénalités & Conséquences --}}
        <div class="col-12">
            <div class="card">
                <div class="card-header py-2"><strong><i class="bi bi-exclamation-triangle me-2 text-danger"></i>Pénalités & Conséquences</strong></div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-3 col-6">
                            <label class="form-label">Pénalité par jour (%)</label>
                            <input type="number" name="penalite_par_jour" class="form-control form-control-sm"
                                   value="{{ old('penalite_par_jour', $palier->penalite_par_jour) }}" min="0" max="100" step="0.01">
                        </div>
                        <div class="col-md-3 col-6">
                            <label class="form-label">Jours avant prélèvement garants</label>
                            <input type="number" name="jours_avant_prelevement_garant" class="form-control form-control-sm"
                                   value="{{ old('jours_avant_prelevement_garant', $palier->jours_avant_prelevement_garant) }}" min="1">
                        </div>
                        <div class="col-md-3 col-6">
                            <label class="form-label">Jours pour downgrade</label>
                            <input type="number" name="jours_impayes_pour_downgrade" class="form-control form-control-sm"
                                   value="{{ old('jours_impayes_pour_downgrade', $palier->jours_impayes_pour_downgrade) }}" min="1">
                        </div>
                        <div class="col-md-3 col-6">
                            <label class="form-label">Nb défauts pour interdiction</label>
                            <input type="number" name="nb_recidives_pour_interdiction" class="form-control form-control-sm"
                                   value="{{ old('nb_recidives_pour_interdiction', $palier->nb_recidives_pour_interdiction) }}" min="1">
                        </div>
                        <div class="col-md-6">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="downgrade_en_cas_impayes" id="downgrade" value="1"
                                       {{ old('downgrade_en_cas_impayes', $palier->downgrade_en_cas_impayes) ? 'checked' : '' }}>
                                <label class="form-check-label" for="downgrade">Downgrade automatique en cas d'impayés</label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="interdiction_en_cas_recidive" id="interdiction" value="1"
                                       {{ old('interdiction_en_cas_recidive', $palier->interdiction_en_cas_recidive) ? 'checked' : '' }}>
                                <label class="form-check-label" for="interdiction">Interdiction en cas de récidive</label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12">
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary btn-sm">
                    <i class="bi bi-check-circle me-1"></i> Enregistrer les modifications
                </button>
                <a href="{{ route('nano-credit-paliers.index') }}" class="btn btn-outline-secondary btn-sm">Annuler</a>
            </div>
        </div>
    </div>
</form>
@endsection
