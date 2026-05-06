@extends('layouts.app')

@section('title', 'Tableau de Bord des Comptes')

@section('content')
<div class="page-header d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h2 mb-0 text-gray-800"><i class="bi bi-bank2 me-2 text-primary"></i>Tableau de Bord des Comptes</h1>
        <p class="text-muted mb-0">Vue analytique globale de la santé financière des clients.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('caisses.index') }}" class="btn btn-primary shadow-sm">
            <i class="bi bi-list-ul me-1"></i> Liste des Comptes
        </a>
        <a href="{{ route('caisses.historique') }}" class="btn btn-outline-secondary shadow-sm">
            <i class="bi bi-clock-history me-1"></i> Historique
        </a>
    </div>
</div>

<!-- KPIs Row -->
<div class="row g-4 mb-4">
    <div class="col-xl-3 col-md-6">
        <div class="card border-0 shadow-sm h-100 overflow-hidden bg-gradient-primary text-white" style="background: linear-gradient(135deg, #4e73df 0%, #224abe 100%);">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-uppercase mb-1 opacity-75">Liquidité Totale</div>
                        <div class="h3 mb-0 font-weight-bold">{{ number_format($totalLiquidite, 0, ',', ' ') }} <small>FCFA</small></div>
                    </div>
                    <div class="col-auto">
                        <i class="bi bi-cash-stack fs-1 opacity-25"></i>
                    </div>
                </div>
            </div>
            <div class="card-footer bg-white bg-opacity-10 border-0">
                <small class="text-white-50"><i class="bi bi-info-circle me-1"></i> Courant + Épargne + Tontines</small>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6">
        <div class="card border-0 shadow-sm h-100 overflow-hidden bg-gradient-success text-white" style="background: linear-gradient(135deg, #1cc88a 0%, #13855c 100%);">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-uppercase mb-1 opacity-75">Encours de Crédit</div>
                        <div class="h3 mb-0 font-weight-bold">{{ number_format($totalCredit, 0, ',', ' ') }} <small>FCFA</small></div>
                    </div>
                    <div class="col-auto">
                        <i class="bi bi-credit-card-2-front fs-1 opacity-25"></i>
                    </div>
                </div>
            </div>
            <div class="card-footer bg-white bg-opacity-10 border-0">
                <small class="text-white-50"><i class="bi bi-activity me-1"></i> Capital actif en circulation</small>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6">
        <div class="card border-0 shadow-sm h-100 overflow-hidden bg-gradient-danger text-white" style="background: linear-gradient(135deg, #e74a3b 0%, #be2617 100%);">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-uppercase mb-1 opacity-75">Total Impayés</div>
                        <div class="h3 mb-0 font-weight-bold">{{ number_format($totalImpayes, 0, ',', ' ') }} <small>FCFA</small></div>
                    </div>
                    <div class="col-auto">
                        <i class="bi bi-exclamation-triangle fs-1 opacity-25"></i>
                    </div>
                </div>
            </div>
            <div class="card-footer bg-white bg-opacity-10 border-0">
                <small class="text-white-50"><i class="bi bi-arrow-right-circle me-1"></i> À recouvrer d'urgence</small>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6">
        <div class="card border-0 shadow-sm h-100 overflow-hidden bg-gradient-info text-white" style="background: linear-gradient(135deg, #36b9cc 0%, #258391 100%);">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-uppercase mb-1 opacity-75">Flux (30j)</div>
                        <div class="h3 mb-0 font-weight-bold">{{ number_format($volumeTransactions, 0, ',', ' ') }} <small>FCFA</small></div>
                    </div>
                    <div class="col-auto">
                        <i class="bi bi-arrow-left-right fs-1 opacity-25"></i>
                    </div>
                </div>
            </div>
            <div class="card-footer bg-white bg-opacity-10 border-0">
                <small class="text-white-50"><i class="bi bi-graph-up me-1"></i> Volume total brassé</small>
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <!-- Chart: Flux Hebdo -->
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white border-bottom py-3">
                <h6 class="m-0 font-weight-bold text-primary"><i class="bi bi-bar-chart-line me-2"></i>Flux Financiers Hebdomadaires (Entrées vs Sorties)</h6>
            </div>
            <div class="card-body">
                <div class="chart-area" style="height: 320px;">
                    <canvas id="fluxChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Chart: Répartition par Type -->
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white border-bottom py-3">
                <h6 class="m-0 font-weight-bold text-primary"><i class="bi bi-pie-chart me-2"></i>Répartition des Comptes</h6>
            </div>
            <div class="card-body">
                <div class="chart-pie pt-4 pb-2" style="height: 250px;">
                    <canvas id="typeChart"></canvas>
                </div>
                <div class="mt-4 text-center small">
                    <span class="mr-2"><i class="bi bi-circle-fill text-primary"></i> Courant</span>
                    <span class="mr-2"><i class="bi bi-circle-fill text-success"></i> Épargne</span>
                    <span class="mr-2"><i class="bi bi-circle-fill text-info"></i> Tontine</span>
                    <br>
                    <span class="mr-2"><i class="bi bi-circle-fill text-warning"></i> Crédit</span>
                    <span class="mr-2"><i class="bi bi-circle-fill text-danger"></i> Impayés</span>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- Top Clients -->
    <div class="col-lg-5">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white border-bottom py-3">
                <h6 class="m-0 font-weight-bold text-primary"><i class="bi bi-star-fill text-warning me-2"></i>Top 5 Clients les plus solvables</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th class="ps-4">Client</th>
                                <th class="text-end pe-4">Solde Global</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($topClients as $client)
                            <tr>
                                <td class="ps-4">
                                    <div class="d-flex align-items-center">
                                        <div class="avatar-sm bg-primary bg-opacity-10 text-primary rounded-circle me-3 d-flex align-items-center justify-content-center" style="width: 35px; height: 35px;">
                                            {{ strtoupper(substr($client['nom'], 0, 1)) }}
                                        </div>
                                        <span class="fw-bold">{{ $client['nom'] }}</span>
                                    </div>
                                </td>
                                <td class="text-end pe-4">
                                    <span class="badge bg-success bg-opacity-10 text-success p-2">
                                        {{ number_format($client['solde'], 0, ',', ' ') }} FCFA
                                    </span>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Derniers Mouvements -->
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
                <h6 class="m-0 font-weight-bold text-primary"><i class="bi bi-activity me-2"></i>Derniers Mouvements de Compte</h6>
                <a href="{{ route('caisses.historique') }}" class="btn btn-sm btn-light">Tout voir</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th class="ps-4">Date</th>
                                <th>Client / Compte</th>
                                <th>Libellé</th>
                                <th class="text-end pe-4">Montant</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($derniersMouvements as $mouv)
                            <tr>
                                <td class="ps-4 small text-muted">
                                    {{ $mouv->date_operation->format('d/m/Y H:i') }}
                                </td>
                                <td>
                                    <div class="small fw-bold">{{ $mouv->caisse->membre?->nom_complet }}</div>
                                    <div class="text-muted" style="font-size: 0.75rem;">{{ $mouv->caisse->nom }}</div>
                                </td>
                                <td>
                                    <span class="small">{{ $mouv->libelle }}</span>
                                    <div><small class="badge bg-{{ $mouv->isEntree() ? 'success' : 'danger' }} bg-opacity-10 text-{{ $mouv->isEntree() ? 'success' : 'danger' }}" style="font-size: 0.65rem;">{{ strtoupper($mouv->type) }}</small></div>
                                </td>
                                <td class="text-end pe-4 fw-bold {{ $mouv->isEntree() ? 'text-success' : 'text-danger' }}">
                                    {{ $mouv->isEntree() ? '+' : '-' }} {{ number_format($mouv->montant, 0, ',', ' ') }}
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // 1. Chart Répartition par Type
    const typeCtx = document.getElementById('typeChart').getContext('2d');
    new Chart(typeCtx, {
        type: 'doughnut',
        data: {
            labels: {!! json_encode(array_keys($repartitionType)) !!},
            datasets: [{
                data: {!! json_encode(array_values($repartitionType)) !!},
                backgroundColor: ['#4e73df', '#1cc88a', '#36b9cc', '#f6c23e', '#e74a3b'],
                hoverBackgroundColor: ['#2e59d9', '#17a673', '#2c9faf', '#dda20a', '#be2617'],
                hoverBorderColor: "rgba(234, 236, 244, 1)",
            }],
        },
        options: {
            maintainAspectRatio: false,
            tooltips: {
                backgroundColor: "rgb(255,255,255)",
                bodyFontColor: "#858796",
                borderColor: '#dddfeb',
                borderWidth: 1,
                xPadding: 15,
                yPadding: 15,
                displayColors: false,
                caretPadding: 10,
            },
            legend: {
                display: false
            },
            cutoutPercentage: 80,
        },
    });

    // 2. Chart Flux Hebdo
    const fluxCtx = document.getElementById('fluxChart').getContext('2d');
    new Chart(fluxCtx, {
        type: 'bar',
        data: {
            labels: {!! json_encode(collect($fluxSemaine)->pluck('date')) !!},
            datasets: [{
                label: 'Entrées',
                data: {!! json_encode(collect($fluxSemaine)->pluck('entree')) !!},
                backgroundColor: 'rgba(28, 200, 138, 0.8)',
                borderColor: '#1cc88a',
                borderWidth: 1
            }, {
                label: 'Sorties',
                data: {!! json_encode(collect($fluxSemaine)->pluck('sortie')) !!},
                backgroundColor: 'rgba(231, 74, 59, 0.8)',
                borderColor: '#e74a3b',
                borderWidth: 1
            }]
        },
        options: {
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return value.toLocaleString() + ' FCFA';
                        }
                    }
                }
            },
            plugins: {
                legend: {
                    position: 'top',
                }
            }
        }
    });
</script>
@endpush
