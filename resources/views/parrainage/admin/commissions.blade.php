@extends('layouts.app')

@section('title', 'Commissions de parrainage')

@section('content')
<div class="container-fluid py-4">

    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h2 class="mb-1 fw-bold" style="color: var(--primary-dark-blue);">
                <i class="bi bi-cash-coin me-2 text-primary"></i>Commissions de Parrainage
            </h2>
            <p class="text-muted mb-0">Gérez les commissions et réclamations des parrains</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('parrainage.admin.config') }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-gear me-1"></i>Configuration
            </a>
            <a href="{{ route('parrainage.admin.parrains') }}" class="btn btn-outline-primary btn-sm">
                <i class="bi bi-person-lines-fill me-1"></i>Parrains
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- Stats rapides -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-xl-3">
            <div class="card border-0 shadow-sm text-center py-3">
                <div class="text-warning fw-bold fs-4">{{ number_format($stats['total_en_attente'], 0, ',', ' ') }}</div>
                <div class="small text-muted">FCFA en attente</div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="card border-0 shadow-sm text-center py-3">
                <div class="text-success fw-bold fs-4">{{ number_format($stats['total_disponible'], 0, ',', ' ') }}</div>
                <div class="small text-muted">FCFA disponibles</div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="card border-0 shadow-sm text-center py-3">
                <div class="text-info fw-bold fs-4">{{ number_format($stats['total_reclame'], 0, ',', ' ') }}</div>
                <div class="small text-muted">FCFA réclamés ({{ $stats['nb_reclames'] }})</div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="card border-0 shadow-sm text-center py-3">
                <div class="text-primary fw-bold fs-4">{{ number_format($stats['total_paye'], 0, ',', ' ') }}</div>
                <div class="small text-muted">FCFA payés</div>
            </div>
        </div>
    </div>

    <!-- Action groupée sur les réclamations -->
    @if($stats['nb_reclames'] > 0)
        <div class="alert alert-warning d-flex align-items-center justify-content-between mb-4">
            <div>
                <i class="bi bi-exclamation-triangle me-2"></i>
                <strong>{{ $stats['nb_reclames'] }} réclamation(s)</strong> en attente de traitement —
                {{ number_format($stats['total_reclame'], 0, ',', ' ') }} FCFA à verser.
            </div>
            <form method="POST" action="{{ route('parrainage.admin.commissions.payer-tout') }}"
                  onsubmit="return confirm('Approuver et payer toutes les réclamations en attente ?')">
                @csrf
                <button type="submit" class="btn btn-success btn-sm">
                    <i class="bi bi-check-all me-1"></i>Tout approuver & payer
                </button>
            </form>
        </div>
    @endif

    <!-- Filtres -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body py-2">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label small">Statut</label>
                    <select name="statut" class="form-select form-select-sm">
                        <option value="">Tous les statuts</option>
                        <option value="en_attente" {{ request('statut') === 'en_attente' ? 'selected' : '' }}>En attente</option>
                        <option value="disponible" {{ request('statut') === 'disponible' ? 'selected' : '' }}>Disponible</option>
                        <option value="reclame" {{ request('statut') === 'reclame' ? 'selected' : '' }}>Réclamé</option>
                        <option value="paye" {{ request('statut') === 'paye' ? 'selected' : '' }}>Payé</option>
                        <option value="annule" {{ request('statut') === 'annule' ? 'selected' : '' }}>Annulé</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small">Rechercher parrain</label>
                    <input type="text" name="search" class="form-control form-control-sm"
                           placeholder="Nom, prénom, numéro..." value="{{ request('search') }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label small">Date début</label>
                    <input type="date" name="date_debut" class="form-control form-control-sm"
                           value="{{ request('date_debut') }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label small">Date fin</label>
                    <input type="date" name="date_fin" class="form-control form-control-sm"
                           value="{{ request('date_fin') }}">
                </div>
                <div class="col-md-2 d-flex gap-1">
                    <button type="submit" class="btn btn-primary btn-sm flex-fill">
                        <i class="bi bi-search"></i>
                    </button>
                    <a href="{{ route('parrainage.admin.commissions') }}" class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-x"></i>
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Tableau des commissions -->
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0 align-middle">
                    <thead style="background:#f8f9fa">
                        <tr>
                            <th class="px-3 py-3 fw-semibold small">Référence</th>
                            <th class="fw-semibold small">Parrain</th>
                            <th class="fw-semibold small">Filleul</th>
                            <th class="fw-semibold small text-center">Niv.</th>
                            <th class="fw-semibold small">Déclencheur</th>
                            <th class="fw-semibold small text-end">Montant</th>
                            <th class="fw-semibold small text-center">Statut</th>
                            <th class="fw-semibold small">Date</th>
                            <th class="fw-semibold small text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($commissions as $commission)
                            <tr>
                                <td class="px-3">
                                    <span class="font-monospace small text-muted">{{ $commission->reference }}</span>
                                </td>
                                <td>
                                    @if($commission->parrain)
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center flex-shrink-0"
                                                 style="width:32px;height:32px;font-size:0.75rem">
                                                {{ strtoupper(substr($commission->parrain->prenom, 0, 1)) }}{{ strtoupper(substr($commission->parrain->nom, 0, 1)) }}
                                            </div>
                                            <div>
                                                <div class="small fw-semibold">{{ $commission->parrain->nom_complet }}</div>
                                                <div class="small text-muted">{{ $commission->parrain->numero }}</div>
                                            </div>
                                        </div>
                                    @else
                                        <span class="text-muted small">—</span>
                                    @endif
                                </td>
                                <td>
                                    @if($commission->filleul)
                                        <div class="small">
                                            <div class="fw-semibold">{{ $commission->filleul->nom_complet }}</div>
                                            <div class="text-muted">{{ $commission->filleul->numero }}</div>
                                        </div>
                                    @else
                                        <span class="text-muted small">—</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <span class="badge rounded-pill bg-secondary">N{{ $commission->niveau }}</span>
                                </td>
                                <td><span class="small">{{ $commission->label_declencheur }}</span></td>
                                <td class="text-end fw-bold">{{ number_format($commission->montant, 0, ',', ' ') }} FCFA</td>
                                <td class="text-center">
                                    <span class="badge bg-{{ $commission->badge_statut }}">{{ $commission->label_statut }}</span>
                                </td>
                                <td class="small text-muted">{{ $commission->created_at->format('d/m/Y') }}</td>
                                <td class="text-center">
                                    <div class="d-flex gap-1 justify-content-center">
                                        <a href="{{ route('parrainage.admin.commissions.show', $commission) }}"
                                           class="btn btn-sm btn-outline-secondary" title="Détail">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        @if(in_array($commission->statut, ['reclame', 'disponible']))
                                            <button type="button" class="btn btn-sm btn-success"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#modalApprouver{{ $commission->id }}"
                                                    title="Approuver">
                                                <i class="bi bi-check"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-danger"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#modalRejeter{{ $commission->id }}"
                                                    title="Rejeter">
                                                <i class="bi bi-x"></i>
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>

                            <!-- Modal Approuver -->
                            @if(in_array($commission->statut, ['reclame', 'disponible']))
                            <div class="modal fade" id="modalApprouver{{ $commission->id }}" tabindex="-1">
                                <div class="modal-dialog">
                                    <form method="POST" action="{{ route('parrainage.admin.commissions.approuver', $commission) }}">
                                        @csrf
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h6 class="modal-title fw-bold">
                                                    <i class="bi bi-check-circle me-2 text-success"></i>Approuver la commission
                                                </h6>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                <p class="mb-2">Vous allez marquer comme <strong>payée</strong> la commission :</p>
                                                <ul class="small mb-3">
                                                    <li>Réf : <strong>{{ $commission->reference }}</strong></li>
                                                    <li>Parrain : <strong>{{ $commission->parrain?->nom_complet }}</strong></li>
                                                    <li>Montant : <strong class="text-success">{{ number_format($commission->montant, 0, ',', ' ') }} FCFA</strong></li>
                                                </ul>
                                                <div class="mb-3">
                                                    <label class="form-label small">Note (optionnelle)</label>
                                                    <textarea name="note_admin" rows="2" class="form-control form-control-sm"
                                                              placeholder="Ex: Virement effectué..."></textarea>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
                                                <button type="submit" class="btn btn-sm btn-success">
                                                    <i class="bi bi-check me-1"></i>Confirmer le paiement
                                                </button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>

                            <!-- Modal Rejeter -->
                            <div class="modal fade" id="modalRejeter{{ $commission->id }}" tabindex="-1">
                                <div class="modal-dialog">
                                    <form method="POST" action="{{ route('parrainage.admin.commissions.rejeter', $commission) }}">
                                        @csrf
                                        <div class="modal-content">
                                            <div class="modal-header bg-danger text-white">
                                                <h6 class="modal-title fw-bold">
                                                    <i class="bi bi-x-circle me-2"></i>Rejeter la commission
                                                </h6>
                                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                <p class="mb-2 text-danger">Vous allez <strong>annuler</strong> définitivement :</p>
                                                <ul class="small mb-3">
                                                    <li>Réf : <strong>{{ $commission->reference }}</strong></li>
                                                    <li>Parrain : <strong>{{ $commission->parrain?->nom_complet }}</strong></li>
                                                    <li>Montant : <strong>{{ number_format($commission->montant, 0, ',', ' ') }} FCFA</strong></li>
                                                </ul>
                                                <div class="mb-3">
                                                    <label class="form-label small fw-semibold">Motif du rejet <span class="text-danger">*</span></label>
                                                    <textarea name="note_admin" rows="2" class="form-control form-control-sm" required
                                                              placeholder="Raison du rejet..."></textarea>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
                                                <button type="submit" class="btn btn-sm btn-danger">
                                                    <i class="bi bi-x me-1"></i>Confirmer le rejet
                                                </button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                            @endif
                        @empty
                            <tr>
                                <td colspan="9" class="text-center py-5 text-muted">
                                    <i class="bi bi-inbox fs-2 d-block mb-2"></i>
                                    Aucune commission trouvée
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($commissions->hasPages())
                <div class="px-3 py-2">
                    {{ $commissions->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
