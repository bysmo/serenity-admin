@extends('layouts.app')

@section('title', 'Journal / Balance des comptes')

@section('content')
<div class="page-header">
    <h1><i class="bi bi-journal-text"></i> Journal / Balance des comptes</h1>
</div>

<div class="card">
    <div class="card-header">
        <i class="bi bi-list-ul"></i> Sélectionner un compte
    </div>
    <div class="card-body">
        <p class="text-muted" style="font-size: 0.8rem; margin-bottom: 1rem;">
            Sélectionnez un compte pour consulter son journal des mouvements et sa balance.
        </p>
        
        @if($caisses->count() > 0)
            <style>
                .table-journal {
                    margin-bottom: 0;
                }
                .table-journal thead th {
                    padding: 0.15rem 0.35rem !important;
                    font-size: 0.6rem !important;
                    line-height: 1.05 !important;
                    vertical-align: middle !important;
                    font-weight: 300 !important;
                    font-family: 'Ubuntu', sans-serif !important;
                    color: var(--primary-dark-blue) !important;
                }
                .table-journal tbody td {
                    padding: 0.15rem 0.35rem !important;
                    font-size: 0.65rem !important;
                    line-height: 1.05 !important;
                    vertical-align: middle !important;
                    border-bottom: 1px solid #f0f0f0 !important;
                    font-weight: 300 !important;
                    font-family: 'Ubuntu', sans-serif !important;
                    color: var(--primary-dark-blue) !important;
                }
                .table-journal tbody tr:last-child td {
                    border-bottom: none !important;
                }
                table.table.table-journal.table-hover tbody tr {
                    background-color: #ffffff !important;
                    transition: background-color 0.2s ease !important;
                }
                table.table.table-journal.table-hover tbody tr:nth-child(even) {
                    background-color: #d4dde8 !important;
                }
                table.table.table-journal.table-hover tbody tr:hover {
                    background-color: #b8c7d9 !important;
                    cursor: pointer !important;
                }
                table.table.table-journal.table-hover tbody tr:nth-child(even):hover {
                    background-color: #9fb3cc !important;
                }
                .table-journal .btn {
                    padding: 0.1rem 0.25rem !important;
                    font-size: 0.55rem !important;
                    line-height: 1.1 !important;
                }
                .table-journal .btn i {
                    font-size: 0.65rem !important;
                }
            </style>
            <div class="table-responsive">
                <table class="table table-journal table-striped table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Numéro</th>
                            <th>Nom</th>
                            <th>Description</th>
                            <th>Solde Actuel</th>
                            <th>Statut</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($caisses as $caisse)
                            <tr>
                                <td>{{ $caisse->id }}</td>
                                <td>{{ $caisse->numero ?? '-' }}</td>
                                <td>{{ $caisse->nom }}</td>
                                <td>{{ $caisse->description ?? '-' }}</td>
                                <td>
                                    {{ number_format((float) ($caisse->solde_actuel ?? 0), 0, ',', ' ') }} XOF
                                </td>
                                <td>
                                    @if($caisse->statut === 'active')
                                        <i class="bi bi-check-circle"></i> Active
                                    @else
                                        <i class="bi bi-x-circle"></i> Inactive
                                    @endif
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm" role="group">
                                        <a href="{{ route('caisses.mouvements', $caisse) }}" class="btn btn-outline-primary" title="Voir le journal">
                                            <i class="bi bi-journal-text"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="text-center py-3">
                <i class="bi bi-inbox" style="font-size: 1.5rem; color: #ccc;"></i>
                <p class="text-muted mt-2 mb-0" style="font-size: 0.75rem;">Aucune caisse active disponible</p>
            </div>
        @endif
    </div>
</div>
@endsection
