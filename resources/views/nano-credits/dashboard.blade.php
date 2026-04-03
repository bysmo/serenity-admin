@extends('layouts.app')

@section('title', 'Tableau de Bord Nano-Crédits')

@section('content')
<div class="page-header d-flex justify-content-between align-items-center">
    <h1><i class="bi bi-speedometer2"></i> Analyse & Risques : Nano-Crédits</h1>
</div>

<div class="row g-3 mb-4">
    <!-- Risque Global -->
    <div class="col-md-12 col-lg-4">
        <div class="card h-100 border-{{$couleurRisque}} shadow-sm" style="border-width: 2px;">
            <div class="card-header bg-{{$couleurRisque}} text-white">
                <i class="bi bi-shield-exclamation"></i> Score de Risque Global
            </div>
            <div class="card-body text-center d-flex flex-column justify-content-center">
                <h1 class="display-3 text-{{$couleurRisque}} mb-0" style="font-family: 'Ubuntu', sans-serif;">{{ $riskScore }}<small>%</small></h1>
                <p class="fs-5 mt-2 fw-medium mb-1">Niveau : {{ $niveauRisque }}</p>
                <small class="text-muted">Basé sur le total des encours en retard ({{ number_format($totalImpaye, 0, ',', ' ') }} FCFA) et le comportement de récidive des membres.</small>
            </div>
        </div>
    </div>

    <!-- KPIs Financières -->
    <div class="col-md-12 col-lg-8">
        <div class="row g-3">
            <div class="col-sm-6">
                <div class="card shadow-sm h-100">
                    <div class="card-body">
                        <h6 class="text-muted mb-2"><i class="bi bi-cash"></i> Total Accordé (Historique)</h6>
                        <h3 class="mb-0 text-primary">{{ number_format($totalAccorde ?? 0, 0, ',', ' ') }} FCFA</h3>
                        <small class="text-muted">{{ $nbTotalCredits }} nano-crédits émis au total.</small>
                    </div>
                </div>
            </div>
            <div class="col-sm-6">
                <div class="card shadow-sm h-100">
                    <div class="card-body">
                        <h6 class="text-muted mb-2"><i class="bi bi-cash-stack"></i> Total Remboursé</h6>
                        <h3 class="mb-0 text-success">{{ number_format($totalRembourse ?? 0, 0, ',', ' ') }} FCFA</h3>
                        <small class="text-muted">Versements effectivement perçus.</small>
                    </div>
                </div>
            </div>
            <div class="col-sm-6">
                <div class="card shadow-sm h-100">
                    <div class="card-body">
                        <h6 class="text-muted mb-2"><i class="bi bi-graph-up-arrow"></i> Pénalités / Intérêts Captés</h6>
                        <h3 class="mb-0 text-info">{{ number_format($totalPenalites ?? 0, 0, ',', ' ') }} FCFA</h3>
                        <small class="text-muted">Montant des retards comptabilisés.</small>
                    </div>
                </div>
            </div>
            <div class="col-sm-6">
                <div class="card shadow-sm h-100">
                    <div class="card-body">
                        <h6 class="text-muted mb-2"><i class="bi bi-exclamation-triangle"></i> En Souffrance (Impayé Brut)</h6>
                        <h3 class="mb-0 text-danger">{{ number_format($totalImpaye ?? 0, 0, ',', ' ') }} FCFA</h3>
                        <small class="text-muted">Représente le capital non recouvré sur {{ $nbCreditsEnRetard }} crédit(s) en défaut.</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card mb-4 shadow-sm">
    <div class="card-header">
        <i class="bi bi-pie-chart"></i> Répartition du portefeuille actuel
    </div>
    <div class="card-body">
        <div class="row align-items-center">
            <div class="col-md-3 text-center border-end">
                <h4 class="text-primary mb-1">{{ $nbCreditsEnCours }}</h4>
                <p class="text-muted small mb-0">Crédits Actifs (En cours)</p>
            </div>
            <div class="col-md-3 text-center border-end">
                <h4 class="text-success mb-1">{{ number_format($totalEnCours, 0, ',', ' ') }} <small>FCFA</small></h4>
                <p class="text-muted small mb-0">Capital restant à percevoir</p>
            </div>
            <div class="col-md-6">
                @php
                    $sains = $nbCreditsEnCours - $nbCreditsEnRetard;
                    $pctSains = $nbCreditsEnCours > 0 ? round(($sains / $nbCreditsEnCours) * 100) : 0;
                    $pctRetard = $nbCreditsEnCours > 0 ? round(($nbCreditsEnRetard / $nbCreditsEnCours) * 100) : 0;
                @endphp
                <p class="small mb-1 d-flex justify-content-between">
                    <span>Sains ({{ $sains }})</span>
                    <span class="text-success">{{ $pctSains }}%</span>
                </p>
                <div class="progress mb-3" style="height: 10px;">
                    <div class="progress-bar bg-success" role="progressbar" style="width: {{ $pctSains }}%"></div>
                </div>
                
                <p class="small mb-1 d-flex justify-content-between">
                    <span>En Retard ({{ $nbCreditsEnRetard }})</span>
                    <span class="text-danger">{{ $pctRetard }}%</span>
                </p>
                <div class="progress" style="height: 10px;">
                    <div class="progress-bar bg-danger" role="progressbar" style="width: {{ $pctRetard }}%"></div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
