@extends('layouts.app')

@section('title', 'Plans de tontine')

@section('content')
<div class="page-header">
    <h1><i class="bi bi-piggy-bank"></i> Plans de tontine</h1>
</div>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="bi bi-list-ul"></i> Liste des plans</span>
        <a href="{{ route('epargne-plans.create') }}" class="btn btn-primary btn-sm">
            <i class="bi bi-plus-circle"></i> Nouveau plan
        </a>
    </div>
    <div class="card-body">
        <form method="GET" action="{{ route('epargne-plans.index') }}" class="mb-3">
            <div class="d-flex flex-nowrap align-items-center gap-2 w-100">
                <label class="form-label small mb-0 text-nowrap flex-shrink-0">Recherche</label>
                <input type="text"
                       name="search"
                       class="form-control form-control-sm flex-grow-1"
                       placeholder="Nom ou description..."
                       value="{{ request('search') }}"
                       style="min-width: 0;">
                <label class="form-label small mb-0 text-nowrap flex-shrink-0">Statut</label>
                <select name="actif" class="form-select form-select-sm flex-shrink-0" style="width: 200px;">
                    <option value="">Tous</option>
                    <option value="1" {{ request('actif') === '1' ? 'selected' : '' }}>Actifs</option>
                    <option value="0" {{ request('actif') === '0' ? 'selected' : '' }}>Inactifs</option>
                </select>
                <button type="submit" class="btn btn-primary btn-sm flex-shrink-0">
                    <i class="bi bi-search"></i> Filtrer
                </button>
                @if(request()->hasAny(['search', 'actif']))
                    <a href="{{ route('epargne-plans.index') }}" class="btn btn-secondary btn-sm flex-shrink-0">
                        <i class="bi bi-x-circle"></i> Effacer
                    </a>
                @endif
            </div>
        </form>

        @if($plans->count() > 0)
            <style>
                .table-epargne thead th {
                    padding: 0.15rem 0.35rem !important;
                    font-size: 0.6rem !important;
                    line-height: 1.05 !important;
                    vertical-align: middle !important;
                    font-weight: 300 !important;
                    font-family: 'Ubuntu', sans-serif !important;
                    color: var(--primary-dark-blue) !important;
                }
                .table-epargne tbody td {
                    padding: 0.15rem 0.35rem !important;
                    font-size: 0.65rem !important;
                    line-height: 1.05 !important;
                    vertical-align: middle !important;
                    border-bottom: 1px solid #f0f0f0 !important;
                    font-weight: 300 !important;
                    font-family: 'Ubuntu', sans-serif !important;
                    color: var(--primary-dark-blue) !important;
                }
                .table-epargne .btn {
                    padding: 0 !important;
                    font-size: 0.5rem !important;
                    line-height: 1 !important;
                    height: 18px !important;
                    width: 22px !important;
                    display: inline-flex !important;
                    align-items: center !important;
                    justify-content: center !important;
                }
                .table-epargne .btn i { font-size: 0.6rem !important; }
                .table-epargne tbody tr:last-child td { border-bottom: none !important; }
                table.table.table-epargne.table-hover tbody tr { background-color: #ffffff !important; }
                table.table.table-epargne.table-hover tbody tr:nth-child(even) { background-color: #d4dde8 !important; }
                table.table.table-epargne.table-hover tbody tr:hover { background-color: #b8c7d9 !important; cursor: pointer !important; }
            </style>
            <div class="table-responsive">
                <table class="table table-epargne table-striped table-hover">
                    <thead>
                        <tr>
                            <th>Nom</th>
                            <th>Fréquence</th>
                            <th class="text-end">Montant min</th>
                            <th class="text-end">Montant max</th>
                            <th class="text-center">Taux</th>
                            <th class="text-center">Durée</th>
                            <th>Caisse</th>
                            <th class="text-center">Ordre</th>
                            <th class="text-center">Souscriptions</th>
                            <th class="text-center">Statut</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($plans as $plan)
                            <tr>
                                <td>{{ $plan->nom }}</td>
                                <td>{{ $plan->frequence_label }}</td>
                                <td class="text-end">{{ number_format($plan->montant_min, 0, ',', ' ') }} XOF</td>
                                <td class="text-end">{{ $plan->montant_max ? number_format($plan->montant_max, 0, ',', ' ') . ' XOF' : '—' }}</td>
                                <td class="text-center">{{ number_format($plan->taux_remuneration ?? 0, 1, ',', ' ') }} %</td>
                                <td class="text-center">{{ $plan->duree_mois ?? 12 }} mois</td>
                                <td>{{ $plan->caisse ? $plan->caisse->nom : '—' }}</td>
                                <td class="text-center">{{ $plan->ordre }}</td>
                                <td class="text-center">{{ $plan->souscriptions_count ?? 0 }}</td>
                                <td class="text-center">
                                    @if($plan->actif)
                                        <span class="badge bg-success">Actif</span>
                                    @else
                                        <span class="badge bg-secondary">Inactif</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <div class="btn-group btn-group-sm" role="group">
                                        @if(auth()->user()->hasRole('admin') && auth()->user()->hasPermission('epargne.edit'))
                                        <a href="{{ route('epargne-plans.edit', $plan) }}" class="btn btn-sm btn-outline-warning" title="Modifier">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        @endif
                                        @if(auth()->user()->hasRole('admin') && auth()->user()->hasPermission('epargne.delete'))
                                        <form action="{{ route('epargne-plans.destroy', $plan) }}"
                                              method="POST"
                                              class="d-inline delete-form"
                                              data-message="Êtes-vous sûr de vouloir supprimer ce plan de tontine ?">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Supprimer">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="text-center py-4">
                <i class="bi bi-piggy-bank" style="font-size: 2rem; color: #ccc;"></i>
                <p class="text-muted mt-2 mb-2">Aucun plan de tontine défini</p>
                @if(auth()->user()->hasRole('admin') || auth()->user()->hasRole('tresorier'))
                <!--a href="{{ route('epargne-plans.create') }}" class="btn btn-primary btn-sm">
                    <i class="bi bi-plus-circle"></i> Créer un plan
                </a-->
                @endif
            </div>
        @endif
    </div>
</div>
@endsection
