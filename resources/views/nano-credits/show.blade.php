@extends('layouts.app')

@section('title', 'Demande nano crédit #' . $nanoCredit->id)

@section('content')
<div class="page-header d-flex justify-content-between align-items-center flex-wrap">
    <h1><i class="bi bi-phone"></i> Demande nano crédit #{{ $nanoCredit->id }}</h1>
    <a href="{{ route('nano-credits.index') }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i> Retour aux demandes</a>
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

<div class="row">
    <div class="col-md-6 mb-3">
        <div class="card h-100">
            <div class="card-header"><i class="bi bi-person"></i> Membre & demande</div>
            <div class="card-body">
                <p class="mb-1"><strong>Membre :</strong> 
                    @if($nanoCredit->membre)
                        <a href="{{ route('membres.show', $nanoCredit->membre) }}">{{ $nanoCredit->membre->nom_complet ?? '—' }}</a>
                    @else
                        <span class="text-muted">Inconnu</span>
                    @endif
                </p>
                @if($nanoCredit->beneficiaire_effectif_id)
                    <p class="mb-1 text-warning"><strong>Bénéficiaire Effectif :</strong> 
                        @if($nanoCredit->beneficiaireEffectif)
                            <a href="{{ route('membres.show', $nanoCredit->beneficiaireEffectif) }}" class="fw-bold text-warning">{{ $nanoCredit->beneficiaireEffectif->nom_complet ?? '—' }}</a>
                        @else
                            <span class="text-muted">Inconnu</span>
                        @endif
                    </p>
                @endif
                <p class="mb-1"><strong>Palier Appliqué :</strong> 
                    @if($nanoCredit->palier)
                        <span class="badge" style="background: var(--primary-dark-blue);">Palier {{ $nanoCredit->palier->numero }} : {{ $nanoCredit->palier->nom }}</span>
                    @else
                        <span class="badge bg-secondary">Non spécifié (Ancien système)</span>
                    @endif
                </p>
                <p class="mb-1"><strong>Téléphone :</strong> {{ $nanoCredit->telephone ?: ($nanoCredit->membre->telephone ?? '—') }}</p>
                <div class="bg-light p-2 rounded mb-2 border-start border-primary border-4">
                    <p class="mb-1"><strong>Capital emprunté :</strong> <span class="text-primary fw-bold">{{ number_format($nanoCredit->montant, 0, ',', ' ') }} XOF</span></p>
                    @if($nanoCredit->palier)
                        @php $amort = $nanoCredit->palier->calculAmortissement((float)$nanoCredit->getRawOriginal('montant')); @endphp
                        <p class="mb-1 small"><strong>Taux d'intérêt :</strong> {{ $nanoCredit->palier->taux_interet }}% (Simple)</p>
                        <p class="mb-1 small"><strong>Intérêts attendus :</strong> <span class="text-success">+{{ number_format($amort['interet_total'], 0, ',', ' ') }} XOF</span></p>
                        <p class="mb-0 fw-bold border-top mt-1 pt-1"><strong>Total à rembourser :</strong> {{ number_format($amort['montant_total_du'], 0, ',', ' ') }} XOF</p>
                    @endif
                </div>
                
                @if($nanoCredit->montant_penalite > 0)
                    <p class="mb-1 text-danger"><strong>Pénalités de retard :</strong> {{ number_format($nanoCredit->montant_penalite, 0, ',', ' ') }} XOF</p>
                    <p class="mb-1 text-danger"><strong>Jours de retard :</strong> {{ $nanoCredit->jours_retard }} jours</p>
                @endif
                
                <p class="mb-1"><strong>Statut :</strong> <span class="badge {{ $nanoCredit->statut === 'debourse' ? 'bg-success' : 'bg-secondary' }}">{{ $nanoCredit->statut_label }}</span></p>
                <p class="mb-0"><strong>Date demande :</strong> {{ $nanoCredit->created_at->format('d/m/Y H:i') }}</p>

                <hr>
                <div class="mb-2">
                    <strong><i class="bi bi-people"></i> Garants ({{ $nanoCredit->garants()->count() }}) :</strong>
                    @if($nanoCredit->garants()->count() > 0)
                        <ul class="mb-0 small" style="padding-left: 1rem;">
                            @foreach($nanoCredit->garants as $garant)
                                <li>
                                    @if($garant->membre)
                                        <a href="{{ route('membres.show', $garant->membre) }}">{{ $garant->membre->nom_complet }}</a>
                                    @else
                                        <span class="text-muted">Membre inconnu</span>
                                    @endif
                                    @if($garant->statut === 'en_attente')
                                        <span class="badge bg-warning text-dark">En attente</span>
                                    @elseif($garant->statut === 'accepte')
                                        <span class="badge bg-success">Accepté</span>
                                    @elseif($garant->statut === 'refuse')
                                        <span class="badge bg-danger">Refusé</span>
                                    @elseif($garant->statut === 'impaye')
                                        <span class="badge bg-dark">Prélevé (Impayé)</span>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <span class="text-muted small ms-1">Aucun garant.</span>
                    @endif
                </div>

                <hr>
                @if($nanoCredit->membre->kycVerification)
                    <a href="{{ route('kyc.show', $nanoCredit->membre->kycVerification) }}" class="btn btn-outline-primary btn-sm" target="_blank">
                        <i class="bi bi-shield-check"></i> Consulter le KYC du client
                    </a>
                @else
                    <span class="text-muted small">Aucun KYC enregistré pour ce membre.</span>
                @endif
            </div>
        </div>
        
        <div class="card mt-3 border-info">
            <div class="card-header bg-info text-white"><i class="bi bi-shield-lock"></i> Évaluation du risque</div>
            <div class="card-body">
                <div class="d-flex justify-content-between mb-2">
                    <span>Score IA (Basé sur le système) :</span>
                    <span class="badge {{ $nanoCredit->score_ai <= 1 ? 'bg-success' : ($nanoCredit->score_ai == 2 ? 'bg-warning' : 'bg-danger') }}">{{ $nanoCredit->score_ai ?? 'N/A' }} / 3</span>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span>Score Humain (Admin) :</span>
                    <span class="badge bg-secondary">{{ $nanoCredit->score_humain ?? 'Non évalué' }} @if($nanoCredit->score_humain !== null) / 3 @endif</span>
                </div>
                <div class="d-flex justify-content-between mb-3 fw-bold border-top pt-2">
                    <span>Score Global :</span>
                    <span class="badge {{ $nanoCredit->score_global <= 1 ? 'bg-success' : ($nanoCredit->score_global <= 3 ? 'bg-warning text-dark' : 'bg-danger') }} fs-6">{{ $nanoCredit->score_global ?? 'N/A' }} / 6</span>
                </div>

                    <form action="{{ route('nano-credits.risk-score.update', $nanoCredit) }}" method="POST" class="mt-3 border-top pt-3">
                        @csrf
                        <div class="mb-2">
                            <label class="form-label small fw-bold">Évaluation Manuelle (0 = Sûr, 3 = Risqué)</label>
                            <select class="form-select form-select-sm" name="score_humain" required>
                                <option value="" disabled selected>Sélectionner une note</option>
                                <option value="0" {{ $nanoCredit->score_humain === 0 ? 'selected' : '' }}>0 - Profil très sûr</option>
                                <option value="1" {{ $nanoCredit->score_humain === 1 ? 'selected' : '' }}>1 - Risque mineur</option>
                                <option value="2" {{ $nanoCredit->score_humain === 2 ? 'selected' : '' }}>2 - Risque modéré</option>
                                <option value="3" {{ $nanoCredit->score_humain === 3 ? 'selected' : '' }}>3 - Profil très risqué</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-warning btn-sm text-dark fw-bold w-100">
                            <i class="bi bi-arrow-repeat"></i> Réévaluer manuellement
                        </button>
                    </form>
            </div>
        </div>
    </div>

    <div class="col-md-6 mb-3">
        @if($nanoCredit->isEnAttente())
            <div class="card border-primary">
                <div class="card-header bg-primary text-white"><i class="bi bi-send"></i> Octroyer le crédit</div>
                <div class="card-body">
                    @php
                        $defaultAlias = $nanoCredit->membre->defaultWalletAlias();
                    @endphp
                    @if($defaultAlias)
                        <div class="alert alert-info py-2 mb-3" style="font-size: 0.75rem;">
                            <i class="bi bi-lightning-charge-fill text-warning me-1"></i>
                            <strong>Alias Pi-SPI détecté :</strong> <code>{{ $defaultAlias->alias }}</code> ({{ $defaultAlias->label }})
                        </div>
                    @endif
                    <p class="small text-muted">Le montant sera envoyé au membre via le canal choisi. Priorité à l'alias Pi-SPI si disponible.</p>
                    <form action="{{ route('nano-credits.octroyer', $nanoCredit) }}" method="POST">
                        @csrf
                        <div class="mb-2">
                            <label for="withdraw_mode" class="form-label small">Canal de déboursement <span class="text-danger">*</span></label>
                            <select class="form-select form-select-sm @error('withdraw_mode') is-invalid @enderror" id="withdraw_mode_display" disabled>
                                @foreach($withdrawModes as $value => $label)
                                    <option value="{{ $value }}" {{ $value === 'pispi' ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                            <input type="hidden" name="withdraw_mode" value="pispi">
                            @error('withdraw_mode')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="mb-2" id="div_telephone">
                            <label for="telephone" class="form-label small" id="label_beneficiary">Alias Pi-SPI du bénéficiaire <span class="text-danger">*</span></label>
                            <input type="text" class="form-control form-control-sm @error('telephone') is-invalid @enderror" id="telephone" name="telephone" 
                                   value="{{ $defaultAlias ? $defaultAlias->alias : '' }}" 
                                   readonly placeholder="26b54952...">
                            @if($defaultAlias)
                                <div class="form-text x-small text-success"><i class="bi bi-info-circle"></i> Alias par défaut détecté : <strong>{{ $defaultAlias->alias }}</strong></div>
                            @endif
                            @error('telephone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <button type="submit" class="btn btn-primary btn-sm w-100 py-2 fw-bold" onclick="return confirm('Confirmer l\'octroi (DÉBOURSEMENT) de ce nano-crédit via Pi-SPI ?');">
                            <i class="bi bi-send-check"></i> CONFIRMER L'OCTROI (DÉBOURSEMENT)
                        </button>
                    </form>

                    <script>
                        // Canal fixe vers Pi-SPI comme demandé
                        document.addEventListener('DOMContentLoaded', function() {
                            const input = document.getElementById('telephone');
                            if (!input.value && "{{ $defaultAlias->alias ?? '' }}") {
                                input.value = "{{ $defaultAlias->alias ?? '' }}";
                            }
                        });
                    </script>
                </div>
            </div>
        @else
            <div class="card shadow-sm border-0 bg-light-soft">
                <div class="card-header bg-white py-3">
                    <h5 class="card-title mb-0 text-success"><i class="bi bi-info-circle-fill me-2"></i>Détails du décaissement</h5>
                </div>
                <div class="card-body p-4">
                    @if($nanoCredit->date_octroi)
                        <div class="mb-3 d-flex justify-content-between align-items-center">
                            <span class="text-muted">Date d'activation :</span>
                            <span class="fw-bold"><i class="bi bi-calendar-check me-1"></i>{{ $nanoCredit->date_octroi->format('d/m/Y') }}</span>
                        </div>
                        <div class="mb-3 d-flex justify-content-between align-items-center text-danger">
                            <span class="text-muted text-danger">Échéance finale :</span>
                            <span class="fw-bold"><i class="bi bi-calendar-x me-1"></i>{{ $nanoCredit->date_fin_remboursement ? $nanoCredit->date_fin_remboursement->format('d/m/Y') : '—' }}</span>
                        </div>
                    @endif
                    @if($nanoCredit->transaction_id)
                        <div class="bg-white p-3 rounded border">
                            <label class="x-small text-muted text-uppercase fw-bold d-block mb-1">Réf. Transaction (Pi-SPI / PayDunya)</label>
                            <code class="text-dark small">{{ $nanoCredit->transaction_id }}</code>
                        </div>
                    @endif
                </div>
            </div>
        @endif
    </div>
</div>

@if($nanoCredit->isDebourse())
    <div class="card mb-3 shadow-sm border-0">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0 text-primary"><i class="bi bi-calendar-week-fill me-2"></i>Tableau d'amortissement</h5>
            
            <form action="{{ route('nano-credits.regenerer-echeancier', $nanoCredit) }}" method="POST" onsubmit="return confirm('Attention : cela supprimera les échéances actuelles pour les recalculer selon le palier. Continuer ?');">
                @csrf
                <button type="submit" class="btn btn-sm btn-outline-warning">
                    <i class="bi bi-arrow-repeat me-1"></i> Régénérer l'échéancier
                </button>
            </form>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm table-striped mb-0">
                    <thead>
                        <tr>
                            <th>Date échéance</th>
                            <th class="text-end">Montant</th>
                            <th>Statut</th>
                            <th>État</th>
                            <th>Date paiement</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($nanoCredit->echeances as $e)
                            @php 
                                $tempStatus = $e->temporal_status;
                                $etat = $e->statut;
                            @endphp
                            <tr>
                                <td>{{ $e->date_echeance->format('d/m/Y') }}</td>
                                <td class="text-end fw-bold">{{ number_format($e->montant, 0, ',', ' ') }} XOF</td>
                                <td>
                                    @if($tempStatus === 'en_retard')
                                        <span class="badge bg-danger">En retard</span>
                                    @elseif($tempStatus === 'aujourd_hui')
                                        <span class="badge bg-warning text-dark">Aujourd'hui</span>
                                    @else
                                        <span class="badge bg-secondary">À venir</span>
                                    @endif
                                </td>
                                <td>
                                    @if($etat === 'payee')
                                        <span class="badge bg-success">Payée</span>
                                    @else
                                        <span class="badge bg-light text-muted border">En attente</span>
                                    @endif
                                </td>
                                <td>{{ $e->paye_le ? $e->paye_le->format('d/m/Y H:i') : '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endif

@if($nanoCredit->isDebourse())
    <div class="card mb-3">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span><i class="bi bi-cash-coin"></i> Enregistrer un remboursement</span>
        </div>
        <div class="card-body">
            <form action="{{ route('nano-credits.versement.store', $nanoCredit) }}" method="POST" class="row g-2 align-items-end">
                @csrf
                <div class="col-md-2">
                    <label class="form-label small mb-0">Montant (XOF)</label>
                    <input type="number" step="1" min="1" name="montant" class="form-control form-control-sm" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label small mb-0">Date</label>
                    <input type="date" name="date_versement" class="form-control form-control-sm" value="{{ date('Y-m-d') }}" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label small mb-0">Mode</label>
                    <input type="text" name="mode_paiement" class="form-control form-control-sm" value="especes" placeholder="Espèces, virement...">
                </div>
                <div class="col-md-3">
                    <label class="form-label small mb-0">Imputer à l'échéance</label>
                    <select name="echeance_id" class="form-select form-select-sm">
                        <option value="">—</option>
                        @foreach($nanoCredit->echeances->where('statut', '!=', 'payee') as $e)
                            <option value="{{ $e->id }}">{{ $e->date_echeance->format('d/m/Y') }} — {{ number_format($e->montant, 0, ',', ' ') }} XOF</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary btn-sm w-100"><i class="bi bi-plus"></i> Enregistrer</button>
                </div>
            </form>
        </div>
    </div>
@endif

@if($nanoCredit->versements->count() > 0)
    <div class="card">
        <div class="card-header"><i class="bi bi-cash-coin"></i> Historique des remboursements</div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm table-striped mb-0">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th class="text-end">Montant</th>
                            <th>Mode</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($nanoCredit->versements->sortByDesc('date_versement') as $v)
                            <tr>
                                <td>{{ $v->date_versement->format('d/m/Y') }}</td>
                                <td class="text-end">{{ number_format($v->montant, 0, ',', ' ') }} XOF</td>
                                <td>{{ $v->mode_paiement }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endif
@endsection
