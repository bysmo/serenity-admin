@extends('layouts.membre')

@section('title', 'Mes Épargnes')

@section('content')
@php
    // ── Disponibilité des moyens de paiement ─────────────────────────────────
    // On calcule une seule fois pour toute la vue
    $pdConfig    = \App\Models\PayDunyaConfiguration::first();
    $pispiConfig = \App\Models\PiSpiConfiguration::first();
    // PayDunya : config existante et activée
    $paydunyaOk  = $pdConfig && $pdConfig->enabled;
    // Pi-SPI   : config existante et activée (et clés présentes)
    $pispiOk     = $pispiConfig && $pispiConfig->enabled && !empty($pispiConfig->client_id);
    // Au moins un moyen disponible
    $aucunMoyen  = !$paydunyaOk && !$pispiOk;
@endphp

{{-- Debug --}}
@if(request()->has('debug_pay'))
<div class="alert alert-info py-1 mb-2" style="font-size:0.65rem;">
    PayDunya: {{ var_export($paydunyaOk, true) }} | Pi-SPI: {{ var_export($pispiOk, true) }} | ClientID: {{ $pispiConfig?->client_id ? 'OK' : 'EMPTY' }}
</div>
@endif

<style>
    .tontine-table thead th {
        padding: 0.2rem 0.5rem !important; font-size: 0.62rem !important; font-weight: 300 !important;
        font-family: 'Ubuntu', sans-serif !important; color: #fff !important;
        background-color: var(--primary-dark-blue) !important; white-space: nowrap;
    }
    .tontine-table tbody td {
        padding: 0.22rem 0.5rem !important; font-size: 0.68rem !important; font-weight: 300 !important;
        font-family: 'Ubuntu', sans-serif !important; color: var(--primary-dark-blue) !important;
        border-bottom: 1px solid #f0f0f0 !important; vertical-align: middle;
    }
    .tontine-table .btn { padding: 0 0.35rem !important; font-size: 0.6rem !important; height: 22px !important; font-weight: 300 !important; line-height: 1.2 !important; }
    .row-overdue { background-color: #f8c0c0 !important; font-weight: 500 !important; }
    .row-today   { background-color: #fffbe6 !important; }
    .row-paid    { background-color: #d1e7dd !important; }
    .row-unpaid  { background-color: #f8dbdd !important; }
</style>

@if($aucunMoyen)
<div class="alert alert-warning d-flex align-items-center gap-2 mb-3" style="font-size:.78rem; font-weight:300; font-family:'Ubuntu',sans-serif;">
    <i class="bi bi-exclamation-triangle-fill fs-5"></i>
    <div>
        <strong>Aucun moyen de paiement en ligne disponible.</strong>
        Contactez l'administration pour activer PayDunya ou Pi-SPI.
    </div>
</div>
@endif

{{-- ─── En-tête ──────────────────────────────────────────────────────────── --}}
<div class="page-header d-flex justify-content-between align-items-center flex-wrap mb-3">
    <h1 style="font-size: 1.1rem; font-weight: 300; font-family: 'Ubuntu', sans-serif; color: var(--primary-dark-blue); margin: 0;">
        <i class="bi bi-wallet2"></i> Mes tontines
    </h1>
    <a href="{{ route('membre.epargne.index') }}" class="btn btn-outline-primary btn-sm" style="font-weight: 300; font-family: 'Ubuntu', sans-serif;">
        <i class="bi bi-piggy-bank"></i> Voir les plans
    </a>
</div>

@if(session('success'))
    <div class="alert alert-success" style="font-size:.78rem; font-weight:300; font-family:'Ubuntu',sans-serif;">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="alert alert-danger" style="font-size:.78rem; font-weight:300; font-family:'Ubuntu',sans-serif;">{{ session('error') }}</div>
@endif

{{-- ─── Tableau récapitulatif des souscriptions ─────────────────────────── --}}
<div class="card mb-3">
    <div class="card-header" style="font-weight: 300; font-family: 'Ubuntu', sans-serif; font-size: .78rem;">
        <i class="bi bi-list-ul"></i> Mes souscriptions
    </div>
    <div class="card-body p-0">
        @if($souscriptions->count() > 0)
            <div class="table-responsive">
                <table class="table tontine-table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Plan</th>
                            <th>Fréquence</th>
                            <th>Début</th>
                            <th class="text-end">Montant / versement</th>
                            <th class="text-end">Solde actuel</th>
                            <th>Date de fin</th>
                            <th>Montant à l'échéance</th>
                            <th>Prochaine échéance</th>
                            <th>Statut</th>
                            <th class="text-center">Payer</th>
                            <th class="text-center">Détail</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($souscriptions as $s)
                            @php
                                // temporal_status est calculé dynamiquement depuis la date (indépendant du statut BDD)
                                $prochaine = $s->echeances->filter(fn($e) => $e->temporal_status !== 'termine')->sortBy(function($e) {
                                    $order = ['en_retard' => 0, 'aujourd_hui' => 1, 'a_venir' => 2];
                                    return [$order[$e->temporal_status] ?? 3, $e->date_echeance->timestamp];
                                })->first();
                            @endphp
                            <tr>
                                <td><strong>{{ $s->plan->nom }}</strong></td>
                                <td>{{ $s->plan->frequence_label }}</td>
                                <td><span style="font-size:.62rem; color:#6c757d;">{{ \Carbon\Carbon::parse($s->date_debut)->format('d/m/Y') }}</span></td>
                                <td class="text-end">{{ number_format($s->montant, 0, ',', ' ') }} XOF</td>
                                <td class="text-end fw-bold" style="color: #198754;">{{ number_format($s->solde_courant, 0, ',', ' ') }} XOF</td>
                                <td>{{ $s->date_fin ? $s->date_fin->format('d/m/Y') : '—' }}</td>
                                <td>{{ $s->date_fin ? number_format($s->montant_total_reverse, 0, ',', ' ') . ' XOF' : '—' }}</td>
                                <td>
                                    @if($prochaine)
                                        @if($prochaine->temporal_status === 'en_retard')
                                            <span class="text-danger" style="font-size:.62rem; font-weight:500;">
                                                <i class="bi bi-exclamation-triangle-fill"></i>
                                                {{ $prochaine->date_echeance->format('d/m/Y') }}
                                            </span>
                                            <span class="badge bg-danger ms-1" style="font-size:.55rem;">En retard</span>
                                        @elseif($prochaine->temporal_status === 'aujourd_hui')
                                            <span style="font-size:.62rem;">{{ $prochaine->date_echeance->format('d/m/Y') }}</span>
                                            <span class="badge bg-warning text-dark ms-1" style="font-size:.55rem;">Aujourd'hui</span>
                                        @else
                                            <span style="font-size:.62rem;">{{ $prochaine->date_echeance->format('d/m/Y') }}</span>
                                            <span class="badge bg-secondary ms-1" style="font-size:.55rem;">À venir</span>
                                        @endif
                                    @else
                                        <span class="text-success" style="font-size:.62rem;"><i class="bi bi-check-circle-fill"></i> Toutes payées</span>
                                    @endif
                                </td>
                                <td>
                                    @if($s->statut === 'active')
                                        <span class="badge bg-success" style="font-size:.58rem;">Active</span>
                                    @elseif($s->statut === 'suspendue')
                                        <span class="badge bg-warning" style="font-size:.58rem;">Suspendue</span>
                                    @else
                                        <span class="badge bg-secondary" style="font-size:.58rem;">Clôturée</span>
                                    @endif
                                </td>
                                {{-- Bouton payer la prochaine (ou prochaine en retard) --}}
                                <td class="text-center">
                                    @if($prochaine && $s->statut === 'active')
                                        <div class="btn-group" role="group">
                                            @if($paydunyaOk)
                                                <button type="button" class="btn {{ $prochaine->statut === 'en_retard' ? 'btn-danger' : 'btn-primary' }}"
                                                        onclick="initierPaiement('paydunya', {{ $prochaine->id }}, {{ $prochaine->montant }}, '{{ $s->plan->nom }}')"
                                                        title="Payer {{ number_format($prochaine->montant, 0, ',', ' ') }} XOF par Mobile/Carte">
                                                    <i class="bi bi-phone-fill"></i>
                                                </button>
                                            @endif
                                            @if($pispiOk)
                                                <button type="button" class="btn btn-success"
                                                        onclick="initierPaiement('pispi', {{ $prochaine->id }}, {{ $prochaine->montant }}, '{{ $s->plan->nom }}')"
                                                        title="Payer {{ number_format($prochaine->montant, 0, ',', ' ') }} XOF par Compte Bancaire">
                                                    <i class="bi bi-bank"></i>
                                                </button>
                                            @endif
                                            @if(!$paydunyaOk && !$pispiOk)
                                                <span class="text-muted" style="font-size:.58rem;">Aucun moyen configuré</span>
                                            @endif
                                        </div>
                                    @elseif(!$prochaine && $s->statut === 'active')
                                        <i class="bi bi-check-circle text-success" title="Toutes les échéances sont payées"></i>
                                    @else
                                        —
                                    @endif
                                </td>
                                <td class="text-center">
                                    <a href="{{ route('membre.epargne.souscription.show', $s) }}"
                                       class="btn btn-outline-secondary"
                                       title="Voir le détail complet et toutes les échéances">
                                        <i class="bi bi-info-circle"></i>
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="text-center py-5">
                <i class="bi bi-wallet2 text-muted" style="font-size: 2.5rem;"></i>
                <p class="text-muted mt-2 mb-2" style="font-size:.8rem; font-weight:300; font-family:'Ubuntu',sans-serif;">Vous n'avez aucune souscription tontine.</p>
                <a href="{{ route('membre.epargne.index') }}" class="btn btn-primary btn-sm" style="font-weight:300; font-family:'Ubuntu',sans-serif;">
                    <i class="bi bi-piggy-bank"></i> Voir les plans
                </a>
            </div>
        @endif
    </div>
</div>

{{-- ─── Tableau consolidé de TOUTES les échéances impayées ─────────────────── --}}
@if($souscriptions->count() > 0)
@php
    $toutesEcheances = collect();
    foreach($souscriptions as $s) {
        foreach($s->echeances as $e) {
            $e->_souscription = $s;
            $toutesEcheances->push($e);
        }
    }
    // Tri : en_retard en premier, puis par date croissante
    $enRetard = $toutesEcheances->where('statut', 'en_retard')->sortBy('date_echeance');
    $avenir   = $toutesEcheances->where('statut', 'a_venir')->sortBy('date_echeance');
    $toutesTriees = $enRetard->concat($avenir);
@endphp

@if($toutesTriees->count() > 0)
<div class="card">
    <div class="card-header d-flex align-items-center gap-2" style="font-weight: 300; font-family: 'Ubuntu', sans-serif; font-size: .78rem;">
        <span><i class="bi bi-calendar-event"></i> Toutes mes échéances à régler</span>
        @if($enRetard->count() > 0)
            <span class="badge bg-danger" style="font-size:.6rem;">
                <i class="bi bi-exclamation-triangle-fill"></i>
                {{ $enRetard->count() }} impayé(s) en retard
            </span>
        @endif
        <span class="badge bg-secondary ms-auto" style="font-size:.6rem;">{{ $toutesTriees->count() }} au total</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table tontine-table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Statut</th>
                        <th>État</th>
                        <th>Date échéance</th>
                        <th>Plan tontine</th>
                        <th class="text-end">Montant (XOF)</th>
                        <th class="text-center" colspan="2">Payer via</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($toutesTriees as $ech)
                    @php 
                        $tempStatus = $ech->temporal_status;
                        $etat = $ech->statut; // en_attente, en_cours, payee
                        $rowClass = $tempStatus === 'en_retard' ? 'row-overdue' : ($tempStatus === 'aujourd_hui' ? 'row-today' : '');
                    @endphp
                    <tr class="{{ $rowClass }}">
                        <td>
                            @if($tempStatus === 'en_retard')
                                <span class="badge bg-danger" style="font-size:.58rem;"><i class="bi bi-exclamation-triangle"></i> En retard</span>
                            @elseif($tempStatus === 'aujourd_hui')
                                <span class="badge bg-warning text-dark" style="font-size:.58rem;"><i class="bi bi-bell-fill"></i> Aujourd'hui</span>
                            @else
                                <span class="badge bg-secondary" style="font-size:.58rem;">À venir</span>
                            @endif
                        </td>
                        <td>
                            @if($etat === 'payee')
                                <span class="badge bg-success" style="font-size:.58rem;"><i class="bi bi-check-circle"></i> Payée</span>
                            @elseif($etat === 'en_cours')
                                <span class="badge bg-info-subtle text-info border border-info" style="font-size:.58rem; animation: pulse 2s infinite;">
                                    <i class="bi bi-hourglass-split"></i> En cours...
                                </span>
                            @else
                                <span class="badge bg-light text-muted border" style="font-size:.58rem;">En attente</span>
                            @endif
                        </td>
                        <td>
                            <strong>{{ $ech->date_echeance->format('d/m/Y') }}</strong>
                            @if($tempStatus === 'en_retard')
                                <span class="text-danger ms-1" style="font-size:.58rem;">{{ $ech->date_echeance->diffForHumans() }}</span>
                            @elseif($tempStatus === 'aujourd_hui')
                                <span class="text-warning ms-1" style="font-size:.58rem;">Aujourd'hui</span>
                            @else
                                <span class="text-muted ms-1" style="font-size:.58rem;">{{ $ech->date_echeance->diffForHumans() }}</span>
                            @endif
                        </td>
                        <td>{{ $ech->_souscription->plan->nom }}</td>
                        <td class="text-end fw-bold {{ $tempStatus === 'en_retard' ? 'text-danger' : '' }}">
                            {{ number_format($ech->montant, 0, ',', ' ') }}
                        </td>

                        {{-- Bouton PayDunya --}}
                        <td class="text-end" style="width: 45px; padding-right: 2px !important;">
                            @if($paydunyaOk)
                                <button type="button"
                                    onclick="initierPaiement('paydunya', {{ $ech->id }}, {{ $ech->montant }}, '{{ $ech->_souscription->plan->nom }}')"
                                    class="btn {{ $tempStatus === 'en_retard' ? 'btn-danger' : 'btn-primary' }} btn-sm px-2 py-1"
                                    title="Payer par Mobile/Carte">
                                    <i class="bi bi-phone-vibrate"></i> <span class="d-none d-lg-inline" style="font-size: 0.6rem;">Mobile</span>
                                </button>
                            @else
                                <span>—</span>
                            @endif
                        </td>

                        {{-- Bouton Pi-SPI --}}
                        <td class="text-start" style="width: 45px; padding-left: 2px !important;">
                            @if($pispiOk)
                                <button type="button"
                                    onclick="initierPaiement('pispi', {{ $ech->id }}, {{ $ech->montant }}, '{{ $ech->_souscription->plan->nom }}')"
                                    class="btn btn-success btn-sm px-2 py-1"
                                    title="Payer par Compte Bancaire (Pi-SPI)">
                                    <i class="bi bi-bank"></i> <span class="d-none d-lg-inline" style="font-size: 0.6rem;">Banque</span>
                                </button>
                            @else
                                <span>—</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr style="background: #f8fafd;">
                        <td colspan="3" style="font-size:.65rem; font-weight:300; font-family:'Ubuntu',sans-serif; padding: .3rem .5rem !important; color: #6c757d;">
                            Total restant à payer
                        </td>
                        <td class="text-end fw-bold" style="font-size:.72rem; padding: .3rem .5rem !important; color: var(--primary-dark-blue);">
                            {{ number_format($toutesTriees->sum('montant'), 0, ',', ' ') }} XOF
                        </td>
                        <td colspan="2"></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>
@endif
@endif

<!-- Modal de confirmation de paiement (Épargne) -->
<div class="modal fade" id="paymentConfirmModal" tabindex="-1" aria-labelledby="paymentConfirmModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header" id="modalHeader" style="background: var(--primary-dark-blue); color: white;">
                <h5 class="modal-title" id="paymentConfirmModalLabel" style="font-weight: 300; font-family: 'Ubuntu', sans-serif;">
                    <i class="bi bi-credit-card"></i> Confirmation de paiement
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" style="font-weight: 300; font-family: 'Ubuntu', sans-serif;">
                <p id="paymentConfirmMessage"></p>
                
                <div id="pispiWalletGroup" class="mb-3" style="display: none;">
                    <label class="form-label small fw-bold">Sélectionnez votre compte externe Pi-SPI :</label>
                    @include('membres.partials.pispi-compte-selector')
                </div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal" style="font-weight: 300; font-family: 'Ubuntu', sans-serif;">Annuler</button>
                <button type="button" class="btn btn-primary rounded-pill px-4" id="paymentConfirmButton" style="font-weight: 300; font-family: 'Ubuntu', sans-serif;">
                    <i class="bi bi-check-circle"></i> Confirmer
                </button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
let paymentMode = null;
let currentEcheanceId = null;

function initierPaiement(mode, echeanceId, montant, planNom) {
    paymentMode = mode;
    currentEcheanceId = echeanceId;
    
    if (mode === 'paydunya') {
        document.getElementById('modalHeader').style.background = 'var(--primary-dark-blue)';
        document.getElementById('paymentConfirmModalLabel').innerHTML = '<i class="bi bi-phone"></i> Paiement Mobile/Carte';
        document.getElementById('pispiWalletGroup').style.display = 'none';
        document.getElementById('paymentConfirmMessage').innerHTML = 'Régler l\'échéance du plan "<strong>' + planNom + '</strong>" d\'un montant de <strong>' + new Intl.NumberFormat('fr-FR').format(montant) + ' XOF</strong> ?';
    } else {
        document.getElementById('modalHeader').style.background = '#198754';
        document.getElementById('paymentConfirmModalLabel').innerHTML = '<i class="bi bi-bank"></i> Paiement Compte Bancaire (Pi-SPI)';
        document.getElementById('pispiWalletGroup').style.display = 'block';
        document.getElementById('paymentConfirmMessage').innerHTML = 'Régler l\'échéance du plan "<strong>' + planNom + '</strong>" via Pi-SPI ?';
    }
    
    const modal = new bootstrap.Modal(document.getElementById('paymentConfirmModal'));
    modal.show();
}

document.addEventListener('DOMContentLoaded', function() {
    const confirmButton = document.getElementById('paymentConfirmButton');
    if (confirmButton) {
        confirmButton.addEventListener('click', function() {
            if (!paymentMode || !currentEcheanceId) return;

            if (paymentMode === 'pispi') {
                const compteId = document.getElementById('compte_externe_id')?.value;
                if (!compteId) {
                    alert("Sélectionnez un compte externe.");
                    return;
                }
            }

            const form = document.createElement('form');
            form.method = 'POST';
            form.action = (paymentMode === 'pispi' 
                ? '{{ route("membre.epargne.echeance.pispi", ":id") }}' 
                : '{{ route("membre.epargne.echeance.paydunya", ":id") }}'
            ).replace(':id', currentEcheanceId);
            
            const csrfToken = document.createElement('input');
            csrfToken.type = 'hidden'; csrfToken.name = '_token'; csrfToken.value = '{{ csrf_token() }}';
            form.appendChild(csrfToken);

            if (paymentMode === 'pispi') {
                const walletInput = document.createElement('input');
                walletInput.type = 'hidden'; walletInput.name = 'compte_externe_id';
                walletInput.value = document.getElementById('compte_externe_id').value;
                form.appendChild(walletInput);
            }
            
            document.body.appendChild(form);
            form.submit();
        });
    }
});
</script>
@endpush
@endsection
