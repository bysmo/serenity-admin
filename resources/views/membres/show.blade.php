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

        {{-- ─── Parrainage (admin) ──────────────────────────────────── --}}
        <div class="card mb-4" style="border-left:4px solid #fd7e14;">
            <div class="card-header"
                 style="background:linear-gradient(135deg,#fd7e14 0%,#e8590c 100%); color:#fff; font-weight:300; font-family:'Ubuntu',sans-serif; padding:0.5rem 0.75rem;">
                <i class="bi bi-people-fill me-1"></i> Parrainage
            </div>
            <div class="card-body" style="padding:0.75rem; font-size:0.78rem; font-weight:300; font-family:'Ubuntu',sans-serif;">

                {{-- Code parrainage --}}
                <dl class="row mb-2" style="font-size:0.78rem;">
                    <dt class="col-sm-5" style="font-weight:400;">Code</dt>
                    <dd class="col-sm-7">
                        @if($membre->code_parrainage)
                            <code style="font-size:0.85rem; color:#fd7e14;">{{ $membre->code_parrainage }}</code>
                        @else
                            <span class="text-muted">Non généré</span>
                        @endif
                    </dd>

                    <dt class="col-sm-5" style="font-weight:400;">Parrain</dt>
                    <dd class="col-sm-7">
                        @if($membre->parrain)
                            <a href="{{ route('membres.show', $membre->parrain) }}"
                               style="font-size:0.78rem;">
                                {{ $membre->parrain->nom_complet }}
                            </a>
                        @else
                            <span class="text-muted">Aucun</span>
                        @endif
                    </dd>

                    <dt class="col-sm-5" style="font-weight:400;">Filleul(s)</dt>
                    <dd class="col-sm-7">
                        <span class="badge" style="background:#fd7e14;">{{ $nbFilleuls }}</span>
                    </dd>
                </dl>

                <hr class="my-2">

                {{-- Commissions résumé --}}
                <div class="row g-1 mb-2">
                    <div class="col-6">
                        <div class="p-1 rounded text-center" style="background:#e8f5e9; border:1px solid #a5d6a7;">
                            <div style="font-size:0.85rem; font-weight:600; color:#28a745;">{{ number_format($commissionsDisponibles, 0, ',', ' ') }}</div>
                            <div style="font-size:0.6rem; color:#555;">Disponible (XOF)</div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="p-1 rounded text-center" style="background:#fff8e1; border:1px solid #ffe082;">
                            <div style="font-size:0.85rem; font-weight:600; color:#e67e00;">{{ number_format($commissionsReclames, 0, ',', ' ') }}</div>
                            <div style="font-size:0.6rem; color:#555;">Réclamé (XOF)</div>
                        </div>
                    </div>
                </div>
                <div class="text-center mb-2" style="font-size:0.68rem; color:#555;">
                    Total cumulé : <strong>{{ number_format($commissionsTotales, 0, ',', ' ') }} XOF</strong>
                </div>

                {{-- Lien vers les commissions admin --}}
                @if($parrainageConfig->actif)
                <a href="{{ route('parrainage.admin.commissions', ['membre_id' => $membre->id]) }}"
                   class="btn btn-sm btn-outline-warning w-100"
                   style="font-size:0.7rem; font-weight:300; font-family:'Ubuntu',sans-serif;">
                    <i class="bi bi-list-check me-1"></i> Voir toutes ses commissions
                </a>
                @endif
            </div>
        </div>
        {{-- ─── Fin Parrainage (admin) ───────────────────────────────── --}}
    </div>
</div>

