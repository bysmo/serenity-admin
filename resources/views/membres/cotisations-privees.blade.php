@extends('layouts.membre')

@section('title', 'Tontines privées')

@section('content')
<div class="page-header">
    <h1 style="font-weight: 300; font-family: 'Ubuntu', sans-serif;"><i class="bi bi-lock"></i> Tontines privées</h1>
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
</style>

<div class="card border-0 bg-transparent shadow-none">
    <div class="card-header border-0 bg-transparent px-0 pb-3" style="font-weight: 300; font-family: 'Ubuntu', sans-serif;">
        <i class="bi bi-lock"></i> Mes cagnottes privées (adhésion acceptée)
    </div>
    <div class="card-body p-0">
        <div class="mb-4 d-flex align-items-center gap-2 w-100">
            <div class="input-group">
                <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                <input type="text" class="form-control border-start-0 ps-0 table-search-cot" placeholder="Rechercher par nom, tag, description..." id="searchTontinesPrivees">
            </div>
        </div>
        
        @if($cotisations->total() > 0)
            <div class="row g-3" id="cotisations-privees-grid">
                @foreach($cotisations as $cotisation)
                    @php 
                        $adhesion = $adhesions[$cotisation->id] ?? null; 
                        $currentMembreId = Auth::guard('membre')->id();
                        $canSeeAmount = ($cotisation->created_by_membre_id === $currentMembreId) || 
                                      ($cotisation->admin_membre_id === $currentMembreId);
                    @endphp
                    <div class="col-md-6 col-lg-4 cotisation-item">
                        <div class="card h-100 border shadow-sm" style="border-radius: 8px; transition: transform 0.2s;">
                            <div class="card-body d-flex flex-column">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <h6 class="card-title mb-0 text-truncate" style="font-weight: 500; color: var(--primary-dark-blue);" title="{{ $cotisation->nom }}">
                                        {{ $cotisation->nom }}
                                    </h6>
                                    @if($cotisation->actif)
                                        <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill" style="font-size: 0.65rem;">Active</span>
                                    @else
                                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill" style="font-size: 0.65rem;">Inactive</span>
                                    @endif
                                </div>
                                
                                <p class="card-text small text-muted mb-3 flex-grow-1" style="font-size: 0.8rem;">
                                    {{ Str::limit($cotisation->description ?? 'Aucune description disponible', 100) }}
                                </p>
                                
                                <div class="bg-light rounded p-2 mb-3">
                                    <ul class="list-unstyled small mb-0 text-secondary" style="font-size: 0.75rem;">
                                        <li class="mb-1 d-flex justify-content-between">
                                            <span><i class="bi bi-tag me-1"></i> Type :</span>
                                            <strong>{{ ucfirst($cotisation->type) }}</strong>
                                        </li>
                                        <li class="mb-1 d-flex justify-content-between">
                                            <span><i class="bi bi-cash-stack me-1"></i> Montant :</span>
                                            @if($cotisation->type_montant === 'libre')
                                                <strong>Libre</strong>
                                            @else
                                                @if($canSeeAmount)
                                                    <strong>{{ number_format((float)($cotisation->montant ?? 0), 0, ',', ' ') }} XOF</strong>
                                                @else
                                                    <span class="text-muted fst-italic">Masqué</span>
                                                @endif
                                            @endif
                                        </li>
                                        <li class="mb-1 d-flex justify-content-between">
                                            <span><i class="bi bi-calendar-event me-1"></i> Début :</span>
                                            <span>{{ $cotisation->created_at->format('d/m/Y') }}</span>
                                        </li>
                                        <li class="mb-1 d-flex justify-content-between">
                                            <span><i class="bi bi-calendar-x me-1"></i> Fin :</span>
                                            <span>Illimitée</span>
                                        </li>
                                        <li class="d-flex justify-content-between">
                                            <span><i class="bi bi-receipt me-1"></i> Paiements :</span>
                                            <span class="badge bg-secondary rounded-pill">{{ $cotisation->paiements_count }}</span>
                                        </li>
                                    </ul>
                                </div>

                                <div class="d-grid gap-2 mt-auto">
                                    <a href="{{ route('membre.cotisations.show', $cotisation->id) }}" class="btn btn-outline-primary btn-sm">
                                        <i class="bi bi-eye"></i> Voir détails
                                    </a>
                                    
                                    @if($adhesion && $adhesion->statut === 'accepte' && $cotisation->actif)
                                        <div class="d-flex gap-1">
                                            @if($paydunyaEnabled)
                                                <button type="button" class="btn btn-primary btn-sm flex-grow-1 shadow-sm" onclick="initierPaiementPayDunya({{ $cotisation->id }}, '{{ addslashes($cotisation->nom) }}', {{ (float)($cotisation->montant ?? 0) }})">
                                                    <i class="bi bi-phone"></i> PayDunya
                                                </button>
                                            @endif
                                            @if($pispiEnabled)
                                                <button type="button" class="btn btn-success btn-sm flex-grow-1 shadow-sm" onclick="initierPaiementPiSpi({{ $cotisation->id }}, '{{ addslashes($cotisation->nom) }}', {{ (float)($cotisation->montant ?? 0) }})">
                                                    <i class="bi bi-bank"></i> Pi-SPI
                                                </button>
                                            @endif
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
            
            @if($cotisations->hasPages())
                <div class="d-flex justify-content-end mt-4">
                    <div class="pagination-custom">{{ $cotisations->links() }}</div>
                </div>
            @endif
        @else
            <div class="text-center py-5 bg-white rounded shadow-sm">
                <i class="bi bi-inbox text-muted" style="font-size: 3rem; opacity: 0.5;"></i>
                <h5 class="mt-3 text-muted fw-light">Aucune cagnotte privée</h5>
                <p class="text-muted small">Utilisez « Rechercher par code » pour demander l'adhésion à une cagnotte privée.</p>
            </div>
        @endif
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('searchTontinesPrivees');
    if (!searchInput) return;

    searchInput.addEventListener('input', function() {
        const query = this.value.trim().toLowerCase();
        const items = document.querySelectorAll('.cotisation-item');
        
        items.forEach(function(item) {
            const text = item.textContent.replace(/\s+/g, ' ').toLowerCase();
            if (text.indexOf(query) !== -1) {
                item.style.display = '';
            } else {
                item.style.display = 'none';
            }
        });
    });
});
</script>

