@extends('layouts.app')

@section('title', 'Gestion des Utilisateurs')

@section('content')
<div class="page-header">
    <h1><i class="bi bi-person-badge"></i> Gestion des Utilisateurs</h1>
</div>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="bi bi-list-ul"></i> Liste des Utilisateurs</span>
        <a href="{{ route('users.create') }}" class="btn btn-light btn-sm">
            <i class="bi bi-plus-circle"></i> Nouvel Utilisateur
        </a>
    </div>
    <div class="card-body">
        <!-- Barre de recherche -->
        <form method="GET" action="{{ route('users.index') }}" class="mb-3">
            <div class="row g-2">
                <div class="col-md-10">
                    <input type="text" 
                           name="search" 
                           class="form-control form-control-sm" 
                           placeholder="Rechercher par nom ou email..." 
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
                    <a href="{{ route('users.index') }}" class="btn btn-secondary btn-sm">
                        <i class="bi bi-x-circle"></i> Effacer
                    </a>
                </div>
            @endif
        </form>
        
        @if($users->count() > 0)
            <style>
                .table-users thead th {
                    padding: 0.15rem 0.35rem !important;
                    font-size: 0.6rem !important;
                    line-height: 1.05 !important;
                    vertical-align: middle !important;
                    font-weight: 300 !important;
                    font-family: 'Ubuntu', sans-serif !important;
                    color: var(--primary-dark-blue) !important;
                    position: sticky;
                    top: 0;
                    background-color: #f8f9fa !important;
                    z-index: 10;
                }
                
                .table-users tbody td {
                    padding: 0.15rem 0.35rem !important;
                    font-size: 0.65rem !important;
                    line-height: 1.05 !important;
                    vertical-align: middle !important;
                    font-weight: 300 !important;
                    font-family: 'Ubuntu', sans-serif !important;
                    color: var(--primary-dark-blue) !important;
                }
                
                .table-users tbody tr:nth-child(even) {
                    background-color: rgba(30, 58, 95, 0.08) !important;
                }
                
                .table-users tbody tr:hover {
                    background-color: rgba(30, 58, 95, 0.15) !important;
                    transition: background-color 0.2s ease;
                }
                
                .table-users .btn {
                    padding: 0.1rem 0.25rem !important;
                    font-size: 0.55rem !important;
                    line-height: 1.1 !important;
                    font-weight: 300 !important;
                    font-family: 'Ubuntu', sans-serif !important;
                }
                
                .table-users .btn i {
                    font-size: 0.65rem !important;
                }
            </style>
            
            <div class="table-responsive">
                <table class="table table-hover table-striped table-users">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nom</th>
                            <th>Email</th>
                            <th>Rôles</th>
                            <th>Date de création</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($users as $user)
                            <tr>
                                <td>{{ $user->id }}</td>
                                <td>{{ $user->name }}</td>
                                <td>{{ $user->email }}</td>
                                <td>
                                    @if($user->roles->count() > 0)
                                        @foreach($user->roles as $role)
                                            <small class="d-inline-block me-1">{{ $role->nom }}</small>
                                            @if(!$loop->last) / @endif
                                        @endforeach
                                    @else
                                        <small class="text-muted">Aucun rôle</small>
                                    @endif
                                </td>
                                <td>{{ $user->created_at->format('d/m/Y') }}</td>
                                <td>
                                    <div class="btn-group btn-group-sm" role="group">
                                        <a href="{{ route('users.show', $user) }}" class="btn btn-outline-primary" title="Voir">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <a href="{{ route('users.edit', $user) }}" class="btn btn-outline-primary" title="Modifier">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <button type="button" 
                                                class="btn btn-outline-danger" 
                                                title="Supprimer"
                                                onclick="confirmDelete({{ $user->id }}, '{{ $user->name }}', '{{ route('users.destroy', $user) }}')">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <div class="d-flex justify-content-end mt-3">
                {{ $users->links() }}
            </div>
        @else
            <div class="alert alert-info text-center">
                <i class="bi bi-info-circle"></i> Aucun utilisateur trouvé.
            </div>
        @endif
    </div>
</div>

<script>
function confirmDelete(id, name, url) {
    const modal = new bootstrap.Modal(document.getElementById('confirmModal'));
    const modalBody = document.querySelector('#confirmModal .modal-body');
    const modalForm = document.querySelector('#confirmModal form');
    
    modalBody.innerHTML = `<p>Êtes-vous sûr de vouloir supprimer l'utilisateur <strong>${escapeHtml(name)}</strong> ?</p><p class="text-danger"><small>Cette action est irréversible.</small></p>`;
    modalForm.action = url;
    
    modal.show();
}
</script>
@endsection
