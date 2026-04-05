@extends('layouts.membre')

@section('title', 'Dashboard')

@section('content')
<style>
    .table-compact {
        font-size: 0.65rem;
        font-weight: 300;
        font-family: 'Ubuntu', sans-serif;
        margin-bottom: 0;
    }
    .table-compact thead th {
        padding: 0.15rem 0.35rem !important;
        font-size: 0.6rem !important;
        line-height: 1.05 !important;
        vertical-align: middle !important;
        font-weight: 300 !important;
        font-family: 'Ubuntu', sans-serif !important;
        color: #ffffff !important;
        background-color: var(--primary-dark-blue) !important;
    }
    .table-compact tbody td {
        padding: 0.15rem 0.35rem !important;
        font-size: 0.65rem !important;
        line-height: 1.05 !important;
        vertical-align: middle !important;
        border-bottom: 1px solid #f0f0f0 !important;
        font-weight: 300 !important;
        font-family: 'Ubuntu', sans-serif !important;
        color: var(--primary-dark-blue) !important;
    }
    .table-compact tbody tr:last-child td {
        border-bottom: none !important;
    }
    table.table.table-compact.table-hover tbody tr {
        background-color: #ffffff !important;
        transition: background-color 0.2s ease !important;
    }
    table.table.table-compact.table-hover tbody tr:nth-child(even) {
        background-color: #d4dde8 !important;
    }
    table.table.table-compact.table-hover tbody tr:hover {
        background-color: #b8c7d9 !important;
        cursor: pointer !important;
    }
    table.table.table-compact.table-hover tbody tr:nth-child(even):hover {
        background-color: #9fb3cc !important;
    }
    .table-compact .btn {
        padding: 0 !important;
        font-size: 0.5rem !important;
        line-height: 1 !important;
        height: 18px !important;
        width: 22px !important;
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
    }
    .table-compact .btn i {
        font-size: 0.6rem !important;
        line-height: 1 !important;
    }
    .chart-container {
        height: 250px !important;
    }
</style>

<div class="page-header">
    <h1 style="font-weight: 300; font-family: 'Ubuntu', sans-serif;">
        <i class="bi bi-speedometer2"></i> Tableau de bord
    </h1>
</div>

@if($annonces->count() > 0)
    @foreach($annonces as $annonce)
        <div class="alert alert-{{ $annonce->type }} alert-dismissible fade show" role="alert">
            <h5 class="alert-heading" style="font-weight: 400; font-size: 0.9rem;">
                <i class="bi bi-megaphone"></i> {{ $annonce->titre }}
            </h5>
            <p style="font-weight: 300; font-size: 0.8rem; margin-bottom: 0;">{{ $annonce->contenu }}</p>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endforeach
@endif

<!-- Statistiques -->
<div class="row mb-4">
    <div class="col-md-4 mb-2">
        <div class="card text-white" style="background: var(--primary-dark-blue);">
            <div class="card-body" style="padding: 0.5rem 0.75rem;">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-subtitle mb-0 text-white-50" style="font-size: 0.65rem; font-weight: 300; font-family: 'Ubuntu', sans-serif;">Total Paiements</h6>
                        <h5 class="mb-0" style="font-size: 1rem; font-weight: 300; font-family: 'Ubuntu', sans-serif;">{{ $totalPaiements }}</h5>
                    </div>
                    <i class="bi bi-receipt" style="font-size: 1.25rem; opacity: 0.5;"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4 mb-2">
        <div class="card text-white" style="background: var(--primary-blue);">
            <div class="card-body" style="padding: 0.5rem 0.75rem;">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-subtitle mb-0 text-white-50" style="font-size: 0.65rem; font-weight: 300; font-family: 'Ubuntu', sans-serif;">Montant Total</h6>
                        <h5 class="mb-0" style="font-size: 0.9rem; font-weight: 300; font-family: 'Ubuntu', sans-serif;">{{ number_format($montantTotal, 0, ',', ' ') }} XOF</h5>
                    </div>
                    <i class="bi bi-cash-coin" style="font-size: 1.25rem; opacity: 0.5;"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4 mb-2">
        <div class="card text-white" style="background: #28a745;">
            <div class="card-body" style="padding: 0.5rem 0.75rem;">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-subtitle mb-0 text-white-50" style="font-size: 0.65rem; font-weight: 300; font-family: 'Ubuntu', sans-serif;">Engagements en cours</h6>
                        <h5 class="mb-0" style="font-size: 1rem; font-weight: 300; font-family: 'Ubuntu', sans-serif;">{{ $engagementsTotal }}</h5>
                    </div>
                    <i class="bi bi-clipboard-check" style="font-size: 1.25rem; opacity: 0.5;"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Graphiques -->
