@extends('layouts.app')

@section('title', 'Détails du Client')

@section('content')
<div class="page-header d-flex justify-content-between align-items-center">
    <h1><i class="bi bi-person"></i> {{ $membre->nom_complet }}</h1>
    <div class="text-end">
        <span class="text-muted" style="font-size: 0.7rem; display: block; margin-bottom: 2px;">SOLDE GLOBAL</span>
        <span class="badge bg-success" style="font-size: 1.1rem; padding: 0.5rem 1rem;">
            {{ number_format((float) $membre->solde_global, 0, ',', ' ') }} XOF
        </span>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <!-- Informations Client -->
        <div class="card mb-4">
            <div class="card-header">
                <i class="bi bi-info-circle"></i> Informations personnelles
            </div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-4" style="font-weight: 300; font-family: 'Ubuntu', sans-serif;">Numéro Client</dt>
                    <dd class="col-sm-8" style="font-weight: 300; font-family: 'Ubuntu', sans-serif;">
                        <strong>{{ $membre->numero ?? '-' }}</strong>
                    </dd>
                    
                    <dt class="col-sm-4" style="font-weight: 300; font-family: 'Ubuntu', sans-serif;">Nom & Prénom</dt>
                    <dd class="col-sm-8" style="font-weight: 300; font-family: 'Ubuntu', sans-serif;">{{ $membre->nom }} {{ $membre->prenom }}</dd>

                    <dt class="col-sm-4" style="font-weight: 300; font-family: 'Ubuntu', sans-serif;">Sexe</dt>
                    <dd class="col-sm-8" style="font-weight: 300; font-family: 'Ubuntu', sans-serif;">
                        @if($membre->sexe === 'M') Masculin @elseif($membre->sexe === 'F') Féminin @else - @endif
                    </dd>
                    
                    <dt class="col-sm-4" style="font-weight: 300; font-family: 'Ubuntu', sans-serif;">Email & Tél.</dt>
                    <dd class="col-sm-8" style="font-weight: 300; font-family: 'Ubuntu', sans-serif;">
                        {{ $membre->email }} <br>
                        {{ $membre->telephone ?? '-' }}
                    </dd>
                    
                    <dt class="col-sm-4" style="font-weight: 300; font-family: 'Ubuntu', sans-serif;">Adresse</dt>
                    <dd class="col-sm-8" style="font-weight: 300; font-family: 'Ubuntu', sans-serif;">
                        {{ $membre->adresse ?? '-' }} ({{ $membre->ville ?? '-' }}, {{ $membre->pays ?? '-' }})
                    </dd>
                    
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
                </dl>
            </div>
        </div>

        <!-- Comptes du Client -->
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-wallet2"></i> Comptes Bancaires</span>
                <a href="{{ route('caisses.create', ['membre_id' => $membre->id]) }}" class="btn btn-xs btn-light text-primary">
                    <i class="bi bi-plus-circle"></i> Ajouter un compte
                </a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0" style="font-size: 0.75rem;">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3">Numéro</th>
                                <th>Nom</th>
                                <th>Type</th>
                                <th>Core Banking</th>
                                <th class="text-end pe-3">Solde actuel</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($membre->comptes as $compte)
                                <tr>
                                    <td class="ps-3"><code class="text-primary">{{ $compte->numero }}</code></td>
                                    <td>{{ $compte->nom }}</td>
                                    <td><span class="badge bg-secondary">{{ strtoupper($compte->type) }}</span></td>
                                    <td><code>{{ $compte->numero_core_banking ?? '-' }}</code></td>
                                    <td class="text-end pe-3">
                                        <strong>{{ number_format((float)$compte->solde_actuel, 0, ',', ' ') }} XOF</strong>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center py-3 text-muted">Aucun compte rattaché à ce client.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Nano-Crédit -->
        <div class="card mb-4">
            <div class="card-header">
                <i class="bi bi-phone"></i> Services de Nano-Crédit
            </div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-4" style="font-weight: 300; font-family: 'Ubuntu', sans-serif;">Palier Actuel</dt>
                    <dd class="col-sm-8" style="font-weight: 300; font-family: 'Ubuntu', sans-serif;">
                        @if($membre->nanoCreditPalier)
                            <span class="badge" style="background: var(--primary-dark-blue);">Palier {{ $membre->nanoCreditPalier->numero }} : {{ $membre->nanoCreditPalier->nom }}</span>
                            <div class="mt-1" style="font-size: 0.8rem;">
                                Plafond: {{ number_format((float)$membre->nanoCreditPalier->montant_plafond, 0, ',', ' ') }} FCFA<br>
                                Garants requis: {{ $membre->nanoCreditPalier->nombre_garants }}
                            </div>
                        @else
                            <span class="badge bg-secondary">Non assigné</span>
                        @endif
                    </dd>

                    <dt class="col-sm-4" style="font-weight: 300; font-family: 'Ubuntu', sans-serif;">Éligibilité</dt>
                    <dd class="col-sm-8" style="font-weight: 300; font-family: 'Ubuntu', sans-serif;">
                        @if($membre->nano_credit_interdit)
                            <span class="badge bg-danger"><i class="bi bi-slash-circle me-1"></i> INTERDIT</span>
                            <div class="mt-1 text-danger" style="font-size: 0.8rem;">Motif : {{ $membre->motif_interdiction }}</div>
                        @else
                            <span class="badge bg-success">AUTORISÉ</span>
                        @endif
                    </dd>
                </dl>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card mb-4">
            <div class="card-header">
                <i class="bi bi-tools"></i> Actions administratives
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <a href="{{ route('membres.edit', $membre) }}" class="btn btn-warning">
                        <i class="bi bi-pencil"></i> Modifier le client
                    </a>
                    <a href="{{ route('membres.index') }}" class="btn btn-secondary">
                        <i class="bi bi-arrow-left"></i> Retour à la liste
                    </a>
                </div>
            </div>
        </div>

        {{-- ─── Parrainage (admin) ──────────────────────────────────── --}}
        <div class="card mb-4" style="border-left:4px solid #fd7e14;">
            <div class="card-header"
                 style="background:linear-gradient(135deg,#fd7e14 0%,#e8590c 100%); color:#fff; font-weight:300; font-family:'Ubuntu',sans-serif; padding:0.5rem 0.75rem;">
                <i class="bi bi-people-fill me-1"></i> Système de Parrainage
            </div>
            <div class="card-body" style="padding:0.75rem; font-size:0.78rem; font-weight:300; font-family:'Ubuntu',sans-serif;">
                <dl class="row mb-2">
                    <dt class="col-sm-5">Code Promo</dt>
                    <dd class="col-sm-7"><code style="color:#fd7e14;">{{ $membre->code_parrainage ?? '-' }}</code></dd>

                    <dt class="col-sm-5">Parrain</dt>
                    <dd class="col-sm-7">
                        @if($membre->parrain)
                            <a href="{{ route('membres.show', $membre->parrain) }}">{{ $membre->parrain->nom_complet }}</a>
                        @else <span class="text-muted">Aucun</span> @endif
                    </dd>

                    <dt class="col-sm-5">Filleuls</dt>
                    <dd class="col-sm-7"><span class="badge" style="background:#fd7e14;">{{ $nbFilleuls }}</span></dd>
                </dl>
                <div class="text-center mt-2 border-top pt-2">
                    <span class="text-muted d-block" style="font-size: 0.6rem;">COMMISSIONS DISPONIBLES</span>
                    <strong class="text-success" style="font-size: 1rem;">{{ number_format($commissionsDisponibles, 0, ',', ' ') }} XOF</strong>
                </div>
            </div>
        </div>
    </div>
</div>

@if($nbFilleuls > 0)
<div class="card mb-4">
    <div class="card-header">
        <i class="bi bi-people"></i> Liste des Filleuls
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm mb-0" style="font-size:0.72rem;">
                <thead class="table-dark">
                    <tr>
                        <th class="ps-3">Numéro</th>
                        <th>Nom complet</th>
                        <th>Email</th>
                        <th>Date inscription</th>
                        <th class="text-center">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($membre->filleuls as $filleul)
                    <tr>
                        <td class="ps-3">{{ $filleul->numero }}</td>
                        <td>{{ $filleul->nom_complet }}</td>
                        <td>{{ $filleul->email }}</td>
                        <td>{{ $filleul->created_at->format('d/m/Y') }}</td>
                        <td class="text-center">
                            <a href="{{ route('membres.show', $filleul) }}" class="btn btn-xs btn-outline-primary"><i class="bi bi-eye"></i></a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endif

@endsection
