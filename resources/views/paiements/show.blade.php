@extends('layouts.app')

@section('title', 'Détails du Paiement')

@section('content')
<div class="page-header">
    <h1><i class="bi bi-cash-coin"></i> Paiement {{ $paiement->numero }}</h1>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card mb-4">
            <div class="card-header">
                <i class="bi bi-info-circle"></i> Informations
            </div>
            <div class="card-body">
                <dl class="row">
                    <dt class="col-sm-4" style="font-weight: 300; font-family: 'Ubuntu', sans-serif;">Numéro</dt>
                    <dd class="col-sm-8" style="font-weight: 300; font-family: 'Ubuntu', sans-serif;">
                        <strong>{{ $paiement->numero ?? '-' }}</strong>
                    </dd>
                    
                    <dt class="col-sm-4" style="font-weight: 300; font-family: 'Ubuntu', sans-serif;">Membre</dt>
                    <dd class="col-sm-8" style="font-weight: 300; font-family: 'Ubuntu', sans-serif;">
                        {{ $paiement->membre->nom_complet ?? '-' }} ({{ $paiement->membre->numero ?? '-' }})
                    </dd>
                    
                    <dt class="col-sm-4" style="font-weight: 300; font-family: 'Ubuntu', sans-serif;">Cagnotte</dt>
                    <dd class="col-sm-8" style="font-weight: 300; font-family: 'Ubuntu', sans-serif;">{{ $paiement->cotisation->nom ?? '-' }}</dd>
                    
                    <dt class="col-sm-4" style="font-weight: 300; font-family: 'Ubuntu', sans-serif;">Caisse</dt>
                    <dd class="col-sm-8" style="font-weight: 300; font-family: 'Ubuntu', sans-serif;">{{ $paiement->caisse->nom ?? '-' }}</dd>
                    
                    <dt class="col-sm-4" style="font-weight: 300; font-family: 'Ubuntu', sans-serif;">Montant</dt>
                    <dd class="col-sm-8" style="font-weight: 300; font-family: 'Ubuntu', sans-serif;">
                        <span class="badge bg-success">
                            {{ number_format($paiement->montant, 0, ',', ' ') }} XOF
                        </span>
                    </dd>
                    
                    <dt class="col-sm-4" style="font-weight: 300; font-family: 'Ubuntu', sans-serif;">Date de paiement</dt>
                    <dd class="col-sm-8" style="font-weight: 300; font-family: 'Ubuntu', sans-serif;">
                        {{ $paiement->date_paiement ? $paiement->date_paiement->format('d/m/Y') : '-' }}
                    </dd>
                    
                    <dt class="col-sm-4" style="font-weight: 300; font-family: 'Ubuntu', sans-serif;">Mode de paiement</dt>
                    <dd class="col-sm-8" style="font-weight: 300; font-family: 'Ubuntu', sans-serif;">
                        <span class="badge bg-info">{{ ucfirst(str_replace('_', ' ', $paiement->mode_paiement)) }}</span>
                    </dd>
                    
                    <dt class="col-sm-4" style="font-weight: 300; font-family: 'Ubuntu', sans-serif;">Notes</dt>
                    <dd class="col-sm-8" style="font-weight: 300; font-family: 'Ubuntu', sans-serif;">{{ $paiement->notes ?? '-' }}</dd>
                </dl>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card mb-4">
            <div class="card-header">
                <i class="bi bi-tools"></i> Actions
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <a href="{{ route('paiements.pdf', $paiement) }}?v={{ time() }}" target="_blank" class="btn btn-primary">
                        <i class="bi bi-file-pdf"></i> Voir l'aperçu PDF
                    </a>
                    <a href="{{ route('paiements.index') }}" class="btn btn-secondary">
                        <i class="bi bi-arrow-left"></i> Retour à la liste
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
