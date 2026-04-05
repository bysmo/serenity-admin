@extends('layouts.membre')

@section('title', 'Mes Cotisations')

@section('content')
<div class="page-header">
    <h1 style="font-weight: 300; font-family: 'Ubuntu', sans-serif;"><i class="bi bi-receipt-cutoff"></i> Mes Cotisations</h1>
</div>

<style>
.table-cotisations-membre { margin-bottom: 0; }
.table-cotisations-membre thead th {
    padding: 0.15rem 0.35rem !important;
    font-size: 0.65rem !important;
    line-height: 1.05 !important;
    vertical-align: middle !important;
    font-weight: 300 !important;
    font-family: 'Ubuntu', sans-serif !important;
    color: #ffffff !important;
    background-color: var(--primary-dark-blue) !important;
    border-bottom: 2px solid #dee2e6 !important;
}
.table-cotisations-membre tbody td {
    padding: 0.15rem 0.35rem !important;
    font-size: 0.65rem !important;
    line-height: 1.05 !important;
    vertical-align: middle !important;
    border-bottom: 1px solid #f0f0f0 !important;
    font-weight: 300 !important;
    font-family: 'Ubuntu', sans-serif !important;
    color: var(--primary-dark-blue) !important;
}
.table-cotisations-membre tbody tr:last-child td { border-bottom: none !important; }
table.table.table-cotisations-membre.table-hover tbody tr { background-color: #ffffff !important; transition: background-color 0.2s ease !important; }
table.table.table-cotisations-membre.table-hover tbody tr:nth-child(even) { background-color: #d4dde8 !important; }
table.table.table-cotisations-membre.table-hover tbody tr:hover { background-color: #b8c7d9 !important; cursor: pointer !important; }
table.table.table-cotisations-membre.table-hover tbody tr:nth-child(even):hover { background-color: #9fb3cc !important; }
.table-cotisations-membre td:last-child { white-space: nowrap; min-width: 120px; }
.table-cotisations-membre .actions-cell { display: flex; flex-wrap: wrap; gap: 0.2rem; align-items: center; }
.table-cotisations-membre .actions-cell .btn {
    padding: 0.15rem 0.3rem !important;
    font-size: 0.6rem !important;
    line-height: 1.1 !important;
    min-height: 20px !important;
    display: inline-flex !important;
    align-items: center !important;
    justify-content: center !important;
    gap: 0.1rem;
}
.table-cotisations-membre .actions-cell .btn i { font-size: 0.65rem !important; }
.table-cotisations-membre .btn-group-sm > .btn, .table-cotisations-membre .btn-group > .btn { border-radius: 0.2rem !important; }
.card-header-compact-cot { padding: 0.35rem 0.6rem !important; font-size: 0.75rem !important; font-weight: 300 !important; font-family: 'Ubuntu', sans-serif !important; }
#cotisationsTab .nav-link { font-size: 0.8rem !important; padding: 0.35rem 0.75rem !important; font-weight: 300 !important; font-family: 'Ubuntu', sans-serif !important; }
#cotisationsTab .nav-link.active { background-color: var(--primary-dark-blue); color: #fff; border-color: var(--primary-dark-blue); }
</style>

<div class="card">
    <div class="card-header card-header-compact-cot" style="font-weight: 300; font-family: 'Ubuntu', sans-serif;">
        <i class="bi bi-list-ul"></i> Cagnottes Disponibles
    </div>
    <div class="card-body pt-2 pb-3">
        <ul class="nav nav-tabs nav-tabs-small mb-3" id="cotisationsTab" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="publiques-tab" data-bs-toggle="tab" data-bs-target="#publiques" type="button" role="tab">
                    <i class="bi bi-globe"></i> Publiques ({{ $cotisationsPubliques->total() }})
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="privees-tab" data-bs-toggle="tab" data-bs-target="#privees" type="button" role="tab">
                    <i class="bi bi-lock"></i> Privées ({{ $cotisationsPrivees->total() }})
                </button>
            </li>
        </ul>

        <div class="tab-content" id="cotisationsTabContent">
            <!-- Onglet Publiques -->
            <div class="tab-pane fade show active" id="publiques" role="tabpanel">
                <div class="mb-2 d-flex align-items-center gap-2 w-100">
                    <label class="small mb-0 text-muted flex-shrink-0">Rechercher :</label>
                    <input type="text" class="form-control form-control-sm table-search-cot flex-grow-1" placeholder="Nom, tag, description…" style="height: 28px; font-size: 0.75rem;" data-table-target="table-cot-publiques">
                    <button type="button" class="btn btn-sm btn-outline-secondary flex-shrink-0" style="height: 28px;" title="Filtrer"><i class="bi bi-search"></i> Rechercher</button>
                </div>
                @if($cotisationsPubliques->total() > 0)
                    <div class="table-responsive">
                        <table class="table table-cotisations-membre table-striped table-hover" id="table-cot-publiques">
                            <thead>
                                <tr>
                                    <th>Nom</th>
                                    <th>Tag</th>
                                    <th>Type</th>
                                    <th>Public / Privé</th>
                                    <th>Fréquence</th>
                                    <th>Montant</th>
                                    <th>Statut</th>
                                    <th>Description</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($cotisationsPubliques as $cotisation)
                                    @php $adhesion = $adhesions[$cotisation->id] ?? null; @endphp
                                    <tr>
                                        <td>{{ $cotisation->nom }}</td>
                                        <td>{{ $cotisation->tag ?? '-' }}</td>
                                        <td>{{ ucfirst($cotisation->type ?? 'N/A') }}</td>
                                        <td>Public</td>
                                        <td>{{ $cotisation->frequence ? ucfirst($cotisation->frequence) : '-' }}</td>
                                        <td>{{ number_format($cotisation->montant ?? 0, 0, ',', ' ') }} XOF</td>
                                        <td>@if($cotisation->actif)<span style="color: #28a745;">Active</span>@else<span style="color: #dc3545;">Inactive</span>@endif</td>
                                        <td>{{ Str::limit($cotisation->description ?? 'Aucune description', 40) }}</td>
                                        <td>
                                            <div class="actions-cell">
                                                <a href="{{ route('membre.cotisations.show', $cotisation->id) }}" class="btn btn-info btn-sm" title="Voir"><i class="bi bi-eye"></i></a>
                                                @if(!$adhesion)
                                                    <form action="{{ route('membre.cotisations.adherer', $cotisation) }}" method="POST" class="d-inline">@csrf
                                                        <button type="submit" class="btn btn-success btn-sm" title="Adhérer"><i class="bi bi-plus-circle"></i><span>Adhérer</span></button>
                                                    </form>
                                                @elseif($adhesion->statut === 'en_attente')
                                                    <span class="btn btn-secondary btn-sm disabled"><i class="bi bi-clock"></i> Attente</span>
                                                @elseif($adhesion->statut === 'accepte' && $paydunyaEnabled && $cotisation->actif)
                                                    <button type="button" class="btn btn-primary btn-sm" onclick="initierPaiementPayDunya({{ $cotisation->id }}, '{{ addslashes($cotisation->nom) }}', {{ $cotisation->montant ?? 0 }})" title="Payer"><i class="bi bi-phone"></i><span>Payer</span></button>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @if($cotisationsPubliques->hasPages())
                        <div class="d-flex justify-content-end mt-2">
                            <div class="pagination-custom">{{ $cotisationsPubliques->withQueryString()->links() }}</div>
                        </div>
                    @endif
                @else
                    <div class="text-center py-3">
                        <i class="bi bi-inbox" style="font-size: 1.5rem; color: #ccc;"></i>
                        <p class="text-muted mt-2 mb-0" style="font-size: 0.75rem;">Aucune cagnotte publique</p>
                    </div>
                @endif
            </div>

            <!-- Onglet Privées -->
            <div class="tab-pane fade" id="privees" role="tabpanel">
                <div class="mb-2 d-flex align-items-center gap-2 w-100">
                    <label class="small mb-0 text-muted flex-shrink-0">Rechercher :</label>
                    <input type="text" class="form-control form-control-sm table-search-cot flex-grow-1" placeholder="Nom, tag, description…" style="height: 28px; font-size: 0.75rem;" data-table-target="table-cot-privees">
                    <button type="button" class="btn btn-sm btn-outline-secondary flex-shrink-0" style="height: 28px;" title="Filtrer"><i class="bi bi-search"></i> Rechercher</button>
                </div>
                @if($cotisationsPrivees->total() > 0)
                    <div class="table-responsive">
                        <table class="table table-cotisations-membre table-striped table-hover" id="table-cot-privees">
                            <thead>
                                <tr>
                                    <th>Nom</th>
                                    <th>Tag</th>
                                    <th>Type</th>
                                    <th>Public / Privé</th>
                                    <th>Fréquence</th>
                                    <th>Montant</th>
                                    <th>Statut</th>
                                    <th>Description</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($cotisationsPrivees as $cotisation)
                                    @php $adhesion = $adhesions[$cotisation->id] ?? null; @endphp
                                    <tr>
                                        <td>{{ $cotisation->nom }}</td>
                                        <td>{{ $cotisation->tag ?? '-' }}</td>
                                        <td>{{ ucfirst($cotisation->type ?? 'N/A') }}</td>
                                        <td>Privé</td>
                                        <td>{{ $cotisation->frequence ? ucfirst($cotisation->frequence) : '-' }}</td>
                                        <td>{{ number_format($cotisation->montant ?? 0, 0, ',', ' ') }} XOF</td>
                                        <td>@if($cotisation->actif)<span style="color: #28a745;">Active</span>@else<span style="color: #dc3545;">Inactive</span>@endif</td>
                                        <td>{{ Str::limit($cotisation->description ?? 'Aucune description', 40) }}</td>
                                        <td>
                                            <div class="actions-cell">
                                                <a href="{{ route('membre.cotisations.show', $cotisation->id) }}" class="btn btn-info btn-sm" title="Voir"><i class="bi bi-eye"></i></a>
                                                @if(!$adhesion)
                                                    <form action="{{ route('membre.cotisations.adherer', $cotisation) }}" method="POST" class="d-inline">@csrf
                                                        <button type="submit" class="btn btn-success btn-sm" title="Demander l'adhésion"><i class="bi bi-plus-circle"></i><span>Demander</span></button>
                                                    </form>
                                                @elseif($adhesion->statut === 'en_attente')
                                                    <span class="btn btn-secondary btn-sm disabled"><i class="bi bi-clock"></i> Attente</span>
                                                @elseif($adhesion->statut === 'accepte' && $paydunyaEnabled && $cotisation->actif)
                                                    <button type="button" class="btn btn-primary btn-sm" onclick="initierPaiementPayDunya({{ $cotisation->id }}, '{{ addslashes($cotisation->nom) }}', {{ $cotisation->montant ?? 0 }})" title="Payer"><i class="bi bi-phone"></i><span>Payer</span></button>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @if($cotisationsPrivees->hasPages())
                        <div class="d-flex justify-content-end mt-2">
                            <div class="pagination-custom">{{ $cotisationsPrivees->withQueryString()->links() }}</div>
                        </div>
                    @endif
                @else
                    <div class="text-center py-3">
                        <i class="bi bi-inbox" style="font-size: 1.5rem; color: #ccc;"></i>
                        <p class="text-muted mt-2 mb-0" style="font-size: 0.75rem;">Aucune cagnotte privée</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

@if($paydunyaEnabled)
<!-- Modal confirmation paiement PayDunya -->
<div class="modal fade" id="modalPaiementPayDunya" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-phone"></i> Paiement via PayDunya</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="mb-0">Voulez-vous payer la cagnotte <strong id="modalPayDunyaNom"></strong> d'un montant de <strong id="modalPayDunyaMontant"></strong> XOF via PayDunya ?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <a href="#" id="modalPayDunyaConfirmLink" class="btn btn-primary">OK</a>
            </div>
        </div>
    </div>
</div>
@endif

<script>
document.querySelectorAll('.table-search-cot').forEach(function(inp) {
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

@if($paydunyaEnabled)
<script>
function initierPaiementPayDunya(cotisationId, nomCotisation, montant) {
    var modal = new bootstrap.Modal(document.getElementById('modalPaiementPayDunya'));
    document.getElementById('modalPayDunyaNom').textContent = '"' + nomCotisation + '"';
    document.getElementById('modalPayDunyaMontant').textContent = new Intl.NumberFormat('fr-FR').format(montant);
    var link = document.getElementById('modalPayDunyaConfirmLink');
    link.href = '{{ route("membre.cotisations.show", ":id") }}'.replace(':id', cotisationId) + '?init_payment=1';
    modal.show();
}
</script>
@endif
@endsection
