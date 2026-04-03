@extends('layouts.app')

@section('title', 'Détails de la Cagnotte')

@section('content')
<div class="page-header">
    <h1><i class="bi bi-receipt"></i> {{ $cotisation->nom }}</h1>
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
                        <strong>{{ $cotisation->numero ?? '-' }}</strong>
                    </dd>
                    
                    <dt class="col-sm-4" style="font-weight: 300; font-family: 'Ubuntu', sans-serif;">Nom</dt>
                    <dd class="col-sm-8" style="font-weight: 300; font-family: 'Ubuntu', sans-serif;">{{ $cotisation->nom }}</dd>
                    
                    <dt class="col-sm-4" style="font-weight: 300; font-family: 'Ubuntu', sans-serif;">Caisse</dt>
                    <dd class="col-sm-8" style="font-weight: 300; font-family: 'Ubuntu', sans-serif;">{{ $cotisation->caisse->nom ?? '-' }}</dd>
                    
                    <dt class="col-sm-4" style="font-weight: 300; font-family: 'Ubuntu', sans-serif;">Type</dt>
                    <dd class="col-sm-8" style="font-weight: 300; font-family: 'Ubuntu', sans-serif;">
                        <span class="badge bg-info">{{ ucfirst($cotisation->type) }}</span>
                    </dd>
                    
                    <dt class="col-sm-4" style="font-weight: 300; font-family: 'Ubuntu', sans-serif;">Fréquence</dt>
                    <dd class="col-sm-8" style="font-weight: 300; font-family: 'Ubuntu', sans-serif;">
                        <span class="badge bg-secondary">{{ ucfirst($cotisation->frequence) }}</span>
                    </dd>
                    
                    <dt class="col-sm-4" style="font-weight: 300; font-family: 'Ubuntu', sans-serif;">Type de montant</dt>
                    <dd class="col-sm-8" style="font-weight: 300; font-family: 'Ubuntu', sans-serif;">
                        <span class="badge {{ ($cotisation->type_montant ?? 'fixe') === 'libre' ? 'bg-warning' : 'bg-primary' }}">
                            {{ ($cotisation->type_montant ?? 'fixe') === 'libre' ? 'Libre' : 'Fixe' }}
                        </span>
                    </dd>
                    
                    <dt class="col-sm-4" style="font-weight: 300; font-family: 'Ubuntu', sans-serif;">Montant</dt>
                    <dd class="col-sm-8" style="font-weight: 300; font-family: 'Ubuntu', sans-serif;">
                        @if($cotisation->montant)
                            <span class="badge bg-primary">
                                {{ number_format($cotisation->montant, 0, ',', ' ') }} XOF
                            </span>
                        @else
                            <span class="text-muted">Montant libre</span>
                        @endif
                    </dd>
                    
                    <dt class="col-sm-4" style="font-weight: 300; font-family: 'Ubuntu', sans-serif;">Description</dt>
                    <dd class="col-sm-8" style="font-weight: 300; font-family: 'Ubuntu', sans-serif;">{{ $cotisation->description ?? '-' }}</dd>
                    
                    <dt class="col-sm-4" style="font-weight: 300; font-family: 'Ubuntu', sans-serif;">Statut</dt>
                    <dd class="col-sm-8" style="font-weight: 300; font-family: 'Ubuntu', sans-serif;">
                        @if($cotisation->actif)
                            <span class="badge bg-success">Active</span>
                        @else
                            <span class="badge bg-secondary">Inactive</span>
                        @endif
                    </dd>
                    
                    <dt class="col-sm-4" style="font-weight: 300; font-family: 'Ubuntu', sans-serif;">Notes</dt>
                    <dd class="col-sm-8" style="font-weight: 300; font-family: 'Ubuntu', sans-serif;">{{ $cotisation->notes ?? '-' }}</dd>
                </dl>
            </div>
        </div>
        
        @if($cotisation->paiements->count() > 0)
            <div class="card">
                <div class="card-header">
                    <i class="bi bi-cash-coin"></i> Paiements associés ({{ $cotisation->paiements->count() }})
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Numéro</th>
                                    <th>Membre</th>
                                    <th>Montant</th>
                                    <th>Date</th>
                                    <th>Mode</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($cotisation->paiements as $paiement)
                                    <tr>
                                        <td style="font-weight: 300; font-family: 'Ubuntu', sans-serif;">{{ $paiement->numero }}</td>
                                        <td style="font-weight: 300; font-family: 'Ubuntu', sans-serif;">{{ $paiement->membre->nom_complet ?? '-' }}</td>
                                        <td style="font-weight: 300; font-family: 'Ubuntu', sans-serif;">{{ number_format($paiement->montant, 0, ',', ' ') }} XOF</td>
                                        <td style="font-weight: 300; font-family: 'Ubuntu', sans-serif;">{{ $paiement->date_paiement->format('d/m/Y') }}</td>
                                        <td style="font-weight: 300; font-family: 'Ubuntu', sans-serif;">{{ ucfirst($paiement->mode_paiement) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @endif
    </div>
    
    <div class="col-md-4">
        <div class="card mb-4">
            <div class="card-header">
                <i class="bi bi-tools"></i> Actions
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <a href="{{ route('cotisations.edit', $cotisation) }}" class="btn btn-warning">
                        <i class="bi bi-pencil"></i> Modifier
                    </a>
                    <a href="{{ route('cotisations.index') }}" class="btn btn-secondary">
                        <i class="bi bi-arrow-left"></i> Retour à la liste
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
