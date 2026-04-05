@extends('layouts.app')

@section('title', 'Payer un Engagement')

@section('content')
<div class="page-header">
    <h1><i class="bi bi-cash-coin"></i> Payer un Engagement</h1>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card mb-4">
            <div class="card-header">
                <i class="bi bi-info-circle"></i> Informations de l'Engagement
            </div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-4" style="font-weight: 300; font-family: 'Ubuntu', sans-serif;">Numéro</dt>
                    <dd class="col-sm-8" style="font-weight: 300; font-family: 'Ubuntu', sans-serif;">
                        <strong>{{ $engagement->numero ?? '-' }}</strong>
                    </dd>
                    
                    <dt class="col-sm-4" style="font-weight: 300; font-family: 'Ubuntu', sans-serif;">Membre</dt>
                    <dd class="col-sm-8" style="font-weight: 300; font-family: 'Ubuntu', sans-serif;">
                        {{ $engagement->membre->nom_complet ?? '-' }} ({{ $engagement->membre->numero ?? '-' }})
                    </dd>
                    
                    <dt class="col-sm-4" style="font-weight: 300; font-family: 'Ubuntu', sans-serif;">Cagnotte</dt>
                    <dd class="col-sm-8" style="font-weight: 300; font-family: 'Ubuntu', sans-serif;">{{ $engagement->cotisation->nom ?? '-' }}</dd>
                    
                    <dt class="col-sm-4" style="font-weight: 300; font-family: 'Ubuntu', sans-serif;">Caisse</dt>
                    <dd class="col-sm-8" style="font-weight: 300; font-family: 'Ubuntu', sans-serif;">{{ $engagement->cotisation->caisse->nom ?? '-' }}</dd>
                    
                    <dt class="col-sm-4" style="font-weight: 300; font-family: 'Ubuntu', sans-serif;">Montant par période</dt>
                    <dd class="col-sm-8" style="font-weight: 300; font-family: 'Ubuntu', sans-serif;">
                        <span class="badge bg-info">
                            {{ number_format($engagement->montant_engage, 0, ',', ' ') }} XOF
                        </span>
                    </dd>
                    
                    <dt class="col-sm-4" style="font-weight: 300; font-family: 'Ubuntu', sans-serif;">Périodicité</dt>
                    <dd class="col-sm-8" style="font-weight: 300; font-family: 'Ubuntu', sans-serif;">
                        <span class="badge bg-secondary">{{ ucfirst($engagement->periodicite ?? 'mensuelle') }}</span>
                        <small class="text-muted">({{ $engagement->nombre_periodes }} période{{ $engagement->nombre_periodes > 1 ? 's' : '' }})</small>
                    </dd>
                    
                    <dt class="col-sm-4" style="font-weight: 300; font-family: 'Ubuntu', sans-serif;">Montant total</dt>
                    <dd class="col-sm-8" style="font-weight: 300; font-family: 'Ubuntu', sans-serif;">
                        <span class="badge bg-primary">
                            {{ number_format($engagement->montant_total, 0, ',', ' ') }} XOF
                        </span>
                    </dd>
                    
                    <dt class="col-sm-4" style="font-weight: 300; font-family: 'Ubuntu', sans-serif;">Montant payé</dt>
                    <dd class="col-sm-8" style="font-weight: 300; font-family: 'Ubuntu', sans-serif;">
                        <span class="badge bg-success">
                            {{ number_format($engagement->montant_paye, 0, ',', ' ') }} XOF
                        </span>
                    </dd>
                    
                    <dt class="col-sm-4" style="font-weight: 300; font-family: 'Ubuntu', sans-serif;">Reste à payer</dt>
                    <dd class="col-sm-8" style="font-weight: 300; font-family: 'Ubuntu', sans-serif;">
                        <span class="badge {{ $engagement->reste_a_payer > 0 ? 'bg-warning' : 'bg-success' }}">
                            {{ number_format($engagement->reste_a_payer, 0, ',', ' ') }} XOF
                        </span>
                    </dd>
                    
                    <dt class="col-sm-4" style="font-weight: 300; font-family: 'Ubuntu', sans-serif;">Période</dt>
                    <dd class="col-sm-8" style="font-weight: 300; font-family: 'Ubuntu', sans-serif;">
                        {{ $engagement->periode_debut->format('d/m/Y') }} - {{ $engagement->periode_fin->format('d/m/Y') }}
                    </dd>
                </dl>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <i class="bi bi-cash-coin"></i> Enregistrer le Paiement
            </div>
            <div class="card-body">
                <form action="{{ route('paiements.engagement.store', $engagement) }}" method="POST">
                    @csrf
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="montant" class="form-label">
                                Montant (XOF) <span class="text-danger">*</span>
                            </label>
                            <input type="number" 
                                   class="form-control @error('montant') is-invalid @enderror" 
                                   id="montant" 
                                   name="montant" 
                                   value="{{ old('montant') }}" 
                                   min="1" 
                                   max="{{ $engagement->reste_a_payer }}"
                                   step="1" 
                                   required>
                            @error('montant')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="form-text text-muted" style="font-size: 0.7rem;">
                                Maximum: {{ number_format($engagement->reste_a_payer, 0, ',', ' ') }} XOF
                            </small>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="date_paiement" class="form-label">
                                Date de paiement <span class="text-danger">*</span>
                            </label>
                            <input type="date" 
                                   class="form-control @error('date_paiement') is-invalid @enderror" 
                                   id="date_paiement" 
                                   name="date_paiement" 
                                   value="{{ old('date_paiement', date('Y-m-d')) }}" 
                                   required>
                            @error('date_paiement')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="mode_paiement" class="form-label">
                                Mode de paiement <span class="text-danger">*</span>
                            </label>
                            <select class="form-select @error('mode_paiement') is-invalid @enderror" 
                                    id="mode_paiement" 
                                    name="mode_paiement" 
                                    required>
                                <option value="especes" {{ old('mode_paiement') === 'especes' ? 'selected' : '' }}>Espèces</option>
                                <option value="cheque" {{ old('mode_paiement') === 'cheque' ? 'selected' : '' }}>Chèque</option>
                                <option value="virement" {{ old('mode_paiement') === 'virement' ? 'selected' : '' }}>Virement</option>
                                <option value="mobile_money" {{ old('mode_paiement') === 'mobile_money' ? 'selected' : '' }}>Mobile Money</option>
                                <option value="autre" {{ old('mode_paiement') === 'autre' ? 'selected' : '' }}>Autre</option>
                            </select>
                            @error('mode_paiement')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="caisse_info" class="form-label">Caisse</label>
                            <input type="text" 
                                   class="form-control" 
                                   id="caisse_info" 
                                   value="{{ $engagement->cotisation->caisse->nom ?? '-' }}"
                                   readonly
                                   style="background-color: #f8f9fa; cursor: not-allowed;">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="notes" class="form-label">Notes</label>
                        <textarea class="form-control @error('notes') is-invalid @enderror" 
                                  id="notes" 
                                  name="notes" 
                                  rows="2">{{ old('notes') }}</textarea>
                        @error('notes')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div class="d-flex justify-content-between">
                        <a href="{{ route('paiements.engagement.index') }}" class="btn btn-secondary">
                            <i class="bi bi-arrow-left"></i> Retour
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-circle"></i> Enregistrer le paiement
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <i class="bi bi-info-circle"></i> À propos des Paiements d'Engagement
            </div>
            <div class="card-body">
                <h6 class="mb-3" style="font-weight: 300; font-family: 'Ubuntu', sans-serif; color: var(--primary-dark-blue);">
                    <i class="bi bi-clipboard-check"></i> Qu'est-ce qu'un paiement d'engagement ?
                </h6>
                <p style="font-size: 0.75rem; line-height: 1.5; font-weight: 300; font-family: 'Ubuntu', sans-serif; color: #666;">
                    Un paiement d'engagement permet de régler partiellement ou totalement un engagement pris par un membre. Le montant ne peut pas dépasser le reste à payer.
                </p>
                
                <h6 class="mt-4 mb-3" style="font-weight: 300; font-family: 'Ubuntu', sans-serif; color: var(--primary-dark-blue);">
                    <i class="bi bi-calculator"></i> Montant maximum
                </h6>
                <p style="font-size: 0.75rem; line-height: 1.5; font-weight: 300; font-family: 'Ubuntu', sans-serif; color: #666;">
                    Le montant du paiement ne peut pas dépasser le reste à payer de l'engagement. Le système calcule automatiquement le montant total, les paiements effectués et le reste à payer.
                </p>
                
                <h6 class="mt-4 mb-3" style="font-weight: 300; font-family: 'Ubuntu', sans-serif; color: var(--primary-dark-blue);">
                    <i class="bi bi-lightbulb"></i> Suivi
                </h6>
                <p style="font-size: 0.75rem; line-height: 1.5; font-weight: 300; font-family: 'Ubuntu', sans-serif; color: #666;">
                    Chaque paiement réduit le reste à payer et crédite la caisse associée à la cagnotte de l'engagement. Vous pouvez effectuer plusieurs paiements pour régler un engagement.
                </p>
            </div>
        </div>
    </div>
</div>
@endsection
