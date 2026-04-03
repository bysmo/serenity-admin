@extends('layouts.membre')

@section('title', 'Gérer la cagnotte')

@section('content')
<style>
.card-header-compact { padding: 0.35rem 0.6rem !important; font-size: 0.75rem !important; font-weight: 300 !important; font-family: 'Ubuntu', sans-serif !important; }
.table-compact-membre thead th { padding: 0.2rem 0.4rem !important; font-size: 0.7rem !important; font-weight: 300 !important; font-family: 'Ubuntu', sans-serif !important; }
.table-compact-membre tbody td { padding: 0.2rem 0.4rem !important; font-size: 0.7rem !important; font-weight: 300 !important; font-family: 'Ubuntu', sans-serif !important; }
#showMesCotisationTabs .nav-link { font-size: 0.8rem !important; padding: 0.4rem 0.75rem !important; font-weight: 300 !important; font-family: 'Ubuntu', sans-serif !important; }
#showMesCotisationTabs .nav-link.active { background-color: var(--primary-dark-blue); color: #fff; border-color: var(--primary-dark-blue); }
</style>
<div class="page-header d-flex justify-content-between align-items-center flex-wrap gap-2" style="font-family: 'Ubuntu', sans-serif; font-weight: 300;">
    <h1 style="font-weight: 300; font-family: 'Ubuntu', sans-serif;"><i class="bi bi-gear"></i> Gérer : {{ $cotisation->nom }}</h1>
    <a href="{{ route('membre.mes-cotisations') }}" class="btn btn-secondary btn-sm"><i class="bi bi-arrow-left"></i> Retour</a>
</div>

@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show">{{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

<ul class="nav nav-tabs mb-3" id="showMesCotisationTabs" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link active" id="vue-ensemble-tab" data-bs-toggle="tab" data-bs-target="#vue-ensemble" type="button" role="tab"><i class="bi bi-grid"></i> Vue d'ensemble</button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="demandes-tab" data-bs-toggle="tab" data-bs-target="#demandes" type="button" role="tab"><i class="bi bi-clock"></i> Demandes en attente @if($adhesionsEnAttente->count() > 0)<span class="badge bg-warning text-dark ms-1">{{ $adhesionsEnAttente->count() }}</span>@endif</button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="adherents-tab" data-bs-toggle="tab" data-bs-target="#adherents" type="button" role="tab"><i class="bi bi-people"></i> Adhérents ({{ $adhesionsAcceptees->count() }})</button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="paiements-tab" data-bs-toggle="tab" data-bs-target="#paiements" type="button" role="tab"><i class="bi bi-cash-stack"></i> Paiements</button>
    </li>
</ul>

