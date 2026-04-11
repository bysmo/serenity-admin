@extends('layouts.app')

@section('title', 'Tableau de Bord Tontines')

@section('content')
<div class="page-header d-flex justify-content-between align-items-center mb-3">
    <h1><i class="bi bi-speedometer2 me-2"></i>Tableau de Bord des Tontines</h1>
    <div>
        <span class="badge bg-light text-dark border">
            <i class="bi bi-calendar3 me-1"></i> {{ $now->format('d/m/Y H:i') }}
        </span>
    </div>
</div>

<div class="row g-3 mb-4">
    <!-- KPIs Principaux -->
    <div class="col-md-3">
        <div class="card h-100 border-0 shadow-sm" style="border-left: 4px solid #0d6efd !important;">
            <div class="card-body">
                <h6 class="text-muted mb-2">Dépôts en cours</h6>
                <h3 class="fw-bold mb-1">{{ number_format($totalDepotsEnCours, 0, ',', ' ') }} <small class="fs-6 fw-normal">FCFA</small></h3>
                <p class="text-primary mb-0"><i class="bi bi-plus-circle me-1"></i> Intérêts dus : {{ number_format($totalInteretsEcheance, 0, ',', ' ') }}</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card h-100 border-0 shadow-sm" style="border-left: 4px solid #198754 !important;">
            <div class="card-body">
                <h6 class="text-muted mb-2">Tontines Remboursées</h6>
                <h3 class="fw-bold mb-1">{{ number_format($totalTontinesRemboursees, 0, ',', ' ') }} <small class="fs-6 fw-normal">FCFA</small></h3>
                <p class="text-success mb-0"><i class="bi bi-check2-all me-1"></i> Intérêts payés : {{ number_format($totalInteretsPayes, 0, ',', ' ') }}</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card h-100 border-0 shadow-sm" style="border-left: 4px solid #ffc107 !important;">
            <div class="card-body">
                <h6 class="text-muted mb-2">Encours Nano-Crédit</h6>
                <h3 class="fw-bold mb-1">{{ number_format($encoursNanoCredit, 0, ',', ' ') }} <small class="fs-6 fw-normal">FCFA</small></h3>
                <p class="text-warning mb-0"><i class="bi bi-activity me-1"></i> Risque actif</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card h-100 border-0 shadow-sm" style="border-left: 4px solid #dc3545 !important;">
            <div class="card-body">
                <h6 class="text-muted mb-2">Total Impayés</h6>
                <h3 class="fw-bold mb-1" style="color: #dc3545;">{{ number_format($totalImpayes, 0, ',', ' ') }} <small class="fs-6 fw-normal">FCFA</small></h3>
                <a href="{{ route('admin.tontines.impayes') }}" class="text-danger text-decoration-none">
                    <i class="bi bi-arrow-right-circle me-1"></i> Voir les détails
                </a>
            </div>
        </div>
    </div>
</div>

<div class="row g-3">
    <!-- Analyse de Liquidité -->
    <div class="col-md-8">
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-white border-bottom py-3">
                <h5 class="card-title mb-0 d-flex align-items-center">
                    <i class="bi bi-bar-chart-fill text-primary me-2"></i> Analyse de la Liquidité & Solidité
                </h5>
            </div>
            <div class="card-body">
                <div class="row g-4 text-center">
                    <div class="col-md-6 border-end">
                        <div class="p-3 bg-light rounded shadow-sm">
                            <h2 class="text-danger fw-bold">{{ number_format($totalVolatile, 0, ',', ' ') }}</h2>
                            <span class="badge bg-danger mb-2">Dépôts Volatiles (< 3 mois)</span>
                            <p class="text-muted small mb-0">Ces fonds devraient être retirés prochainement.</p>
                            <p class="text-danger fw-bold mt-2">Intérêts Urgents : {{ number_format($interetsUrgents, 0, ',', ' ') }}</p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="p-3 bg-light rounded shadow-sm">
                            <h2 class="text-success fw-bold">{{ number_format($totalSolide, 0, ',', ' ') }}</h2>
                            <span class="badge bg-success mb-2">Dépôts Solides (> 3 mois)</span>
                            <p class="text-muted small mb-0">Ces fonds sont stables et utilisables pour le financement.</p>
                            <p class="text-success fw-bold mt-2">Intérêts Futurs : {{ number_format($interetsSolides, 0, ',', ' ') }}</p>
                        </div>
                    </div>
                </div>

                <hr class="my-4">

                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h6 class="mb-0 fw-bold text-primary"><i class="bi bi-shield-check me-2"></i>Indice de Couverture Nano-Crédit</h6>
                    <span class="badge {{ $tauxCouverture >= 100 ? 'bg-success' : ($tauxCouverture > 50 ? 'bg-warning' : 'bg-danger') }} p-2">
                        {{ number_format($tauxCouverture, 1) }}%
                    </span>
                </div>
                <div class="progress" style="height: 15px;">
                    <div class="progress-bar {{ $tauxCouverture >= 100 ? 'bg-success' : ($tauxCouverture > 50 ? 'bg-warning' : 'bg-danger') }} progress-bar-striped progress-bar-animated" 
                         role="progressbar" 
                         style="width: {{ min(100, $tauxCouverture) }}%" 
                         aria-valuenow="{{ $tauxCouverture }}" 
                         aria-valuemin="0" 
                         aria-valuemax="100">
                    </div>
                </div>
                <p class="text-muted mt-2 small">
                    <i class="bi bi-info-circle me-1"></i> Ce taux exprime dans quelle mesure les dépôts **solides** couvrent l'encours total des **nano-crédits**. Un taux supérieur à 100% indique une autonomie financière complète.
                </p>
            </div>
        </div>
    </div>

    <!-- Top Déposants -->
    <div class="col-md-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom py-3">
                <h5 class="card-title mb-0 d-flex align-items-center">
                    <i class="bi bi-trophy-fill text-warning me-2"></i> Plus grands déposants
                </h5>
            </div>
            <div class="card-body p-0">
                <div class="list-group list-group-flush">
                    @forelse($topDeposants as $index => $dep)
                        <div class="list-group-item d-flex justify-content-between align-items-center py-3">
                            <div class="d-flex align-items-center">
                                <span class="badge bg-dark rounded-circle me-3" style="width: 25px; height: 25px; padding: 5px;">{{ $index + 1 }}</span>
                                <div>
                                    <h6 class="mb-0 fw-bold">{{ $dep->membre->nom ?? 'Membre inconnu' }}</h6>
                                    <small class="text-muted">{{ $dep->membre->id_national ?? '' }}</small>
                                </div>
                            </div>
                            <span class="text-primary fw-bold">{{ number_format($dep->total, 0, ',', ' ') }} <small>FCFA</small></span>
                        </div>
                    @empty
                        <div class="p-4 text-center text-muted">Aucun dépôt enregistré.</div>
                    @endforelse
                </div>
            </div>
            <div class="card-footer bg-white text-center py-3">
                <a href="{{ route('admin.tontines.souscriptions') }}" class="btn btn-outline-primary btn-sm w-100">
                    <i class="bi bi-list-ul me-2"></i> Voir toutes les souscriptions
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
