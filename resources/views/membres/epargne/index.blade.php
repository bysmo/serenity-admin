@extends('layouts.membre')

@section('title', 'Tontines')

@section('content')
<div class="page-header d-flex justify-content-between align-items-center flex-wrap">
    <h1 style="font-weight: 300; font-family: 'Ubuntu', sans-serif;">
        <i class="bi bi-piggy-bank"></i> Tontines
    </h1>
    <a href="{{ route('membre.epargne.mes-epargnes') }}" class="btn btn-primary btn-sm">
        <i class="bi bi-wallet2"></i> Mes tontines
    </a>
</div>

<div class="card">
    <div class="card-header" style="font-weight: 300; font-family: 'Ubuntu', sans-serif;">
        <i class="bi bi-list-ul"></i> Plans de tontine disponibles
    </div>
    <div class="card-body">
        @if($plans->count() > 0)
            <p class="text-muted small mb-3">Choisissez un plan et souscrivez pour programmer vos versements à la fréquence qui vous convient.</p>
            <div class="row g-3">
                @foreach($plans as $plan)
                    <div class="col-md-6 col-lg-4">
                        <div class="card h-100 border shadow-sm" style="border-radius: 8px;">
                            <div class="card-body">
                                <h6 class="card-title" style="font-weight: 400; color: var(--primary-dark-blue);">
                                    {{ $plan->nom }}
                                </h6>
                                <p class="card-text small text-muted mb-2" style="font-size: 0.75rem;">
                                    {{ Str::limit($plan->description, 120) }}
                                </p>
                                <ul class="list-unstyled small mb-3" style="font-size: 0.75rem;">
                                    <li><i class="bi bi-arrow-repeat me-1"></i> {{ $plan->frequence_label }}</li>
                                    <li><i class="bi bi-cash-coin me-1"></i> {{ number_format($plan->montant_min, 0, ',', ' ') }} – {{ $plan->montant_max ? number_format($plan->montant_max, 0, ',', ' ') . ' XOF' : 'illimité' }} / versement</li>
                                    <li><i class="bi bi-percent me-1"></i> Taux : {{ number_format($plan->taux_remuneration ?? 0, 1, ',', ' ') }} % / an</li>
                                    <li><i class="bi bi-calendar-range me-1"></i> Durée : {{ $plan->duree_mois ?? 12 }} mois</li>
                                </ul>
                                @if(in_array($plan->id, $planIdsDejaSouscrits ?? []))
                                    <span class="btn btn-outline-secondary btn-sm w-100 disabled" title="Souscription en cours à ce forfait">
                                        <i class="bi bi-check-circle"></i> Déjà souscrit (en cours)
                                    </span>
                                @else
                                    <a href="{{ route('membre.epargne.souscrire', $plan) }}" class="btn btn-primary btn-sm w-100">
                                        <i class="bi bi-plus-circle"></i> Souscrire
                                    </a>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="text-center py-4">
                <i class="bi bi-piggy-bank text-muted" style="font-size: 2.5rem;"></i>
                <p class="text-muted mt-2 mb-0">Aucun plan de tontine disponible pour le moment.</p>
            </div>
        @endif
    </div>
</div>
@endsection
