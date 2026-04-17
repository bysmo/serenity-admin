@extends('layouts.membre')

@section('title', 'Mes Engagements')

@section('content')
<div class="page-header">
    <h1><i class="bi bi-clipboard-check"></i> Mes Engagements</h1>
</div>

<div class="card">
    <div class="card-header">
        <i class="bi bi-list-ul"></i> Liste de mes engagements
    </div>
    <div class="card-body">
        @if($engagements->count() > 0)
            <style>
                .table-engagements-membre {
                    margin-bottom: 0;
                }
                .table-engagements-membre thead th {
                    padding: 0.15rem 0.35rem !important;
                    font-size: 0.6rem !important;
                    line-height: 1.05 !important;
                    vertical-align: middle !important;
                    font-weight: 300 !important;
                    font-family: 'Ubuntu', sans-serif !important;
                    color: white !important;
                    background-color: var(--primary-dark-blue) !important;
                }
                .table-engagements-membre tbody td {
                    padding: 0.15rem 0.35rem !important;
                    font-size: 0.65rem !important;
                    line-height: 1.05 !important;
                    vertical-align: middle !important;
                    border-bottom: 1px solid #f0f0f0 !important;
                    font-weight: 300 !important;
                    font-family: 'Ubuntu', sans-serif !important;
                    color: var(--primary-dark-blue) !important;
                }
                .table-engagements-membre tbody tr:last-child td {
                    border-bottom: none !important;
                }
                .table-engagements-membre .btn {
                    padding: 0 !important;
                    font-size: 0.5rem !important;
                    line-height: 1 !important;
                    height: 18px !important;
                    width: 22px !important;
                    display: inline-flex !important;
                    align-items: center !important;
                    justify-content: center !important;
                }
                .table-engagements-membre .btn i {
                    font-size: 0.6rem !important;
                    line-height: 1 !important;
                }
                .table-engagements-membre .btn-group-sm > .btn,
                .table-engagements-membre .btn-group > .btn {
                    border-radius: 0.2rem !important;
                }
                table.table.table-engagements-membre.table-hover tbody tr {
                    background-color: #ffffff !important;
                    transition: background-color 0.2s ease !important;
                }
                table.table.table-engagements-membre.table-hover tbody tr:nth-child(even) {
                    background-color: #d4dde8 !important;
                }
                table.table.table-engagements-membre.table-hover tbody tr:hover {
                    background-color: #b8c7d9 !important;
                    cursor: pointer !important;
                }
                table.table.table-engagements-membre.table-hover tbody tr:nth-child(even):hover {
                    background-color: #9fb3cc !important;
                }
            </style>
            <div class="table-responsive">
                <table class="table table-engagements-membre table-striped table-hover">
                    <thead>
                        <tr>
                            <th>Numéro</th>
                            <th>Tontine</th>
                            <th>Montant</th>
                            <th>Date échéance</th>
                            <th>Statut</th>
                            <th>Montant payé</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($engagements as $engagement)
                            <tr>
                                <td>{{ $engagement->numero ?? '-' }}</td>
                                <td>{{ $engagement->cotisation->nom ?? '-' }}</td>
                                <td>
                                    {{ number_format($engagement->montant_engage, 0, ',', ' ') }} XOF
                                </td>
                                <td>{{ $engagement->periode_fin ? $engagement->periode_fin->format('d/m/Y') : '-' }}</td>
                                <td>
                                    {{ ucfirst(str_replace('_', ' ', $engagement->statut)) }}
                                </td>
                                <td>
                                    {{ number_format($engagement->montant_paye ?? 0, 0, ',', ' ') }} XOF
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm" role="group">
                                        <a href="{{ route('membre.engagements.show', $engagement->id) }}" 
                                           class="btn btn-outline-primary" 
                                           title="Voir les détails">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        @php
                                            $montantPaye = $engagement->montant_paye ?? 0;
                                            $resteAPayer = $engagement->montant_engage - $montantPaye;
                                        @endphp
                                        @if(in_array($engagement->statut, ['en_cours', 'en_retard']) && $resteAPayer > 0)
                                            <a href="{{ route('membre.engagements.show', $engagement->id) }}?payment=true" 
                                               class="btn btn-outline-success" 
                                               title="Payer">
                                                <i class="bi bi-credit-card"></i>
                                            </a>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            
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
                <p class="text-muted mt-2 mb-0" style="font-size: 0.75rem;">Aucun engagement enregistré</p>
            </div>
        @endif
    </div>
</div>
@endsection
