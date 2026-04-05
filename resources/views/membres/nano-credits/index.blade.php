@extends('layouts.membre')

@section('title', 'Nano crédit')

@section('content')
<div class="page-header d-flex justify-content-between align-items-center flex-wrap">
    <h1 style="font-weight: 300; font-family: 'Ubuntu', sans-serif;">
        <i class="bi bi-phone"></i> Nano crédit
    </h1>
    <a href="{{ route('membre.nano-credits.mes') }}" class="btn btn-primary btn-sm">
        <i class="bi bi-wallet2"></i> Mes nano crédits
    </a>
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
@if(session('info'))
    <div class="alert alert-info alert-dismissible fade show">{{ session('info') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

<div class="row g-3">
    <!-- Carte du Palier Actuel -->
    <div class="col-lg-5">
        <div class="card h-100 shadow-sm border-0" style="border-radius: 12px; overflow: hidden;">
            <div class="card-header bg-primary text-white py-3 border-0">
                <div class="d-flex align-items-center">
                    <div class="bg-white text-primary rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px;">
                        <i class="bi bi-award-fill fs-5"></i>
                    </div>
                    <div>
                        <h6 class="mb-0 text-uppercase fw-bold" style="letter-spacing: 1px;">Votre Palier Actuel</h6>
                        <span class="small opacity-75">Statut de progression</span>
                    </div>
                </div>
            </div>
            <div class="card-body p-4">
                @if($palier)
                    <div class="text-center mb-4">
                        <h2 class="display-6 fw-bold color-primary mb-1">{{ $palier->nom }}</h2>
                        <span class="badge bg-light text-primary border px-3">Palier n°{{ $palier->numero }}</span>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-6">
                            <div class="p-3 bg-light rounded-3 text-center h-100">
                                <i class="bi bi-cash-coin text-primary fs-4 d-block mb-1"></i>
                                <span class="d-block text-muted small">Plafond</span>
                                <strong class="fs-5">{{ number_format($palier->montant_plafond, 0, ',', ' ') }} <small>FCFA</small></strong>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-3 bg-light rounded-3 text-center h-100">
                                <i class="bi bi-calendar-check text-primary fs-4 d-block mb-1"></i>
                                <span class="d-block text-muted small">Durée</span>
                                <strong class="fs-5">{{ $palier->duree_jours }} <small>Jours</small></strong>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-3 bg-light rounded-3 text-center h-100">
                                <i class="bi bi-percent text-primary fs-4 d-block mb-1"></i>
                                <span class="d-block text-muted small">Intérêt</span>
                                <strong class="fs-5">{{ number_format($palier->taux_interet ?? 0, 1, ',', ' ') }} %</strong>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-3 bg-light rounded-3 text-center h-100">
                                <i class="bi bi-people text-primary fs-4 d-block mb-1"></i>
                                <span class="d-block text-muted small">Garants</span>
                                <strong class="fs-5">{{ $palier->nombre_garants }}</strong>
                            </div>
                        </div>
                    </div>

                    <div class="mb-4">
                        <h6 class="small fw-bold text-muted text-uppercase mb-2">Conditions du palier</h6>
                        <ul class="list-unstyled small">
                            <li class="mb-2 d-flex align-items-center">
                                <i class="bi bi-check2-circle text-success me-2"></i>
                                <span>Remboursement : <strong>{{ $palier->frequence_remboursement_label }}</strong></span>
                            </li>
                            <li class="mb-2 d-flex align-items-center">
                                <i class="bi bi-check2-circle text-success me-2"></i>
                                <span>Pénalité de retard : <strong>{{ $palier->penalite_par_jour }}% / jour</strong></span>
                            </li>
                            <li class="mb-2 d-flex align-items-center">
                                <i class="bi bi-check2-circle {{ $palier->pourcentage_partage_garant > 0 ? 'text-success' : 'text-muted' }} me-2"></i>
                                <span>Bonus garants : <strong>{{ $palier->pourcentage_partage_garant }}% des intérêts</strong></span>
                            </li>
                        </ul>
                    </div>

                    <a href="{{ route('membre.nano-credits.demander') }}" class="btn btn-primary w-100 py-2 fs-6 shadow-sm">
                        <i class="bi bi-send me-2"></i> Nouvelle demande de crédit
                    </a>
                @else
                    <div class="text-center py-4">
                        <i class="bi bi-exclamation-triangle text-warning display-4"></i>
                        <p class="mt-3">Aucun palier ne vous est assigné. Veuillez contacter le support ou attendre la validation de votre KYC.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Carte Infos & Prochain Palier -->
    <div class="col-lg-7">
        <div class="card h-100 shadow-sm border-0" style="border-radius: 12px;">
            <div class="card-body p-4">
                <h5 class="fw-bold mb-4" style="color: var(--primary-dark-blue);">Comment ça marche ?</h5>
                
                <div class="timeline-simple mb-5">
                    <div class="d-flex mb-4">
                        <div class="me-3">
                            <div class="bg-light text-primary rounded-circle d-flex align-items-center justify-content-center" style="width: 32px; height: 32px; font-weight: bold;">1</div>
                        </div>
                        <div>
                            <h6 class="fw-bold mb-1">Faites votre demande</h6>
                            <p class="text-muted small">Choisissez le montant souhaité dans la limite de votre plafond actuel ({{ number_format($palier->montant_plafond ?? 0, 0, ',', ' ') }} FCFA).</p>
                        </div>
                    </div>
                    <div class="d-flex mb-4">
                        <div class="me-3">
                            <div class="bg-light text-primary rounded-circle d-flex align-items-center justify-content-center" style="width: 32px; height: 32px; font-weight: bold;">2</div>
                        </div>
                        <div>
                            <h6 class="fw-bold mb-1">Désignez des garants</h6>
                            <p class="text-muted small">Une fois la demande acceptée par l'admin, désignez {{ $palier->nombre_garants }} membre(s) comme garants. Ils devront valider pour que les fonds soient débloqués.</p>
                        </div>
                    </div>
                    <div class="d-flex">
                        <div class="me-3">
                            <div class="bg-light text-primary rounded-circle d-flex align-items-center justify-content-center" style="width: 32px; height: 32px; font-weight: bold;">3</div>
                        </div>
                        <div>
                            <h6 class="fw-bold mb-1">Remboursez et progressez</h6>
                            <p class="text-muted small">Chaque crédit remboursé à temps améliore votre réputation et vous permet d'accéder au palier suivant avec des plafonds plus élevés.</p>
                        </div>
                    </div>
                </div>

                @php
                    $palierSuivant = $palier ? $palier->palierSuivant() : null;
                @endphp

                @if($palierSuivant)
                    <div class="bg-light rounded-4 p-4 border border-dashed">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="fw-bold mb-0 text-primary"><i class="bi bi-graph-up-arrow me-2"></i> Objectif : {{ $palierSuivant->nom }}</h6>
                            <span class="badge bg-white text-primary border">Prochain palier</span>
                        </div>
                        <p class="small text-muted mb-3">Pour débloquer ce palier, vous devez remplir les conditions suivantes :</p>
                        
                        <div class="row g-2">
                            <div class="col-md-6">
                                <div class="bg-white p-2 rounded border small">
                                    <i class="bi bi-check2 text-{{ $membre->nanoCredits()->where('statut', 'rembourse')->count() >= $palierSuivant->min_credits_rembourses ? 'success' : 'muted' }} me-1"></i>
                                    {{ $palierSuivant->min_credits_rembourses }} crédits remboursés
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="bg-white p-2 rounded border small">
                                    <i class="bi bi-check2 text-{{ ($membre->nanoCredits()->where('statut', 'rembourse')->sum('montant') >= $palierSuivant->min_montant_total_rembourse) ? 'success' : 'muted' }} me-1"></i>
                                    {{ number_format($palierSuivant->min_montant_total_rembourse, 0, ',', ' ') }} FCFA remboursés
                                </div>
                            </div>
                            <div class="col-md-12">
                                <div class="bg-white p-2 rounded border small">
                                    <i class="bi bi-check2 text-{{ $membre->totalEpargneSolde() >= $palierSuivant->min_epargne_cumulee ? 'success' : 'muted' }} me-1"></i>
                                    Épargne totale mini. : {{ number_format($palierSuivant->min_epargne_cumulee, 0, ',', ' ') }} FCFA
                                </div>
                            </div>
                        </div>
                    </div>
                @else
                    <div class="bg-success bg-opacity-10 text-success rounded-4 p-4 border border-success border-opacity-25 text-center">
                        <i class="bi bi-stars fs-3 d-block mb-2"></i>
                        <h6 class="fw-bold mb-1">Vous êtes au palier maximum !</h6>
                        <p class="small mb-0">Félicitations, vous bénéficiez des meilleures conditions de crédit de la plateforme.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<style>
.color-primary { color: var(--primary-dark-blue); }
.border-dashed { border-style: dashed !important; }
.timeline-simple { position: relative; }
</style>
@endsection
