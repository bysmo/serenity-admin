@extends('layouts.membre')

@section('title', 'Mes Paiements')

@section('content')
<style>
.table-paiements { margin-bottom: 0; }
.table-paiements thead th {
    padding: 0.15rem 0.35rem !important;
    font-size: 0.65rem !important;
    line-height: 1.05 !important;
    vertical-align: middle !important;
    font-weight: 300 !important;
    font-family: 'Ubuntu', sans-serif !important;
    color: #fff !important;
    background-color: var(--primary-dark-blue) !important;
    border-bottom: 2px solid #dee2e6 !important;
}
.table-paiements tbody td {
    padding: 0.15rem 0.35rem !important;
    font-size: 0.65rem !important;
    line-height: 1.05 !important;
    vertical-align: middle !important;
    border-bottom: 1px solid #f0f0f0 !important;
    font-weight: 300 !important;
    font-family: 'Ubuntu', sans-serif !important;
    color: var(--primary-dark-blue) !important;
}
.table-paiements tbody tr:last-child td { border-bottom: none !important; }
.table-paiements .actions-cell { display: flex; flex-wrap: wrap; gap: 0.2rem; align-items: center; }
.table-paiements .actions-cell .btn {
    padding: 0.15rem 0.3rem !important;
    font-size: 0.6rem !important;
    line-height: 1.1 !important;
    min-height: 20px !important;
    display: inline-flex !important;
    align-items: center !important;
    justify-content: center !important;
}
.table-paiements .actions-cell .btn i { font-size: 0.65rem !important; }
table.table.table-paiements.table-hover tbody tr { background-color: #fff !important; transition: background-color 0.2s ease !important; }
table.table.table-paiements.table-hover tbody tr:nth-child(even) { background-color: #d4dde8 !important; }
table.table.table-paiements.table-hover tbody tr:hover { background-color: #b8c7d9 !important; cursor: pointer !important; }
table.table.table-paiements.table-hover tbody tr:nth-child(even):hover { background-color: #9fb3cc !important; }
.card-header-compact-paiements { padding: 0.35rem 0.6rem !important; font-size: 0.75rem !important; font-weight: 300 !important; font-family: 'Ubuntu', sans-serif !important; }
.nav-tabs .nav-link { font-size: 0.75rem !important; padding: 0.35rem 0.6rem !important; color: var(--primary-dark-blue); }
.nav-tabs .nav-link.active { font-weight: 500; }
</style>
<div class="page-header">
    <h1 style="font-weight: 300; font-family: 'Ubuntu', sans-serif;"><i class="bi bi-receipt"></i> Mes Paiements</h1>
</div>

<div class="card">
    <div class="card-header card-header-compact-paiements d-flex justify-content-between align-items-center flex-wrap gap-2">
        <span><i class="bi bi-list-ul"></i> Liste de mes paiements</span>
        @if($paiements->count() > 0)
            <div class="d-flex align-items-center gap-2">
                <label class="small mb-0 text-white opacity-75">Rechercher :</label>
                <input type="text" class="form-control form-control-sm table-search-paiements" placeholder="Numéro, cotisation, montant…" style="max-width: 200px; height: 28px; font-size: 0.75rem;" data-table-target="table-mes-paiements">
            </div>
        @endif
    </div>
    <div class="card-body pt-2 pb-3">
        @if($annees->isNotEmpty() || $paiements->total() > 0)
            <ul class="nav nav-tabs mb-3" role="tablist">
                <li class="nav-item">
                    <a class="nav-link {{ (request('annee') === null || request('annee') === '') ? 'active' : '' }}" href="{{ route('membre.paiements', ['annee' => '']) }}"><i class="bi bi-grid-3x3-gap"></i> Tous</a>
                </li>
                @foreach($annees as $a)
                    <li class="nav-item">
                        <a class="nav-link {{ request('annee') == $a ? 'active' : '' }}" href="{{ route('membre.paiements', ['annee' => $a]) }}">{{ $a }}</a>
                    </li>
                @endforeach
            </ul>
        @endif
        @if($paiements->count() > 0)
            <div class="table-responsive">
                <table class="table table-paiements table-striped table-hover" id="table-mes-paiements">
                    <thead>
                        <tr>
                            <th>Numéro</th>
                            <th>Tontine</th>
                            <th>Montant</th>
                            <th>Date paiement</th>
                            <th>Mode</th>
                            <th>Compte</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($paiements as $paiement)
                            <tr>
                                <td>{{ $paiement->numero ?? '-' }}</td>
                                <td>{{ $paiement->cotisation->nom ?? '-' }}</td>
                                <td>{{ number_format($paiement->montant, 0, ',', ' ') }} XOF</td>
                                <td>{{ $paiement->date_paiement ? $paiement->date_paiement->format('d/m/Y') : '-' }}</td>
                                <td>{{ ucfirst(str_replace('_', ' ', $paiement->mode_paiement ?? '')) }}</td>
                                <td>{{ $paiement->caisse->nom ?? '-' }}</td>
                                <td>
                                    <div class="actions-cell">
                                        <a href="{{ route('membre.paiements.pdf', $paiement) }}" class="btn btn-outline-primary btn-sm" target="_blank" title="Télécharger le reçu"><i class="bi bi-download"></i></a>
                                        @php
                                            $remboursementExistant = $paiement->remboursements()->whereIn('statut', ['en_attente', 'approuve'])->first();
                                        @endphp
                                        @if(!$remboursementExistant)
                                            <button type="button" class="btn btn-outline-warning btn-sm" data-bs-toggle="modal" data-bs-target="#modalRemboursement{{ $paiement->id }}" title="Demander un remboursement"><i class="bi bi-arrow-counterclockwise"></i></button>
                                        @endif
                                    </div>
                                </td>

                                <!-- Modal demande remboursement -->
                                <div class="modal fade" id="modalRemboursement{{ $paiement->id }}" tabindex="-1">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <form action="{{ route('membre.remboursements.creer') }}" method="POST">
                                                @csrf
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Demander un remboursement</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <input type="hidden" name="paiement_id" value="{{ $paiement->id }}">
                                                    
                                                    <div class="mb-3">
                                                        <label class="form-label">Paiement</label>
                                                        <input type="text" class="form-control" value="{{ $paiement->numero }} - {{ number_format($paiement->montant, 0, ',', ' ') }} XOF" disabled>
                                                    </div>
                                                    
                                                    <div class="mb-3">
                                                        <label for="montant{{ $paiement->id }}" class="form-label">Montant à rembourser <span class="text-danger">*</span></label>
                                                        <input type="number" 
                                                               class="form-control @error('montant') is-invalid @enderror" 
                                                               id="montant{{ $paiement->id }}"
                                                               name="montant" 
                                                               value="{{ old('montant', $paiement->montant) }}"
                                                               min="1" 
                                                               max="{{ $paiement->montant }}" 
                                                               required>
                                                        <small class="text-muted">Maximum: {{ number_format($paiement->montant, 0, ',', ' ') }} XOF</small>
                                                        @error('montant')
                                                            <div class="invalid-feedback">{{ $message }}</div>
                                                        @enderror
                                                    </div>
                                                    
                                                    <div class="mb-3">
                                                        <label for="raison{{ $paiement->id }}" class="form-label">Raison <span class="text-danger">*</span></label>
                                                        <textarea class="form-control @error('raison') is-invalid @enderror" 
                                                                  id="raison{{ $paiement->id }}"
                                                                  name="raison" 
                                                                  rows="3" 
                                                                  maxlength="1000" 
                                                                  required>{{ old('raison') }}</textarea>
                                                        @error('raison')
                                                            <div class="invalid-feedback">{{ $message }}</div>
                                                        @enderror
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                                                    <button type="submit" class="btn btn-warning">Envoyer la demande</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            
            @if($paiements->hasPages() || $paiements->total() > 0)
                <div class="d-flex justify-content-end mt-3">
                    <div class="pagination-custom">
                        {{ $paiements->links() }}
                    </div>
                </div>
            @endif
        @else
            <div class="text-center py-3">
                <i class="bi bi-inbox" style="font-size: 1.5rem; color: #ccc;"></i>
                <p class="text-muted mt-2 mb-0" style="font-size: 0.75rem;">Aucun paiement enregistré</p>
            </div>
        @endif
    </div>
</div>

<script>
document.querySelectorAll('.table-search-paiements').forEach(function(inp) {
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
