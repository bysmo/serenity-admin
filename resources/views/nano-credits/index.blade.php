@extends('layouts.app')

@section('title', 'Gestion & Supervision des Nano-crédits')

@section('content')
<div class="page-header d-flex justify-content-between align-items-center flex-wrap">
    <h1><i class="bi bi-phone"></i> Supervision des Nano-crédits</h1>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show shadow-sm border-0">{{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif
@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show shadow-sm border-0">{{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

<div class="card shadow-sm border-0">
    <div class="card-header bg-white py-3">
        <div class="d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0 text-primary"><i class="bi bi-list-stars me-2"></i>Catalogue du Portefeuille</h5>
            <span class="badge bg-light text-dark border fw-normal">{{ $nanoCredits->total() }} crédit(s) au total</span>
        </div>
    </div>
    <div class="card-body">
        <form method="GET" action="{{ route('nano-credits.index') }}" class="mb-4">
            <div class="row g-3">
                <div class="col-md-5">
                    <label class="form-label small fw-bold">Recherche rapide</label>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-white border-end-0"><i class="bi bi-search"></i></span>
                        <input type="text" name="search" class="form-control border-start-0" placeholder="Client, téléphone, transaction..." value="{{ request('search') }}">
                    </div>
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-bold">Filtrer par état</label>
                    <select name="statut" class="form-select form-select-sm">
                        <option value="">Tous les statuts</option>
                        @foreach(\App\Models\NanoCredit::statutLabels() as $value => $label)
                            <option value="{{ $value }}" {{ request('statut') === $value ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary btn-sm w-100 fw-bold"><i class="bi bi-funnel"></i> Appliquer les filtres</button>
                    @if(request()->has('search') || request()->has('statut'))
                        <a href="{{ route('nano-credits.index') }}" class="btn btn-outline-secondary btn-sm ms-2"><i class="bi bi-x-circle"></i></a>
                    @endif
                </div>
            </div>
        </form>

        @if($nanoCredits->count() > 0)
            <div class="table-responsive">
                <table class="table table-hover align-middle border-top-0" style="font-size: 0.85rem;">
                    <thead class="bg-light">
                        <tr>
                            <th class="border-0">Date Demande</th>
                            <th class="border-0">Bénéficiaire</th>
                            <th class="border-0">Palier / Taux</th>
                            <th class="border-0 text-end">Volume & Intérêts</th>
                            <th class="border-0">Date Début</th>
                            <th class="border-0 text-danger">Date Échéance</th>
                            <th class="border-0 text-center">Durée</th>
                            <th class="border-0 text-center">Score Risque</th>
                            <th class="border-0">Identifiant/Tel</th>
                            <th class="border-0">État Actuel</th>
                            <th class="border-0"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($nanoCredits as $nc)
                            @php
                                $amort = null;
                                if ($nc->palier) {
                                    $amort = $nc->palier->calculAmortissement((float)$nc->getRawOriginal('montant'));
                                }
                                $duree = '—';
                                if ($nc->date_octroi && $nc->date_fin_remboursement) {
                                    $diff = $nc->date_octroi->diffInDays($nc->date_fin_remboursement);
                                    $duree = $diff . ' jours';
                                }
                            @endphp
                            <tr>
                                <td class="text-muted" style="font-size: 0.75rem;">
                                    <i class="bi bi-calendar-event me-1"></i> {{ $nc->created_at->format('d/m/Y') }}
                                    <div class="x-small">{{ $nc->created_at->format('H:i') }}</div>
                                </td>
                                <td>
                                    <div class="fw-bold">{{ $nc->membre->nom_complet ?? '—' }}</div>
                                    <div class="text-muted small">Membre #{{ $nc->membre_id }}</div>
                                    @if($nc->beneficiaire_effectif_id)
                                        <div class="mt-1 text-warning small fw-bold">
                                            <i class="bi bi-person-heart"></i> Pour : {{ $nc->beneficiaireEffectif->nom_complet }}
                                        </div>
                                    @endif
                                </td>
                                <td>
                                    @if($nc->palier)
                                        <div class="badge bg-info-soft text-info border-info px-2 py-1 mb-1">P{{ $nc->palier->numero }} : {{ $nc->palier->nom }}</div>
                                        <div class="text-muted x-small"><i class="bi bi-percent me-1"></i>Taux : {{ $nc->palier->taux_interet }}%</div>
                                    @else
                                        —
                                    @endif
                                </td>
                                <td class="text-end">
                                    <div class="fw-bold text-primary">{{ number_format($nc->montant, 0, ',', ' ') }} XOF</div>
                                    @if($amort)
                                        <div class="text-success x-small">+{{ number_format($amort['interet_total'], 0, ',', ' ') }} XOF (Int.)</div>
                                    @endif
                                </td>
                                <td class="small">
                                    @if($nc->date_octroi)
                                        <i class="bi bi-calendar-check me-1"></i> {{ $nc->date_octroi->format('d/m/Y') }}
                                    @else
                                        —
                                    @endif
                                </td>
                                <td class="small text-danger fw-bold">
                                    @if($nc->date_fin_remboursement)
                                        <i class="bi bi-calendar-x me-1"></i> {{ $nc->date_fin_remboursement->format('d/m/Y') }}
                                    @else
                                        —
                                    @endif
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-light text-dark border">{{ $duree }}</span>
                                </td>
                                <td class="text-center">
                                    <div class="badge {{ $nc->score_global <= 1 ? 'bg-success-soft text-success border-success' : ($nc->score_global <= 3 ? 'bg-warning-soft text-warning border-warning' : 'bg-danger-soft text-danger border-danger') }} px-2 py-1" style="border: 1px solid;">
                                        {{ $nc->score_global ?? '?' }} / 6
                                    </div>
                                </td>
                                <td>
                                    <div class="small fw-bold">{{ $nc->telephone }}</div>
                                    @if($nc->transaction_id)
                                        <div class="x-small text-muted text-truncate" style="max-width: 100px;">TX: {{ $nc->transaction_id }}</div>
                                    @endif
                                </td>
                                <td>
                                    @php
                                        $badgeClass = match($nc->statut) {
                                            'demande_en_attente', 'en_etude' => 'bg-warning text-dark',
                                            'debourse', 'en_remboursement', 'success' => 'bg-success',
                                            'rembourse' => 'bg-info',
                                            'refuse', 'failed' => 'bg-danger',
                                            default => 'bg-secondary'
                                        };
                                    @endphp
                                    <span class="badge {{ $badgeClass }} px-2 py-1">
                                        {{ $nc->statut_label }}
                                    </span>
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('nano-credits.show', $nc) }}" class="btn btn-primary btn-sm rounded-pill px-3 shadow-sm">
                                        <i class="bi bi-arrow-right-circle me-1"></i> Gérer
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="d-flex justify-content-center mt-4">{{ $nanoCredits->links() }}</div>
        @else
            <div class="text-center py-5">
                <i class="bi bi-clipboard2-x display-4 text-muted mb-3 d-block"></i>
                <h5 class="text-muted">Aucun nano-crédit trouvé</h5>
                <p class="text-muted small">Modifiez vos filtres ou effectuez une nouvelle recherche.</p>
            </div>
        @endif
    </div>
</div>

<style>
    .bg-success-soft { background-color: rgba(25, 135, 84, 0.1); }
    .bg-warning-soft { background-color: rgba(255, 193, 7, 0.1); }
    .bg-danger-soft { background-color: rgba(220, 53, 69, 0.1); }
    .bg-info-soft { background-color: rgba(13, 202, 240, 0.1); }
    .x-small { font-size: 0.7rem; }
</style>
@endsection
