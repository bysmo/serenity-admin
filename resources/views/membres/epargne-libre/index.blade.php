@extends('layouts.membre')

@section('title', 'Mon Épargne Libre')

@section('content')
<style>
    .el-card-stat { border-radius: 12px; padding: 1rem 1.25rem; }
    .el-stat-label { font-size: 0.65rem; font-weight: 300; font-family: 'Ubuntu', sans-serif; opacity: 0.8; }
    .el-stat-value { font-size: 1.3rem; font-weight: 400; font-family: 'Ubuntu', sans-serif; }
    .table-el thead th {
        padding: 0.2rem 0.6rem !important; font-size: 0.62rem !important;
        font-weight: 300 !important; font-family: 'Ubuntu', sans-serif !important;
        color: #fff !important; background-color: var(--primary-dark-blue) !important;
    }
    .table-el tbody td {
        padding: 0.2rem 0.6rem !important; font-size: 0.68rem !important;
        font-weight: 300 !important; font-family: 'Ubuntu', sans-serif !important;
        color: var(--primary-dark-blue) !important; border-bottom: 1px solid #f0f0f0 !important;
    }
    .form-versement { background: #f8fafd; border: 1px solid #e2e8f0; border-radius: 10px; padding: 1.2rem; }
</style>

<div class="page-header d-flex justify-content-between align-items-center flex-wrap mb-3">
    <h1 style="font-size: 1.1rem; font-weight: 300; font-family: 'Ubuntu', sans-serif; color: var(--primary-dark-blue); margin: 0;">
        <i class="bi bi-piggy-bank-fill"></i> Mon Épargne Libre
    </h1>
    <a href="{{ route('membre.dashboard') }}" class="btn btn-outline-secondary btn-sm" style="font-weight: 300; font-family: 'Ubuntu', sans-serif;">
        <i class="bi bi-arrow-left"></i> Tableau de bord
    </a>
</div>

{{-- Alertes de statut de paiement --}}
@if($paymentStatus === 'success')
    <div class="alert alert-success d-flex align-items-center gap-2" style="font-size: 0.8rem; font-weight: 300; font-family: 'Ubuntu', sans-serif;">
        <i class="bi bi-check-circle-fill fs-5"></i>
        <div><strong>Versement confirmé !</strong> {{ $paymentMessage }}</div>
    </div>
@elseif($paymentStatus === 'error')
    <div class="alert alert-danger d-flex align-items-center gap-2" style="font-size: 0.8rem; font-weight: 300; font-family: 'Ubuntu', sans-serif;">
        <i class="bi bi-exclamation-circle-fill fs-5"></i>
        <div>{{ $paymentMessage }}</div>
    </div>
@elseif($paymentStatus === 'cancelled')
    <div class="alert alert-warning d-flex align-items-center gap-2" style="font-size: 0.8rem; font-weight: 300; font-family: 'Ubuntu', sans-serif;">
        <i class="bi bi-x-circle-fill fs-5"></i>
        <div>{{ $paymentMessage }}</div>
    </div>
@elseif($paymentStatus === 'pending')
    <div class="alert alert-info d-flex align-items-center gap-2" style="font-size: 0.8rem; font-weight: 300; font-family: 'Ubuntu', sans-serif;">
        <i class="bi bi-hourglass-split fs-5"></i>
        <div>{{ $paymentMessage }}</div>
    </div>
@endif

@if(session('success'))
    <div class="alert alert-success" style="font-size: 0.8rem; font-weight: 300; font-family: 'Ubuntu', sans-serif;">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="alert alert-danger" style="font-size: 0.8rem; font-weight: 300; font-family: 'Ubuntu', sans-serif;">{{ session('error') }}</div>
@endif

{{-- Carte solde --}}
<div class="row mb-3">
    <div class="col-md-4 mb-2">
        <div class="el-card-stat text-white" style="background: linear-gradient(135deg, var(--primary-dark-blue), var(--primary-blue));">
            <div class="el-stat-label"><i class="bi bi-wallet2"></i> Solde compte épargne</div>
            <div class="el-stat-value">{{ number_format($solde, 0, ',', ' ') }} <small style="font-size:0.6em;">XOF</small></div>
            <div style="font-size: 0.6rem; opacity: 0.7; margin-top: 0.25rem;">N° {{ $compteEpargne->numero }}</div>
        </div>
    </div>
    <div class="col-md-8 mb-2">
        <div class="form-versement">
            <h6 style="font-size: 0.78rem; font-weight: 400; font-family: 'Ubuntu', sans-serif; color: var(--primary-dark-blue); margin-bottom: 0.75rem;">
                <i class="bi bi-plus-circle"></i> Effectuer un versement
            </h6>

            @if(!$paydunyaEnabled && !$pispiEnabled)
                <div class="alert alert-warning mb-0" style="font-size: 0.75rem;">
                    Aucun moyen de paiement en ligne n'est configuré. Contactez l'administration.
                </div>
            @else
                <div class="row g-2 align-items-end">
                    <div class="col-sm-5">
                        <label class="form-label" style="font-size: 0.7rem; font-weight: 300; font-family: 'Ubuntu', sans-serif;">Montant (XOF)</label>
                        <input type="number" id="montant-epargne" name="montant" class="form-control form-control-sm"
                               placeholder="Ex : 5000" min="100" step="100"
                               style="font-size: 0.75rem; font-weight: 300;">
                    </div>
                    <div class="col-sm-7">
                        <label class="form-label" style="font-size: 0.7rem; font-weight: 300; font-family: 'Ubuntu', sans-serif;">Moyen de paiement</label>
                        <div class="d-flex gap-2 flex-wrap">
                            @if($paydunyaEnabled)
                                <form id="form-paydunya" action="{{ route('membre.epargne-libre.paydunya') }}" method="POST" class="d-inline">
                                    @csrf
                                    <input type="hidden" name="montant" id="montant-paydunya">
                                    <button type="submit" id="btn-paydunya"
                                        class="btn btn-sm btn-primary"
                                        style="font-size: 0.72rem; font-weight: 300; font-family: 'Ubuntu', sans-serif;">
                                        <i class="bi bi-phone-fill"></i> Payer par Mobile/Carte
                                    </button>
                                </form>
                            @endif
                            @if($pispiEnabled)
                                <form id="form-pispi" action="{{ route('membre.epargne-libre.pispi') }}" method="POST" class="d-inline">
                                    @csrf
                                    <input type="hidden" name="montant" id="montant-pispi">
                                    <button type="submit" id="btn-pispi"
                                        class="btn btn-sm btn-success"
                                        style="font-size: 0.72rem; font-weight: 300; font-family: 'Ubuntu', sans-serif;">
                                        <i class="bi bi-bank"></i> Payer par Compte Bancaire
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>
                </div>
                <small class="text-muted" style="font-size: 0.62rem;">Minimum : 100 XOF. Le versement sera crédité immédiatement après confirmation.</small>
            @endif
        </div>
    </div>
</div>

{{-- Historique des mouvements --}}
<div class="card">
    <div class="card-header" style="font-weight: 300; font-family: 'Ubuntu', sans-serif; font-size: 0.78rem;">
        <i class="bi bi-clock-history"></i> Historique des versements
    </div>
    <div class="card-body p-0">
        @if($mouvements->count() > 0)
            <div class="table-responsive">
                <table class="table table-el table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Libellé</th>
                            <th class="text-center">Sens</th>
                            <th class="text-end">Montant (XOF)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($mouvements as $mv)
                            <tr>
                                <td>{{ \Carbon\Carbon::parse($mv->date_operation)->format('d/m/Y H:i') }}</td>
                                <td>{{ $mv->libelle ?? '—' }}</td>
                                <td class="text-center">
                                    @if($mv->sens === 'entree')
                                        <span class="badge bg-success" style="font-size:0.6rem;">↑ Entrée</span>
                                    @else
                                        <span class="badge bg-danger" style="font-size:0.6rem;">↓ Sortie</span>
                                    @endif
                                </td>
                                <td class="text-end fw-bold" style="color: {{ $mv->sens === 'entree' ? '#198754' : '#dc3545' }};">
                                    {{ $mv->sens === 'entree' ? '+' : '-' }}{{ number_format($mv->montant, 0, ',', ' ') }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="p-2">
                {{ $mouvements->links('pagination::bootstrap-5') }}
            </div>
        @else
            <div class="text-center text-muted p-4" style="font-size: 0.75rem; font-weight: 300; font-family: 'Ubuntu', sans-serif;">
                <i class="bi bi-inbox fs-3 d-block mb-2 opacity-50"></i>
                Aucun versement enregistré pour le moment.
            </div>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const montantInput = document.getElementById('montant-epargne');

    function syncMontant() {
        const val = montantInput ? montantInput.value : '';
        const pd = document.getElementById('montant-paydunya');
        const ps = document.getElementById('montant-pispi');
        if (pd) pd.value = val;
        if (ps) ps.value = val;
    }

    if (montantInput) {
        montantInput.addEventListener('input', syncMontant);
    }

    // Soumission des formulaires — copier le montant
    ['form-paydunya', 'form-pispi'].forEach(function(formId) {
        const form = document.getElementById(formId);
        if (form) {
            form.addEventListener('submit', function (e) {
                syncMontant();
                const hiddenMontant = formId === 'form-paydunya'
                    ? document.getElementById('montant-paydunya')
                    : document.getElementById('montant-pispi');
                if (!hiddenMontant || !hiddenMontant.value || parseFloat(hiddenMontant.value) < 100) {
                    e.preventDefault();
                    alert('Veuillez saisir un montant valide (minimum 100 XOF).');
                }
            });
        }
    });
});
</script>
@endpush
