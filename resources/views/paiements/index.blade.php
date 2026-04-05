@extends('layouts.app')

@section('title', 'Gestion des Paiements')

@section('content')
<div class="page-header">
    <h1><i class="bi bi-cash-coin"></i> Gestion des Paiements</h1>
</div>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="bi bi-list-ul"></i> Liste des Paiements</span>
        @if(auth()->user()->hasRole('admin') && auth()->user()->hasPermission('paiement.create'))
        <a href="{{ route('paiements.create') }}" class="btn btn-light btn-sm">
            <i class="bi bi-plus-circle"></i> Nouveau Paiement
        </a>
        @endif
    </div>
    <div class="card-body">
        <!-- Barre de recherche et filtres -->
        <form method="GET" action="{{ route('paiements.index') }}" class="mb-3">
            <div class="row g-2">
                <div class="col-md-3">
                    <input type="text" 
                           name="search" 
                           class="form-control form-control-sm" 
                           placeholder="Rechercher..." 
                           value="{{ request('search') }}">
                </div>
                <div class="col-md-2">
                    <select name="mode_paiement" class="form-select form-select-sm">
                        <option value="">Tous les modes</option>
                        <option value="especes" {{ request('mode_paiement') === 'especes' ? 'selected' : '' }}>Espèces</option>
                        <option value="cheque" {{ request('mode_paiement') === 'cheque' ? 'selected' : '' }}>Chèque</option>
                        <option value="virement" {{ request('mode_paiement') === 'virement' ? 'selected' : '' }}>Virement</option>
                        <option value="mobile_money" {{ request('mode_paiement') === 'mobile_money' ? 'selected' : '' }}>Mobile Money</option>
                        <option value="autre" {{ request('mode_paiement') === 'autre' ? 'selected' : '' }}>Autre</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <input type="date" 
                           name="date_debut" 
                           class="form-control form-control-sm" 
                           value="{{ request('date_debut') }}"
                           placeholder="Date début">
                </div>
                <div class="col-md-2">
                    <input type="date" 
                           name="date_fin" 
                           class="form-control form-control-sm" 
                           value="{{ request('date_fin') }}"
                           placeholder="Date fin">
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary btn-sm w-100">
                        <i class="bi bi-search"></i> Filtrer
                    </button>
                </div>
            </div>
            @if(request('search') || request('mode_paiement') || request('date_debut') || request('date_fin'))
                <div class="mt-2">
                    <a href="{{ route('paiements.index') }}" class="btn btn-secondary btn-sm">
                        <i class="bi bi-x-circle"></i> Effacer
                    </a>
                </div>
            @endif
        </form>
        
        @if($paiements->count() > 0)
            <style>
                .table-paiements {
                    margin-bottom: 0;
                }
                .table-paiements thead th {
                    padding: 0.15rem 0.35rem !important;
                    font-size: 0.6rem !important;
                    line-height: 1.05 !important;
                    vertical-align: middle !important;
                    font-weight: 300 !important;
                    font-family: 'Ubuntu', sans-serif !important;
                    color: var(--primary-dark-blue) !important;
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
                .table-paiements tbody tr:last-child td {
                    border-bottom: none !important;
                }
                .table-paiements .btn {
                    padding: 0.1rem 0.25rem !important;
                    font-size: 0.55rem !important;
                    line-height: 1.1 !important;
                }
                .table-paiements .btn i {
                    font-size: 0.65rem !important;
                }
                table.table.table-paiements.table-hover tbody tr {
                    background-color: #ffffff !important;
                    transition: background-color 0.2s ease !important;
                }
                table.table.table-paiements.table-hover tbody tr:nth-child(even) {
                    background-color: #d4dde8 !important;
                }
                table.table.table-paiements.table-hover tbody tr:hover {
                    background-color: #b8c7d9 !important;
                    cursor: pointer !important;
                }
                table.table.table-paiements.table-hover tbody tr:nth-child(even):hover {
                    background-color: #9fb3cc !important;
                }
            </style>
            <div class="table-responsive">
                <table class="table table-paiements table-striped table-hover">
                    <thead>
                        <tr>
                            <th>Numéro</th>
                            <th>Membre</th>
                            <th>Cagnotte</th>
                            <th>Montant</th>
                            <th>Date paiement</th>
                            <th>Mode</th>
                            <th>Caisse</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($paiements as $paiement)
                            <tr>
                                <td>{{ $paiement->numero ?? '-' }}</td>
                                <td>
                                    {{ $paiement->membre->nom_complet ?? '-' }}
                                </td>
                                <td>{{ $paiement->cotisation->nom ?? '-' }}</td>
                                <td>
                                    {{ number_format($paiement->montant, 0, ',', ' ') }} XOF
                                </td>
                                <td>
                                    {{ $paiement->date_paiement ? $paiement->date_paiement->format('d/m/Y') : '-' }}
                                </td>
                                <td>
                                    {{ ucfirst(str_replace('_', ' ', $paiement->mode_paiement)) }}
                                </td>
                                <td>{{ $paiement->caisse->nom ?? '-' }}</td>
                                <td>
                                    <div class="btn-group btn-group-sm" role="group">
                                        <a href="{{ route('paiements.show', $paiement) }}" 
                                           class="btn btn-outline-primary" 
                                           title="Voir">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <form action="{{ route('paiements.destroy', $paiement) }}" 
                                              method="POST" 
                                              class="d-inline"
                                              class="delete-form"
                                              data-message="Êtes-vous sûr de vouloir supprimer ce paiement ? Le solde de la caisse sera ajusté.">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" 
                                                    class="btn btn-outline-danger" 
                                                    title="Supprimer">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination Bootstrap -->
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
                <p class="text-muted mt-2 mb-2" style="font-size: 0.75rem;">Aucun paiement enregistré</p>
                <a href="{{ route('paiements.create') }}" class="btn btn-primary btn-sm">
                    <i class="bi bi-plus-circle"></i> Créer le premier paiement
                </a>
            </div>
        @endif
    </div>
</div>
@endsection
