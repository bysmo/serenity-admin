@extends('layouts.app')

@section('title', 'Gestion des Cagnottes')

@section('content')
<div class="page-header">
    <h1><i class="bi bi-piggy-bank-fill"></i> Gestion des Cagnottes</h1>
</div>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="bi bi-list-ul"></i> Liste des Cagnottes</span>
        <a href="{{ route('cotisations.create') }}" class="btn btn-light btn-sm">
            <i class="bi bi-plus-circle"></i> Nouvelle Cagnotte
        </a>
    </div>
    <div class="card-body">
        <!-- Barre de recherche et filtres -->
        <form method="GET" action="{{ route('cotisations.index') }}" class="mb-3">
            <div class="row g-2">
                <div class="col-md-4">
                    <input type="text" 
                           name="search" 
                           class="form-control form-control-sm" 
                           placeholder="Rechercher par nom, type..." 
                           value="{{ request('search') }}">
                </div>
                <div class="col-md-2">
                    <select name="actif" class="form-select form-select-sm">
                        <option value="">Toutes</option>
                        <option value="1" {{ request('actif') === '1' ? 'selected' : '' }}>Actives</option>
                        <option value="0" {{ request('actif') === '0' ? 'selected' : '' }}>Inactives</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <button type="submit" class="btn btn-primary btn-sm w-100">
                        <i class="bi bi-search"></i> Filtrer
                    </button>
                </div>
            </div>
            @if(request('search') || request('actif'))
                <div class="mt-2">
                    <a href="{{ route('cotisations.index') }}" class="btn btn-secondary btn-sm">
                        <i class="bi bi-x-circle"></i> Effacer
                    </a>
                </div>
            @endif
        </form>
        
        @if($cotisations->count() > 0)
            <style>
                .table-cotisations {
                    margin-bottom: 0;
                }
                .table-cotisations thead th {
                    padding: 0.15rem 0.35rem !important;
                    font-size: 0.6rem !important;
                    line-height: 1.05 !important;
                    vertical-align: middle !important;
                    font-weight: 300 !important;
                    font-family: 'Ubuntu', sans-serif !important;
                    color: var(--primary-dark-blue) !important;
                }
                .table-cotisations tbody td {
                    padding: 0.15rem 0.35rem !important;
                    font-size: 0.65rem !important;
                    line-height: 1.05 !important;
                    vertical-align: middle !important;
                    border-bottom: 1px solid #f0f0f0 !important;
                    font-weight: 300 !important;
                    font-family: 'Ubuntu', sans-serif !important;
                    color: var(--primary-dark-blue) !important;
                }
                .table-cotisations tbody tr:last-child td {
                    border-bottom: none !important;
                }
                table.table.table-cotisations.table-hover tbody tr {
                    background-color: #ffffff !important;
                    transition: background-color 0.2s ease !important;
                }
                table.table.table-cotisations.table-hover tbody tr:nth-child(even) {
                    background-color: #d4dde8 !important;
                }
                table.table.table-cotisations.table-hover tbody tr:hover {
                    background-color: #b8c7d9 !important;
                    cursor: pointer !important;
                }
                table.table.table-cotisations.table-hover tbody tr:nth-child(even):hover {
                    background-color: #9fb3cc !important;
                }
                .table-cotisations .btn {
                    padding: 0.15rem 0.35rem !important;
                    font-size: 0.6rem !important;
                    line-height: 1.2 !important;
                }
                .table-cotisations .btn i {
                    font-size: 0.7rem !important;
                }
            </style>
            <div class="table-responsive">
                <table class="table table-cotisations table-striped table-hover">
                    <thead>
                        <tr>
                            <th>Numéro</th>
                            <th>Nom</th>
                            <th>Type</th>
                            <th>Fréquence</th>
                            <th>Montant</th>
                            <th>Caisse</th>
                            <th>Tag</th>
                            <th>Public / Privé</th>
                            <th>Statut</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($cotisations as $cotisation)
                            <tr>
                                <td style="font-weight: 300; font-family: 'Ubuntu', sans-serif;">{{ $cotisation->numero ?? '-' }}</td>
                                <td style="font-weight: 300; font-family: 'Ubuntu', sans-serif;">{{ $cotisation->nom }}</td>
                                <td style="font-weight: 300; font-family: 'Ubuntu', sans-serif;">{{ ucfirst($cotisation->type) }}</td>
                                <td style="font-weight: 300; font-family: 'Ubuntu', sans-serif;">{{ ucfirst($cotisation->frequence) }}</td>
                                <td style="font-weight: 300; font-family: 'Ubuntu', sans-serif;">
                                    @if($cotisation->montant)
                                        {{ number_format($cotisation->montant, 0, ',', ' ') }} XOF
                                    @else
                                        Libre
                                    @endif
                                </td>
                                <td style="font-weight: 300; font-family: 'Ubuntu', sans-serif;">{{ $cotisation->caisse->nom ?? '-' }}</td>
                                <td style="font-weight: 300; font-family: 'Ubuntu', sans-serif;">
                                    @if($cotisation->tag)
                                        {{ $cotisation->tag }}
                                    @else
                                        -
                                    @endif
                                </td>
                                <td style="font-weight: 300; font-family: 'Ubuntu', sans-serif;">
                                    @if($cotisation->isPublique())
                                        Public
                                    @else
                                        Privé
                                    @endif
                                </td>
                                <td style="font-weight: 300; font-family: 'Ubuntu', sans-serif;">
                                    @if($cotisation->actif)
                                        Active
                                    @else
                                        Inactive
                                    @endif
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm" role="group">
                                        <a href="{{ route('cotisations.show', $cotisation) }}" 
                                           class="btn btn-outline-primary" 
                                           title="Voir">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <a href="{{ route('cotisations.edit', $cotisation) }}" 
                                           class="btn btn-outline-warning" 
                                           title="Modifier">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <form action="{{ route('cotisations.destroy', $cotisation) }}" 
                                              method="POST" 
                                              class="d-inline"
                                              class="delete-form"
                                              data-message="Êtes-vous sûr de vouloir supprimer cette cagnotte ?">
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
            @if($cotisations->hasPages() || $cotisations->total() > 0)
                <div class="d-flex justify-content-end mt-3">
                    <div class="pagination-custom">
                        {{ $cotisations->links() }}
                    </div>
                </div>
            @endif
        @else
            <div class="text-center py-3">
                <i class="bi bi-inbox" style="font-size: 1.5rem; color: #ccc;"></i>
                <p class="text-muted mt-2 mb-2" style="font-size: 0.75rem;">Aucune cagnotte enregistrée</p>
                <a href="{{ route('cotisations.create') }}" class="btn btn-primary btn-sm">
                    <i class="bi bi-plus-circle"></i> Créer la première cagnotte
                </a>
            </div>
        @endif
    </div>
</div>
@endsection
