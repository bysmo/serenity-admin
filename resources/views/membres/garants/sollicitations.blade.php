@extends('layouts.membre')

@section('title', 'Sollicitations de Garantie')

@section('content')
<style>
    .sollicitation-card {
        border-radius: 16px;
        border: none;
        box-shadow: 0 4px 12px rgba(0,0,0,0.05);
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }
    .sollicitation-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 24px rgba(0,0,0,0.1);
    }
    .gain-badge {
        background: linear-gradient(135deg, #48bb78 0%, #38a169 100%);
        color: white;
        padding: 0.5rem 1rem;
        border-radius: 12px;
        font-weight: 500;
    }
</style>

<div class="page-header mb-4">
    <h1 style="font-weight: 300; font-family: 'Ubuntu', sans-serif;">
        <i class="bi bi-shield-lock-fill text-primary"></i> Sollicitations de Garantie
    </h1>
    <p class="text-muted" style="font-weight: 300; font-family: 'Ubuntu', sans-serif;">Vous avez été sollicité pour garantir les crédits suivants. En acceptant, vous participez au risque mais aussi aux bénéfices.</p>
</div>

@if($sollicitations->count() > 0)
    <div class="row">
        @foreach($sollicitations as $sollicitation)
            @php
                $nanoCredit = $sollicitation->nanoCredit;
                $emprunteur = $nanoCredit->membre;
                $palier = $nanoCredit->palier;
                
                // Calcul du gain potentiel
                $interetsTotaux = (float) ($palier->calculAmortissement((float) $nanoCredit->montant)['interet_total'] ?? 0);
                $pourcentagePartage = (float) ($palier->pourcentage_partage_garant ?? 0);
                $montantAPartager = $interetsTotaux * ($pourcentagePartage / 100);
                $nbGarants = $nanoCredit->garants()->count();
                $gainPotentiel = $nbGarants > 0 ? (int) round($montantAPartager / $nbGarants, 0) : 0;
            @endphp
            <div class="col-md-6 mb-4">
                <div class="card sollicitation-card h-100">
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between align-items-start mb-4">
                            <div>
                                <h6 class="mb-0 text-muted small text-uppercase" style="letter-spacing: 1px;">Demande #{{ $nanoCredit->id }}</h6>
                                <h5 class="fw-bold text-dark mt-1">{{ $emprunteur->nom_complet }}</h5>
                            </div>
                            <span class="badge bg-warning bg-opacity-10 text-warning px-3 py-2 rounded-pill small fw-bold">En attente</span>
                        </div>
                        
                        <div class="row g-3 mb-4">
                            <div class="col-6">
                                <div class="bg-light p-3 rounded-4">
                                    <small class="text-muted d-block mb-1">Montant du Crédit</small>
                                    <span class="fw-bold text-dark">{{ number_format($nanoCredit->montant, 0, ',', ' ') }} XOF</span>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="bg-success bg-opacity-10 p-3 rounded-4">
                                    <small class="text-success d-block mb-1">Gain Potentiel</small>
                                    <span class="fw-bold text-success">+ {{ number_format($gainPotentiel, 0, ',', ' ') }} XOF</span>
                                </div>
                            </div>
                        </div>

                        <div class="p-3 border rounded-4 mb-4 small bg-light bg-opacity-50">
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted">Palier :</span>
                                <span class="fw-bold">{{ $palier->nom }}</span>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted">Durée :</span>
                                <span class="fw-bold">{{ $palier->duree_jours }} Jours</span>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span class="text-muted">Partage Bénéfice :</span>
                                <span class="fw-bold text-primary">{{ $palier->pourcentage_partage_garant }}% des intérêts</span>
                            </div>
                        </div>

                        <!-- Accordéon / Collapsible pour Infos Demandeur -->
                        <div class="mb-4">
                            <button class="btn btn-outline-secondary btn-sm w-100 py-2 rounded-3 text-start d-flex justify-content-between align-items-center" type="button" data-bs-toggle="collapse" data-bs-target="#borrowerInfo{{ $sollicitation->id }}" aria-expanded="false" aria-controls="borrowerInfo{{ $sollicitation->id }}" style="font-size: 0.75rem;">
                                <span><i class="bi bi-person-lines-fill me-2 text-primary"></i> Informations du demandeur</span>
                                <i class="bi bi-chevron-down small"></i>
                            </button>
                            <div class="collapse mt-2" id="borrowerInfo{{ $sollicitation->id }}">
                                <div class="card card-body bg-light border-0 p-3 rounded-4 small mb-0">
                                    <div class="mb-2">
                                        <strong><i class="bi bi-telephone text-muted me-1"></i> Tél :</strong> {{ $emprunteur->telephone ?? '—' }}
                                    </div>
                                    <div class="mb-2">
                                        <strong><i class="bi bi-envelope text-muted me-1"></i> Mail :</strong> {{ $emprunteur->email ?? '—' }}
                                    </div>
                                    <div class="mb-2">
                                        <strong><i class="bi bi-geo-alt text-muted me-1"></i> Adresse :</strong> {{ $emprunteur->adresse ?? '—' }}
                                    </div>
                                    <div class="mb-2">
                                        <strong><i class="bi bi-calendar-date text-muted me-1"></i> Adhésion :</strong> {{ $emprunteur->date_adhesion ? $emprunteur->date_adhesion->format('d/m/Y') : '—' }}
                                    </div>
                                    <hr class="my-2">
                                    @php
                                        $creditsPrecedents = $emprunteur->nanoCredits;
                                        $totalCredits = $creditsPrecedents->count();
                                        $rembourseCredits = $creditsPrecedents->where('statut', 'rembourse')->count();
                                        $defautsPaiement = $emprunteur->nb_defauts_paiement ?? 0;
                                    @endphp
                                    <div class="mb-2">
                                        <strong><i class="bi bi-graph-up text-muted me-1"></i> Crédits précédents :</strong>
                                        <ul class="mb-0 ps-3 mt-1 list-unstyled">
                                            <li>• Demandés : <strong>{{ $totalCredits }}</strong></li>
                                            <li>• Remboursés avec succès : <strong class="text-success">{{ $rembourseCredits }}</strong></li>
                                            <li>• Défauts constatés : <strong class="{{ $defautsPaiement > 0 ? 'text-danger' : 'text-muted' }}">{{ $defautsPaiement }}</strong></li>
                                        </ul>
                                    </div>
                                    <hr class="my-2">
                                    <div>
                                        <strong><i class="bi bi-shield-exclamation text-muted me-1"></i> Risque possible :</strong>
                                        @if(is_null($nanoCredit->score_global))
                                            <span class="badge bg-secondary">Non évalué</span>
                                        @else
                                            @php
                                                $scoreVal = (int) $nanoCredit->score_global;
                                                $riskLabel = $scoreVal <= 1 ? 'Risque Faible' : ($scoreVal <= 3 ? 'Risque Modéré' : 'Risque Élevé');
                                                $badgeClass = $scoreVal <= 1 ? 'bg-success' : ($scoreVal <= 3 ? 'bg-warning text-dark' : 'bg-danger');
                                            @endphp
                                            <span class="badge {{ $badgeClass }}">{{ $riskLabel }} (Score : {{ $scoreVal }}/6)</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="alert alert-info border-0 p-3 small mb-4 rounded-4" style="background-color: #e3f2fd;">
                            <i class="bi bi-info-circle-fill me-2"></i>
                            En acceptant, un montant égal à <strong>{{ $palier->min_epargne_percent }}%</strong> du capital sera bloqué sur votre tontine jusqu'au remboursement final.
                        </div>

                        <div class="d-flex gap-2">
                            <form action="{{ route('membre.garant.accepter', $sollicitation) }}" method="POST" class="flex-grow-1">
                                @csrf
                                <button type="submit" class="btn btn-primary w-100 py-2 rounded-3" onclick="return confirm('Confirmez-vous votre engagement comme garant ? Votre solde sera bloqué à hauteur de la garantie.')">
                                    <i class="bi bi-check-circle me-1"></i> Accepter
                                </button>
                            </form>
                            <button type="button" class="btn btn-outline-danger px-3 py-2 rounded-3" data-bs-toggle="modal" data-bs-target="#refuseModal{{ $sollicitation->id }}">
                                <i class="bi bi-x-lg"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modal Refus -->
            <div class="modal fade" id="refuseModal{{ $sollicitation->id }}" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content border-0 shadow" style="border-radius: 20px;">
                        <form action="{{ route('membre.garant.refuser', $sollicitation) }}" method="POST">
                            @csrf
                            <div class="modal-header border-0 p-4">
                                <h5 class="modal-title fw-bold">Refuser la sollicitation</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body p-4 pt-0">
                                <div class="mb-3">
                                    <label class="form-label small text-muted">Souhaitez-vous indiquer un motif à l'emprunteur ? (Optionnel)</label>
                                    <textarea name="motif_refus" class="form-control rounded-4 p-3" rows="3" placeholder="Ex: Solde insuffisant pour le moment..."></textarea>
                                </div>
                                <p class="small text-danger">
                                    <i class="bi bi-exclamation-circle me-1"></i> 
                                    Le demandeur sera informé et devra trouver un autre garant.
                                </p>
                            </div>
                            <div class="modal-footer border-0 p-4 pt-0">
                                <button type="button" class="btn btn-light px-4" style="border-radius: 12px;" data-bs-dismiss="modal">Annuler</button>
                                <button type="submit" class="btn btn-danger px-4" style="border-radius: 12px;">Confirmer le refus</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="mt-4">
        {{ $sollicitations->links() }}
    </div>
@else
    <div class="card border-0 shadow-sm rounded-4 text-center py-5">
        <div class="card-body">
            <div class="bg-light rounded-circle d-inline-flex p-4 mb-4">
                <i class="bi bi-inbox text-muted fs-1"></i>
            </div>
            <h5 class="fw-bold">Aucune sollicitation en attente</h5>
            <p class="text-muted">Vous n'avez aucune demande de garantie pour le moment.</p>
            <a href="{{ route('membre.garant.index') }}" class="btn btn-primary px-4 py-2 mt-3" style="border-radius: 10px;">Retour à l'espace garant</a>
        </div>
    </div>
@endif

@endsection
