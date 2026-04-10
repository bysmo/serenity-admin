@extends('layouts.app')

@section('title', 'Journal d\'Audit')

@section('content')
<div class="page-header">
    <h1><i class="bi bi-journal-text"></i> Journal d'Audit</h1>
</div>

<div class="card">
    <div class="card-header">
        <i class="bi bi-filter"></i> Filtres
    </div>
    <div class="card-body">
        <form method="GET" action="{{ route('audit-logs.index') }}" class="row g-3">
            <div class="col-md-3">
                <label for="action" class="form-label">Action</label>
                <select class="form-select form-select-sm" id="action" name="action">
                    <option value="">Toutes</option>
                    @foreach($actions as $action)
                        <option value="{{ $action }}" {{ request('action') == $action ? 'selected' : '' }}>{{ $action }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label for="model" class="form-label">Modèle</label>
                <select class="form-select form-select-sm" id="model" name="model">
                    <option value="">Tous</option>
                    @foreach($models as $model)
                        <option value="{{ $model }}" {{ request('model') == $model ? 'selected' : '' }}>{{ $model }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label for="actor_id" class="form-label">Auteur</label>
                <select class="form-select form-select-sm" id="actor_id" name="actor_id">
                    <option value="">Tous</option>
                    {{-- Note: Filtrage par acteur simplifié pour la démo --}}
                </select>
            </div>
            <div class="col-md-3">
                <label for="search" class="form-label">Recherche</label>
                <input type="text" class="form-control form-control-sm" id="search" name="search" value="{{ request('search') }}" placeholder="Rechercher...">
            </div>
            <div class="col-md-3">
                <label for="date_debut" class="form-label">Date début</label>
                <input type="date" class="form-control form-control-sm" id="date_debut" name="date_debut" value="{{ request('date_debut') }}">
            </div>
            <div class="col-md-3">
                <label for="date_fin" class="form-label">Date fin</label>
                <input type="date" class="form-control form-control-sm" id="date_fin" name="date_fin" value="{{ request('date_fin') }}">
            </div>
            <div class="col-md-6 d-flex align-items-end">
                <button type="submit" class="btn btn-primary btn-sm me-2">
                    <i class="bi bi-search"></i> Filtrer
                </button>
                <a href="{{ route('audit-logs.index') }}" class="btn btn-secondary btn-sm">
                    <i class="bi bi-x-circle"></i> Réinitialiser
                </a>
            </div>
        </form>
    </div>
</div>

<div class="card mt-3">
    <div class="card-header">
        <i class="bi bi-list-ul"></i> Logs ({{ $logs->total() }})
    </div>
    <div class="card-body">
        <style>
            .table-audit-logs {
                margin-bottom: 0;
            }
            .table-audit-logs thead th {
                padding: 0.15rem 0.35rem !important;
                font-size: 0.6rem !important;
                line-height: 1.05 !important;
                vertical-align: middle !important;
                font-weight: 300 !important;
                font-family: 'Ubuntu', sans-serif !important;
                color: var(--primary-dark-blue) !important;
            }
            .table-audit-logs tbody td {
                padding: 0.15rem 0.35rem !important;
                font-size: 0.65rem !important;
                line-height: 1.05 !important;
                vertical-align: middle !important;
                border-bottom: 1px solid #f0f0f0 !important;
                font-weight: 300 !important;
                font-family: 'Ubuntu', sans-serif !important;
                color: var(--primary-dark-blue) !important;
            }
            .table-audit-logs tbody tr:last-child td {
                border-bottom: none !important;
            }
            table.table.table-audit-logs.table-hover tbody tr {
                background-color: #ffffff !important;
                transition: background-color 0.2s ease !important;
            }
            table.table.table-audit-logs.table-hover tbody tr:nth-child(even) {
                background-color: #d4dde8 !important;
            }
            table.table.table-audit-logs.table-hover tbody tr:hover {
                background-color: #b8c7d9 !important;
                cursor: pointer !important;
            }
            table.table.table-audit-logs.table-hover tbody tr:nth-child(even):hover {
                background-color: #9fb3cc !important;
            }
            .table-audit-logs .btn {
                padding: 0.05rem 0.2rem !important;
                font-size: 0.5rem !important;
                line-height: 1 !important;
                height: auto !important;
                min-height: auto !important;
            }
            .table-audit-logs .btn i {
                font-size: 0.6rem !important;
                line-height: 1 !important;
            }
            .table-audit-logs .badge {
                font-size: 0.55rem !important;
                padding: 0.15rem 0.35rem !important;
                font-weight: 300 !important;
                font-family: 'Ubuntu', sans-serif !important;
            }
            .table-audit-logs code {
                font-size: 0.6rem !important;
                padding: 0.1rem 0.25rem !important;
                font-family: 'Ubuntu', sans-serif !important;
                font-weight: 300 !important;
            }
        </style>
        <div class="table-responsive">
            <table class="table table-audit-logs table-striped table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Date/Heure</th>
                        <th>Auteur</th>
                        <th>Action</th>
                        <th>Modèle</th>
                        <th>Description</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($logs as $log)
                        <tr>
                            <td>{{ $log->id }}</td>
                            <td>{{ $log->created_at->format('d/m/Y H:i:s') }}</td>
                            <td>
                                {{ $log->actor_name }}
                                @if($log->actor_type === \App\Models\Membre::class)
                                    <span class="badge bg-secondary" style="font-size: 0.5rem;">Membre</span>
                                @elseif($log->actor_type === \App\Models\User::class)
                                    <span class="badge bg-primary" style="font-size: 0.5rem;">Admin</span>
                                @endif
                            </td>
                            <td><span class="badge bg-info">{{ $log->action }}</span></td>
                            <td><code>{{ $log->model }}</code></td>
                            <td>{{ $log->description ?? '-' }}</td>
                            <td>
                                <a href="{{ route('audit-logs.show', $log) }}" class="btn btn-sm btn-outline-primary" title="Voir">
                                    <i class="bi bi-eye"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center">Aucun log trouvé.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <!-- Pagination Bootstrap -->
        @if($logs->hasPages() || $logs->total() > 0)
            <div class="d-flex justify-content-end mt-3">
                <div class="pagination-custom">
                    {{ $logs->links() }}
                </div>
            </div>
        @endif
    </div>
</div>
@endsection
