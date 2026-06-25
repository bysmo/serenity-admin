@extends('layouts.app')

@section('title', 'Détail commission')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex align-items-center gap-3 mb-4">
        <a href="{{ route('parrainage.admin.commissions') }}" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i>Retour
        </a>
        <div>
            <h2 class="mb-0 fw-bold" style="color: var(--primary-dark-blue);">
                <i class="bi bi-receipt me-2"></i>Commission {{ $commission->reference }}
            </h2>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            {!! session('success') !!}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row g-4">
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom py-3">
                    <h6 class="mb-0 fw-semibold">Informations de la commission</h6>
                </div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-4 small text-muted">Référence</dt>
                        <dd class="col-sm-8 font-monospace">{{ $commission->reference }}</dd>

                        <dt class="col-sm-4 small text-muted">Statut</dt>
                        <dd class="col-sm-8">
                            <span class="badge bg-{{ $commission->badge_statut }} fs-6">{{ $commission->label_statut }}</span>
                        </dd>

                        <dt class="col-sm-4 small text-muted">Montant</dt>
                        <dd class="col-sm-8 fw-bold fs-5 text-success">{{ number_format($commission->montant, 0, ',', ' ') }} FCFA</dd>

                        <dt class="col-sm-4 small text-muted">Niveau parrainage</dt>
                        <dd class="col-sm-8"><span class="badge bg-secondary">Niveau {{ $commission->niveau }}</span></dd>

                        <dt class="col-sm-4 small text-muted">Déclencheur</dt>
                        <dd class="col-sm-8">{{ $commission->label_declencheur }}</dd>

                        <dt class="col-sm-4 small text-muted">Date création</dt>
                        <dd class="col-sm-8">{{ $commission->created_at->format('d/m/Y à H:i') }}</dd>

                        @if($commission->disponible_le)
                        <dt class="col-sm-4 small text-muted">Disponible le</dt>
                        <dd class="col-sm-8">{{ $commission->disponible_le->format('d/m/Y à H:i') }}</dd>
                        @endif

                        @if($commission->reclame_le)
                        <dt class="col-sm-4 small text-muted">Réclamée le</dt>
                        <dd class="col-sm-8">{{ $commission->reclame_le->format('d/m/Y à H:i') }}</dd>
                        @endif

                        @if($commission->paye_le)
                        <dt class="col-sm-4 small text-muted">Payée le</dt>
                        <dd class="col-sm-8 text-success fw-semibold">{{ $commission->paye_le->format('d/m/Y à H:i') }}</dd>
                        @endif

                        @if($commission->traitePar)
                        <dt class="col-sm-4 small text-muted">Traité par</dt>
                        <dd class="col-sm-8">{{ $commission->traitePar->name }}</dd>
                        @endif

                        @if($commission->note_admin)
                        <dt class="col-sm-4 small text-muted">Note admin</dt>
                        <dd class="col-sm-8 fst-italic text-muted">{{ $commission->note_admin }}</dd>
                        @endif
                    </dl>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <!-- Parrain -->
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-header bg-white border-bottom py-3">
                    <h6 class="mb-0 fw-semibold"><i class="bi bi-person-fill me-2 text-primary"></i>Parrain</h6>
                </div>
                <div class="card-body">
                    @if($commission->parrain)
                        <div class="d-flex align-items-center gap-3">
                            <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center"
                                 style="width:48px;height:48px">
                                {{ strtoupper(substr($commission->parrain->prenom, 0, 1)) }}{{ strtoupper(substr($commission->parrain->nom, 0, 1)) }}
                            </div>
                            <div>
                                <div class="fw-bold">{{ $commission->parrain->nom_complet }}</div>
                                <div class="text-muted small">{{ $commission->parrain->numero }}</div>
                                <div class="text-muted small">{{ $commission->parrain->email }}</div>
                                @if($commission->parrain->code_parrainage)
                                    <span class="badge bg-light text-dark font-monospace mt-1">
                                        CODE : {{ $commission->parrain->code_parrainage }}
                                    </span>
                                @endif
                            </div>
                        </div>
                    @else
                        <p class="text-muted mb-0">Parrain supprimé</p>
                    @endif
                </div>
            </div>

            <!-- Filleul -->
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-header bg-white border-bottom py-3">
                    <h6 class="mb-0 fw-semibold"><i class="bi bi-person-plus me-2 text-success"></i>Filleul</h6>
                </div>
                <div class="card-body">
                    @if($commission->filleul)
                        <div class="d-flex align-items-center gap-3">
                            <div class="rounded-circle bg-success text-white d-flex align-items-center justify-content-center"
                                 style="width:48px;height:48px">
                                {{ strtoupper(substr($commission->filleul->prenom, 0, 1)) }}{{ strtoupper(substr($commission->filleul->nom, 0, 1)) }}
                            </div>
                            <div>
                                <div class="fw-bold">{{ $commission->filleul->nom_complet }}</div>
                                <div class="text-muted small">{{ $commission->filleul->numero }}</div>
                                <div class="text-muted small">{{ $commission->filleul->email }}</div>
                                <span class="badge {{ $commission->filleul->statut === 'actif' ? 'bg-success' : 'bg-secondary' }} mt-1">
                                    {{ ucfirst($commission->filleul->statut) }}
                                </span>
                            </div>
                        </div>
                    @else
                        <p class="text-muted mb-0">Filleul supprimé</p>
                    @endif
                </div>
            </div>

            <!-- Actions -->
            @if(in_array($commission->statut, ['reclame', 'disponible']))
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom py-3">
                    <h6 class="mb-0 fw-semibold"><i class="bi bi-lightning me-2 text-warning"></i>Actions</h6>
                </div>
                <div class="card-body d-grid gap-2">
                    <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modalApprouver">
                        <i class="bi bi-check-circle me-2"></i>Approuver et payer
                    </button>
                    <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#modalRejeter">
                        <i class="bi bi-x-circle me-2"></i>Rejeter / Annuler
                    </button>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>

<!-- Modals -->
@if(in_array($commission->statut, ['reclame', 'disponible']))
<div class="modal fade" id="modalApprouver" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('parrainage.admin.commissions.approuver', $commission) }}">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h6 class="modal-title fw-bold">Confirmer le paiement</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Montant à verser : <strong class="text-success">{{ number_format($commission->montant, 0, ',', ' ') }} FCFA</strong></p>
                    <div class="mb-3">
                        <label class="form-label small">Note (optionnelle)</label>
                        <textarea name="note_admin" rows="2" class="form-control form-control-sm"
                                  placeholder="Ex: Virement Mobile Money effectué..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-sm btn-success">Confirmer le paiement</button>
                </div>
            </div>
        </form>
    </div>
</div>
<div class="modal fade" id="modalRejeter" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('parrainage.admin.commissions.rejeter', $commission) }}">
            @csrf
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h6 class="modal-title fw-bold">Rejeter la commission</h6>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Motif du rejet <span class="text-danger">*</span></label>
                        <textarea name="note_admin" rows="3" class="form-control" required
                                  placeholder="Raison du rejet..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-sm btn-danger">Confirmer le rejet</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endif
@endsection