<div class="row mb-4">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header" style="font-weight: 300; font-family: 'Ubuntu', sans-serif;">
                <i class="bi bi-graph-up"></i> Évolution des Paiements (6 derniers mois)
            </div>
            <div class="card-body chart-container" style="position: relative;">
                @if($evolutionPaiements->count() > 0)
                    <canvas id="evolutionChart"></canvas>
                @else
                    <div class="text-center py-3" style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%);">
                        <i class="bi bi-inbox" style="font-size: 1.5rem; color: #ccc;"></i>
                        <p class="text-muted mt-2 mb-2" style="font-size: 0.75rem; font-weight: 300; font-family: 'Ubuntu', sans-serif;">Aucune donnée disponible</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card">
            <div class="card-header" style="font-weight: 300; font-family: 'Ubuntu', sans-serif;">
                <i class="bi bi-pie-chart"></i> Répartition par Mode de Paiement
            </div>
            <div class="card-body chart-container" style="position: relative;">
                @if($paiementsParMode->count() > 0)
                    <canvas id="modePaiementChart"></canvas>
                @else
                    <div class="text-center py-3" style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%);">
                        <i class="bi bi-inbox" style="font-size: 1.5rem; color: #ccc;"></i>
                        <p class="text-muted mt-2 mb-2" style="font-size: 0.75rem; font-weight: 300; font-family: 'Ubuntu', sans-serif;">Aucune donnée disponible</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Paiements récents -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center" style="font-weight: 300; font-family: 'Ubuntu', sans-serif;">
        <span><i class="bi bi-receipt"></i> Mes Paiements Récents</span>
        <a href="{{ route('membre.paiements') }}" class="btn btn-light btn-sm" style="font-weight: 300; font-family: 'Ubuntu', sans-serif;">
            <i class="bi bi-arrow-right"></i> Voir tout
        </a>
    </div>
    <div class="card-body">
        @if($paiementsRecents->count() > 0)
            <div class="table-responsive">
                <table class="table table-compact table-striped table-hover">
                    <thead>
                        <tr>
                            <th>Numéro</th>
                            <th>Cagnotte</th>
                            <th>Montant</th>
                            <th>Date</th>
                            <th>Mode</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($paiementsRecents as $paiement)
                            <tr>
                                <td>{{ $paiement->numero ?? '-' }}</td>
                                <td>{{ $paiement->cotisation->nom ?? '-' }}</td>
                                <td>{{ number_format($paiement->montant, 0, ',', ' ') }} XOF</td>
                                <td>{{ $paiement->date_paiement ? $paiement->date_paiement->format('d/m/Y') : '-' }}</td>
                                <td>{{ ucfirst(str_replace('_', ' ', $paiement->mode_paiement)) }}</td>
                                <td class="text-center">
                                    <a href="{{ route('membre.paiements.pdf', $paiement) }}" 
                                       class="btn btn-sm btn-outline-primary" 
                                       target="_blank"
                                       title="Télécharger le reçu">
                                        <i class="bi bi-download"></i>
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="text-center py-3">
                <i class="bi bi-inbox" style="font-size: 1.5rem; color: #ccc;"></i>
                <p class="text-muted mt-2 mb-0" style="font-size: 0.8rem; font-weight: 300; font-family: 'Ubuntu', sans-serif;">Aucun paiement enregistré</p>
            </div>
        @endif
    </div>
</div>

