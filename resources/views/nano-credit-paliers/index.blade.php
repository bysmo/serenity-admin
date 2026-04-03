@extends('layouts.app')

@section('title', 'Paliers Nano-Crédit — Serenity')

@section('content')
<div class="page-header d-flex align-items-center justify-content-between mb-4">
    <div>
        <h1 class="page-title"><i class="bi bi-ladder me-2"></i>Paliers Nano-Crédit</h1>
        <p class="text-muted mb-0" style="font-size: 0.85rem;">
            Configurez les niveaux d'accès aux nano-crédits. Chaque membre KYC validé débute au <strong>Palier 1</strong>.
        </p>
    </div>
    <a href="{{ route('nano-credit-paliers.create') }}" class="btn btn-primary btn-sm">
        <i class="bi bi-plus-circle me-1"></i> Nouveau palier
    </a>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif
@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-triangle me-2"></i>{{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

@if($paliers->isEmpty())
    <div class="card">
        <div class="card-body text-center py-5">
            <i class="bi bi-ladder" style="font-size: 3rem; color: #adb5bd;"></i>
            <p class="mt-3 text-muted">Aucun palier configuré. Créez le <strong>Palier 1</strong> pour commencer.</p>
            <a href="{{ route('nano-credit-paliers.create') }}" class="btn btn-primary btn-sm">
                <i class="bi bi-plus-circle me-1"></i> Créer le Palier 1
            </a>
        </div>
    </div>
@else
<div class="row g-3">
    @foreach($paliers as $palier)
    <div class="col-12">
        <div class="card h-100 {{ !$palier->actif ? 'opacity-75' : '' }}">
            <div class="card-header d-flex align-items-center justify-content-between py-2">
                <div class="d-flex align-items-center gap-2">
                    <span class="badge" style="background: var(--primary-dark-blue); font-size: 1rem; padding: 0.4rem 0.8rem;">
                        Palier {{ $palier->numero }}
                    </span>
                    <strong>{{ $palier->nom }}</strong>
                    @if(!$palier->actif)
                        <span class="badge bg-secondary">Inactif</span>
                    @endif
                    @if($palier->numero === 1)
                        <span class="badge bg-success">Palier initial (KYC)</span>
                    @endif
                </div>
                <div class="d-flex gap-1">
                    <a href="{{ route('nano-credit-paliers.show', $palier) }}" class="btn btn-outline-info btn-sm">
                        <i class="bi bi-eye"></i>
                    </a>
                    <a href="{{ route('nano-credit-paliers.edit', $palier) }}" class="btn btn-outline-primary btn-sm">
                        <i class="bi bi-pencil"></i>
                    </a>
                    <form action="{{ route('nano-credit-paliers.destroy', $palier) }}" method="POST" class="d-inline"
                          onsubmit="return confirm('Supprimer ce palier ? Cette action est irréversible.')">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn btn-outline-danger btn-sm">
                            <i class="bi bi-trash"></i>
                        </button>
                    </form>
                </div>
            </div>
            <div class="card-body py-3">
                <div class="row g-3">
                    {{-- Statistiques membres --}}
                    <div class="col-md-2 col-6">
                        <div class="text-center">
                            <div style="font-size: 1.5rem; font-weight: 700; color: var(--primary-dark-blue);">
                                {{ $palier->membres_count ?? 0 }}
                            </div>
                            <small class="text-muted">Membres</small>
                        </div>
                    </div>
                    <div class="col-md-2 col-6">
                        <div class="text-center">
                            <div style="font-size: 1.5rem; font-weight: 700; color: var(--primary-dark-blue);">
                                {{ $palier->nano_credits_count ?? 0 }}
                            </div>
                            <small class="text-muted">Crédits liés</small>
                        </div>
                    </div>

                    {{-- Paramètres crédit --}}
                    <div class="col-md-8">
                        <div class="row g-2 text-center">
                            <div class="col-6 col-sm-3">
                                <div class="border rounded p-2">
                                    <div class="fw-bold" style="color: #28a745;">
                                        {{ number_format((float)$palier->montant_plafond, 0, ',', ' ') }} FCFA
                                    </div>
                                    <small class="text-muted">Plafond</small>
                                </div>
                            </div>
                            <div class="col-6 col-sm-3">
                                <div class="border rounded p-2">
                                    <div class="fw-bold">{{ $palier->duree_jours }}j</div>
                                    <small class="text-muted">Durée max</small>
                                </div>
                            </div>
                            <div class="col-6 col-sm-3">
                                <div class="border rounded p-2">
                                    <div class="fw-bold" style="color: #fd7e14;">{{ $palier->taux_interet }}%</div>
                                    <small class="text-muted">Taux annuel</small>
                                </div>
                            </div>
                            <div class="col-6 col-sm-3">
                                <div class="border rounded p-2">
                                    <div class="fw-bold" style="color: #dc3545;">{{ $palier->penalite_par_jour }}%/j</div>
                                    <small class="text-muted">Pénalité</small>
                                </div>
                            </div>
                        </div>

                        {{-- Conditions d'accession --}}
                        @if($palier->numero > 1)
                        <div class="mt-2 p-2 rounded" style="background: #f8f9fa; font-size: 0.8rem;">
                            <strong><i class="bi bi-arrow-up-circle me-1 text-success"></i>Conditions d'accession :</strong>
                            <span class="ms-2">
                                {{ $palier->min_credits_rembourses }} crédit(s) remboursé(s),
                                {{ number_format((float)$palier->min_montant_total_rembourse, 0, ',', ' ') }} FCFA remboursés,
                                {{ number_format((float)$palier->min_epargne_cumulee, 0, ',', ' ') }} FCFA d'épargne,
                                {{ $palier->nombre_garants }} garant(s)
                            </span>
                        </div>
                        @endif

                        {{-- Conséquences impayés --}}
                        <div class="mt-2 d-flex gap-2 flex-wrap" style="font-size: 0.78rem;">
                            @if($palier->downgrade_en_cas_impayes)
                                <span class="badge bg-warning text-dark">
                                    <i class="bi bi-arrow-down-circle me-1"></i>
                                    Downgrade après {{ $palier->jours_impayes_pour_downgrade }}j
                                </span>
                            @endif
                            @if($palier->interdiction_en_cas_recidive)
                                <span class="badge bg-danger">
                                    <i class="bi bi-slash-circle me-1"></i>
                                    Interdiction après {{ $palier->nb_recidives_pour_interdiction }} défaut(s)
                                </span>
                            @endif
                            <span class="badge bg-info text-dark">
                                <i class="bi bi-people me-1"></i>
                                Garants prélevés après {{ $palier->jours_avant_prelevement_garant }}j
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endforeach
</div>
@endif
@endsection
