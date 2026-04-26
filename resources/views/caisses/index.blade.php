@extends('layouts.app')

@section('title', 'Gestion des Comptes')

@section('content')
<div class="page-header">
    <h1><i class="bi bi-wallet2"></i> Gestion des Comptes</h1>
</div>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
    <span><i class="bi bi-list-ul"></i> Liste des Comptes</span>
        <!--a href="{{ route('caisses.create') }}" class="btn btn-light btn-sm">
            <i class="bi bi-plus-circle"></i> Nouveau Compte
        </a-->
    </div>
    <div class="card-body">
        <!-- Barre de recherche -->
        <form method="GET" action="{{ route('caisses.index') }}" class="mb-3">
            <div class="row g-2">
                <div class="col-md-10">
                    <input type="text" 
                           name="search" 
                           class="form-control form-control-sm" 
                           placeholder="Rechercher par nom, numéro ou client..." 
                           value="{{ request('search') }}">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary btn-sm w-100">
                        <i class="bi bi-search"></i> Rechercher
                    </button>
                </div>
            </div>
            @if(request('search'))
                <div class="mt-2">
                    <a href="{{ route('caisses.index') }}" class="btn btn-secondary btn-sm">
                        <i class="bi bi-x-circle"></i> Effacer
                    </a>
                </div>
            @endif
        </form>
        
        @if($caisses->count() > 0)
            <style>
                .table-caisses {
                    margin-bottom: 0;
                }
                .table-caisses thead th {
                    padding: 0.15rem 0.35rem !important;
                    font-size: 0.6rem !important;
                    line-height: 1.05 !important;
                    vertical-align: middle !important;
                    font-weight: 300 !important;
                    font-family: 'Ubuntu', sans-serif !important;
                    color: var(--primary-dark-blue) !important;
                }
                .table-caisses tbody td {
                    padding: 0.15rem 0.35rem !important;
                    font-size: 0.65rem !important;
                    line-height: 1.05 !important;
                    vertical-align: middle !important;
                    border-bottom: 1px solid #f0f0f0 !important;
                    font-weight: 300 !important;
                    font-family: 'Ubuntu', sans-serif !important;
                    color: var(--primary-dark-blue) !important;
                }
                .table-caisses tbody td[style*="font-weight"] {
                    color: var(--primary-dark-blue) !important;
                }
                .table-caisses tbody td.statut-active {
                    color: #198754 !important;
                }
                
                .table-caisses tbody tr:last-child td {
                    border-bottom: none !important;
                }
                table.table.table-caisses.table-hover tbody tr {
                    background-color: #ffffff !important;
                    transition: background-color 0.2s ease !important;
                }
                table.table.table-caisses.table-hover tbody tr:nth-child(even) {
                    background-color: #d4dde8 !important;
                }
                table.table.table-caisses.table-hover tbody tr:hover {
                    background-color: #b8c7d9 !important;
                    cursor: pointer !important;
                }
                table.table.table-caisses.table-hover tbody tr:nth-child(even):hover {
                    background-color: #9fb3cc !important;
                }
                .table-caisses .btn {
                    padding: 0.1rem 0.25rem !important;
                    font-size: 0.55rem !important;
                    line-height: 1.1 !important;
                }
                .table-caisses .btn i {
                    font-size: 0.65rem !important;
                }
                .badge-account-type {
                    font-size: 0.55rem;
                    text-transform: uppercase;
                }
            </style>
            <div class="table-responsive">
                <table class="table table-caisses table-striped table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Numéro</th>
                            <th>Client</th>
                            <th>Type</th>
                            <th>Nom</th>
                            <th>Core Banking</th>
                            <th>Solde Actuel</th>
                            <th>Statut</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($caisses as $caisse)
                            <tr class="table-row-caisse">
                                <td>{{ $caisse->id }}</td>
                                <td>{{ $caisse->numero ?? '-' }}</td>
                                <td>
                                    @if($caisse->membre_id)
                                        <a href="{{ route('membres.show', $caisse->membre_id) }}" class="text-decoration-none">
                                            {{ $caisse->membre->nom_complet ?? 'N/A' }}
                                        </a>
                                    @else
                                        <span class="badge bg-light text-dark border small">COMPTE SYSTÈME</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge bg-secondary badge-account-type">{{ $caisse->type }}</span>
                                </td>
                                <td>{{ $caisse->nom }}</td>
                                <td><code>{{ $caisse->numero_core_banking ?? '-' }}</code></td>
                                <td>
                                    <strong>{{ number_format((float) ($caisse->solde_actuel ?? 0), 0, ',', ' ') }} XOF</strong>
                                </td>
                                <td class="{{ $caisse->statut === 'active' ? 'statut-active' : '' }}">
                                    @if($caisse->statut === 'active')
                                        <i class="bi bi-check-circle"></i> Actif
                                    @else
                                        <i class="bi bi-x-circle"></i> Inactif
                                    @endif
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm" role="group">
                                        <a href="{{ route('caisses.journal', $caisse) }}"
                                           class="btn btn-outline-secondary"
                                           title="Journal / Balance">
                                            <i class="bi bi-journal-text"></i>
                                        </a>
                                        <a href="{{ route('caisses.edit', $caisse) }}" 
                                           class="btn btn-outline-warning" 
                                           title="Modifier">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination Bootstrap -->
            @if($caisses->hasPages() || $caisses->total() > 0)
                <div class="d-flex justify-content-between align-items-center mt-3">
                    <div class="pagination-info" style="font-weight: 300; font-family: 'Ubuntu', sans-serif; font-size: 0.75rem; color: #6c757d;">
                        @if($caisses->total() > 0)
                        Affichage de {{ $caisses->firstItem() }} à {{ $caisses->lastItem() }} sur {{ $caisses->total() }} résultat(s)
                        @else
                            Aucun résultat
                        @endif
                    </div>
                    <div class="pagination-custom">
                        {{ $caisses->links() }}
                    </div>
                </div>
            @endif
        @else
            <div class="text-center py-3">
                <i class="bi bi-inbox" style="font-size: 1.5rem; color: #ccc;"></i>
                <p class="text-muted mt-2 mb-2" style="font-size: 0.75rem;">Aucun compte enregistré</p>
                <a href="{{ route('caisses.create') }}" class="btn btn-primary btn-sm">
                    <i class="bi bi-plus-circle"></i> Créer le premier compte
                </a>
            </div>
        @endif
    </div>
</div>
@endsection
