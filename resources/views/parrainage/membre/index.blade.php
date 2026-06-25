@extends('layouts.membre')

@section('title', 'Mon parrainage')

@section('content')
<div class="container-fluid py-4">

    <!-- En-tête -->
    <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
        <div>
            <h2 class="mb-1 fw-bold" style="color: var(--primary-dark-blue);">
                <i class="bi bi-people-fill me-2 text-primary"></i>Mon programme de parrainage
            </h2>
            <p class="text-muted mb-0">Parrainez vos proches et gagnez des commissions</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('membre.parrainage.filleuls') }}" class="btn btn-outline-primary btn-sm">
                <i class="bi bi-person-check me-1"></i>Mes filleuls
            </a>
            <a href="{{ route('membre.parrainage.commissions') }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-cash-coin me-1"></i>Commissions
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            <i class="bi bi-check-circle me-2"></i>{!! session('success') !!}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="bi bi-exclamation-triangle me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- Description du programme -->
    @if($config->description)
        <div class="alert alert-info mb-4 d-flex gap-2 align-items-start">
            <i class="bi bi-info-circle-fill fs-5 mt-1 flex-shrink-0"></i>
            <div>{{ $config->description }}</div>
        </div>
    @endif

    <!-- Statistiques -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center py-4">
                    <div class="fs-2 fw-bold text-primary mb-1">{{ $stats['nb_filleuls'] }}</div>
                    <div class="small text-muted">Filleuls recrutés</div>
                    <div class="small text-success mt-1">{{ $stats['nb_filleuls_actifs'] }} actif(s)</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center py-4">
                    <div class="fs-3 fw-bold text-success mb-1">
                        {{ number_format($stats['total_disponible'], 0, ',', ' ') }}
                        <small class="fs-6">FCFA</small>
                    </div>
                    <div class="small text-muted">Disponible à réclamer</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center py-4">
                    <div class="fs-3 fw-bold text-info mb-1">
                        {{ number_format($stats['total_reclame'], 0, ',', ' ') }}
                        <small class="fs-6">FCFA</small>
                    </div>
                    <div class="small text-muted">En cours de traitement</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center py-4">
                    <div class="fs-3 fw-bold text-primary mb-1">
                        {{ number_format($stats['total_paye'], 0, ',', ' ') }}
                        <small class="fs-6">FCFA</small>
                    </div>
                    <div class="small text-muted">Total reçu</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <!-- Code de parrainage -->
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom py-3">
                    <h6 class="mb-0 fw-semibold">
                        <i class="bi bi-qr-code me-2 text-primary"></i>Mon Code de Parrainage
                    </h6>
                </div>
                <div class="card-body text-center py-4">
                    <!-- Affichage du code -->
                    <div class="p-4 rounded-3 mb-3" style="background: linear-gradient(135deg, var(--primary-dark-blue), #4299e1);">
                        <div class="text-white-50 small mb-1">Votre code unique</div>
                        <div class="text-white fw-bold display-6 font-monospace letter-spacing-2" id="codeParrainage">
                            {{ $membre->code_parrainage }}
                        </div>
                    </div>

                    <!-- Lien d'inscription -->
                    <div class="input-group mb-3">
                        <input type="text" class="form-control form-control-sm font-monospace"
                               id="lienParrainage"
                               value="{{ route('membre.register') }}?ref={{ $membre->code_parrainage }}"
                               readonly>
                        <button class="btn btn-outline-primary btn-sm" type="button" onclick="copierLien()">
                            <i class="bi bi-clipboard" id="iconCopier"></i>
                        </button>
                    </div>

                    <!-- Boutons de partage -->
                    <div class="d-flex gap-2 justify-content-center flex-wrap mb-3">
                        <a href="https://wa.me/?text={{ urlencode('Rejoignez-moi sur ' . config('app.name') . ' ! Utilisez mon code de parrainage ' . $membre->code_parrainage . ' en vous inscrivant ici : ' . route('membre.register') . '?ref=' . $membre->code_parrainage) }}"
                           target="_blank" class="btn btn-success btn-sm">
                            <i class="bi bi-whatsapp me-1"></i>WhatsApp
                        </a>
                        <button class="btn btn-info btn-sm text-white" onclick="copierLien()">
                            <i class="bi bi-link-45deg me-1"></i>Copier le lien
                        </button>
                    </div>

                    <!-- Régénérer code -->
                    <form method="POST" action="{{ route('membre.parrainage.regenerer-code') }}"
                          onsubmit="return confirm('Régénérer votre code ? Votre ancien code ne fonctionnera plus.')">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-outline-secondary text-muted">
                            <i class="bi bi-arrow-clockwise me-1"></i>Régénérer le code
                        </button>
                    </form>
                </div>
            </div>

            <!-- Comment ça marche -->
            <div class="card border-0 shadow-sm mt-3">
                <div class="card-header bg-white border-bottom py-3">
                    <h6 class="mb-0 fw-semibold">
                        <i class="bi bi-question-circle me-2 text-info"></i>Comment ça marche ?
                    </h6>
                </div>
                <div class="card-body p-0">
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item px-3 py-2 d-flex gap-2 align-items-start">
                            <span class="badge bg-primary rounded-circle flex-shrink-0 mt-1">1</span>
                            <span class="small">Partagez votre <strong>code de parrainage</strong> ou votre lien d'invitation</span>
                        </li>
                        <li class="list-group-item px-3 py-2 d-flex gap-2 align-items-start">
                            <span class="badge bg-primary rounded-circle flex-shrink-0 mt-1">2</span>
                            <span class="small">Votre filleul s'inscrit en utilisant votre code</span>
                        </li>
                        <li class="list-group-item px-3 py-2 d-flex gap-2 align-items-start">
                            <span class="badge bg-primary rounded-circle flex-shrink-0 mt-1">3</span>
                            <span class="small">
                                Une commission de
                                <strong>
                                    {{ $config->type_remuneration === 'pourcentage'
                                        ? $config->montant_remuneration . '%'
                                        : number_format($config->montant_remuneration, 0, ',', ' ') . ' FCFA' }}
                                </strong>
                                est générée lors de
                                <strong>{{ strtolower($config->label_declencheur) }}</strong>
                            </span>
                        </li>
                        <li class="list-group-item px-3 py-2 d-flex gap-2 align-items-start">
                            <span class="badge bg-success rounded-circle flex-shrink-0 mt-1">4</span>
                            <span class="small">Réclamez vos gains quand le solde minimum est atteint</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Réclamation + Filleuls récents -->
        <div class="col-lg-7">
            <!-- Bloc de réclamation -->
            <div class="card border-0 shadow-sm mb-4
                {{ $verification['peut'] ? 'border-success' : '' }}"
                 style="{{ $verification['peut'] ? 'border: 2px solid #28a745 !important;' : '' }}">
                <div class="card-header bg-white border-bottom py-3 d-flex align-items-center justify-content-between">
                    <h6 class="mb-0 fw-semibold">
                        <i class="bi bi-wallet2 me-2 text-success"></i>Réclamer mes gains
                    </h6>
                    @if($stats['total_disponible'] > 0)
                        <span class="badge bg-success fs-6">{{ number_format($stats['total_disponible'], 0, ',', ' ') }} FCFA disponibles</span>
                    @endif
                </div>
                <div class="card-body">
                    @if($verification['peut'])
                        <div class="alert alert-success d-flex gap-2 align-items-center mb-3">
                            <i class="bi bi-check-circle-fill fs-5"></i>
                            <div>
                                <strong>Vous pouvez réclamer !</strong><br>
                                <span class="small">Total disponible : {{ number_format($verification['total'], 0, ',', ' ') }} FCFA</span>
                            </div>
                        </div>
                        <form method="POST" action="{{ route('membre.parrainage.reclamer') }}"
                              onsubmit="return confirm('Confirmer la réclamation de vos commissions disponibles ?')">
                            @csrf
                            <button type="submit" class="btn btn-success w-100">
                                <i class="bi bi-cash-stack me-2"></i>Réclamer {{ number_format($verification['total'], 0, ',', ' ') }} FCFA
                            </button>
                        </form>
                    @else
                        <div class="alert alert-light border mb-3">
                            <i class="bi bi-info-circle me-2 text-muted"></i>
                            <small class="text-muted">{{ $verification['raison'] }}</small>
                        </div>

                        <!-- Progression vers réclamation -->
                        <div class="row g-3">
                            <div class="col-6">
                                <div class="text-center p-3 rounded-3 bg-light">
                                    <div class="fs-5 fw-bold">{{ $stats['nb_filleuls'] }} / {{ \App\Models\ParrainageConfig::current()->min_filleuls_retrait }}</div>
                                    <div class="small text-muted">Filleuls requis</div>
                                    <div class="progress mt-2" style="height:4px">
                                        <div class="progress-bar bg-primary" style="width: {{ min(100, ($stats['nb_filleuls'] / max(1, \App\Models\ParrainageConfig::current()->min_filleuls_retrait)) * 100) }}%"></div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="text-center p-3 rounded-3 bg-light">
                                    <div class="fs-5 fw-bold">
                                        {{ number_format($stats['total_disponible'], 0, ',', ' ') }}
                                        / {{ number_format(\App\Models\ParrainageConfig::current()->montant_min_retrait, 0, ',', ' ') }}
                                    </div>
                                    <div class="small text-muted">FCFA requis</div>
                                    <div class="progress mt-2" style="height:4px">
                                        <div class="progress-bar bg-success" style="width: {{ min(100, ($stats['total_disponible'] / max(1, \App\Models\ParrainageConfig::current()->montant_min_retrait)) * 100) }}%"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif

                    @if($stats['total_reclame'] > 0)
                        <div class="mt-3 p-2 rounded bg-info bg-opacity-10 text-center">
                            <i class="bi bi-hourglass-split me-1 text-info"></i>
                            <small><strong>{{ number_format($stats['total_reclame'], 0, ',', ' ') }} FCFA</strong> en cours de traitement par l'administrateur</small>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Derniers filleuls -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-bottom py-3 d-flex align-items-center justify-content-between">
                    <h6 class="mb-0 fw-semibold">
                        <i class="bi bi-person-plus me-2 text-success"></i>Derniers filleuls
                    </h6>
                    @if($stats['nb_filleuls'] > 5)
                        <a href="{{ route('membre.parrainage.filleuls') }}" class="small text-primary">Voir tout ({{ $stats['nb_filleuls'] }})</a>
                    @endif
                </div>
                <div class="card-body p-0">
                    @forelse($derniersFilleuls as $filleul)
                        <div class="d-flex align-items-center gap-3 px-3 py-2 border-bottom">
                            <div class="rounded-circle bg-success text-white d-flex align-items-center justify-content-center flex-shrink-0"
                                 style="width:36px;height:36px;font-size:0.75rem">
                                {{ strtoupper(substr($filleul->prenom, 0, 1)) }}{{ strtoupper(substr($filleul->nom, 0, 1)) }}
                            </div>
                            <div class="flex-grow-1">
                                <div class="small fw-semibold">{{ $filleul->nom_complet }}</div>
                                <div class="text-muted" style="font-size:0.75rem">Inscrit le {{ $filleul->created_at->format('d/m/Y') }}</div>
                            </div>
                            <span class="badge {{ $filleul->statut === 'actif' ? 'bg-success' : 'bg-warning text-dark' }} small">
                                {{ ucfirst($filleul->statut) }}
                            </span>
                        </div>
                    @empty
                        <div class="text-center py-4 text-muted">
                            <i class="bi bi-people fs-3 d-block mb-2"></i>
                            <small>Aucun filleul encore. Partagez votre code !</small>
                        </div>
                    @endforelse
                </div>
            </div>

            <!-- Dernières commissions -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom py-3 d-flex align-items-center justify-content-between">
                    <h6 class="mb-0 fw-semibold">
                        <i class="bi bi-clock-history me-2 text-secondary"></i>Dernières commissions
                    </h6>
                    <a href="{{ route('membre.parrainage.commissions') }}" class="small text-primary">Voir tout</a>
                </div>
                <div class="card-body p-0">
                    @forelse($commissionsRecentes as $commission)
                        <div class="d-flex align-items-center gap-3 px-3 py-2 border-bottom">
                            <div>
                                <div class="small fw-semibold">{{ $commission->filleul?->nom_complet ?? 'Filleul inconnu' }}</div>
                                <div class="text-muted" style="font-size:0.75rem">{{ $commission->created_at->format('d/m/Y') }}</div>
                            </div>
                            <div class="ms-auto text-end">
                                <div class="small fw-bold">{{ number_format($commission->montant, 0, ',', ' ') }} FCFA</div>
                                <span class="badge bg-{{ $commission->badge_statut }}" style="font-size:0.65rem">{{ $commission->label_statut }}</span>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-3 text-muted">
                            <small>Aucune commission générée</small>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function copierLien() {
    const lien = document.getElementById('lienParrainage');
    lien.select();
    lien.setSelectionRange(0, 99999);
    navigator.clipboard.writeText(lien.value).then(() => {
        const icon = document.getElementById('iconCopier');
        icon.className = 'bi bi-check text-success';
        setTimeout(() => { icon.className = 'bi bi-clipboard'; }, 2000);
    }).catch(() => {
        document.execCommand('copy');
    });
}
</script>
@endpush
