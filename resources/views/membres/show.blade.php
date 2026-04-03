@extends('layouts.app')

@section('title', 'Détails du Membre')

@section('content')
<div class="page-header">
    <h1><i class="bi bi-person"></i> {{ $membre->nom_complet }}</h1>
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
                        <strong>{{ $membre->numero ?? '-' }}</strong>
                    </dd>
                    
                    <dt class="col-sm-4" style="font-weight: 300; font-family: 'Ubuntu', sans-serif;">Nom</dt>
                    <dd class="col-sm-8" style="font-weight: 300; font-family: 'Ubuntu', sans-serif;">{{ $membre->nom }}</dd>
                    
                    <dt class="col-sm-4" style="font-weight: 300; font-family: 'Ubuntu', sans-serif;">Prénom</dt>
                    <dd class="col-sm-8" style="font-weight: 300; font-family: 'Ubuntu', sans-serif;">{{ $membre->prenom }}</dd>
                    
                    <dt class="col-sm-4" style="font-weight: 300; font-family: 'Ubuntu', sans-serif;">Email</dt>
                    <dd class="col-sm-8" style="font-weight: 300; font-family: 'Ubuntu', sans-serif;">{{ $membre->email }}</dd>
                    
                    <dt class="col-sm-4" style="font-weight: 300; font-family: 'Ubuntu', sans-serif;">Téléphone</dt>
                    <dd class="col-sm-8" style="font-weight: 300; font-family: 'Ubuntu', sans-serif;">{{ $membre->telephone ?? '-' }}</dd>
                    
                    <dt class="col-sm-4" style="font-weight: 300; font-family: 'Ubuntu', sans-serif;">Adresse</dt>
                    <dd class="col-sm-8" style="font-weight: 300; font-family: 'Ubuntu', sans-serif;">{{ $membre->adresse ?? '-' }}</dd>
                    
                    <dt class="col-sm-4" style="font-weight: 300; font-family: 'Ubuntu', sans-serif;">Date d'adhésion</dt>
                    <dd class="col-sm-8" style="font-weight: 300; font-family: 'Ubuntu', sans-serif;">{{ $membre->date_adhesion ? $membre->date_adhesion->format('d/m/Y') : '-' }}</dd>
                    
                    <dt class="col-sm-4" style="font-weight: 300; font-family: 'Ubuntu', sans-serif;">Statut</dt>
                    <dd class="col-sm-8" style="font-weight: 300; font-family: 'Ubuntu', sans-serif;">
                        @if($membre->statut === 'actif')
                            <span class="badge bg-success">Actif</span>
                        @elseif($membre->statut === 'inactif')
                            <span class="badge bg-secondary">Inactif</span>
                        @else
                            <span class="badge bg-warning">Suspendu</span>
                        @endif
                    </dd>

                    <dt class="col-sm-12 mt-3 mb-2"><h5 class="border-bottom pb-2"><i class="bi bi-phone me-1"></i> Nano-Crédit</h5></dt>

                    <dt class="col-sm-4" style="font-weight: 300; font-family: 'Ubuntu', sans-serif;">Palier Actuel</dt>
                    <dd class="col-sm-8" style="font-weight: 300; font-family: 'Ubuntu', sans-serif;">
                        @if($membre->nanoCreditPalier)
                            <span class="badge" style="background: var(--primary-dark-blue);">Palier {{ $membre->nanoCreditPalier->numero }} : {{ $membre->nanoCreditPalier->nom }}</span>
                            <div class="mt-1" style="font-size: 0.8rem;">
                                Plafond: {{ number_format((float)$membre->nanoCreditPalier->montant_plafond, 0, ',', ' ') }} FCFA<br>
                                Garants: {{ $membre->nanoCreditPalier->nombre_garants }}<br>
                                Garants liés à des défauts: {{ $membre->garants()->where('statut', 'impaye')->count() }}
                            </div>
                        @else
                            <span class="badge bg-secondary">Non assigné</span>
                        @endif
                    </dd>

                    <dt class="col-sm-4" style="font-weight: 300; font-family: 'Ubuntu', sans-serif;">Interdiction</dt>
                    <dd class="col-sm-8" style="font-weight: 300; font-family: 'Ubuntu', sans-serif;">
                        @if($membre->nano_credit_interdit)
                            <span class="badge bg-danger"><i class="bi bi-slash-circle me-1"></i>Oui</span>
                            <div class="mt-1 text-danger" style="font-size: 0.8rem;">
                                Motif : {{ $membre->motif_interdiction }}<br>
                                Depuis : {{ $membre->date_interdiction ? $membre->date_interdiction->format('d/m/Y') : '' }}
                            </div>
                            <form action="{{ route('membres.lever-interdiction-nano-credit', $membre) }}" method="POST" class="mt-2" onsubmit="return confirm('Lever l\'interdiction de ce membre ?');">
                                @csrf
                                <button class="btn btn-sm btn-outline-success p-1 py-0"><i class="bi bi-check-circle"></i> Lever l'interdiction</button>
                            </form>
                        @else
                            <span class="badge bg-success">Non (Autorisé)</span>
                            <div class="mt-2">
                                <button type="button" class="btn btn-sm btn-outline-danger p-1 py-0" data-bs-toggle="collapse" data-bs-target="#interdictionForm">
                                    <i class="bi bi-slash-circle"></i> Interdire
                                </button>
                                <div class="collapse mt-2" id="interdictionForm">
                                    <form action="{{ route('membres.interdire-nano-credit', $membre) }}" method="POST">
                                        @csrf
                                        <input type="text" name="motif" class="form-control form-control-sm mb-1" placeholder="Motif de l'interdiction" required>
                                        <button type="submit" class="btn btn-sm btn-danger w-100">Confirmer interdiction</button>
                                    </form>
                                </div>
                            </div>
                        @endif
                    </dd>

                    @if($membre->nanoCreditPalier && !$membre->nano_credit_interdit)
                        <dt class="col-sm-4 mt-2" style="font-weight: 300; font-family: 'Ubuntu', sans-serif;">Actions Palier</dt>
                        <dd class="col-sm-8 mt-2 d-flex gap-2">
                            <form action="{{ route('membres.upgrader-palier', $membre) }}" method="POST" onsubmit="return confirm('Forcer la vérification et l\'upgrade de ce membre ?');">
                                @csrf
                                <button class="btn btn-sm btn-outline-primary"><i class="bi bi-arrow-up"></i> Vérifier Upgrade</button>
                            </form>
                            
                            <button type="button" class="btn btn-sm btn-outline-warning" data-bs-toggle="collapse" data-bs-target="#downgradeForm">
                                <i class="bi bi-arrow-down"></i> Rétrograder
                            </button>
                        </dd>
                        <dd class="col-12 mt-0">
                            <div class="collapse" id="downgradeForm">
                                <form action="{{ route('membres.downgrader-palier', $membre) }}" method="POST">
                                    @csrf
                                    <div class="input-group input-group-sm mt-1">
                                        <input type="text" name="motif" class="form-control" placeholder="Motif du downgrade" required>
                                        <button class="btn btn-warning" type="submit">Confirmer</button>
                                    </div>
                                </form>
                            </div>
                        </dd>
                    @endif
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
                    <a href="{{ route('membres.edit', $membre) }}" class="btn btn-warning">
                        <i class="bi bi-pencil"></i> Modifier
                    </a>
                    <a href="{{ route('membres.index') }}" class="btn btn-secondary">
                        <i class="bi bi-arrow-left"></i> Retour à la liste
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
