@extends('layouts.app')

@section('title', 'Gestion des Membres')

@section('content')
<div class="page-header">
    <h1><i class="bi bi-people"></i> Gestion des Membres</h1>
</div>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="bi bi-list-ul"></i> Liste des Membres</span>
        <a href="{{ route('membres.create') }}" class="btn btn-light btn-sm">
            <i class="bi bi-plus-circle"></i> Nouveau Membre
        </a>
    </div>
    <div class="card-body">
        <!-- Barre de recherche -->
        <form method="GET" action="{{ route('membres.index') }}" class="mb-3">
            <div class="row g-2">
                <div class="col-md-10">
                    <input type="text" 
                           name="search" 
                           class="form-control form-control-sm" 
                           placeholder="Rechercher par nom, prénom, email ou numéro..." 
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
                    <a href="{{ route('membres.index') }}" class="btn btn-secondary btn-sm">
                        <i class="bi bi-x-circle"></i> Effacer
                    </a>
                </div>
            @endif
        </form>
        
        @if($membres->count() > 0)
            <style>
                .table-membres thead th {
                    padding: 0.15rem 0.35rem !important;
                    font-size: 0.6rem !important;
                    line-height: 1.05 !important;
                    vertical-align: middle !important;
                    font-weight: 300 !important;
                    font-family: 'Ubuntu', sans-serif !important;
                    color: var(--primary-dark-blue) !important;
                }
                .table-membres tbody td {
                    padding: 0.15rem 0.35rem !important;
                    font-size: 0.65rem !important;
                    line-height: 1.05 !important;
                    vertical-align: middle !important;
                    border-bottom: 1px solid #f0f0f0 !important;
                    font-weight: 300 !important;
                    font-family: 'Ubuntu', sans-serif !important;
                    color: var(--primary-dark-blue) !important;
                }
                .table-membres .btn {
                    padding: 0.1rem 0.25rem !important;
                    font-size: 0.55rem !important;
                    line-height: 1.1 !important;
                }
                .table-membres .btn i {
                    font-size: 0.65rem !important;
                }
                .table-membres tbody tr:last-child td {
                    border-bottom: none !important;
                }
                table.table.table-membres.table-hover tbody tr {
                    background-color: #ffffff !important;
                    transition: background-color 0.2s ease !important;
                }
                table.table.table-membres.table-hover tbody tr:nth-child(even) {
                    background-color: #d4dde8 !important;
                }
                table.table.table-membres.table-hover tbody tr:hover {
                    background-color: #b8c7d9 !important;
                    cursor: pointer !important;
                }
                table.table.table-membres.table-hover tbody tr:nth-child(even):hover {
                    background-color: #9fb3cc !important;
                }
            </style>
            <div class="table-responsive">
                <table class="table table-membres table-striped table-hover">
                    <thead>
                        <tr>
                            <th>Numéro</th>
                            <th>Nom & Prénom</th>
                            <th>Segment</th>
                            <th>Email / Téléphone</th>
                            <th>Date d'adhésion</th>
                            <th>Statut</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($membres as $membre)
                            <tr>
                                <td>{{ $membre->numero ?? '-' }}</td>
                                <td>
                                    <div class="fw-bold">{{ $membre->nom }} {{ $membre->prenom }}</div>
                                </td>
                                <td>
                                    @if($membre->segment)
                                        <span class="badge border" style="background-color: {{ $membre->segment->couleur }}11; color: {{ $membre->segment->couleur }}; font-size: 0.6rem;">
                                            <i class="{{ $membre->segment->icone ?: 'bi bi-tag' }} me-1"></i>{{ $membre->segment->nom }}
                                        </span>
                                    @else
                                        <span class="badge bg-light text-muted border small" style="font-size: 0.6rem;">AUCUN</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="small">{{ $membre->email }}</div>
                                    <div class="small text-muted">{{ $membre->telephone ?? '-' }}</div>
                                </td>
                                <td>{{ $membre->date_adhesion ? $membre->date_adhesion->format('d/m/Y') : '-' }}</td>
                                <td>
                                    @if($membre->statut === 'actif')
                                        <span class="badge bg-success-subtle text-success border border-success" style="font-size: 0.6rem;">ACTIF</span>
                                    @elseif($membre->statut === 'suspendu')
                                        <span class="badge bg-danger-subtle text-danger border border-danger" style="font-size: 0.6rem;">SUSPENDU</span>
                                    @else
                                        <span class="badge bg-warning-subtle text-warning border border-warning" style="font-size: 0.6rem;">{{ strtoupper($membre->statut) }}</span>
                                    @endif
                                </td>       
                                <td class="text-center">
                                    <div class="btn-group btn-group-sm" role="group">
                                        <a href="{{ route('membres.show', $membre) }}" 
                                           class="btn btn-light border" 
                                           title="Voir">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <a href="{{ route('membres.edit', $membre) }}" 
                                           class="btn btn-light border" 
                                           title="Modifier">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <form action="{{ route('membres.destroy', $membre) }}" 
                                              method="POST" 
                                              class="delete-form d-inline"
                                              data-message="Êtes-vous sûr de vouloir supprimer ce membre ?">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" 
                                                    class="btn btn-light border text-danger" 
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
            @if($membres->hasPages() || $membres->total() > 0)
                <div class="d-flex justify-content-end mt-3">
                    <div class="pagination-custom">
                        {{ $membres->links() }}
                    </div>
                </div>
            @endif
        @else
            <div class="text-center py-3">
                <i class="bi bi-inbox" style="font-size: 1.5rem; color: #ccc;"></i>
                <p class="text-muted mt-2 mb-2" style="font-size: 0.75rem;">Aucun membre enregistré</p>
                <a href="{{ route('membres.create') }}" class="btn btn-primary btn-sm">
                    <i class="bi bi-plus-circle"></i> Créer le premier membre
                </a>
            </div>
        @endif
    </div>
</div>
@endsection