@if($paydunyaEnabled || $pispiEnabled)
<!-- Modal confirmation paiement -->
<div class="modal fade" id="modalPaiementPayDunya" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title" id="modalPayDunyaTitle">Confirmation de paiement</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="mb-2">Voulez-vous payer la cagnotte <strong id="modalPayDunyaNom"></strong> d'un montant de <strong id="modalPayDunyaMontant"></strong> XOF ?</p>
                <p class="small text-muted mb-0" id="modalPayDunyaNote"></p>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Annuler</button>
                <button type="button" id="modalPayDunyaConfirmLink" class="btn btn-primary btn-sm">Confirmer</button>
            </div>
        </div>
    </div>
</div>

<script>
let currentTontineId = null;
let currentMethod = 'paydunya';

function initierPaiementPayDunya(cotisationId, nomTontine, montant) {
    currentTontineId = cotisationId;
    currentMethod = 'paydunya';
    var modalElement = document.getElementById('modalPaiementPayDunya');
    var modal = new bootstrap.Modal(modalElement);
    document.getElementById('modalPayDunyaTitle').innerHTML = '<i class="bi bi-phone"></i> Paiement via PayDunya';
    document.getElementById('modalPayDunyaNom').textContent = '"' + nomTontine + '"';
    document.getElementById('modalPayDunyaMontant').textContent = new Intl.NumberFormat('fr-FR').format(montant);
    document.getElementById('modalPayDunyaNote').textContent = "Vous allez être redirigé vers la page de paiement sécurisée de PayDunya.";
    modal.show();
}

function initierPaiementPiSpi(cotisationId, nomTontine, montant) {
    currentTontineId = cotisationId;
    currentMethod = 'pispi';
    var modalElement = document.getElementById('modalPaiementPayDunya');
    var modal = new bootstrap.Modal(modalElement);
    document.getElementById('modalPayDunyaTitle').innerHTML = '<i class="bi bi-bank"></i> Paiement via Pi-SPI (BCEAO)';
    document.getElementById('modalPayDunyaNom').textContent = '"' + nomTontine + '"';
    document.getElementById('modalPayDunyaMontant').textContent = new Intl.NumberFormat('fr-FR').format(montant);
    document.getElementById('modalPayDunyaNote').textContent = "Une demande de paiement sera envoyée directement sur votre téléphone.";
    modal.show();
}

document.getElementById('modalPayDunyaConfirmLink')?.addEventListener('click', function(e) {
    if (currentTontineId) {
        let route = currentMethod === 'paydunya' 
            ? '{{ route("membre.cotisations.paydunya", ":id") }}' 
            : '{{ route("membre.cotisations.pispi", ":id") }}';
            
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = route.replace(':id', currentTontineId);
        
        const csrf = document.createElement('input');
        csrf.type = 'hidden';
        csrf.name = '_token';
        csrf.value = '{{ csrf_token() }}';
        form.appendChild(csrf);
        
        document.body.appendChild(form);
        form.submit();
    }
});
</script>
@endif
@endsection
