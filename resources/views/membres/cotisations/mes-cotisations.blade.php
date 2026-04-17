@extends('layouts.membre')

@section('title', 'Mes cagnottes créées')

@section('content')
<style>
.table-mes-cotisations thead th { padding: 0.15rem 0.35rem !important; font-size: 0.65rem !important; font-weight: 300 !important; font-family: 'Ubuntu', sans-serif !important; background-color: var(--primary-dark-blue); color: #fff !important; vertical-align: middle !important; }
.table-mes-cotisations tbody td { padding: 0.15rem 0.35rem !important; font-size: 0.65rem !important; font-weight: 300 !important; font-family: 'Ubuntu', sans-serif !important; vertical-align: middle !important; color: var(--primary-dark-blue); }
.table-mes-cotisations .btn { padding: 0.15rem 0.3rem !important; font-size: 0.6rem !important; min-height: 20px !important; }
</style>
<div class="page-header d-flex justify-content-between align-items-center">
    <h1 style="font-weight: 300;"><i class="bi bi-plus-circle"></i> Mes cagnottes créées</h1>
    <a href="{{ route('membre.mes-cotisations.create') }}" class="btn btn-primary btn-sm"><i class="bi bi-plus-circle"></i> Créer une cagnotte</a>
</div>

<div class="card">
    <div class="card-header" style="padding: 0.35rem 0.6rem; font-size: 0.75rem;"><i class="bi bi-list-ul"></i> Tontines dont vous êtes l'administrateur</div>
    <div class="card-body pt-2 pb-3">
        @if($cotisations->count() > 0)
            <div class="table-responsive">
                <table class="table table-mes-cotisations table-sm table-hover mb-0">
                    <thead>
                        <tr><th>Nom</th><th>Code</th><th>Type</th><th>Public / Privé</th><th>Montant</th><th>Adhérents</th><th>Actions</th></tr>
                    </thead>
                    <tbody>
                        @foreach($cotisations as $cotisation)
                            <tr>
                                <td>{{ $cotisation->nom }}</td>
                                <td><code>{{ $cotisation->code }}</code></td>
                                <td>{{ ucfirst($cotisation->type ?? '-') }}</td>
                                <td>{{ ($cotisation->visibilite ?? 'publique') === 'publique' ? 'Public' : 'Privé' }}</td>
                                <td>{{ $cotisation->montant ? number_format((float) $cotisation->montant, 0, ',', ' ') . ' XOF' : 'Libre' }}</td>
                                <td>{{ $cotisation->adhesions()->where('statut', 'accepte')->count() }}</td>
                                <td><a href="{{ route('membre.mes-cotisations.show', $cotisation) }}" class="btn btn-sm btn-outline-info" title="Gérer"><i class="bi bi-gear"></i></a></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if($cotisations->hasPages())
                <div class="d-flex justify-content-end mt-2">{{ $cotisations->links() }}</div>
            @endif
        @else
            <div class="text-center py-4">
                <i class="bi bi-inbox" style="font-size: 2rem; color: #ccc;"></i>
                <p class="text-muted mt-2">Aucune cagnotte créée.</p>
                <a href="{{ route('membre.mes-cotisations.create') }}" class="btn btn-primary btn-sm"><i class="bi bi-plus-circle"></i> Créer ma première cagnotte</a>
            </div>
        @endif
    </div>
</div>
@endsection
