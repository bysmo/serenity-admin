@extends('layouts.membre')

@section('title', 'Mes commissions de parrainage')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex align-items-center gap-3 mb-4">
        <a href="{{ route('membre.parrainage.index') }}" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i>Retour
        </a>
        <div>
            <h2 class="mb-1 fw-bold" style="color: var(--primary-dark-blue);">
                <i class="bi bi-cash-coin me-2 text-primary"></i>Mes commissions
            </h2>
            <p class="text-muted mb-0">Historique de toutes vos commissions de parrainage</p>
        </div>
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

    <!-- Résumé financier -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm text-center py-3">
                <div class="text-warning fw-bold fs-5">{{ number_format($stats['total_en_attente'], 0, ',', ' ') }}</div>
                <div class="small text-muted">FCFA en attente</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm text-center py-3">
                <div class="text-success fw-bold fs-5">{{ number_format($stats['total_disponible'], 0, ',', ' ') }}</div>
                <div class="small text-muted">FCFA disponibles</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm text-center py-3">
                <div class="text-info fw-bold fs-5">{{ number_format($stats['total_reclame'], 0, ',', ' ') }}</div>
                <div class="small text-muted">FCFA réclamés</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm text-center py-3">
                <div class="text-primary fw-bold fs-5">{{ number_format($stats['total_paye'], 0, ',', ' ') }}</div>
                <div class="small text-muted">FCFA reçus</div>
            </div>
        </div>
    </div>

    <!-- Bouton réclamer -->
    @if($stats['total_disponible'] > 0)
        <div class="alert alert-success d-flex align-items-center justify-content-between mb-4">
            <div>
                <i class="bi bi-check-circle me-2"></i>
                <strong>{{ number_format($stats['total_disponible'], 0, ',', ' ') }} FCFA</strong> sont disponibles à la réclamation.
            </div>
            <form method="POST" action="{{ route('membre.parrainage.reclamer') }}"
                  onsubmit="return confirm('Confirmer la réclamation ?')">
                @csrf
                <button type="submit" class="btn btn-success btn-sm">
                    <i class="bi bi-wallet2 me-1"></i>Réclamer maintenant
                </button>
            </form>
        </div>
    @endif

    <!-- Filtre par statut -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body py-2">
            <form method="GET" class="d-flex gap-2 align-items-center flex-wrap">
                <label class="form-label mb-0 small fw-semibold">Filtrer :</label>
                @foreach([''=>'Toutes', 'en_attente'=>'En attente', 'disponible'=>'Disponible', 'reclame'=>'Réclamé', 'paye'=>'Payé', 'annule'=>'Annulé'] as $val => $label)
                    <a href="{{ route('membre.parrainage.commissions', $val ? ['statut' => $val] : []) }}"
                       class="btn btn-sm {{ request('statut', '') === $val ? 'btn-primary' : 'btn-outline-secondary' }}">
                        {{ $label }}
                    </a>
                @endforeach
            </form>
        </div>
    </div>

    <!-- Tableau -->
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0 align-middle">
                    <thead style="background:#f8f9fa">
                        <tr>
                            <th class="px-3 py-3 fw-semibold small">Filleul</th>
                            <th class="fw-semibold small text-center">Niveau</th>
                            <th class="fw-semibold small">Déclencheur</th>
                            <th class="fw-semibold small text-end">Montant</th>
                            <th class="fw-semibold small text-center">Statut</th>
                            <th class="fw-semibold small">Date</th>
                            <th class="fw-semibold small">Disponible le</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($commissions as $commission)
                            <tr>
                                <td class="px-3">
                                    <div class="small fw-semibold">{{ $commission->filleul?->nom_complet ?? '—' }}</div>
                                    <div class="text-muted" style="font-size:0.75rem">{{ $commission->filleul?->numero }}</div>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-secondary rounded-pill">N{{ $commission->niveau }}</span>
                                </td>
                                <td class="small">{{ $commission->label_declencheur }}</td>
                                <td class="text-end fw-bold">{{ number_format($commission->montant, 0, ',', ' ') }} FCFA</td>
                                <td class="text-center">
                                    <span class="badge bg-{{ $commission->badge_statut }}">{{ $commission->label_statut }}</span>
                                </td>
                                <td class="small text-muted">{{ $commission->created_at->format('d/m/Y') }}</td>
                                <td class="small text-muted">
                                    @if($commission->disponible_le)
                                        {{ $commission->disponible_le->format('d/m/Y') }}
                                        @if($commission->disponible_le->isFuture())
                                            <span class="text-warning">({{ $commission->disponible_le->diffForHumans() }})</span>
                                        @endif
                                    @else
                                        <span class="text-success">Immédiat</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-5 text-muted">
                                    <i class="bi bi-inbox fs-2 d-block mb-2"></i>
                                    Aucune commission trouvée
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($commissions->hasPages())
                <div class="px-3 py-2">{{ $commissions->links() }}</div>
            @endif
        </div>
    </div>
</div>
@endsection