<!-- Engagements en cours -->
@if($engagementsEnCours->count() > 0)
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center" style="font-weight: 300; font-family: 'Ubuntu', sans-serif;">
        <span><i class="bi bi-clipboard-check"></i> Mes Engagements en cours</span>
        <a href="{{ route('membre.engagements') }}" class="btn btn-light btn-sm" style="font-weight: 300; font-family: 'Ubuntu', sans-serif;">
            <i class="bi bi-arrow-right"></i> Voir tout
        </a>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-compact table-striped table-hover">
                <thead>
                    <tr>
                        <th>Numéro</th>
                        <th>Cagnotte</th>
                        <th>Montant</th>
                        <th>Date échéance</th>
                        <th>Statut</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($engagementsEnCours as $engagement)
                        <tr>
                            <td>{{ $engagement->numero ?? '-' }}</td>
                            <td>{{ $engagement->cotisation->nom ?? '-' }}</td>
                            <td>{{ number_format($engagement->montant_engage, 0, ',', ' ') }} XOF</td>
                            <td>{{ $engagement->periode_fin ? $engagement->periode_fin->format('d/m/Y') : '-' }}</td>
                            <td>{{ ucfirst(str_replace('_', ' ', $engagement->statut)) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endif

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
window.addEventListener('load', function() {
    if (typeof Chart === 'undefined') {
        console.error('Chart.js non chargé');
        return;
    }

    // Configuration commune
    Chart.defaults.font.family = 'Ubuntu';
    Chart.defaults.font.weight = '300';
    Chart.defaults.font.style = 'normal';

    // Données depuis le serveur
    const evolutionData = @json($evolutionPaiements->values());
    const modeData = @json($paiementsParMode->values());

    // Graphique Évolution des Paiements
    const evolutionCtx = document.getElementById('evolutionChart');
    if (evolutionCtx && evolutionData && evolutionData.length > 0) {
        const labels = evolutionData.map(item => {
            const d = new Date(item.date);
            return d.toLocaleDateString('fr-FR', { month: 'short', year: 'numeric' });
        });
        const values = evolutionData.map(item => parseFloat(item.total || 0));
        
        new Chart(evolutionCtx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Montant (XOF)',
                    data: values,
                    borderColor: 'rgb(30, 58, 95)',
                    backgroundColor: 'rgba(30, 58, 95, 0.1)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: true,
                        position: 'top',
                        labels: {
                            font: { style: 'normal', size: 10, weight: '300', family: 'Ubuntu' }
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return new Intl.NumberFormat('fr-FR').format(context.parsed.y) + ' XOF';
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        ticks: {
                            font: { style: 'normal', size: 10, weight: '300', family: 'Ubuntu' }
                        }
                    },
                    y: {
                        beginAtZero: true,
                        ticks: {
                            font: { style: 'normal', size: 10, weight: '300', family: 'Ubuntu' },
                            callback: function(value) {
                                return new Intl.NumberFormat('fr-FR').format(value) + ' XOF';
                            }
                        }
                    }
                }
            }
        });
    }

    // Graphique Répartition par Mode de Paiement
    const modeCtx = document.getElementById('modePaiementChart');
    if (modeCtx && modeData && modeData.length > 0) {
        const labels = modeData.map(item => {
            const m = item.mode_paiement || '';
            return m.charAt(0).toUpperCase() + m.slice(1).replace('_', ' ');
        });
        const values = modeData.map(item => parseFloat(item.total || 0));
        
        // Couleurs pour le graphique en camembert
        const colors = [
            'rgba(30, 58, 95, 0.8)',
            'rgba(40, 167, 69, 0.8)',
            'rgba(220, 53, 69, 0.8)',
            'rgba(255, 193, 7, 0.8)',
            'rgba(23, 162, 184, 0.8)',
        ];
        
        new Chart(modeCtx, {
            type: 'doughnut',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Montant (XOF)',
                    data: values,
                    backgroundColor: colors.slice(0, values.length),
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: true,
                        position: 'bottom',
                        labels: {
                            font: { style: 'normal', size: 10, weight: '300', family: 'Ubuntu' },
                            padding: 10
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const label = context.label || '';
                                const value = context.parsed || 0;
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = ((value / total) * 100).toFixed(1);
                                return label + ': ' + new Intl.NumberFormat('fr-FR').format(value) + ' XOF (' + percentage + '%)';
                            }
                        }
                    }
                }
            }
        });
    }
});
</script>
@endpush
@endsection
