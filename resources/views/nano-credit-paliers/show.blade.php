@extends('layouts.app')

@section('title', 'Détail Palier — Serenity')

@section('content')
<div class="page-header mb-4">
    <div class="d-flex align-items-center gap-2 mb-1">
        <a href="{{ route('nano-credit-paliers.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left"></i>
        </a>
        <h1 class="page-title mb-0">
            <span class="badge me-2" style="background: var(--primary-dark-blue); font-size: 1rem;">Palier {{ $palier->numero }}</span>
            {{ $palier->nom }}
            @if(!$palier->actif)<span class="badge bg-secondary ms-2">Inactif</span>@endif
        </h1>
    </div>
    @if($palier->description)
        <p class="text-muted mb-0" style="font-size: 0.85rem;">{{ $palier->description }}</p>
    @endif
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show">
        <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif
@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show">
        <i class="bi bi-exclamation-triangle me-2"></i>{{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

<div class="row g-4 mb-4">
    {{-- Stats crédits --}}
    <div class="col-6 col-md-3">
        <div class="card text-center">
            <div class="card-body py-3">
                <div style="font-size: 2rem; font-weight: 700; color: var(--primary-dark-blue);">{{ $statsCredits['total'] }}</div>
                <small class="text-muted">Crédits au total</small>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card text-center">
            <div class="card-body py-3">
                <div style="font-size: 2rem; font-weight: 700; color: #fd7e14;">{{ $statsCredits['en_cours'] }}</div>
                <small class="text-muted">En cours</small>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card text-center">
            <div class="card-body py-3">
                <div style="font-size: 2rem; font-weight: 700; color: #28a745;">{{ $statsCredits['rembourses'] }}</div>
                <small class="text-muted">Remboursés</small>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card text-center">
            <div class="card-body py-3">
                <div style="font-size: 2rem; font-weight: 700; color: #dc3545;">{{ $statsCredits['en_retard'] }}</div>
                <small class="text-muted">En retard</small>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    {{-- Infos palier --}}
    <div class="col-md-5">
        <div class="card">
            <div class="card-header py-2"><strong><i class="bi bi-gear me-2"></i>Configuration du palier</strong></div>
            <div class="card-body">
                <table class="table table-sm mb-0" style="font-size: 0.85rem;">
                    <tr><td class="text-muted">Plafond</td><td class="fw-bold">{{ number_format((float)$palier->montant_plafond, 0, ',', ' ') }} FCFA</td></tr>
                    <tr><td class="text-muted">Durée max</td><td>{{ $palier->duree_jours }} jours</td></tr>
                    <tr><td class="text-muted">Garants requis</td><td>{{ $palier->nombre_garants }}</td></tr>
                    <tr><td class="text-muted">Taux intérêt</td><td>{{ $palier->taux_interet }}% / an</td></tr>
                    <tr><td class="text-muted">Fréquence remb.</td><td>{{ $palier->frequence_remboursement_label }}</td></tr>
                    <tr><td class="text-muted">Pénalité retard</td><td class="text-danger fw-bold">{{ $palier->penalite_par_jour }}% / jour</td></tr>
                    <tr><td class="text-muted">Prélèvement garants</td><td>Après {{ $palier->jours_avant_prelevement_garant }} jours</td></tr>
                    <tr><td class="text-muted">Downgrade impayés</td><td>{{ $palier->downgrade_en_cas_impayes ? 'Oui (après '.$palier->jours_impayes_pour_downgrade.'j)' : 'Non' }}</td></tr>
                    <tr><td class="text-muted">Interdiction récidive</td><td>{{ $palier->interdiction_en_cas_recidive ? 'Oui (après '.$palier->nb_recidives_pour_interdiction.' défauts)' : 'Non' }}</td></tr>
                    <tr>
                        <td colspan="2" class="pt-3">
                            <strong class="text-success"><i class="bi bi-arrow-up-circle"></i> Conditions d'accession :</strong>
                            <ul class="mb-0 mt-1" style="font-size: 0.8rem; padding-left: 1rem;">
                                <li>{{ $palier->min_credits_rembourses }} crédit(s) remboursé(s)</li>
                                <li>{{ number_format((float)$palier->min_montant_total_rembourse, 0, ',', ' ') }} FCFA remboursés</li>
                                <li>{{ number_format((float)$palier->min_epargne_cumulee, 0, ',', ' ') }} FCFA d'épargne</li>
                            </ul>
                        </td>
                    </tr>
                </table>
            </div>
            <div class="card-footer py-2">
                <a href="{{ route('nano-credit-paliers.edit', $palier) }}" class="btn btn-primary btn-sm">
                    <i class="bi bi-pencil me-1"></i> Modifier
                </a>
            </div>
        </div>
    </div>

    {{-- Membres à ce palier --}}
    <div class="col-md-7">
        <div class="card">
            <div class="card-header py-2 d-flex align-items-center justify-content-between">
                <strong><i class="bi bi-people me-2"></i>Membres à ce palier</strong>
                <span class="badge bg-secondary">{{ $membres->total() }}</span>
            </div>
            <div class="card-body p-0">
                @if($membres->isEmpty())
                    <p class="text-center text-muted py-4" style="font-size: 0.85rem;">Aucun membre à ce palier.</p>
                @else
                    <div class="table-responsive">
                        <table class="table table-sm table-hover mb-0" style="font-size: 0.82rem;">
                            <thead>
                                <tr>
                                    <th>Membre</th>
                                    <th>Crédits remb.</th>
                                    <th>Statut</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($membres as $membre)
                                <tr>
                                    <td>
                                        <a href="{{ route('membres.show', $membre) }}">{{ $membre->nom_complet }}</a>
                                        @if($membre->nano_credit_interdit)
                                            <span class="badge bg-danger ms-1" title="{{ $membre->motif_interdiction }}">Interdit</span>
                                        @endif
                                    </td>
                                    <td>{{ $membre->credits_rembourses ?? 0 }}</td>
                                    <td>
                                        @if($membre->hasImpayes())
                                            <span class="badge bg-warning text-dark">Impayés</span>
                                        @else
                                            <span class="badge bg-success">OK</span>
                                        @endif
                                    </td>
                                    <td>
                                        <a href="{{ route('membres.show', $membre) }}" class="btn btn-sm btn-outline-info" style="padding: 0.1rem 0.4rem;">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="p-2">{{ $membres->links() }}</div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