<div class="tab-content" id="showMesCotisationTabContent">
    <!-- Onglet Vue d'ensemble -->
    <div class="tab-pane fade show active" id="vue-ensemble" role="tabpanel">
        <div class="card mb-3" style="font-family: 'Ubuntu', sans-serif; font-weight: 300;">
            <div class="card-header card-header-compact d-flex justify-content-between align-items-center">
                <span><i class="bi bi-info-circle"></i> Code de partage</span>
                <code class="fs-6">{{ $cotisation->code }}</code>
            </div>
            <div class="card-body py-2">
                <p class="mb-0 small text-muted">Les membres peuvent rechercher ce code pour demander à adhérer à votre cagnotte.</p>
            </div>
        </div>
        <div class="card mb-3" style="font-family: 'Ubuntu', sans-serif; font-weight: 300;">
            <div class="card-header card-header-compact"><i class="bi bi-person-badge"></i> Administrateur et actions</div>
            <div class="card-body py-2">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
                    <div class="d-flex align-items-center gap-2 flex-nowrap">
                        <span class="small mb-0">{{ $adminMembre->nom_complet ?? '-' }} ({{ $adminMembre->email ?? '-' }})</span>
                        @if($adhesionsAcceptees->count() > 1)
                            <form action="{{ route('membre.mes-cotisations.designer-admin', $cotisation) }}" method="POST" class="d-inline-flex align-items-center gap-2 mb-0 flex-nowrap">
                                @csrf
                                <span class="small text-muted">— Désigner :</span>
                                <select name="admin_membre_id" class="form-select form-select-sm" style="width: auto; min-width: 160px; font-size: 0.8rem;">
                                    <option value="">Choisir un adhérent</option>
                                    @foreach($adhesionsAcceptees as $adhesion)
                                        @if($adhesion->membre->id !== $cotisation->getAdminMembreId())
                                            <option value="{{ $adhesion->membre->id }}">{{ $adhesion->membre->nom_complet }}</option>
                                        @endif
                                    @endforeach
                                </select>
                                <button type="submit" class="btn btn-sm btn-outline-primary flex-shrink-0">Désigner</button>
                            </form>
                        @else
                            <span class="small text-muted">Plusieurs adhérents requis pour désigner un admin.</span>
                        @endif
                    </div>
                    @if($cotisation->actif)
                        <div class="d-flex align-items-center gap-2 flex-shrink-0">
                            @if(!$demandeVersementEnCours && $soldeCaisse > 0)
                                <form id="formDemandeVersement" action="{{ route('membre.mes-cotisations.demande-versement', $cotisation) }}" method="POST" class="d-inline mb-0">
                                    @csrf
                                    <button type="button" class="btn btn-sm btn-outline-success" data-bs-toggle="modal" data-bs-target="#modalConfirmVersement"><i class="bi bi-cash-stack"></i> Demander le versement des fonds</button>
                                </form>
                            @elseif($demandeVersementEnCours)
                                <span class="btn btn-sm btn-secondary disabled"><i class="bi bi-hourglass-split"></i> Demande en cours</span>
                            @endif
                            <form id="formCloturer" action="{{ route('membre.mes-cotisations.cloturer', $cotisation) }}" method="POST" class="d-inline mb-0">
                                @csrf
                                <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#modalConfirmCloturer"><i class="bi bi-x-circle"></i> Clôturer la cagnotte</button>
                            </form>
                        </div>
                    @else
                        <span class="small text-muted">Cagnotte clôturée.</span>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Onglet Demandes en attente -->
    <div class="tab-pane fade" id="demandes" role="tabpanel">
        <div class="card mb-3" style="font-family: 'Ubuntu', sans-serif; font-weight: 300;">
            <div class="card-header card-header-compact d-flex justify-content-between align-items-center flex-wrap gap-2">
                <span><i class="bi bi-clock"></i> Demandes en attente</span>
                @if($adhesionsEnAttente->count() > 0)
                    <input type="text" class="form-control form-control-sm table-search-input" placeholder="Rechercher…" style="max-width: 180px; height: 28px; font-size: 0.75rem;" data-table-target="table-adhesions-attente">
                @endif
            </div>
            <div class="card-body">
                @if($adhesionsEnAttente->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-compact-membre table-sm table-hover mb-0" id="table-adhesions-attente">
                            <thead>
                                <tr>
                                    <th>Membre</th>
                                    <th>Email</th>
                                    <th>Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($adhesionsEnAttente as $adhesion)
                                    <tr>
                                        <td>{{ $adhesion->membre->nom_complet }}</td>
                                        <td>{{ $adhesion->membre->email }}</td>
                                        <td>{{ $adhesion->created_at->format('d/m/Y H:i') }}</td>
                                        <td>
                                            <form action="{{ route('membre.adhesions.accepter', $adhesion) }}" method="POST" class="d-inline">@csrf
                                                <button type="submit" class="btn btn-sm btn-success" title="Accepter"><i class="bi bi-check"></i></button>
                                            </form>
                                            <form id="formRefuser{{ $adhesion->id }}" action="{{ route('membre.adhesions.refuser', $adhesion) }}" method="POST" class="d-inline">@csrf
                                                <button type="button" class="btn btn-sm btn-danger" title="Refuser" data-bs-toggle="modal" data-bs-target="#modalRefuserDemande" data-form-id="formRefuser{{ $adhesion->id }}"><i class="bi bi-x"></i></button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <p class="text-muted mb-0 small">Aucune demande en attente.</p>
                @endif
            </div>
        </div>
    </div>

    <!-- Onglet Adhérents acceptés -->
    <div class="tab-pane fade" id="adherents" role="tabpanel">
        <div class="card mb-3" style="font-family: 'Ubuntu', sans-serif; font-weight: 300;">
            <div class="card-header card-header-compact d-flex justify-content-between align-items-center flex-wrap gap-2">
                <span><i class="bi bi-people"></i> Adhérents acceptés</span>
                @if($adhesionsAcceptees->count() > 0)
                    <input type="text" class="form-control form-control-sm table-search-input" placeholder="Rechercher…" style="max-width: 180px; height: 28px; font-size: 0.75rem;" data-table-target="table-adhesions-acceptees">
                @endif
            </div>
            <div class="card-body">
                @if($adhesionsAcceptees->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-compact-membre table-sm table-hover mb-0" id="table-adhesions-acceptees">
                            <thead>
                                <tr>
                                    <th>Membre</th>
                                    <th>Email</th>
                                    <th>Type de membre</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($adhesionsAcceptees as $adhesion)
                                    <tr>
                                        <td>{{ $adhesion->membre->nom_complet }}</td>
                                        <td>{{ $adhesion->membre->email }}</td>
                                        <td>
                                            @if($adhesion->membre->id === $cotisation->getAdminMembreId())
                                                <span class="text-primary fw-medium">Admin</span>
                                            @else
                                                <span class="text-muted">Membre simple</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <p class="text-muted mb-0 small">Aucun adhérent pour le moment.</p>
                @endif
            </div>
        </div>
    </div>

    <!-- Onglet Paiements / Collecte -->
    <div class="tab-pane fade" id="paiements" role="tabpanel">
        <div class="card mb-3" style="font-family: 'Ubuntu', sans-serif; font-weight: 300;">
            <div class="card-header card-header-compact d-flex justify-content-between align-items-center flex-wrap gap-2">
                <span><i class="bi bi-cash-stack"></i> Montant total collecté</span>
                <span class="small fw-medium">{{ number_format($totalCollecte ?? 0, 0, ',', ' ') }} XOF</span>
            </div>
            <div class="card-body py-2">
                <p class="small text-muted mb-2">Détails des paiements par les membres (réservé aux administrateurs de la cagnotte).</p>
                @if(isset($paiementsCotisation) && $paiementsCotisation->count() > 0)
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <label class="small mb-0 text-muted">Rechercher :</label>
                        <input type="text" class="form-control form-control-sm table-search-input" placeholder="Membre, montant…" style="max-width: 180px; height: 28px; font-size: 0.75rem;" data-table-target="table-paiements-cotisation">
                    </div>
                    <div class="table-responsive">
                        <table class="table table-compact-membre table-sm table-hover mb-0" id="table-paiements-cotisation">
                            <thead>
                                <tr>
                                    <th>Membre</th>
                                    <th>Date</th>
                                    <th>Montant</th>
                                    <th>Mode</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($paiementsCotisation as $paiement)
                                    <tr>
                                        <td>{{ $paiement->membre->nom_complet ?? '-' }}</td>
                                        <td>{{ $paiement->date_paiement ? $paiement->date_paiement->format('d/m/Y H:i') : '-' }}</td>
                                        <td>{{ number_format($paiement->montant ?? 0, 0, ',', ' ') }} XOF</td>
                                        <td>{{ ucfirst(str_replace('_', ' ', $paiement->mode_paiement ?? '')) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <p class="text-muted mb-0 small">Aucun paiement enregistré pour cette cagnotte.</p>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Modal confirmation demande versement -->
<div class="modal fade" id="modalConfirmVersement" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-cash-stack"></i> Demande de versement</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                Envoyer une demande de versement des fonds ({{ number_format($soldeCaisse ?? 0, 0, ',', ' ') }} XOF) à l'administration ?
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-success" data-submit-form="formDemandeVersement">Confirmer</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal confirmation clôturer -->
<div class="modal fade" id="modalConfirmCloturer" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-x-circle"></i> Clôturer la cagnotte</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                Clôturer cette cagnotte ? Les membres ne pourront plus y adhérer ni payer.
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-danger" data-submit-form="formCloturer">Confirmer</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal confirmation refuser demande -->
<div class="modal fade" id="modalRefuserDemande" tabindex="-1" data-form-id="">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Refuser la demande</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                Refuser cette demande d'adhésion ?
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-danger" id="btnConfirmRefuserDemande">Confirmer</button>
            </div>
        </div>
    </div>
</div>

<script>
document.querySelectorAll('[data-submit-form]').forEach(function(btn) {
    btn.addEventListener('click', function() { document.getElementById(this.getAttribute('data-submit-form')).submit(); });
});
document.getElementById('modalRefuserDemande').addEventListener('show.bs.modal', function(e) {
    var opener = e.relatedTarget;
    this.dataset.formId = opener && opener.dataset.formId ? opener.dataset.formId : '';
});
document.getElementById('btnConfirmRefuserDemande').addEventListener('click', function() {
    var formId = document.getElementById('modalRefuserDemande').dataset.formId;
    if (formId) document.getElementById(formId).submit();
});
document.querySelectorAll('.table-search-input').forEach(function(inp) {
    var tableId = inp.getAttribute('data-table-target');
    var table = document.getElementById(tableId);
    if (!table) return;
    inp.addEventListener('input', function() {
        var q = this.value.trim().toLowerCase();
        table.querySelectorAll('tbody tr').forEach(function(tr) {
            var text = tr.textContent.replace(/\s+/g, ' ').toLowerCase();
            tr.style.display = text.indexOf(q) !== -1 ? '' : 'none';
        });
    });
});
</script>
@endsection
