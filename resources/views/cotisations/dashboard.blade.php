@extends('layouts.app')

@section('title', 'Tableau de Bord Cagnottes')

@section('content')
<div class="page-header d-flex justify-content-between align-items-center mb-3">
    <h1 style="color: var(--primary-dark-blue); font-weight: 400;">
        <i class="bi bi-piggy-bank-fill me-2"></i>Tableau de Bord des Cagnottes
    </h1>
    <div class="d-flex gap-2">
        <span class="badge bg-white text-dark border shadow-sm px-3 py-2">
            <i class="bi bi-calendar3 me-1 text-primary"></i> {{ $now->format('d/m/Y H:i') }}
        </span>
    </div>
</div>

<div class="row g-3 mb-4">
    <!-- KPI: Total Collecté -->
    <div class="col-md-3">
        <div class="card h-100 border-0 shadow-sm overflow-hidden" style="background: linear-gradient(135deg, #1e3a8a, #3b82f6); border-radius: 12px;">
            <div class="card-body p-4 text-white position-relative">
                <div class="position-absolute top-0 end-0 p-3 opacity-25">
                    <i class="bi bi-currency-exchange fs-1"></i>
                </div>
                <h6 class="text-uppercase small opacity-75 mb-2 fw-bold" style="letter-spacing: 1px;">Total Collecté</h6>
                <h3 class="fw-bold mb-1">{{ number_format($totalFondsCollectes, 0, ',', ' ') }} <small class="fs-6 fw-normal">XOF</small></h3>
                <div class="mt-3">
                    <span class="badge bg-white bg-opacity-25 px-2 py-1">
                        <i class="bi bi-people-fill me-1"></i> {{ $totalAdherents }} Participants
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- KPI: Cagnottes Actives -->
    <div class="col-md-3">
        <div class="card h-100 border-0 shadow-sm" style="background: #fff; border-radius: 12px; border-left: 5px solid #10b981 !important;">
            <div class="card-body p-4">
                <h6 class="text-muted text-uppercase small mb-2 fw-bold" style="letter-spacing: 1px;">Cagnottes Actives</h6>
                <h3 class="fw-bold mb-1 text-dark">{{ $activeCagnottes }} / {{ $totalCagnottes }}</h3>
                <div class="progress mt-3" style="height: 6px;">
                    <div class="progress-bar bg-success" role="progressbar" style="width: {{ $totalCagnottes > 0 ? ($activeCagnottes / $totalCagnottes) * 100 : 0 }}%"></div>
                </div>
                <p class="text-muted small mt-2 mb-0">
                    <span class="text-success"><i class="bi bi-globe me-1"></i>{{ $publiqueCagnottes }} Publiques</span>
                </p>
            </div>
        </div>
    </div>

    <!-- KPI: Retraits en Attente -->
    <div class="col-md-3">
        <div class="card h-100 border-0 shadow-sm" style="background: #fff; border-radius: 12px; border-left: 5px solid #f59e0b !important;">
            <div class="card-body p-4">
                <h6 class="text-muted text-uppercase small mb-2 fw-bold" style="letter-spacing: 1px;">Retraits en Attente</h6>
                <h3 class="fw-bold mb-1 text-warning">{{ number_format($demandesEnAttenteAmount, 0, ',', ' ') }} <small class="fs-6 fw-normal">XOF</small></h3>
                <div class="mt-3">
                    <a href="{{ route('cotisation-versement-demandes.index') }}?statut=en_attente" class="btn btn-sm btn-outline-warning rounded-pill px-3">
                        {{ $demandesEnAttenteCount }} Demandes <i class="bi bi-arrow-right ms-1"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- KPI: Taux d'Adhésion -->
    <div class="col-md-3">
        <div class="card h-100 border-0 shadow-sm overflow-hidden" style="background: linear-gradient(135deg, #10b981, #059669); border-radius: 12px;">
            <div class="card-body p-4 text-white position-relative">
                <div class="position-absolute top-0 end-0 p-3 opacity-25">
                    <i class="bi bi-person-check-fill fs-1"></i>
                </div>
                <h6 class="text-uppercase small opacity-75 mb-2 fw-bold" style="letter-spacing: 1px;">Participation Moyenne</h6>
                <h3 class="fw-bold mb-1">{{ $totalCagnottes > 0 ? round($totalAdherents / $totalCagnottes, 1) : 0 }}</h3>
                <p class="mb-0 small opacity-75 mt-3">Membres par cagnotte</p>
            </div>
        </div>
    </div>
</div>

