@extends('layouts.app')

@section('title', 'Demandes de nano crédit')

@section('content')
<div class="page-header d-flex justify-content-between align-items-center flex-wrap">
    <h1><i class="bi bi-inbox"></i> Demandes de nano crédit</h1>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show">{{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif
@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show">{{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

<div class="card">
    <div class="card-header"><i class="bi bi-list-ul"></i> Liste des demandes</div>
    <div class="card-body">
        <form method="GET" action="{{ route('nano-credits.index') }}" class="mb-3">
            <div class="row g-2 align-items-end">
                <div class="col-md-6">
                    <input type="text" name="search" class="form-control form-control-sm" placeholder="Client, téléphone, transaction..." value="{{ request('search') }}">
                </div>
                <div class="col-md-3">
                    <select name="statut" class="form-select form-select-sm">
                        <option value="">Tous les statuts</option>
                        @foreach(\App\Models\NanoCredit::statutLabels() as $value => $label)
                            <option value="{{ $value }}" {{ request('statut') === $value ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary btn-sm w-100"><i class="bi bi-search"></i> Filtrer</button>
                </div>
            </div>
        </form>

        @if($nanoCredits->count() > 0)
            <style>
                .table-nano-demandes thead th {
                    padding: 0.15rem 0.35rem !important;
                    font-size: 0.6rem !important;
                    line-height: 1.05 !important;
                    vertical-align: middle !important;
                    font-weight: 300 !important;
                    font-family: 'Ubuntu', sans-serif !important;
                    color: var(--primary-dark-blue) !important;
                }
                .table-nano-demandes tbody td {
                    padding: 0.15rem 0.35rem !important;
                    font-size: 0.65rem !important;
                    line-height: 1.05 !important;
                    vertical-align: middle !important;
                    border-bottom: 1px solid #f0f0f0 !important;
                    font-weight: 300 !important;
                    font-family: 'Ubuntu', sans-serif !important;
                    color: var(--primary-dark-blue) !important;
                }
                .table-nano-demandes .btn {
                    padding: 0 0.35rem !important;
                    font-size: 0.5rem !important;
                    line-height: 1 !important;
                    height: 18px !important;
                    font-weight: 300 !important;
                }
                .table-nano-demandes .btn i { font-size: 0.6rem !important; }
                .table-nano-demandes tbody tr:last-child td { border-bottom: none !important; }
                table.table.table-nano-demandes.table-hover tbody tr { background-color: #fff !important; }
                table.table.table-nano-demandes.table-hover tbody tr:nth-child(even) { background-color: #d4dde8 !important; }
                table.table.table-nano-demandes.table-hover tbody tr:hover { background-color: #b8c7d9 !important; cursor: pointer !important; }
            </style>
            <div class="table-responsive">
                <table class="table table-nano-demandes table-striped table-hover">
                    <thead>
                        <tr>
                            <th>Date demande</th>
                            <th>Client</th>
                            <th>Palier</th>
                            <th class="text-center">Risque</th>
                            <th class="text-end">Montant</th>
                            <th>Téléphone</th>
                            <th>Statut</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($nanoCredits as $nc)
                            <tr>
                                <td>{{ $nc->created_at->format('d/m/Y H:i') }}</td>
                                <td>{{ $nc->membre->nom_complet ?? '—' }}</td>
                                <td>
                                    @if($nc->palier)
                                        <span class="badge bg-light text-primary border fw-normal" style="font-size: 0.55rem;">P{{ $nc->palier->numero }} : {{ $nc->palier->nom }}</span>
                                    @else
                                        —
                                    @endif
                                </td>
                                <td class="text-center">
                                    <span class="badge {{ $nc->score_global <= 1 ? 'bg-success' : ($nc->score_global <= 3 ? 'bg-warning text-dark' : 'bg-danger') }} fw-normal" style="font-size: 0.55rem;">
                                        {{ $nc->score_global ?? 'N/A' }} / 6
                                    </span>
                                </td>
                                <td class="text-end text-primary fw-bold">{{ number_format($nc->montant, 0, ',', ' ') }} XOF</td>
                                <td>{{ $nc->telephone }}</td>
                                <td>
                                    @if(in_array($nc->statut, ['demande_en_attente', 'en_etude']))
                                        <span class="badge bg-warning text-dark">{{ $nc->statut_label }}</span>
                                    @elseif(in_array($nc->statut, ['debourse', 'en_remboursement', 'success']))
                                        <span class="badge bg-success">{{ $nc->statut_label }}</span>
                                    @elseif(in_array($nc->statut, ['refuse', 'failed']))
                                        <span class="badge bg-danger">{{ $nc->statut_label }}</span>
                                    @elseif($nc->statut === 'rembourse')
                                        <span class="badge bg-info">{{ $nc->statut_label }}</span>
                                    @else
                                        <span class="badge bg-secondary">{{ $nc->statut_label }}</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('nano-credits.show', $nc) }}" class="btn btn-sm btn-primary"><i class="bi bi-gear"></i> Traiter</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="d-flex justify-content-center mt-2">{{ $nanoCredits->links() }}</div>
        @else
            <p class="text-muted mb-0">Aucune demande de nano crédit.</p>
        @endif
    </div>
</div>
@endsection
