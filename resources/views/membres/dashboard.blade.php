@extends('layouts.app')

@section('title', 'Tableau de Bord Membres')

@section('content')
<div class="page-header d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0"><i class="bi bi-speedometer2 me-2" style="color: var(--primary-blue);"></i>Tableau de Bord des Membres</h1>
    <div class="d-flex gap-2">
        <form action="{{ route('membres.dashboard') }}" method="GET" class="d-flex gap-2 align-items-center bg-white p-2 rounded shadow-sm border">
            <label class="small fw-bold text-muted mb-0">Période :</label>
            <input type="date" name="date_debut" class="form-control form-control-sm border-0 bg-light" value="{{ $dateDebut->format('Y-m-d') }}">
            <span class="text-muted small">au</span>
            <input type="date" name="date_fin" class="form-control form-control-sm border-0 bg-light" value="{{ $dateFin->format('Y-m-d') }}">
            <button type="submit" class="btn btn-primary btn-sm">
                <i class="bi bi-filter"></i>
            </button>
        </form>
    </div>
</div>

<!-- KPI Cards -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100 card-stat" style="border-left: 4px solid var(--primary-blue) !important;">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h6 class="text-muted mb-0 small text-uppercase fw-bold">Total Membres</h6>
                    <i class="bi bi-people fs-4 text-primary opacity-50"></i>
                </div>
                <h3 class="fw-bold mb-0">{{ number_format($totalMembres, 0, ',', ' ') }}</h3>
                <p class="text-success small mb-0 mt-2">
                    <i class="bi bi-plus-circle me-1"></i> {{ $nouveauxMembres }} nouveaux
                </p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100 card-stat" style="border-left: 4px solid #198754 !important;">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h6 class="text-muted mb-0 small text-uppercase fw-bold">Membres Actifs</h6>
                    <i class="bi bi-person-check fs-4 text-success opacity-50"></i>
                </div>
                <h3 class="fw-bold mb-0">{{ number_format($membresActifs, 0, ',', ' ') }}</h3>
                <p class="text-muted small mb-0 mt-2">
                    {{ $totalMembres > 0 ? round(($membresActifs / $totalMembres) * 100, 1) : 0 }}% du total
                </p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100 card-stat" style="border-left: 4px solid #ffc107 !important;">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h6 class="text-muted mb-0 small text-uppercase fw-bold">KYC Validés</h6>
                    <i class="bi bi-shield-check fs-4 text-warning opacity-50"></i>
                </div>
                <h3 class="fw-bold mb-0">{{ number_format($kycStats['valide'], 0, ',', ' ') }}</h3>
                <p class="text-muted small mb-0 mt-2">
                    {{ $kycStats['en_attente'] }} en attente
                </p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100 card-stat" style="border-left: 4px solid #dc3545 !important;">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h6 class="text-muted mb-0 small text-uppercase fw-bold">KYC Manquants</h6>
                    <i class="bi bi-shield-slash fs-4 text-danger opacity-50"></i>
                </div>
                <h3 class="fw-bold mb-0 text-danger">{{ number_format($kycStats['manquant'], 0, ',', ' ') }}</h3>
                <p class="text-danger small mb-0 mt-2">
                    <i class="bi bi-exclamation-triangle me-1"></i> Dossiers à initier
                </p>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <!-- Évolution des adhésions -->
    <div class="col-md-8">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white py-3 border-0">
                <h5 class="card-title mb-0" style="font-size: 1rem; color: var(--primary-dark-blue); font-family: 'Ubuntu', sans-serif;">Évolution des Adhésions</h5>
            </div>
            <div class="card-body">
                <canvas id="growthChart" height="300"></canvas>
            </div>
        </div>
    </div>
    
    <!-- Répartition par Sexe -->
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white py-3 border-0">
                <h5 class="card-title mb-0" style="font-size: 1rem; color: var(--primary-dark-blue); font-family: 'Ubuntu', sans-serif;">Répartition par Genre</h5>
            </div>
            <div class="card-body d-flex flex-column align-items-center justify-content-center">
                <div style="width: 200px; height: 200px;">
                    <canvas id="sexeChart"></canvas>
                </div>
                <div class="mt-4 w-100">
                    @foreach($sexeStats as $sexe => $count)
                        <div class="d-flex justify-content-between small mb-1">
                            <span><i class="bi bi-circle-fill me-2" style="color: {{ $sexe === 'M' ? '#4a6cf7' : ($sexe === 'F' ? '#e83e8c' : '#adb5bd') }}; font-size: 0.5rem;"></i> {{ $sexe === 'M' ? 'Masculin' : ($sexe === 'F' ? 'Féminin' : 'Non précisé') }}</span>
                            <span class="fw-bold">{{ $count }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <!-- Distribution par Segment -->
    <div class="col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white py-3 border-0">
                <h5 class="card-title mb-0" style="font-size: 1rem; color: var(--primary-dark-blue); font-family: 'Ubuntu', sans-serif;">Répartition par Segment</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <canvas id="segmentChart"></canvas>
                    </div>
                    <div class="col-md-6">
                        <div class="list-group list-group-flush small">
                            @foreach($segments as $segment)
                                <div class="list-group-item d-flex justify-content-between align-items-center border-0 px-0 py-2">
                                    <div class="d-flex align-items-center">
                                        <div class="rounded-circle me-2" style="width: 10px; height: 10px; background-color: {{ $segment->couleur ?: '#adb5bd' }};"></div>
                                        <span>{{ $segment->nom }}</span>
                                    </div>
                                    <span class="fw-bold">{{ $segment->membres_count }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- État KYC -->
    <div class="col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white py-3 border-0">
                <h5 class="card-title mb-0" style="font-size: 1rem; color: var(--primary-dark-blue); font-family: 'Ubuntu', sans-serif;">État de Conformité (KYC)</h5>
            </div>
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <canvas id="kycChart"></canvas>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <div class="d-flex justify-content-between small mb-1">
                                <span>Validés</span>
                                <span class="fw-bold text-success">{{ $kycStats['valide'] }}</span>
                            </div>
                            <div class="progress" style="height: 6px;">
                                <div class="progress-bar bg-success" role="progressbar" style="width: {{ $totalMembres > 0 ? ($kycStats['valide'] / $totalMembres) * 100 : 0 }}%"></div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <div class="d-flex justify-content-between small mb-1">
                                <span>En attente</span>
                                <span class="fw-bold text-warning">{{ $kycStats['en_attente'] }}</span>
                            </div>
                            <div class="progress" style="height: 6px;">
                                <div class="progress-bar bg-warning" role="progressbar" style="width: {{ $totalMembres > 0 ? ($kycStats['en_attente'] / $totalMembres) * 100 : 0 }}%"></div>
                            </div>
                        </div>
                        <div class="mb-0">
                            <div class="d-flex justify-content-between small mb-1">
                                <span>Manquants</span>
                                <span class="fw-bold text-danger">{{ $kycStats['manquant'] }}</span>
                            </div>
                            <div class="progress" style="height: 6px;">
                                <div class="progress-bar bg-danger" role="progressbar" style="width: {{ $totalMembres > 0 ? ($kycStats['manquant'] / $totalMembres) * 100 : 0 }}%"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Growth Chart
    const growthCtx = document.getElementById('growthChart').getContext('2d');
    new Chart(growthCtx, {
        type: 'line',
        data: {
            labels: @json($evolution->keys()),
            datasets: [{
                label: 'Adhésions',
                data: @json($evolution->values()),
                borderColor: '#4a6cf7',
                backgroundColor: 'rgba(74, 108, 247, 0.1)',
                borderWidth: 3,
                fill: true,
                tension: 0.4,
                pointBackgroundColor: '#4a6cf7',
                pointRadius: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false }
            },
            scales: {
                y: { beginAtZero: true, grid: { borderDash: [5, 5] } },
                x: { grid: { display: false } }
            }
        }
    });

    // Sexe Chart
    const sexeCtx = document.getElementById('sexeChart').getContext('2d');
    new Chart(sexeCtx, {
        type: 'doughnut',
        data: {
            labels: @json($sexeStats->keys()),
            datasets: [{
                data: @json($sexeStats->values()),
                backgroundColor: ['#4a6cf7', '#e83e8c', '#adb5bd'],
                borderWidth: 0
            }]
        },
        options: {
            cutout: '70%',
            plugins: { legend: { display: false } }
        }
    });

    // Segment Chart
    const segmentCtx = document.getElementById('segmentChart').getContext('2d');
    new Chart(segmentCtx, {
        type: 'pie',
        data: {
            labels: @json($segments->pluck('nom')),
            datasets: [{
                data: @json($segments->pluck('membres_count')),
                backgroundColor: @json($segments->pluck('couleur')->map(fn($c) => $c ?: '#adb5bd')),
                borderWidth: 0
            }]
        },
        options: {
            plugins: { legend: { display: false } }
        }
    });

    // KYC Chart
    const kycCtx = document.getElementById('kycChart').getContext('2d');
    new Chart(kycCtx, {
        type: 'doughnut',
        data: {
            labels: ['Validés', 'En attente', 'Rejetés', 'Manquants'],
            datasets: [{
                data: [
                    {{ $kycStats['valide'] }}, 
                    {{ $kycStats['en_attente'] }}, 
                    {{ $kycStats['rejete'] }}, 
                    {{ $kycStats['manquant'] }}
                ],
                backgroundColor: ['#198754', '#ffc107', '#dc3545', '#6c757d'],
                borderWidth: 0
            }]
        },
        options: {
            cutout: '70%',
            plugins: { legend: { display: false } }
        }
    });
});
</script>
@endpush

<style>
.card-stat {
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}
.card-stat:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 20px rgba(0,0,0,0.05) !important;
}
.uppercase {
    letter-spacing: 0.05rem;
}
</style>
@endsection