{{-- ─── Filleuls (table) ─────────────────────────────────────────────── --}}
@if($nbFilleuls > 0)
<div class="card mb-4">
    <div class="card-header" style="font-weight:300; font-family:'Ubuntu',sans-serif;">
        <i class="bi bi-people me-1"></i> Filleuls directs de {{ $membre->nom_complet }}
        <span class="badge ms-1" style="background:#fd7e14;">{{ $nbFilleuls }}</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm mb-0"
                   style="font-size:0.72rem; font-weight:300; font-family:'Ubuntu',sans-serif;">
                <thead style="background:var(--primary-dark-blue); color:#fff;">
                    <tr>
                        <th style="padding:0.3rem 0.6rem;">Numéro</th>
                        <th style="padding:0.3rem 0.6rem;">Nom complet</th>
                        <th style="padding:0.3rem 0.6rem;">Email</th>
                        <th style="padding:0.3rem 0.6rem;">Inscrit le</th>
                        <th style="padding:0.3rem 0.6rem;">Statut</th>
                        <th style="padding:0.3rem 0.6rem;" class="text-center">Fiche</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($membre->filleuls as $filleul)
                    <tr>
                        <td style="padding:0.25rem 0.6rem;">{{ $filleul->numero ?? '-' }}</td>
                        <td style="padding:0.25rem 0.6rem;">{{ $filleul->nom_complet }}</td>
                        <td style="padding:0.25rem 0.6rem;">{{ $filleul->email ?? '-' }}</td>
                        <td style="padding:0.25rem 0.6rem;">{{ $filleul->created_at ? $filleul->created_at->format('d/m/Y') : '-' }}</td>
                        <td style="padding:0.25rem 0.6rem;">
                            @if($filleul->statut === 'actif')
                                <span class="badge bg-success" style="font-size:0.6rem;">Actif</span>
                            @elseif($filleul->statut === 'inactif')
                                <span class="badge bg-secondary" style="font-size:0.6rem;">Inactif</span>
                            @else
                                <span class="badge bg-warning" style="font-size:0.6rem;">{{ ucfirst($filleul->statut) }}</span>
                            @endif
                        </td>
                        <td style="padding:0.25rem 0.6rem;" class="text-center">
                            <a href="{{ route('membres.show', $filleul) }}"
                               class="btn btn-sm btn-outline-primary"
                               style="padding:0.1rem 0.35rem; font-size:0.6rem;">
                                <i class="bi bi-eye"></i>
                            </a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endif

{{-- ─── Dernières commissions de parrainage (admin) ─────────────────── --}}
@if($commissionsParrainage->count() > 0)
<div class="card mb-4">
    <div class="card-header" style="font-weight:300; font-family:'Ubuntu',sans-serif;">
        <i class="bi bi-cash-coin me-1"></i> Dernières commissions de parrainage
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm mb-0"
                   style="font-size:0.72rem; font-weight:300; font-family:'Ubuntu',sans-serif;">
                <thead style="background:var(--primary-dark-blue); color:#fff;">
                    <tr>
                        <th style="padding:0.3rem 0.6rem;">Filleul</th>
                        <th style="padding:0.3rem 0.6rem;">Niveau</th>
                        <th style="padding:0.3rem 0.6rem;">Montant</th>
                        <th style="padding:0.3rem 0.6rem;">Déclencheur</th>
                        <th style="padding:0.3rem 0.6rem;">Statut</th>
                        <th style="padding:0.3rem 0.6rem;">Date</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($commissionsParrainage as $commission)
                    <tr>
                        <td style="padding:0.25rem 0.6rem;">
                            {{ $commission->filleul ? $commission->filleul->nom_complet : '-' }}
                        </td>
                        <td style="padding:0.25rem 0.6rem; text-align:center;">
                            <span class="badge" style="background:var(--primary-dark-blue); font-size:0.6rem;">N{{ $commission->niveau }}</span>
                        </td>
                        <td style="padding:0.25rem 0.6rem; font-weight:500; color:#28a745;">
                            {{ number_format($commission->montant, 0, ',', ' ') }} XOF
                        </td>
                        <td style="padding:0.25rem 0.6rem;">
                            @php
                                $labels = ['inscription'=>'Inscription','premier_paiement'=>'1er paiement','adhesion_cotisation'=>'Adhésion'];
                            @endphp
                            {{ $labels[$commission->declencheur] ?? ucfirst($commission->declencheur) }}
                        </td>
                        <td style="padding:0.25rem 0.6rem;">
                            @php
                                $badgeMap = ['en_attente'=>'warning','disponible'=>'success','reclame'=>'info','paye'=>'primary','rejete'=>'danger'];
                                $labelMap = ['en_attente'=>'En attente','disponible'=>'Disponible','reclame'=>'Réclamé','paye'=>'Payé','rejete'=>'Rejeté'];
                            @endphp
                            <span class="badge bg-{{ $badgeMap[$commission->statut] ?? 'secondary' }}" style="font-size:0.6rem;">
                                {{ $labelMap[$commission->statut] ?? ucfirst($commission->statut) }}
                            </span>
                        </td>
                        <td style="padding:0.25rem 0.6rem;">
                            {{ $commission->created_at ? $commission->created_at->format('d/m/Y') : '-' }}
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @if($parrainageConfig->actif)
    <div class="card-footer text-end" style="font-size:0.7rem; font-weight:300; font-family:'Ubuntu',sans-serif; padding:0.4rem 0.75rem;">
        <a href="{{ route('parrainage.admin.commissions', ['membre_id' => $membre->id]) }}"
           style="color:var(--primary-dark-blue); text-decoration:none;">
            Voir toutes les commissions <i class="bi bi-arrow-right ms-1"></i>
        </a>
    </div>
    @endif
</div>
@endif

@endsection
