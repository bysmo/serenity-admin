@extends('layouts.app')

@section('title', 'Configuration du parrainage')

@section('content')
<div class="container-fluid py-4">

    <!-- En-tête -->
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h2 class="mb-1 fw-bold" style="color: var(--primary-dark-blue);">
                <i class="bi bi-people-fill me-2 text-primary"></i>Système de Parrainage
            </h2>
            <p class="text-muted mb-0">Configurez et gérez le programme de parrainage rémunéré</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('parrainage.admin.commissions') }}" class="btn btn-outline-primary btn-sm">
                <i class="bi bi-cash-coin me-1"></i>Commissions
            </a>
            <a href="{{ route('parrainage.admin.parrains') }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-person-lines-fill me-1"></i>Parrains
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- Statistiques rapides -->
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0"
                         style="width:48px;height:48px;background:rgba(13,110,253,.1)">
                        <i class="bi bi-people text-primary fs-5"></i>
                    </div>
                    <div>
                        <div class="fs-4 fw-bold text-dark">{{ number_format($stats['total_parrains']) }}</div>
                        <div class="small text-muted">Parrains actifs</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0"
                         style="width:48px;height:48px;background:rgba(25,135,84,.1)">
                        <i class="bi bi-person-check text-success fs-5"></i>
                    </div>
                    <div>
                        <div class="fs-4 fw-bold text-dark">{{ number_format($stats['total_filleuls']) }}</div>
                        <div class="small text-muted">Filleuls recrutés</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0"
                         style="width:48px;height:48px;background:rgba(255,193,7,.1)">
                        <i class="bi bi-hourglass-split text-warning fs-5"></i>
                    </div>
                    <div>
                        <div class="fs-4 fw-bold text-dark">{{ $stats['nb_reclames'] }}</div>
                        <div class="small text-muted">Réclamations en attente</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0"
                         style="width:48px;height:48px;background:rgba(220,53,69,.1)">
                        <i class="bi bi-cash-stack text-danger fs-5"></i>
                    </div>
                    <div>
                        <div class="fs-4 fw-bold text-dark">{{ number_format($stats['montant_total_paye'], 0, ',', ' ') }}</div>
                        <div class="small text-muted">FCFA déjà versés</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <!-- Formulaire de configuration -->
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom py-3">
                    <h5 class="mb-0 fw-semibold">
                        <i class="bi bi-gear me-2 text-primary"></i>Paramètres du programme
                    </h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('parrainage.admin.config.update') }}" method="POST" id="formConfig">
                        @csrf
                        @method('PUT')

                        <!-- Activation -->
                        <div class="mb-4 p-3 rounded-3" style="background:#f8f9fa">
                            <div class="form-check form-switch d-flex align-items-center gap-3">
                                <input class="form-check-input" type="checkbox" id="actif" name="actif"
                                       value="1" style="width:3rem;height:1.5rem;"
                                       {{ $config->actif ? 'checked' : '' }}>
                                <div>
                                    <label class="form-check-label fw-semibold fs-6" for="actif">
                                        Activer le système de parrainage
                                    </label>
                                    <div class="small text-muted">Les membres pourront partager leur code et gagner des commissions</div>
                                </div>
                            </div>
                        </div>

                        <div class="row g-3">
                            <!-- Type de rémunération -->
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Type de rémunération <span class="text-danger">*</span></label>
                                <select name="type_remuneration" id="type_remuneration" class="form-select @error('type_remuneration') is-invalid @enderror">
                                    <option value="fixe" {{ $config->type_remuneration === 'fixe' ? 'selected' : '' }}>Montant fixe (FCFA)</option>
                                    <option value="pourcentage" {{ $config->type_remuneration === 'pourcentage' ? 'selected' : '' }}>Pourcentage (%)</option>
                                </select>
                                @error('type_remuneration')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Montant / Taux niveau 1 -->
                            <div class="col-md-6">
                                <label class="form-label fw-semibold" id="label_montant">
                                    Rémunération niveau 1 <span class="text-danger">*</span>
                                </label>
                                <div class="input-group">
                                    <input type="number" name="montant_remuneration" step="0.01" min="0"
                                           value="{{ old('montant_remuneration', $config->montant_remuneration) }}"
                                           class="form-control @error('montant_remuneration') is-invalid @enderror"
                                           placeholder="0">
                                    <span class="input-group-text" id="unite_montant">
                                        {{ $config->type_remuneration === 'pourcentage' ? '%' : 'FCFA' }}
                                    </span>
                                    @error('montant_remuneration')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <!-- Déclencheur -->
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Événement déclencheur <span class="text-danger">*</span></label>
                                <select name="declencheur" class="form-select @error('declencheur') is-invalid @enderror">
                                    <option value="inscription" {{ $config->declencheur === 'inscription' ? 'selected' : '' }}>Inscription du filleul</option>
                                    <option value="premier_paiement" {{ $config->declencheur === 'premier_paiement' ? 'selected' : '' }}>Premier paiement du filleul</option>
                                    <option value="adhesion_cotisation" {{ $config->declencheur === 'adhesion_cotisation' ? 'selected' : '' }}>Adhésion à une cotisation</option>
                                </select>
                                @error('declencheur')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Délai de validation -->
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Délai de validation (jours)</label>
                                <div class="input-group">
                                    <input type="number" name="delai_validation_jours" min="0" max="365"
                                           value="{{ old('delai_validation_jours', $config->delai_validation_jours) }}"
                                           class="form-control @error('delai_validation_jours') is-invalid @enderror">
                                    <span class="input-group-text">jours</span>
                                    @error('delai_validation_jours')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="form-text">0 = commission disponible immédiatement</div>
                            </div>

                            <!-- Niveaux de parrainage -->
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Niveaux de parrainage</label>
                                <select name="niveaux_parrainage" id="niveaux_parrainage" class="form-select">
                                    <option value="1" {{ $config->niveaux_parrainage == 1 ? 'selected' : '' }}>1 niveau (direct)</option>
                                    <option value="2" {{ $config->niveaux_parrainage == 2 ? 'selected' : '' }}>2 niveaux</option>
                                    <option value="3" {{ $config->niveaux_parrainage == 3 ? 'selected' : '' }}>3 niveaux</option>
                                </select>
                            </div>

                            <!-- Taux niveau 2 -->
                            <div class="col-md-4" id="wrap_niveau2" {{ $config->niveaux_parrainage < 2 ? 'style=display:none' : '' }}>
                                <label class="form-label fw-semibold">Rémunération niveau 2</label>
                                <div class="input-group">
                                    <input type="number" name="taux_niveau_2" step="0.01" min="0"
                                           value="{{ old('taux_niveau_2', $config->taux_niveau_2) }}"
                                           class="form-control" placeholder="0">
                                    <span class="input-group-text" id="unite_n2">
                                        {{ $config->type_remuneration === 'pourcentage' ? '%' : 'FCFA' }}
                                    </span>
                                </div>
                            </div>

                            <!-- Taux niveau 3 -->
                            <div class="col-md-4" id="wrap_niveau3" {{ $config->niveaux_parrainage < 3 ? 'style=display:none' : '' }}>
                                <label class="form-label fw-semibold">Rémunération niveau 3</label>
                                <div class="input-group">
                                    <input type="number" name="taux_niveau_3" step="0.01" min="0"
                                           value="{{ old('taux_niveau_3', $config->taux_niveau_3) }}"
                                           class="form-control" placeholder="0">
                                    <span class="input-group-text" id="unite_n3">
                                        {{ $config->type_remuneration === 'pourcentage' ? '%' : 'FCFA' }}
                                    </span>
                                </div>
                            </div>

                            <!-- Conditions de retrait -->
                            <div class="col-12 mt-2">
                                <h6 class="fw-semibold text-muted border-bottom pb-2">
                                    <i class="bi bi-shield-check me-1"></i>Conditions pour réclamer
                                </h6>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Filleuls minimum requis</label>
                                <input type="number" name="min_filleuls_retrait" min="1"
                                       value="{{ old('min_filleuls_retrait', $config->min_filleuls_retrait) }}"
                                       class="form-control @error('min_filleuls_retrait') is-invalid @enderror">
                                @error('min_filleuls_retrait')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Montant minimum réclamable (FCFA)</label>
                                <div class="input-group">
                                    <input type="number" name="montant_min_retrait" step="0.01" min="0"
                                           value="{{ old('montant_min_retrait', $config->montant_min_retrait) }}"
                                           class="form-control @error('montant_min_retrait') is-invalid @enderror">
                                    <span class="input-group-text">FCFA</span>
                                    @error('montant_min_retrait')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <!-- Description -->
                            <div class="col-12">
                                <label class="form-label fw-semibold">Description du programme (affichée aux membres)</label>
                                <textarea name="description" rows="3"
                                          class="form-control @error('description') is-invalid @enderror"
                                          placeholder="Ex: Parrainez vos proches et gagnez une commission à chaque inscription validée...">{{ old('description', $config->description) }}</textarea>
                                @error('description')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="d-flex justify-content-end mt-4">
                            <button type="submit" class="btn btn-primary px-4">
                                <i class="bi bi-save me-2"></i>Enregistrer la configuration
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Résumé & Accès rapides -->
        <div class="col-lg-4">
            <!-- Réclamations en attente -->
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-header bg-white border-bottom py-3">
                    <h6 class="mb-0 fw-semibold">
                        <i class="bi bi-bell me-2 text-warning"></i>Réclamations à traiter
                    </h6>
                </div>
                <div class="card-body">
                    @if($stats['commissions_reclames'] > 0)
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <div>
                                <div class="fw-bold fs-5">{{ $stats['commissions_reclames'] }} réclamation(s)</div>
                                <div class="small text-muted">
                                    {{ number_format($stats['montant_total_reclame'], 0, ',', ' ') }} FCFA à verser
                                </div>
                            </div>
                            <span class="badge bg-warning text-dark fs-6">{{ $stats['commissions_reclames'] }}</span>
                        </div>
                        <div class="d-grid gap-2">
                            <a href="{{ route('parrainage.admin.commissions', ['statut' => 'reclame']) }}"
                               class="btn btn-warning btn-sm">
                                <i class="bi bi-eye me-1"></i>Voir les réclamations
                            </a>
                            <form method="POST" action="{{ route('parrainage.admin.commissions.payer-tout') }}"
                                  onsubmit="return confirm('Payer toutes les réclamations en attente ?')">
                                @csrf
                                <button type="submit" class="btn btn-success btn-sm w-100">
                                    <i class="bi bi-check-all me-1"></i>Tout approuver & payer
                                </button>
                            </form>
                        </div>
                    @else
                        <div class="text-center text-muted py-3">
                            <i class="bi bi-check-circle fs-3 text-success"></i>
                            <p class="mt-2 mb-0 small">Aucune réclamation en attente</p>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Tableau des commissions -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom py-3">
                    <h6 class="mb-0 fw-semibold">
                        <i class="bi bi-bar-chart me-2 text-primary"></i>Résumé financier
                    </h6>
                </div>
                <div class="card-body p-0">
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item d-flex justify-content-between align-items-center px-3 py-2">
                            <span class="small"><span class="badge bg-warning text-dark me-2">En attente</span></span>
                            <strong class="small">{{ number_format($stats['montant_total_disponible'], 0, ',', ' ') }} FCFA</strong>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center px-3 py-2">
                            <span class="small"><span class="badge bg-info me-2">Réclamé</span></span>
                            <strong class="small">{{ number_format($stats['montant_total_reclame'], 0, ',', ' ') }} FCFA</strong>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center px-3 py-2">
                            <span class="small"><span class="badge bg-success me-2">Payé</span></span>
                            <strong class="small text-success">{{ number_format($stats['montant_total_paye'], 0, ',', ' ') }} FCFA</strong>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center px-3 py-2 bg-light">
                            <span class="small fw-bold">Total engagé</span>
                            <strong class="small text-primary">
                                {{ number_format($stats['montant_total_disponible'] + $stats['montant_total_reclame'] + $stats['montant_total_paye'], 0, ',', ' ') }} FCFA
                            </strong>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const typeSelect  = document.getElementById('type_remuneration');
    const niveauxSel  = document.getElementById('niveaux_parrainage');
    const wrapN2      = document.getElementById('wrap_niveau2');
    const wrapN3      = document.getElementById('wrap_niveau3');
    const unites      = ['unite_montant', 'unite_n2', 'unite_n3'];

    function updateUnites() {
        const unite = typeSelect.value === 'pourcentage' ? '%' : 'FCFA';
        unites.forEach(id => {
            const el = document.getElementById(id);
            if (el) el.textContent = unite;
        });
    }

    function updateNiveaux() {
        const n = parseInt(niveauxSel.value);
        wrapN2.style.display = n >= 2 ? '' : 'none';
        wrapN3.style.display = n >= 3 ? '' : 'none';
    }

    typeSelect.addEventListener('change', updateUnites);
    niveauxSel.addEventListener('change', updateNiveaux);
});
</script>
@endpush
