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
                <p class="mb-1"><strong>Palier Appliqué :</strong> 
                    @if($nanoCredit->palier)
                        <span class="badge" style="background: var(--primary-dark-blue);">Palier {{ $nanoCredit->palier->numero }} : {{ $nanoCredit->palier->nom }}</span>
                    @else
                        <span class="badge bg-secondary">Non spécifié (Ancien système)</span>
                    @endif
                </p>
                <p class="mb-1"><strong>Téléphone :</strong> {{ $nanoCredit->telephone ?: ($nanoCredit->membre->telephone ?? '—') }}</p>
                <p class="mb-1"><strong>Montant demandé :</strong> {{ number_format($nanoCredit->montant, 0, ',', ' ') }} XOF</p>
                
                @if($nanoCredit->montant_penalite > 0)
                    <p class="mb-1 text-danger"><strong>Pénalités de retard :</strong> {{ number_format($nanoCredit->montant_penalite, 0, ',', ' ') }} XOF</p>
                    <p class="mb-1 text-danger"><strong>Jours de retard :</strong> {{ $nanoCredit->jours_retard }} jours</p>
                @endif
                
                <p class="mb-1"><strong>Statut :</strong> {{ $nanoCredit->statut_label }}</p>
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
                    <p class="small text-muted">Le montant sera envoyé au mobile money du membre via PayDunya. Vérifiez le KYC avant d'octroyer.</p>
                    <form action="{{ route('nano-credits.octroyer', $nanoCredit) }}" method="POST">
                        @csrf
                        <div class="mb-2">
                            <label for="telephone" class="form-label small">Numéro bénéficiaire (sans indicatif) <span class="text-danger">*</span></label>
                            <input type="text" class="form-control form-control-sm @error('telephone') is-invalid @enderror" id="telephone" name="telephone" value="{{ old('telephone', $nanoCredit->telephone ?: preg_replace('/\D/', '', $nanoCredit->membre->telephone ?? '')) }}" required placeholder="771234567">
                            @error('telephone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="mb-2">
                            <label for="withdraw_mode" class="form-label small">Canal de retrait <span class="text-danger">*</span></label>
                            <select class="form-select form-select-sm @error('withdraw_mode') is-invalid @enderror" id="withdraw_mode" name="withdraw_mode" required>
                                @foreach($withdrawModes as $value => $label)
                                    <option value="{{ $value }}" {{ old('withdraw_mode', $nanoCredit->withdraw_mode ?? 'orange-money-senegal') === $value ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('withdraw_mode')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <button type="submit" class="btn btn-primary btn-sm" onclick="return confirm('Confirmer l\'octroi de ce nano-crédit ? Le montant sera transféré vers le compte mobile money du membre.');">
                            <i class="bi bi-send"></i> Octroyer le crédit (PayDunya)
                        </button>
                    </form>
                </div>
            </div>
        @else
            <div class="card">
                <div class="card-header"><i class="bi bi-info-circle"></i> Détails octroi</div>
                <div class="card-body">
                    @if($nanoCredit->date_octroi)
                        <p class="mb-1"><strong>Date d'octroi :</strong> {{ $nanoCredit->date_octroi->format('d/m/Y') }}</p>
                        <p class="mb-1"><strong>Fin remboursement :</strong> {{ $nanoCredit->date_fin_remboursement ? $nanoCredit->date_fin_remboursement->format('d/m/Y') : '—' }}</p>
                    @endif
                    @if($nanoCredit->transaction_id)
                        <p class="mb-0"><strong>Transaction PayDunya :</strong> <small>{{ $nanoCredit->transaction_id }}</small></p>
                    @endif
                </div>
            </div>
        @endif
    </div>
</div>

@if($nanoCredit->isDebourse() && $nanoCredit->echeances->count() > 0)
    <div class="card mb-3">
        <div class="card-header"><i class="bi bi-calendar-check"></i> Tableau d'amortissement</div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm table-striped mb-0">
                    <thead>
                        <tr>
                            <th>Date échéance</th>
                            <th class="text-end">Montant</th>
                            <th>Statut</th>
                            <th>Date paiement</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($nanoCredit->echeances as $e)
                            <tr>
                                <td>{{ $e->date_echeance->format('d/m/Y') }}</td>
                                <td class="text-end">{{ number_format($e->montant, 0, ',', ' ') }} XOF</td>
                                <td>
                                    @if($e->statut === 'payee')
                                        <span class="badge bg-success">Payée</span>
                                    @elseif($e->statut === 'en_retard')
                                        <span class="badge bg-danger">En retard</span>
                                    @else
                                        <span class="badge bg-secondary">À venir</span>
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
