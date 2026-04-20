@extends('layouts.app')

@section('title', 'Détails de la demande de versement')

@section('content')
<div class="page-header d-flex justify-content-between align-items-center mb-4">
    <h1 style="color: var(--primary-dark-blue); font-weight: 400;">
        <i class="bi bi-info-circle-fill me-2"></i>Détails de la Demande #{{ $demande->id }}
    </h1>
    <a href="{{ route('cotisation-versement-demandes.index') }}" class="btn btn-secondary shadow-sm">
        <i class="bi bi-arrow-left me-1"></i> Retour à la liste
    </a>
</div>

@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show shadow-sm border-0 border-start border-danger border-4">
        <i class="bi bi-exclamation-triangle-fill me-2"></i> {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

<div class="row g-4">
    <!-- Colonne Gauche: Informations Générales -->
    <div class="col-md-7">
        <div class="card border-0 shadow-sm mb-4" style="border-radius: 12px;">
            <div class="card-header bg-white border-bottom py-3">
                <h5 class="card-title mb-0 fw-bold">Statut actuel : 
                    @if($demande->statut === 'en_attente')
                        <span class="badge bg-warning text-dark px-3 py-2 rounded-pill">En attente de traitement</span>
                    @elseif($demande->statut === 'traite')
                        <span class="badge bg-success px-3 py-2 rounded-pill">Approuvée et Traitée</span>
                    @else
                        <span class="badge bg-secondary px-3 py-2 rounded-pill">Rejetée</span>
                    @endif
                </h5>
            </div>
            <div class="card-body p-4">
                <div class="row mb-4 text-center">
                    <div class="col-12">
                        <div class="display-5 fw-bold text-primary mb-1">{{ number_format($demande->montant_demande, 0, ',', ' ') }} <small class="fs-4 fw-normal">XOF</small></div>
                        <p class="text-muted text-uppercase small ls-1 fw-bold">Montant Demandé</p>
                    </div>
                </div>

                <div class="row g-3">
                    <div class="col-sm-6">
                        <label class="text-muted small text-uppercase fw-bold mb-1">Cagnotte Source</label>
                        <div class="p-3 bg-light rounded-3 border">
                            <h6 class="mb-1 text-dark fw-bold">{{ $demande->cotisation->nom }}</h6>
                            <div class="small">Cod: <code>{{ $demande->cotisation->code }}</code></div>
                            <div class="small mt-1 text-muted"><i class="bi bi-wallet2 me-1"></i> {{ $demande->cotisation->caisse->numero ?? 'N/A' }}</div>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <label class="text-muted small text-uppercase fw-bold mb-1">Compte Cible (Client)</label>
                        <div class="p-3 bg-light rounded-3 border">
                            <h6 class="mb-1 text-dark fw-bold">{{ $demande->demandeParMembre->nom_complet }}</h6>
                            <div class="small text-muted">{{ $demande->demandeParMembre->email }}</div>
                            <div class="small mt-1 fw-medium text-primary"><i class="bi bi-bank me-1"></i> {{ $demande->demandeParMembre->compteCourant->numero ?? 'N/A' }}</div>
                        </div>
                    </div>
                </div>

                <div class="mt-4 pt-3 border-top">
                    <div class="row text-center text-sm-start g-3">
                        <div class="col-sm-6">
                            <span class="text-muted small fw-bold text-uppercase d-block">Soumise le</span>
                            <span class="fw-medium">{{ $demande->created_at->format('d/m/Y à H:i') }}</span>
                        </div>
                        @if($demande->traite_le)
                            <div class="col-sm-6">
                                <span class="text-muted small fw-bold text-uppercase d-block">Traitée le</span>
                                <span class="fw-medium">{{ $demande->traite_le->format('d/m/Y à H:i') }}</span>
                            </div>
                        @endif
                    </div>
                </div>

                @if($demande->commentaire)
                    <div class="mt-4 p-3 bg-light border-start border-warning border-4 rounded" style="font-style: italic;">
                        <span class="text-muted small fw-bold text-uppercase d-block mb-1">Motif / Commentaire :</span>
                        "{{ $demande->commentaire }}"
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Colonne Droite: Actions -->
    <div class="col-md-5">
        @if($demande->statut === 'en_attente')
            <div class="card border-0 shadow-sm mb-4" style="border-radius: 12px; border-top: 5px solid #10b981 !important;">
                <div class="card-body p-4">
                    <h5 class="fw-bold text-success mb-3"><i class="bi bi-check-circle-fill me-2"></i>Approuver le versement</h5>
                    <p class="small text-muted mb-4">
                        En approuvant cette demande, un transfert de fonds sera immédiatement effectué du compte de la cagnotte vers le compte courant du membre. Cette action est irréversible.
                    </p>
                    <form action="{{ route('cotisation-versement-demandes.approve', $demande) }}" method="POST">
                        @csrf
                        <button type="submit" class="btn btn-success w-100 py-3 fw-bold rounded-pill" onclick="return confirm('Êtes-vous sûr de vouloir approuver ce versement ?');">
                            <i class="bi bi-check-lg me-1"></i> CONFIRMER L'APPROBATION
                        </button>
                    </form>
                </div>
            </div>

            <div class="card border-0 shadow-sm" style="border-radius: 12px; border-top: 5px solid #ef4444 !important;">
                <div class="card-body p-4">
                    <h5 class="fw-bold text-danger mb-3"><i class="bi bi-x-circle-fill me-2"></i>Rejeter la demande</h5>
                    <form action="{{ route('cotisation-versement-demandes.reject', $demande) }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label class="small fw-bold text-muted text-uppercase mb-1">Motif du rejet (obligatoire)</label>
                            <textarea name="commentaire" class="form-control" rows="3" placeholder="Expliquez la raison du rejet..." required></textarea>
                        </div>
                        <button type="submit" class="btn btn-outline-danger w-100 py-2 fw-bold rounded-pill" onclick="return confirm('Rejeter cette demande ?');">
                            <i class="bi bi-x me-1"></i> REJETER LA DEMANDE
                        </button>
                    </form>
                </div>
            </div>
        @else
            <div class="card border-0 shadow-sm" style="border-radius: 12px;">
                <div class="card-body p-5 text-center">
                    <div class="rounded-circle bg-light d-flex align-items-center justify-content-center mx-auto mb-3" style="width: 80px; height: 80px;">
                        <i class="bi bi-lock-fill text-muted fs-1"></i>
                    </div>
                    <h5 class="fw-bold text-dark mb-2">Demande Verrouillée</h5>
                    <p class="text-muted small">
                        Cette demande a déjà été traitée par <strong>{{ $demande->traiteParUser->name ?? 'le système' }}</strong>. 
                        Aucune autre action n'est possible sur ce dossier.
                    </p>
                </div>
            </div>
        @endif
    </div>
</div>
@endsection
