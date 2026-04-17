@extends('layouts.membre')

@section('title', 'Rechercher par code')

@section('content')
<style>
.table-demandes-adhesion { margin-bottom: 0; }
.table-demandes-adhesion thead th {
    padding: 0.15rem 0.35rem !important;
    font-size: 0.65rem !important;
    line-height: 1.05 !important;
    font-weight: 300 !important;
    font-family: 'Ubuntu', sans-serif !important;
    background-color: var(--primary-dark-blue) !important;
    color: #fff !important;
    vertical-align: middle !important;
}
.table-demandes-adhesion tbody td {
    padding: 0.15rem 0.35rem !important;
    font-size: 0.65rem !important;
    line-height: 1.05 !important;
    font-weight: 300 !important;
    font-family: 'Ubuntu', sans-serif !important;
    color: var(--primary-dark-blue) !important;
    vertical-align: middle !important;
}
.table-demandes-adhesion code { font-size: 0.65rem !important; }
.card-header-compact-rechercher { padding: 0.35rem 0.6rem !important; font-size: 0.75rem !important; font-weight: 300 !important; font-family: 'Ubuntu', sans-serif !important; }
</style>
<div class="page-header">
    <h1 style="font-weight: 300; font-family: 'Ubuntu', sans-serif;"><i class="bi bi-search"></i> Rechercher une cagnotte par code</h1>
</div>

<div class="card">
    <div class="card-header card-header-compact-rechercher"><i class="bi bi-key"></i> Entrez le code de la cagnotte</div>
    <div class="card-body py-2 pb-3">
        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show py-2 small">{{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif
        <form method="GET" action="{{ route('membre.cotisations.rechercher') }}" class="w-100">
            <div class="row g-2 align-items-end">
                <div class="col">
                    <label for="code" class="form-label mb-0 small">Code</label>
                    <input type="text" name="code" id="code" class="form-control form-control-sm text-uppercase w-100" placeholder="Ex: ABC123" value="{{ old('code', request('code')) }}" maxlength="20" required autofocus style="height: 28px; font-size: 0.75rem;">
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-primary btn-sm" style="height: 28px; line-height: 1;"><i class="bi bi-search"></i> Rechercher</button>
                </div>
            </div>
        </form>
        <small class="text-muted mt-1 d-block" style="font-size: 0.7rem;">Demandez le code à l'admin de la cagnotte.</small>
    </div>
</div>

@isset($mesDemandesAdhesion)
<div class="card mt-3">
    <div class="card-header card-header-compact-rechercher"><i class="bi bi-clock-history"></i> Mes demandes d'adhésion</div>
    <div class="card-body py-2">
        @if($mesDemandesAdhesion->count() > 0)
            <div class="table-responsive">
                <table class="table table-demandes-adhesion table-sm table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Tontine</th>
                            <th>Code</th>
                            <th>Public / Privé</th>
                            <th>Date demande</th>
                            <th>Statut</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($mesDemandesAdhesion as $adhesion)
                            <tr>
                                <td>{{ $adhesion->cotisation->nom ?? '-' }}</td>
                                <td><code>{{ $adhesion->cotisation->code ?? '-' }}</code></td>
                                <td>
                                    @if($adhesion->cotisation && $adhesion->cotisation->isPublique())
                                        Public
                                    @else
                                        Privé
                                    @endif
                                </td>
                                <td>{{ $adhesion->created_at->format('d/m/Y H:i') }}</td>
                                <td>
                                    @if($adhesion->statut === 'en_attente')
                                        En attente
                                    @elseif($adhesion->statut === 'accepte')
                                        Accepté
                                    @else
                                        Refusé
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <p class="text-muted mb-0 small" style="font-size: 0.7rem;">Vous n'avez aucune demande d'adhésion en cours.</p>
        @endif
    </div>
</div>
@endisset
@endsection
