@extends('layouts.membre')

@section('title', 'Demander un nano crédit')

@section('content')
<!-- Tom Select CSS -->
<link href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.bootstrap5.min.css" rel="stylesheet">

<style>
    .page-nano-demander,
    .page-nano-demander .card,
    .page-nano-demander .form-label,
    .page-nano-demander .btn,
    .page-nano-demander p {
        font-family: 'Ubuntu', sans-serif !important;
        font-weight: 300 !important;
    }
    .ts-control {
        border-radius: 8px !important;
        padding: 0.6rem 1rem !important;
        font-weight: 400 !important;
    }
    .amortization-card {
        background: linear-gradient(135deg, #1e3a5f 0%, #2c5282 100%);
        border-radius: 16px;
        color: white;
        transition: transform 0.3s ease;
    }
    .amortization-card:hover {
        transform: translateY(-5px);
    }
    .schedule-item {
        border-left: 2px dashed rgba(255,255,255,0.3);
        margin-left: 10px;
        padding-left: 20px;
        position: relative;
    }
    .schedule-item::before {
        content: '';
        position: absolute;
        left: -6px;
        top: 0;
        width: 10px;
        height: 10px;
        background: #4299e1;
        border-radius: 50%;
    }
    .is-invalid { border-color: #f56565 !important; }
    .is-valid { border-color: #48bb78 !important; }
</style>

<div class="page-header page-nano-demander">
    <h1 style="font-weight: 300; font-family: 'Ubuntu', sans-serif;">
        <i class="bi bi-send-fill text-primary"></i> Nouvelle demande de nano crédit
    </h1>
</div>

<div class="row page-nano-demander g-4">
    <div class="col-lg-8">
        <form action="{{ route('membre.nano-credits.demander.store') }}" method="POST" id="nanoCreditForm">
            @csrf

            <div class="card border-0 shadow-sm mb-4" style="border-radius: 16px;">
                <div class="card-header bg-white border-bottom py-3 fw-bold">
                    <i class="bi bi-cash-stack me-2 text-primary"></i> Paramètres du Crédit
                </div>
                <div class="card-body p-4">
                    <div class="mb-4">
                        <label for="montant" class="form-label fw-bold">Montant souhaité (XOF) <span class="text-danger">*</span></label>
                        <div class="input-group input-group-lg">
                            <input type="number" step="1" class="form-control @error('montant') is-invalid @enderror" 
                                   id="montant" name="montant" value="{{ old('montant', 1000) }}" required 
                                   min="1000" max="{{ (int) $palier->montant_plafond }}" 
                                   style="border-radius: 12px 0 0 12px;">
                            <span class="input-group-text bg-light fw-bold" style="border-radius: 0 12px 12px 0;">XOF</span>
                        </div>
                        <div class="d-flex justify-content-between mt-2">
                            <small class="text-muted">Min: 1 000 XOF</small>
                            <small class="text-primary fw-bold" id="max-amount-label">Max: {{ number_format($palier->montant_plafond, 0, ',', ' ') }} XOF</small>
                        </div>
                        @error('montant')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>

                    <div id="calculator-preview" class="p-3 bg-light rounded-4 mb-3 border border-dashed text-center">
                        <div class="row">
                            <div class="col-6 border-end">
                                <small class="text-muted d-block">Intérêts ({{ $palier->taux_interet }}%)</small>
                                <span class="fw-bold text-primary" id="preview-interests">0 XOF</span>
                            </div>
                            <div class="col-6">
                                <small class="text-muted d-block">Total à rembourser</small>
                                <span class="fw-bold text-dark" id="preview-total">0 XOF</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            @if($palier->nombre_garants > 0)
            <div class="card border-0 shadow-sm mb-4" style="border-radius: 16px;">
                <div class="card-header bg-white border-bottom py-3 fw-bold">
                    <i class="bi bi-people-fill me-2 text-primary"></i> Choix des Garants ({{ $palier->nombre_garants }} requis)
                </div>
                <div class="card-body p-4">
                    <p class="small text-muted mb-4">
                        Recherchez et sélectionnez vos {{ $palier->nombre_garants }} garants parmi les membres éligibles. Ils recevront une notification pour valider leur engagement.
                    </p>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Rechercher des membres <span class="text-danger">*</span></label>
                        <select id="guarantor-select" name="garant_ids[]" placeholder="Entrez un nom ou numéro..." multiple required></select>
                        @error('garant_ids')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        @error('garant_ids.*')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>

                    <div class="alert alert-warning border-0 bg-warning bg-opacity-10 p-3 small">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        Note : Un garant doit avoir une épargne (tontine) d'au moins <strong>{{ $palier->min_epargne_percent }}%</strong> du montant demandé (soit <span id="required-savings-text">0</span> XOF).
                    </div>
                </div>
            </div>
            @else
                <input type="hidden" name="garant_ids" value="">
            @endif

            <div class="d-flex gap-3 mb-5">
                <button type="submit" class="btn btn-primary btn-lg px-5 shadow-sm" style="border-radius: 12px;" id="submit-btn">
                    <i class="bi bi-check2-circle me-2"></i> Valider et Envoyer
                </button>
                <a href="{{ route('membre.nano-credits') }}" class="btn btn-light btn-lg px-4 border" style="border-radius: 12px;">Annuler</a>
            </div>
        </form>
    </div>

    <div class="col-lg-4">
        <!-- Amortissement Preview -->
        <div class="amortization-card p-4 shadow-lg mb-4">
            <h5 class="fw-bold mb-4 border-bottom border-white border-opacity-25 pb-2">
                <i class="bi bi-calendar3 me-2"></i> Échéancier Prévisionnel
            </h5>
            
            <div id="schedule-container" class="pe-2" style="max-height: 400px; overflow-y: auto;">
                <!-- Dynamically filled by JS -->
                <div class="text-center opacity-50 py-5">
                    <i class="bi bi-calculator fs-1 d-block mb-2"></i>
                    Saisissez un montant pour voir les échéances
                </div>
            </div>

            <div class="mt-4 pt-3 border-top border-white border-opacity-25">
                <div class="d-flex justify-content-between small opacity-75 mb-1">
                    <span>Fréquence</span>
                    <span class="fw-bold">{{ $palier->frequence_remboursement_label }}</span>
                </div>
                <div class="d-flex justify-content-between small opacity-75">
                    <span>Durée</span>
                    <span class="fw-bold">{{ $palier->duree_jours }} Jours</span>
                </div>
            </div>
        </div>

        <div class="card border-0 bg-white shadow-sm rounded-4">
            <div class="card-body p-4 text-center">
                <div class="bg-primary bg-opacity-10 rounded-circle d-inline-flex p-3 mb-3">
                    <i class="bi bi-lightning-charge-fill text-primary fs-3"></i>
                </div>
                <h6>Déblocage Automatique</h6>
                <p class="small text-muted mb-0">Une fois que tous vos garants auront accepté, les fonds seront envoyés instantanément sur votre mobile money.</p>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // 1. Calculateur d'amortissement
    const montantInput = document.getElementById('montant');
    const palier = {
        taux: {{ (float) ($palier->taux_interet ?? 0) }},
        duree: {{ (int) ($palier->duree_jours ?? 30) }},
        frequence: '{{ $palier->frequence_remboursement }}',
        frequenceLabel: '{{ $palier->frequence_remboursement_label }}',
        nbEcheances: {{ (int) $palier->nombre_echeances }},
        minEpargnePercent: {{ (float) $palier->min_epargne_percent }},
        maxMontant: {{ (float) $palier->montant_plafond }}
    };

    function updateAmortization() {
        const montant = parseFloat(montantInput.value) || 0;
        
        // Update required savings text
        const requiredSavings = Math.round(montant * (palier.minEpargnePercent / 100));
        const savingsTextEl = document.getElementById('required-savings-text');
        if (savingsTextEl) savingsTextEl.textContent = requiredSavings.toLocaleString();

        const scheduleContainer = document.getElementById('schedule-container');
        const previewInterests = document.getElementById('preview-interests');
        const previewTotal = document.getElementById('preview-total');
        const submitBtn = document.getElementById('submit-btn');

        // Validation visuelle
        if (montant < 1000 || montant > palier.maxMontant) {
            montantInput.classList.add('is-invalid');
            montantInput.classList.remove('is-valid');
            
            let errorMsg = montant < 1000 ? 'Montant trop faible (min 1000)' : 'Montant trop élevé (max ' + palier.maxMontant.toLocaleString() + ')';
            scheduleContainer.innerHTML = `<div class="text-center opacity-50 py-5"><i class="bi bi-exclamation-triangle fs-1 d-block mb-2"></i>${errorMsg}</div>`;
            
            previewInterests.textContent = '0 XOF';
            previewTotal.textContent = '0 XOF';
            previewInterests.className = 'fw-bold text-danger';
            previewTotal.className = 'fw-bold text-danger';
            submitBtn.disabled = true;
            return;
        } else {
            montantInput.classList.remove('is-invalid');
            montantInput.classList.add('is-valid');
            previewInterests.className = 'fw-bold text-primary';
            previewTotal.className = 'fw-bold text-dark';
            submitBtn.disabled = false;
        }

        // Calculs
        const interets = Math.round(montant * (palier.taux / 100));
        const total = Math.round(montant + interets);
        const montantEcheance = Math.round(total / palier.nbEcheances);

        previewInterests.textContent = interets.toLocaleString() + ' XOF';
        previewTotal.textContent = total.toLocaleString() + ' XOF';

        // Générer le tableau
        let html = '';
        let date = new Date();
        for (let i = 1; i <= palier.nbEcheances; i++) {
            // Calcul date approximative
            if (palier.frequence === 'journalier') date.setDate(date.getDate() + 1);
            else if (palier.frequence === 'hebdomadaire') date.setDate(date.getDate() + 7);
            else if (palier.frequence === 'mensuel') date.setMonth(date.getMonth() + 1);
            else if (palier.frequence === 'trimestriel') date.setMonth(date.getMonth() + 3);
            else date.setMonth(date.getMonth() + 1);

            const displayDate = date.toLocaleDateString('fr-FR', { day: 'numeric', month: 'short', year: 'numeric' });
            
            html += `
                <div class="schedule-item mb-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <small class="d-block opacity-75">Échéance #${i}</small>
                            <span class="small fw-bold">${displayDate}</span>
                        </div>
                        <span class="fw-bold">${montantEcheance.toLocaleString()} XOF</span>
                    </div>
                </div>
            `;
        }
        scheduleContainer.innerHTML = html;
    }

    montantInput.addEventListener('input', updateAmortization);
    updateAmortization();

    // 2. Recherche AJAX Garants (Tom Select)
    new TomSelect("#guarantor-select", {
        valueField: 'id',
        labelField: 'text',
        searchField: 'text',
        maxItems: {{ (int) $palier->nombre_garants }},
        load: function(query, callback) {
            var url = '{{ route("membre.nano-credits.search-guarantors") }}?q=' + encodeURIComponent(query);
            fetch(url)
                .then(response => response.json())
                .then(json => {
                    callback(json);
                }).catch(()=>{
                    callback();
                });
        },
        render: {
            option: function(item, escape) {
                return `<div class="py-2 px-3 border-bottom">
                    <div class="fw-bold">${escape(item.text)}</div>
                    <div class="small text-muted"><i class="bi bi-star-fill text-warning me-1"></i>Qualité Garant : ${escape(item.qualite)}</div>
                </div>`;
            },
            item: function(item, escape) {
                return `<div>${escape(item.text)}</div>`;
            }
        }
    });
});
</script>
@endpush
