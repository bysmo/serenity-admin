@extends('layouts.membre')

@section('title', 'Tontine trouvée')

@section('content')
<div class="page-header">
    <h1 style="font-weight: 300;"><i class="bi bi-check-circle text-success"></i> Tontine trouvée</h1>
</div>

<div class="card mb-3">
    <div class="card-header">
        <i class="bi bi-receipt-cutoff"></i> {{ $cotisation->nom }}
    </div>
    <div class="card-body">
        <div class="row mb-3">
            <div class="col-md-6">
                <strong>Code :</strong> {{ $cotisation->code }}<br>
                <strong>Type :</strong> {{ ucfirst($cotisation->type ?? 'N/A') }}<br>
                <strong>Fréquence :</strong> {{ ucfirst($cotisation->frequence ?? '-') }}<br>
            </div>
            <div class="col-md-6">
                <strong>Montant :</strong> {{ $cotisation->montant ? number_format($cotisation->montant, 0, ',', ' ') . ' XOF' : 'Libre' }}<br>
                @if($cotisation->description)
                    <strong>Description :</strong> {{ $cotisation->description }}<br>
                @endif
            </div>
        </div>

        @if(!$adhesion)
            <form action="{{ route('membre.cotisations.adherer', $cotisation) }}" method="POST">
                @csrf
                <button type="submit" class="btn btn-success">
                    <i class="bi bi-plus-circle"></i> Demander à adhérer
                </button>
            </form>
        @elseif($adhesion->statut === 'en_attente')
            <div class="alert alert-warning mb-0">
                <i class="bi bi-clock"></i> Votre demande d'adhésion est en attente de validation par l'administrateur de la cagnotte.
            </div>
        @elseif($adhesion->statut === 'accepte')
            <div class="alert alert-success mb-0">
                <i class="bi bi-check-circle"></i> Vous êtes adhérent.
                <a href="{{ route('membre.cotisations.show', $cotisation->id) }}">Voir la cagnotte et payer</a>
            </div>
        @else
            <div class="alert alert-secondary mb-0">
                Votre demande a été refusée.
            </div>
        @endif
    </div>
</div>

<a href="{{ route('membre.cotisations.rechercher') }}" class="btn btn-outline-secondary">
    <i class="bi bi-arrow-left"></i> Nouvelle recherche
</a>
@endsection
