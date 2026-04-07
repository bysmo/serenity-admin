@extends('layouts.app')

@section('title', 'Tag des cagnottes: ' . $tag)

@section('content')
<div class="page-header">
    <h1>
        <i class="bi bi-tags"></i> Tag des cagnottes: 
        <span class="badge bg-info">{{ $tag }}</span>
    </h1>
</div>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="bi bi-receipt"></i> Liste des cagnottes ({{ $cotisations->total() }})</span>
        <a href="{{ route('tags.index') }}" class="btn btn-light btn-sm">
            <i class="bi bi-arrow-left"></i> Retour
        </a>
    </div>
    <div class="card-body">
        @if($cotisations->count() > 0)
            <style>
                .table-cotisations-tag thead th {
                    padding: 0.15rem 0.35rem !important;
                    font-size: 0.6rem !important;
                    line-height: 1.05 !important;
                    vertical-align: middle !important;
                    font-weight: 300 !important;
                    font-family: 'Ubuntu', sans-serif !important;
                    color: var(--primary-dark-blue) !important;
                }
                .table-cotisations-tag tbody td {
                    padding: 0.15rem 0.35rem !important;
                    font-size: 0.65rem !important;
                    line-height: 1.05 !important;
                    vertical-align: middle !important;
                    border-bottom: 1px solid #f0f0f0 !important;
                    font-weight: 300 !important;
                    font-family: 'Ubuntu', sans-serif !important;
                    color: var(--primary-dark-blue) !important;
                }
                .table-cotisations-tag .btn {
                    padding: 0.1rem 0.25rem !important;
                    font-size: 0.55rem !important;
                    line-height: 1.1 !important;
                }
                .table-cotisations-tag .btn i {
                    font-size: 0.65rem !important;
                }
                .table-cotisations-tag tbody tr:last-child td {
                    border-bottom: none !important;
                }
                table.table.table-cotisations-tag.table-hover tbody tr {
                    background-color: #ffffff !important;
                    transition: background-color 0.2s ease !important;
                }
                table.table.table-cotisations-tag.table-hover tbody tr:nth-child(even) {
                    background-color: #d4dde8 !important;
                }
                table.table.table-cotisations-tag.table-hover tbody tr:hover {
                    background-color: #b8c7d9 !important;
                    cursor: pointer !important;
                }
                table.table.table-cotisations-tag.table-hover tbody tr:nth-child(even):hover {
                    background-color: #9fb3cc !important;
                }
            </style>
            <div class="table-responsive">
                <table class="table table-cotisations-tag table-striped table-hover">
                    <thead>
                        <tr>
                            <th>Numéro</th>
                            <th>Nom</th>
                            <th>Caisse</th>
                            <th>Type</th>
                            <th>Montant</th>
                            <th>Statut</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($cotisations as $cotisation)
                            <tr>
                                <td>{{ $cotisation->numero ?? '-' }}</td>
                                <td>{{ $cotisation->nom }}</td>
                                <td>{{ $cotisation->caisse->nom ?? '-' }}</td>
                                <td>{{ ucfirst($cotisation->type) }}</td>
                                <td>
                                    @if($cotisation->type_montant === 'fixe' && $cotisation->montant)
                                        {{ number_format($cotisation->montant, 0, ',', ' ') }} XOF
                                    @else
                                        <span class="text-muted">Libre</span>
                                    @endif
                                </td>
                                <td>
                                    @if($cotisation->actif)
                                        <i class="bi bi-check-circle"></i> Actif
                                    @else
                                        <i class="bi bi-x-circle"></i> Inactif
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
                <p class="text-muted mt-2 mb-2" style="font-size: 0.75rem;">Aucune cotisation avec ce tag</p>
            </div>
        @endif
    </div>
</div>
@endsection
