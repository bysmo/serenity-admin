@extends('layouts.app')

@section('title', 'Gestion des Garants')

@section('content')
<div class="row mb-4">
    <div class="col-md-6">
        <h1 class="h3 mb-0 text-gray-800">Gestion des Garants</h1>
        <p class="mb-0 text-muted">Liste des clients et leur qualité de garant pour les nano-crédits.</p>
    </div>
</div>

<div class="card shadow mb-4">
    <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
        <h6 class="m-0 font-weight-bold text-primary">Répertoire des Garants</h6>
        <form action="{{ route('nano-credits.garants.index') }}" method="GET" class="d-none d-sm-inline-block form-inline mr-auto ml-md-3 my-2 my-md-0 mw-100 navbar-search">
            <div class="input-group">
                <input type="text" name="search" value="{{ request('search') }}" class="form-control bg-light border-0 small" placeholder="Rechercher un garant..." aria-label="Search" aria-describedby="basic-addon2">
                <div class="input-group-append">
                    <button class="btn btn-primary" type="submit">
                        <i class="fas fa-search fa-sm"></i>
                    </button>
                </div>
            </div>
        </form>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered table-hover" id="dataTable" width="100%" cellspacing="0">
                <thead class="thead-light">
                    <tr>
                        <th>Membre</th>
                        <th>Téléphone</th>
                        <th>Solde Tontine</th>
                        <th>Garanties Actives</th>
                        <th>Qualité Garant</th>
                        <th>Solde Sous-compte</th>
                        <th>Statut</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($membres as $membre)
                        <tr>
                            <td>
                                <strong>{{ $membre->nom_complet }}</strong>
                                <br>
                                <small class="text-muted"># {{ $membre->numero }}</small>
                            </td>
                            <td>{{ $membre->telephone }}</td>
                            <td class="text-right font-weight-bold text-success">
                                {{ number_format($membre->totalEpargneSolde(), 0, ',', ' ') }} XOF
                            </td>
                            <td class="text-center">
                                <span class="badge badge-{{ $membre->garanties_actives > 0 ? 'warning' : 'secondary' }}">
                                    {{ $membre->garanties_actives }} / {{ $membre->maximumGaranties() }}
                                </span>
                            </td>
                            <td class="text-center">
                                <div class="d-flex align-items-center justify-content-center">
                                    <span class="mr-2">{{ $membre->garant_qualite }}</span>
                                    <div class="progress progress-sm w-100" style="max-width: 100px;">
                                        <div class="progress-bar bg-{{ $membre->garant_qualite > 2 ? 'success' : ($membre->garant_qualite > 0 ? 'info' : 'danger') }}" role="progressbar" style="width: {{ min(100, $membre->garant_qualite * 10) }}%" aria-valuenow="{{ $membre->garant_qualite }}" aria-valuemin="0" aria-valuemax="10"></div>
                                    </div>
                                </div>
                            </td>
                            <td class="text-right font-weight-bold text-primary">
                                {{ number_format($membre->garant_solde, 0, ',', ' ') }} XOF
                            </td>
                            <td class="text-center">
                                @if($membre->isEpargneBloquee())
                                    <span class="badge badge-danger">
                                        <i class="fas fa-lock small"></i> Épargne Bloquée
                                    </span>
                                @else
                                    <span class="badge badge-success">
                                        <i class="fas fa-unlock small"></i> Épargne Libre
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-4">Aucun garant trouvé.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">
            {{ $membres->appends(request()->query())->links() }}
        </div>
    </div>
</div>
@endsection
