@extends('layouts.membre')

@section('title', 'Tableau de Bord')

@section('content')
<div class="row g-3 mb-4">
    <div class="col-12">
        <div class="page-header d-flex flex-wrap align-items-center justify-content-between gap-3">
            <div>
                <h1 style="font-weight: 300; font-family: 'Ubuntu', sans-serif;">
                    Bonjour, {{ $membre->prenom }} 👋
                </h1>
                <p class="text-muted mb-0 small">Ravi de vous revoir sur votre espace personnel Serenity.</p>
            </div>
            <div class="d-flex gap-2">
                <span class="badge bg-white text-dark border shadow-sm px-3 py-2" style="font-weight: 300;">
                    <i class="bi bi-calendar3 me-1 text-primary"></i> {{ now()->format('d/m/Y') }}
                </span>
            </div>
        </div>
    </div>
</div>

<!-- Section KPI : Vue d'ensemble -->
<div class="row g-3 mb-4">
    <!-- Solde Global -->
    <div class="col-md-3">
        <div class="card h-100 border-0 shadow-sm overflow-hidden" style="background: linear-gradient(135deg, #1e3a5f, #2c5282); border-radius: 12px;">
            <div class="card-body p-4 text-white position-relative">
                <div class="position-absolute top-0 end-0 p-3 opacity-25">
                    <i class="bi bi-wallet2 fs-1"></i>
                </div>
                <h6 class="text-uppercase small opacity-75 mb-2 fw-bold" style="letter-spacing: 1px;">Solde Global</h6>
                <h3 class="fw-bold mb-1">{{ number_format($soldeGlobal, 0, ',', ' ') }} <small class="fs-6 fw-normal">XOF</small></h3>
                <div class="mt-3">
                    <span class="badge bg-white bg-opacity-25 px-2 py-1">
                        {{ $comptes->count() }} Comptes actifs
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- Épargne (Tontines) -->
    <div class="col-md-3">
        <div class="card h-100 border-0 shadow-sm overflow-hidden" style="background: linear-gradient(135deg, #059669, #10b981); border-radius: 12px;">
            <div class="card-body p-4 text-white position-relative">
                <div class="position-absolute top-0 end-0 p-3 opacity-25">
                    <i class="bi bi-piggy-bank fs-1"></i>
                </div>
                <h6 class="text-uppercase small opacity-75 mb-2 fw-bold" style="letter-spacing: 1px;">Mon Épargne</h6>
                <h3 class="fw-bold mb-1">{{ number_format($epargneTotal, 0, ',', ' ') }} <small class="fs-6 fw-normal">XOF</small></h3>
                <div class="mt-3">
                    <span class="badge bg-white bg-opacity-25 px-2 py-1">
                        {{ $epargnesActivesCount }} Tontines en cours
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- Crédit (Nano-Crédit) -->
    <div class="col-md-3">
        <div class="card h-100 border-0 shadow-sm overflow-hidden" style="background: linear-gradient(135deg, #d97706, #f59e0b); border-radius: 12px;">
            <div class="card-body p-4 text-white position-relative">
                <div class="position-absolute top-0 end-0 p-3 opacity-25">
                    <i class="bi bi-credit-card fs-1"></i>
                </div>
                <h6 class="text-uppercase small opacity-75 mb-2 fw-bold" style="letter-spacing: 1px;">Mon Crédit</h6>
                @if($creditActif)
                    <h3 class="fw-bold mb-1">{{ number_format($creditActif->montant, 0, ',', ' ') }} <small class="fs-6 fw-normal">XOF</small></h3>
                    <div class="mt-3 small">
                        Prochaine échéance : <span class="fw-bold">{{ $prochaineEcheance ? $prochaineEcheance->date_echeance->format('d/m/Y') : 'N/A' }}</span>
                    </div>
                @else
                    <h3 class="fw-bold mb-1">0 <small class="fs-6 fw-normal">XOF</small></h3>
                    <div class="mt-3 small">
                        Limite : <span class="fw-bold">{{ number_format($limiteCredit, 0, ',', ' ') }} XOF</span>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Parrainage -->
    <div class="col-md-3">
        <div class="card h-100 border-0 shadow-sm overflow-hidden" style="background: linear-gradient(135deg, #2563eb, #3b82f6); border-radius: 12px;">
            <div class="card-body p-4 text-white position-relative">
                <div class="position-absolute top-0 end-0 p-3 opacity-25">
                    <i class="bi bi-people fs-1"></i>
                </div>
                <h6 class="text-uppercase small opacity-75 mb-2 fw-bold" style="letter-spacing: 1px;">Ma Retraite</h6>
                <h3 class="fw-bold mb-1">{{ number_format($commissionsDisponibles, 0, ',', ' ') }} <small class="fs-6 fw-normal">XOF</small></h3>
                <div class="mt-3">
                    <a href="{{ route('membre.parrainage.index') }}" class="btn btn-sm btn-light border-0 text-primary py-1 px-3 rounded-pill fw-bold" style="font-size: 0.7rem;">
                        Détails <i class="bi bi-arrow-right"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- Colonne Gauche : Activités & Annonces -->
    <div class="col-md-8">
        <!-- Activités Récentes -->
        <div class="card border-0 shadow-sm mb-4" style="border-radius: 12px;">
            <div class="card-header bg-white border-bottom py-3 px-4">
                <h5 class="card-title mb-0 fw-bold text-dark">
                    <i class="bi bi-clock-history text-primary me-2"></i>Activités Récentes
                </h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" style="font-size: 0.75rem;">
                        <thead class="bg-light">
                            <tr>
                                <th class="ps-4">Opération</th>
                                <th>Compte</th>
                                <th>Date</th>
                                <th class="pe-4 text-end">Montant</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($activites as $act)
                                <tr>
                                    <td class="ps-4">
                                        <div class="d-flex align-items-center">
                                            <div class="rounded-circle d-flex align-items-center justify-content-center me-2 {{ $act->sens === 'entree' ? 'bg-success bg-opacity-10 text-success' : 'bg-danger bg-opacity-10 text-danger' }}" style="width: 28px; height: 28px;">
                                                <i class="bi {{ $act->sens === 'entree' ? 'bi-arrow-down-left' : 'bi-arrow-up-right' }}"></i>
                                            </div>
                                            <div>
                                                <div class="fw-bold">{{ $act->libelle }}</div>
                                                <div class="text-muted small">{{ ucfirst($act->type) }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>{{ $act->caisse->nom }}</td>
                                    <td>{{ $act->date_operation->format('d/m/Y H:i') }}</td>
                                    <td class="pe-4 text-end fw-bold {{ $act->sens === 'entree' ? 'text-success' : 'text-danger' }}">
                                        {{ $act->sens === 'entree' ? '+' : '-' }} {{ number_format($act->montant, 0, ',', ' ') }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="p-4 text-center text-muted">Aucune activité enregistrée.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer bg-white border-top text-center py-2">
                <a href="{{ route('membre.paiements') }}" class="small text-decoration-none">Voir tout l'historique</a>
            </div>
        </div>

        <!-- Section Annonces -->
        @if($annonces->count() > 0)
            <div id="annonceCarousel" class="carousel slide" data-bs-ride="carousel">
                <div class="carousel-inner">
                    @foreach($annonces as $index => $annonce)
                        <div class="carousel-item {{ $index === 0 ? 'active' : '' }}">
                            <div class="card border-0 shadow-sm" style="border-radius: 12px; background: #eef2f7;">
                                <div class="card-body p-4">
                                    <h6 class="fw-bold text-primary mb-2"><i class="bi bi-info-circle me-1"></i> {{ $annonce->titre }}</h6>
                                    <p class="mb-0 small text-muted">{{ $annonce->message }}</p>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>

    <!-- Colonne Droite : Mes Comptes & Actions Rapides -->
    <div class="col-md-4">
        <!-- Actions Rapides -->
        <div class="card border-0 shadow-sm mb-4" style="border-radius: 12px;">
            <div class="card-header bg-white border-bottom py-3 px-4">
                <h5 class="card-title mb-0 fw-bold text-dark">Actions Rapides</h5>
            </div>
            <div class="card-body p-3">
                <div class="d-grid gap-2">
                    <a href="{{ route('membre.cotisations.publiques') }}" class="btn btn-outline-primary d-flex align-items-center justify-content-between p-3 rounded-3 border-light-subtle shadow-sm hover-lift">
                        <span class="fw-bold"><i class="bi bi-receipt-cutoff me-2"></i> Payer une cagnotte</span>
                        <i class="bi bi-chevron-right small text-muted"></i>
                    </a>
                    <a href="{{ route('membre.epargne.index') }}" class="btn btn-outline-success d-flex align-items-center justify-content-between p-3 rounded-3 border-light-subtle shadow-sm hover-lift">
                        <span class="fw-bold"><i class="bi bi-piggy-bank me-2"></i> Commencer une épargne</span>
                        <i class="bi bi-chevron-right small text-muted"></i>
                    </a>
                    <a href="{{ route('membre.nano-credits') }}" class="btn btn-outline-warning d-flex align-items-center justify-content-between p-3 rounded-3 border-light-subtle shadow-sm hover-lift text-dark">
                        <span class="fw-bold"><i class="bi bi-lightning-charge me-2"></i> Demander un crédit</span>
                        <i class="bi bi-chevron-right small text-muted"></i>
                    </a>
                </div>
            </div>
        </div>

        <!-- Mes Comptes -->
        <div class="card border-0 shadow-sm" style="border-radius: 12px;">
            <div class="card-header bg-white border-bottom py-3 px-4">
                <h5 class="card-title mb-0 fw-bold text-dark">Mes Comptes</h5>
            </div>
            <div class="card-body p-0">
                <div class="list-group list-group-flush">
                    @foreach($comptes as $compte)
                        <div class="list-group-item py-3 px-4 bg-transparent border-0 border-bottom">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="fw-bold text-dark" style="font-size: 0.8rem;">{{ $compte->nom }}</span>
                                <span class="badge bg-light text-dark fw-normal" style="font-size: 0.65rem;">{{ strtoupper($compte->type) }}</span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="text-muted small">N° {{ $compte->numero }}</span>
                                <span class="fw-bold text-primary">{{ number_format($compte->solde_actuel, 0, ',', ' ') }} XOF</span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
            <div class="card-footer bg-white border-top-0 text-center py-3">
                <a href="{{ route('membre.comptes') }}" class="btn btn-link btn-sm text-decoration-none">Gérer tous mes comptes</a>
            </div>
        </div>
    </div>
</div>

<style>
    .hover-lift { transition: transform 0.2s, box-shadow 0.2s; }
    .hover-lift:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,0.1) !important; color: white !important; background-color: var(--primary-dark-blue) !important; border-color: var(--primary-dark-blue) !important; }
    .hover-lift:hover i { color: white !important; }
    .ls-1 { letter-spacing: 1px; }
</style>
@endsection
