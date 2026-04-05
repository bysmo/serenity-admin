@extends('layouts.app')

@section('title', 'Paiements d\'Engagements')

@section('content')
<div class="page-header">
    <h1><i class="bi bi-clipboard-check"></i> Paiements d'Engagements</h1>
</div>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="bi bi-list-ul"></i> Liste des Engagements en Cours</span>
    </div>
    <div class="card-body">
        <!-- Barre de recherche -->
        <form method="GET" action="{{ route('paiements.engagement.index') }}" class="mb-3">
            <div class="row g-2">
                <div class="col-md-10">
                    <input type="text" 
                           name="search" 
                           class="form-control form-control-sm" 
                           placeholder="Rechercher par membre, cotisation, numéro..." 
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
                    <a href="{{ route('paiements.engagement.index') }}" class="btn btn-secondary btn-sm">
                        <i class="bi bi-x-circle"></i> Effacer
                    </a>
                </div>
            @endif
        </form>
        
        @if($engagements->count() > 0)
            <style>
                .table-paiements-engagement {
                    margin-bottom: 0;
                }
                .table-paiements-engagement thead th {
                    padding: 0.15rem 0.35rem !important;
                    font-size: 0.6rem !important;
                    line-height: 1.05 !important;
                    vertical-align: middle !important;
                    font-weight: 300 !important;
                    font-family: 'Ubuntu', sans-serif !important;
                    color: var(--primary-dark-blue) !important;
                }
                .table-paiements-engagement tbody td {
                    padding: 0.15rem 0.35rem !important;
                    font-size: 0.65rem !important;
                    line-height: 1.05 !important;
                    vertical-align: middle !important;
                    border-bottom: 1px solid #f0f0f0 !important;
                    font-weight: 300 !important;
                    font-family: 'Ubuntu', sans-serif !important;
                    color: var(--primary-dark-blue) !important;
                }
                .table-paiements-engagement tbody tr:last-child td {
                    border-bottom: none !important;
                }
                .table-paiements-engagement .btn {
                    padding: 0.1rem 0.25rem !important;
                    font-size: 0.55rem !important;
                    line-height: 1.1 !important;
                }
                .table-paiements-engagement .btn i {
                    font-size: 0.65rem !important;
                }
                table.table.table-paiements-engagement.table-hover tbody tr {
                    background-color: #ffffff !important;
                    transition: background-color 0.2s ease !important;
                }
                table.table.table-paiements-engagement.table-hover tbody tr:nth-child(even) {
                    background-color: #d4dde8 !important;
                }
                table.table.table-paiements-engagement.table-hover tbody tr:hover {
                    background-color: #b8c7d9 !important;
                    cursor: pointer !important;
                }
                table.table.table-paiements-engagement.table-hover tbody tr:nth-child(even):hover {
                    background-color: #9fb3cc !important;
                }
            </style>
            <div class="table-responsive">
                <table class="table table-paiements-engagement table-striped table-hover">
                    <thead>
                        <tr>
                            <th>Membre</th>
                            <th>Cagnotte</th>
                            <th>Montant/ période</th>
                            <th>Périodicité</th>
                            <th>Montant total</th>
                            <th>Montant payé</th>
                            <th>Reste à payer</th>
                            <th>Période</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($engagements as $engagement)
                            <tr>
                                <td>
                                    {{ $engagement->membre->nom_complet ?? '-' }}
                                </td>
                                <td>{{ $engagement->cotisation->nom ?? '-' }}</td>
                                <td>
                                    {{ number_format($engagement->montant_engage, 0, ',', ' ') }} XOF
                                </td>
                                <td>
                                    {{ ucfirst($engagement->periodicite ?? 'mensuelle') }}
                                </td>
                                <td>
                                    {{ number_format($engagement->montant_total, 0, ',', ' ') }} XOF
                                </td>
                                <td>
                                    {{ number_format($engagement->montant_paye, 0, ',', ' ') }} XOF
                                </td>
                                <td>
                                    {{ number_format($engagement->reste_a_payer, 0, ',', ' ') }} XOF
                                </td>
                                <td>
                                    {{ $engagement->periode_debut->format('d/m/Y') }} - {{ $engagement->periode_fin->format('d/m/Y') }}
                                </td>
                                <td>
                                    @if($engagement->reste_a_payer > 0)
                                        <div class="btn-group btn-group-sm" role="group">
                                            <a href="{{ route('paiements.engagement.create', $engagement) }}" 
                                               class="btn btn-outline-primary" 
                                               title="Payer">
                                                <i class="bi bi-cash-coin"></i>
                                            </a>
                                        </div>
                                    @else
                                        <i class="bi bi-check-circle"></i> Payé
                                    @endif
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
                <p class="text-muted mt-2 mb-2" style="font-size: 0.75rem;">Aucun engagement en cours</p>
            </div>
        @endif
    </div>
</div>
@endsection
