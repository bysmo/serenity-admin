@extends('layouts.app')

@section('title', 'Gestion des Engagements')

@section('content')
<div class="page-header">
    <h1><i class="bi bi-clipboard-check"></i> Gestion des Engagements</h1>
</div>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="bi bi-list-ul"></i> Liste des Engagements</span>
        @if(auth()->user()->hasRole('admin') || auth()->user()->hasRole('tresorier'))
        <!--a href="{{ route('engagements.create') }}" class="btn btn-light btn-sm">
            <i class="bi bi-plus-circle"></i> Nouvel Engagement
        </a-->
        @endif
    </div>
    <div class="card-body">
        <!-- Barre de recherche et filtres -->
        <form method="GET" action="{{ route('engagements.index') }}" class="mb-3">
            <div class="row g-2">
                <div class="col-md-3">
                    <input type="text" 
                           name="search" 
                           class="form-control form-control-sm" 
                           placeholder="Rechercher..." 
                           value="{{ request('search') }}">
                </div>
                <div class="col-md-2">
                    <select name="statut" class="form-select form-select-sm">
                        <option value="">Tous les statuts</option>
                        <option value="en_cours" {{ request('statut') === 'en_cours' ? 'selected' : '' }}>En cours</option>
                        <option value="termine" {{ request('statut') === 'termine' ? 'selected' : '' }}>Terminé</option>
                        <option value="annule" {{ request('statut') === 'annule' ? 'selected' : '' }}>Annulé</option>
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
            @if(request('search') || request('statut') || request('date_debut') || request('date_fin'))
                <div class="mt-2">
                    <a href="{{ route('engagements.index') }}" class="btn btn-secondary btn-sm">
                        <i class="bi bi-x-circle"></i> Effacer
                    </a>
                </div>
            @endif
        </form>
        
        @if($engagements->count() > 0)
            <style>
                .table-engagements {
                    margin-bottom: 0;
                }
                .table-engagements thead th {
                    padding: 0.15rem 0.35rem !important;
                    font-size: 0.6rem !important;
                    line-height: 1.05 !important;
                    vertical-align: middle !important;
                    font-weight: 300 !important;
                    font-family: 'Ubuntu', sans-serif !important;
                    color: var(--primary-dark-blue) !important;
                }
                .table-engagements tbody td {
                    padding: 0.15rem 0.35rem !important;
                    font-size: 0.65rem !important;
                    line-height: 1.05 !important;
                    vertical-align: middle !important;
                    border-bottom: 1px solid #f0f0f0 !important;
                    font-weight: 300 !important;
                    font-family: 'Ubuntu', sans-serif !important;
                    color: var(--primary-dark-blue) !important;
                }
                .table-engagements tbody tr:last-child td {
                    border-bottom: none !important;
                }
                table.table.table-engagements.table-hover tbody tr {
                    background-color: #ffffff !important;
                    transition: background-color 0.2s ease !important;
                }
                table.table.table-engagements.table-hover tbody tr:nth-child(even) {
                    background-color: #d4dde8 !important;
                }
                table.table.table-engagements.table-hover tbody tr:hover {
                    background-color: #b8c7d9 !important;
                    cursor: pointer !important;
                }
                table.table.table-engagements.table-hover tbody tr:nth-child(even):hover {
                    background-color: #9fb3cc !important;
                }
                .table-engagements .btn {
                    padding: 0.1rem 0.25rem !important;
                    font-size: 0.55rem !important;
                    line-height: 1.1 !important;
                }
                .table-engagements .btn i {
                    font-size: 0.65rem !important;
                }
            </style>
            <div class="table-responsive">
                <table class="table table-engagements table-striped table-hover">
                    <thead>
                        <tr>
                            <th>Numéro</th>
                            <th>Membre</th>
                            <th>Cotisation</th>
                            <th>Montant engagé</th>
                            <th>Périodicité</th>
                            <th>Période</th>
                            <th>Tag</th>
                            <th>Statut</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($engagements as $engagement)
                            <tr>
                                <td style="font-weight: 300; font-family: 'Ubuntu', sans-serif;">{{ $engagement->numero ?? '-' }}</td>
                                <td style="font-weight: 300; font-family: 'Ubuntu', sans-serif;">
                                    {{ $engagement->membre->nom_complet ?? '-' }}
                                </td>
                                <td style="font-weight: 300; font-family: 'Ubuntu', sans-serif;">{{ $engagement->cotisation->nom ?? '-' }}</td>
                                <td style="font-weight: 300; font-family: 'Ubuntu', sans-serif;">
                                    {{ number_format($engagement->montant_engage, 0, ',', ' ') }} XOF
                                </td>
                                <td style="font-weight: 300; font-family: 'Ubuntu', sans-serif;">{{ ucfirst($engagement->periodicite ?? 'mensuelle') }}</td>
                                <td style="font-weight: 300; font-family: 'Ubuntu', sans-serif;">
                                    {{ $engagement->periode_debut->format('d/m/Y') }} - {{ $engagement->periode_fin->format('d/m/Y') }}
                                </td>
                                <td style="font-weight: 300; font-family: 'Ubuntu', sans-serif;">
                                    {{ $engagement->tag ?? '-' }}
                                </td>
                                <td style="font-weight: 300; font-family: 'Ubuntu', sans-serif;">
                                    @if($engagement->statut === 'en_cours')
                                        En cours
                                    @elseif($engagement->statut === 'termine')
                                        Terminé
                                    @else
                                        Annulé
                                    @endif
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm" role="group">
                                        <a href="{{ route('engagements.show', $engagement) }}" 
                                           class="btn btn-outline-primary" 
                                           title="Voir">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <a href="{{ route('engagements.edit', $engagement) }}" 
                                           class="btn btn-outline-warning" 
                                           title="Modifier">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <form action="{{ route('engagements.destroy', $engagement) }}" 
                                              method="POST" 
                                              class="d-inline"
                                              class="delete-form"
                                              data-message="Êtes-vous sûr de vouloir supprimer cet engagement ?">
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
            @if($engagements->hasPages() || $engagements->total() > 0)
                <div class="d-flex justify-content-end mt-3">
                    <div class="pagination-custom">
                        {{ $engagements->links() }}
                    </div>
                </div>
            @endif
        @else
            <div class="text-center py-3">
                <i class="bi bi-inbox" style="font-size: 1.5rem; color: #ccc;"></i>
                <p class="text-muted mt-2 mb-2" style="font-size: 0.75rem;">Aucun engagement enregistré</p>
                <a href="{{ route('engagements.create') }}" class="btn btn-primary btn-sm">
                    <i class="bi bi-plus-circle"></i> Créer le premier engagement
                </a>
            </div>
        @endif
    </div>
</div>
@endsection