<div class="row g-3">
    <!-- Graphique d'Évolution -->
    <div class="col-md-8">
        <div class="card border-0 shadow-sm h-100" style="border-radius: 12px;">
            <div class="card-header bg-white border-bottom py-3 px-4">
                <h5 class="card-title mb-0 fw-bold text-dark">
                    <i class="bi bi-graph-up-arrow text-primary me-2"></i>Flux de Versements (30 derniers jours)
                </h5>
            </div>
            <div class="card-body p-4">
                <canvas id="paymentsChart" style="height: 300px; width: 100%;"></canvas>
            </div>
        </div>
    </div>

    <!-- Top Cagnottes -->
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100" style="border-radius: 12px;">
            <div class="card-header bg-white border-bottom py-3 px-4">
                <h5 class="card-title mb-0 fw-bold text-dark">
                    <i class="bi bi-trophy-fill text-warning me-2"></i>Top Cagnottes
                </h5>
            </div>
            <div class="card-body p-0">
                <div class="list-group list-group-flush">
                    @forelse($topCagnottes as $index => $cagnotte)
                        <div class="list-group-item d-flex justify-content-between align-items-center py-3 px-4 hover-highlight" style="transition: background 0.2s;">
                            <div class="d-flex align-items-center">
                                <div class="badge bg-light text-dark rounded-circle me-3 d-flex align-items-center justify-content-center" style="width: 30px; height: 30px;">
                                    {{ $index + 1 }}
                                </div>
                                <div>
                                    <h6 class="mb-0 fw-bold" style="font-size: 0.9rem;">{{ $cagnotte->nom }}</h6>
                                    <small class="text-muted">{{ $cagnotte->visibilite === 'publique' ? 'Publique' : 'Privée' }}</small>
                                </div>
                            </div>
                            <div class="text-end">
                                <div class="fw-bold text-primary">{{ number_format($cagnotte->caisse->solde_actuel ?? 0, 0, ',', ' ') }}</div>
                                <small class="text-muted" style="font-size: 0.7rem;">XOF</small>
                            </div>
                        </div>
                    @empty
                        <div class="p-4 text-center text-muted">Aucune donnée disponible.</div>
                    @endforelse
                </div>
            </div>
            <div class="card-footer bg-white border-top-0 text-center py-3">
                <a href="{{ route('cotisations.index') }}" class="btn btn-sm btn-light w-100 text-primary fw-bold">
                    Voir toutes les cagnottes <i class="bi bi-box-arrow-in-right ms-1"></i>
                </a>
            </div>
        </div>
    </div>

    <!-- Activité Récente -->
    <div class="col-12 mt-3">
        <div class="card border-0 shadow-sm" style="border-radius: 12px;">
            <div class="card-header bg-white border-bottom py-3 px-4">
                <h5 class="card-title mb-0 fw-bold text-dark">
                    <i class="bi bi-clock-history text-info me-2"></i>Versements Récents
                </h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th class="ps-4">Membre</th>
                                <th>Cagnotte</th>
                                <th>Montant</th>
                                <th>Date & Heure</th>
                                <th class="pe-4 text-end">Mode</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentPayments as $payment)
                                <tr>
                                    <td class="ps-4">
                                        <div class="d-flex align-items-center">
                                            <div class="rounded-circle bg-primary bg-opacity-10 text-primary d-flex align-items-center justify-content-center me-2" style="width: 32px; height: 32px;">
                                                <i class="bi bi-person"></i>
                                            </div>
                                            <span class="fw-medium">{{ $payment->membre->nom_complet }}</span>
                                        </div>
                                    </td>
                                    <td>{{ $payment->cotisation->nom ?? 'N/A' }}</td>
                                    <td class="fw-bold text-success">+ {{ number_format($payment->montant, 0, ',', ' ') }} XOF</td>
                                    <td>
                                        <div class="small fw-medium">{{ $payment->date_paiement->format('d M Y') }}</div>
                                        <div class="text-muted" style="font-size: 0.7rem;">{{ $payment->date_paiement->format('H:i') }}</div>
                                    </td>
                                    <td class="pe-4 text-end">
                                        <span class="badge bg-info bg-opacity-10 text-info px-2 py-1 rounded-pill" style="font-size: 0.7rem;">
                                            {{ strtoupper($payment->mode_paiement) }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="p-4 text-center text-muted">Aucun versement récent.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
    .hover-highlight:hover { background-color: #f8fafc !important; cursor: pointer; }
    #paymentsChart { filter: drop-shadow(0 4px 6px rgba(0,0,0,0.05)); }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const ctx = document.getElementById('paymentsChart').getContext('2d');
        
        const gradient = ctx.createLinearGradient(0, 0, 0, 300);
        gradient.addColorStop(0, 'rgba(59, 130, 246, 0.5)');
        gradient.addColorStop(1, 'rgba(59, 130, 246, 0)');

        new Chart(ctx, {
            type: 'line',
            data: {
                labels: {!! json_encode($chartLabels) !!},
                datasets: [{
                    label: 'Volume des Versements (XOF)',
                    data: {!! json_encode($chartData) !!},
                    borderColor: '#3b82f6',
                    borderWidth: 3,
                    backgroundColor: gradient,
                    fill: true,
                    tension: 0.4,
                    pointBackgroundColor: '#fff',
                    pointBorderColor: '#3b82f6',
                    pointBorderWidth: 2,
                    pointRadius: 4,
                    pointHoverRadius: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: '#1e293b',
                        titleFont: { family: 'Ubuntu', size: 12 },
                        bodyFont: { family: 'Ubuntu', size: 14, weight: 'bold' },
                        padding: 12,
                        cornerRadius: 8,
                        displayColors: false,
                        callbacks: {
                            label: function(context) {
                                return context.parsed.y.toLocaleString() + ' XOF';
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: { font: { family: 'Ubuntu', size: 11 }, color: '#64748b' }
                    },
                    y: {
                        beginAtZero: true,
                        grid: { borderDash: [5, 5], color: '#e2e8f0' },
                        ticks: {
                            font: { family: 'Ubuntu', size: 11 },
                            color: '#64748b',
                            callback: function(value) {
                                if (value >= 1000) return (value / 1000) + 'k';
                                return value;
                            }
                        }
                    }
                }
            }
        });
    });
</script>
@endpush
@endsection
