@extends('layouts.app')

@section('title', 'Créer un Palier — Serenity')

@section('content')
<div class="page-header mb-4">
    <div class="d-flex align-items-center gap-2 mb-1">
        <a href="{{ route('nano-credit-paliers.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left"></i>
        </a>
        <h1 class="page-title mb-0"><i class="bi bi-plus-circle me-2"></i>Nouveau Palier Nano-Crédit</h1>
    </div>
    <p class="text-muted mb-0" style="font-size: 0.85rem;">Configurez toutes les conditions et paramètres du palier.</p>
</div>

<form action="{{ route('nano-credit-paliers.store') }}" method="POST">
    @csrf

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
                            <label class="form-label">Numéro du palier <span class="text-danger">*</span></label>
                            <input type="number" name="numero" class="form-control form-control-sm @error('numero') is-invalid @enderror"
                                   value="{{ old('numero', $prochainNumero) }}" min="1" required>
                            @error('numero')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Nom du palier <span class="text-danger">*</span></label>
                            <input type="text" name="nom" class="form-control form-control-sm @error('nom') is-invalid @enderror"
                                   value="{{ old('nom') }}" placeholder="Ex: Palier 1 — Démarrage" required>
                            @error('nom')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Statut</label>
                            <div class="form-check mt-2">
                                <input class="form-check-input" type="checkbox" name="actif" id="actif" value="1" {{ old('actif', true) ? 'checked' : '' }}>
                                <label class="form-check-label" for="actif">Palier actif</label>
                            </div>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Description</label>
                            <textarea name="description" class="form-control form-control-sm" rows="2"
                                      placeholder="Description du palier (facultatif)">{{ old('description') }}</textarea>
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
                            <input type="number" name="montant_plafond" class="form-control form-control-sm @error('montant_plafond') is-invalid @enderror"
                                   value="{{ old('montant_plafond') }}" min="1" placeholder="50000" required>
                            @error('montant_plafond')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-6">
                            <label class="form-label">Durée max (jours) <span class="text-danger">*</span></label>
                            <input type="number" name="duree_jours" class="form-control form-control-sm @error('duree_jours') is-invalid @enderror"
                                   value="{{ old('duree_jours', 30) }}" min="1" max="3650" required>
                            @error('duree_jours')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-6">
                            <label class="form-label">Nombre de garants <span class="text-danger">*</span></label>
                            <input type="number" name="nombre_garants" class="form-control form-control-sm"
                                   value="{{ old('nombre_garants', 0) }}" min="0" max="10">
                        </div>
                        <div class="col-6">
                            <label class="form-label">Taux d'intérêt annuel (%) <span class="text-danger">*</span></label>
                            <input type="number" name="taux_interet" class="form-control form-control-sm @error('taux_interet') is-invalid @enderror"
                                   value="{{ old('taux_interet', 0) }}" min="0" max="100" step="0.01" required>
                            @error('taux_interet')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-6">
                            <label class="form-label">Fréquence remboursement <span class="text-danger">*</span></label>
                            <select name="frequence_remboursement" class="form-select form-select-sm" required>
                                @foreach(['journalier' => 'Journalier','hebdomadaire' => 'Hebdomadaire','mensuel' => 'Mensuel','trimestriel' => 'Trimestriel'] as $val => $label)
                                    <option value="{{ $val }}" {{ old('frequence_remboursement', 'mensuel') === $val ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Conditions d'accession --}}
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header py-2"><strong><i class="bi bi-arrow-up-circle me-2 text-success"></i>Conditions d'accession à ce palier</strong></div>
                <div class="card-body">
                    <p class="text-muted" style="font-size: 0.8rem;">Pour le Palier 1 : laisser à 0 (assigné automatiquement via KYC).</p>
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">Crédits entièrement remboursés (min)</label>
                            <input type="number" name="min_credits_rembourses" class="form-control form-control-sm"
                                   value="{{ old('min_credits_rembourses', 0) }}" min="0">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Montant total remboursé (FCFA, min)</label>
                            <input type="number" name="min_montant_total_rembourse" class="form-control form-control-sm"
                                   value="{{ old('min_montant_total_rembourse', 0) }}" min="0">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Épargne/Tontine cumulée (FCFA, min)</label>
                            <input type="number" name="min_epargne_cumulee" class="form-control form-control-sm"
                                   value="{{ old('min_epargne_cumulee', 0) }}" min="0">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Pénalités & Conséquences --}}
        <div class="col-12">
            <div class="card">
                <div class="card-header py-2"><strong><i class="bi bi-exclamation-triangle me-2 text-danger"></i>Pénalités & Conséquences en cas d'impayés</strong></div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-3 col-6">
                            <label class="form-label">Pénalité par jour de retard (%) <span class="text-danger">*</span></label>
                            <input type="number" name="penalite_par_jour" class="form-control form-control-sm @error('penalite_par_jour') is-invalid @enderror"
                                   value="{{ old('penalite_par_jour', 5) }}" min="0" max="100" step="0.01" required>
                            <small class="text-muted">Ex: 5 = 5% du capital restant dû par jour</small>
                            @error('penalite_par_jour')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-3 col-6">
                            <label class="form-label">Jours avant prélèvement garants <span class="text-danger">*</span></label>
                            <input type="number" name="jours_avant_prelevement_garant" class="form-control form-control-sm"
                                   value="{{ old('jours_avant_prelevement_garant', 30) }}" min="1">
                            <small class="text-muted">Après n jours d'impayés, les garants sont prélevés</small>
                        </div>
                        <div class="col-md-3 col-6">
                            <label class="form-label">Jours pour déclencher downgrade</label>
                            <input type="number" name="jours_impayes_pour_downgrade" class="form-control form-control-sm"
                                   value="{{ old('jours_impayes_pour_downgrade', 15) }}" min="1">
                        </div>
                        <div class="col-md-3 col-6">
                            <label class="form-label">Nb défauts pour interdiction</label>
                            <input type="number" name="nb_recidives_pour_interdiction" class="form-control form-control-sm"
                                   value="{{ old('nb_recidives_pour_interdiction', 3) }}" min="1">
                        </div>
                        <div class="col-md-6">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="downgrade_en_cas_impayes" id="downgrade" value="1"
                                       {{ old('downgrade_en_cas_impayes', true) ? 'checked' : '' }}>
                                <label class="form-check-label" for="downgrade">
                                    Rétrograder automatiquement le membre (et ses garants) en cas d'impayés
                                </label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="interdiction_en_cas_recidive" id="interdiction" value="1"
                                       {{ old('interdiction_en_cas_recidive') ? 'checked' : '' }}>
                                <label class="form-check-label" for="interdiction">
                                    Interdire le membre de prendre des nano-crédits en cas de récidive
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12">
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary btn-sm">
                    <i class="bi bi-check-circle me-1"></i> Créer le palier
                </button>
                <a href="{{ route('nano-credit-paliers.index') }}" class="btn btn-outline-secondary btn-sm">Annuler</a>
            </div>
        </div>

    </div>
</form>
@endsection
