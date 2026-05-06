@extends('layouts.app')

@section('title', 'Module Collecte Terrain')

@section('content')
<div class="page-header d-flex justify-content-between align-items-center">
    <h1><i class="bi bi-wallet2"></i> Collecte Terrain</h1>
    <div>
        @if(!$session)
            <form action="{{ route('collecte.session.open') }}" method="POST">
                @csrf
                <button type="submit" class="btn btn-success">
                    <i class="bi bi-play-fill"></i> Ouvrir la journée
                </button>
            </form>
        @else
            <form action="{{ route('collecte.session.close') }}" method="POST">
                @csrf
                <button type="submit" class="btn btn-danger">
                    <i class="bi bi-stop-fill"></i> Fermer la journée
                </button>
            </form>
        @endif
    </div>
</div>

<div class="row mb-4">
    <div class="col-md-4">
        <div class="card bg-primary text-white h-100 shadow-sm border-0">
            <div class="card-body text-center p-4">
                <h6 class="text-uppercase mb-3 opacity-75" style="letter-spacing: 1px;">Espèces en main</h6>
                <h2 class="display-5 fw-bold mb-2">{{ number_format($account->solde_actuel ?? 0, 0, ',', ' ') }} <small>XOF</small></h2>
                <div class="mt-3">
                    <span class="badge bg-white text-primary px-3 py-2 rounded-pill">
                        <i class="bi bi-person-badge me-1"></i> {{ auth()->user()->name }}
                    </span>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card bg-dark text-white h-100 shadow-sm border-0 overflow-hidden" style="background: linear-gradient(135deg, #1e293b 0%, #334155 100%);">
            <div class="card-body text-center p-4">
                <h6 class="text-uppercase mb-3 opacity-75" style="letter-spacing: 1px;">Compte de reversement</h6>
                <h3 class="fw-light mb-2">{{ $account->alias ?? 'Non configuré' }}</h3>
                <p class="small text-info mb-3">Alias Pi-SPI / Mobile Money</p>
                <form action="{{ route('collecte.settle') }}" method="POST" onsubmit="return confirm('Confirmer le reversement de la totalité des fonds collectés vers le compte global ?')">
                    @csrf
                    <button type="submit" class="btn btn-info btn-sm px-4 rounded-pill text-white fw-bold" {{ ($account->solde_actuel ?? 0) <= 0 ? 'disabled' : '' }}>
                        <i class="bi bi-send me-1"></i> Effectuer un reversement
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card h-100 shadow-sm border-0 bg-light">
            <div class="card-body text-center p-4">
                <h6 class="text-uppercase mb-3 text-muted" style="letter-spacing: 1px;">Statut Session</h6>
                @if($session)
                    <div class="d-flex flex-column align-items-center">
                        <span class="badge bg-success px-4 py-2 mb-2 rounded-pill">OUVERTE</span>
                        <small class="text-muted">Depuis le {{ $session->opened_at->format('d/m/Y H:i') }}</small>
                    </div>
                @else
                    <div class="d-flex flex-column align-items-center">
                        <span class="badge bg-secondary px-4 py-2 mb-2 rounded-pill">FERMÉE</span>
                        <p class="small text-muted mb-0">Ouvrez une session pour collecter</p>
                    </div>
                @endif
                <div class="mt-3">
                    <a href="{{ route('collecte.history') }}" class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-clock-history me-1"></i> Historique sessions
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

@if($session)
<div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-white py-3 border-bottom-0">
        <h5 class="mb-0 fw-bold text-primary"><i class="bi bi-search me-2"></i> Nouvelle Collecte</h5>
    </div>
    <div class="card-body">
        <div class="input-group input-group-lg mb-3 shadow-sm rounded">
            <span class="input-group-text bg-white border-end-0"><i class="bi bi-qr-code-scan text-primary"></i></span>
            <input type="text" id="member_search" class="form-control border-start-0" placeholder="Scanner QR Code ou saisir numéro membre / téléphone..." autocomplete="off">
            <button class="btn btn-primary px-4" type="button" id="btn_search">Rechercher</button>
        </div>
        <div id="search_result" class="mt-4" style="display: none;">
            <!-- AJAX content will appear here -->
        </div>
    </div>
</div>

<div class="card shadow-sm border-0">
    <div class="card-header bg-white py-3">
        <h5 class="mb-0 fw-bold"><i class="bi bi-list-task me-2 text-primary"></i> Collectes du jour</h5>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="bg-light">
                <tr>
                    <th class="ps-4">Heure</th>
                    <th>Membre</th>
                    <th>Type</th>
                    <th>Référence</th>
                    <th class="text-end pe-4">Montant</th>
                </tr>
            </thead>
            <tbody>
                @forelse($todayCollections as $coll)
                    <tr>
                        <td class="ps-4 text-muted">{{ $coll->confirmed_at ? $coll->confirmed_at->format('H:i') : $coll->created_at->format('H:i') }}</td>
                        <td>
                            <div class="d-flex align-items-center">
                                <div class="avatar-sm bg-primary text-white rounded-circle me-3 d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                                    {{ strtoupper(substr($coll->membre->prenom, 0, 1)) }}
                                </div>
                                <div>
                                    <h6 class="mb-0">{{ $coll->membre->nom_complet }}</h6>
                                    <small class="text-muted">{{ $coll->membre->numero }}</small>
                                </div>
                            </div>
                        </td>
                        <td>
                            <span class="badge {{ $coll->type_collecte == 'tontine' ? 'bg-soft-primary text-primary' : 'bg-soft-warning text-warning' }} px-3 rounded-pill">
                                {{ strtoupper($coll->type_collecte) }}
                            </span>
                        </td>
                        <td><code class="text-dark">{{ $coll->reference_transaction }}</code></td>
                        <td class="text-end pe-4 fw-bold text-primary">{{ number_format($coll->montant, 0, ',', ' ') }} XOF</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center py-5 text-muted">
                            <i class="bi bi-inbox display-4 d-block mb-3 opacity-25"></i>
                            Aucune collecte effectuée aujourd'hui.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endif

@endsection

@push('styles')
<style>
    .bg-soft-primary { background-color: rgba(79, 70, 229, 0.1); }
    .bg-soft-warning { background-color: rgba(245, 158, 11, 0.1); }
    .display-5 { font-size: 2.5rem; letter-spacing: -1px; }
    .avatar-sm { font-size: 0.8rem; font-weight: bold; }
    .input-group-text { border-color: #e2e8f0; }
    #member_search:focus { box-shadow: none; border-color: var(--primary-color); }
</style>
@endpush

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.getElementById('member_search');
        const btnSearch = document.getElementById('btn_search');
        const resultDiv = document.getElementById('search_result');

        function doSearch() {
            const q = searchInput.value;
            if (q.length < 3) return;

            btnSearch.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>';
            btnSearch.disabled = true;

            fetch(`{{ route('collecte.search-membre') }}?q=${q}`)
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        resultDiv.innerHTML = `<div class="alert alert-warning"><i class="bi bi-exclamation-triangle me-2"></i> ${data.error}</div>`;
                    } else {
                        resultDiv.innerHTML = `
                            <div class="card border border-primary-subtle bg-primary-subtle bg-opacity-10">
                                <div class="card-body d-flex align-items-center justify-content-between">
                                    <div class="d-flex align-items-center">
                                        <div class="avatar-lg bg-primary text-white rounded me-4 d-flex align-items-center justify-content-center" style="width: 64px; height: 64px; font-size: 1.5rem;">
                                            ${data.nom_complet.charAt(0)}
                                        </div>
                                        <div>
                                            <h4 class="mb-1 fw-bold text-dark">${data.nom_complet}</h4>
                                            <p class="mb-0 text-muted">Numéro: <span class="fw-bold text-primary">${data.numero}</span> | Tél: ${data.telephone}</p>
                                        </div>
                                    </div>
                                    <a href="/collecte/membre/${data.id}" class="btn btn-primary px-4">
                                        Sélectionner <i class="bi bi-chevron-right ms-2"></i>
                                    </a>
                                </div>
                            </div>
                        `;
                    }
                    resultDiv.style.display = 'block';
                })
                .catch(error => {
                    resultDiv.innerHTML = `<div class="alert alert-danger">Une erreur est survenue lors de la recherche.</div>`;
                    resultDiv.style.display = 'block';
                })
                .finally(() => {
                    btnSearch.innerHTML = 'Rechercher';
                    btnSearch.disabled = false;
                });
        }

        btnSearch.addEventListener('click', doSearch);
        searchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') doSearch();
        });
    });
</script>
@endpush
