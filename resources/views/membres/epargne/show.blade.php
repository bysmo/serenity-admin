@extends('layouts.membre')

@section('title', 'Tontine - ' . $souscription->plan->nom)

@section('content')
<style>
    .page-header .page-title { font-size: 1.1rem; font-weight: 300; font-family: 'Ubuntu', sans-serif; color: var(--primary-dark-blue); margin: 0; }
    .table-epargne-compact thead th { padding: 0.15rem 0.5rem !important; font-size: 0.6rem !important; line-height: 1.05 !important; vertical-align: middle !important; font-weight: 300 !important; font-family: 'Ubuntu', sans-serif !important; color: #fff !important; background-color: var(--primary-dark-blue) !important; }
    .table-epargne-compact tbody td { padding: 0.15rem 0.5rem !important; font-size: 0.65rem !important; line-height: 1.05 !important; vertical-align: middle !important; font-weight: 300 !important; font-family: 'Ubuntu', sans-serif !important; color: var(--primary-dark-blue) !important; border-bottom: 1px solid #f0f0f0 !important; }
    /* Espacement colonne Montant : plus d’espace à droite pour séparer de Statut/Mode */
    .table-epargne-compact thead th:nth-child(2),
    .table-epargne-compact tbody td:nth-child(2) { padding-left: 1rem !important; padding-right: 2rem !important; min-width: 6rem; }
    /* Colonne Statut / Mode : bien séparée de Montant */
    .table-epargne-compact thead th:nth-child(3),
    .table-epargne-compact tbody td:nth-child(3) { padding-left: 1.5rem !important; padding-right: 0.75rem !important; }
    /* Colonne Date de paiement (tableau Échéances) */
    .table-epargne-compact thead th:nth-child(4),
    .table-epargne-compact tbody td:nth-child(4) { padding-left: 1rem !important; padding-right: 0.75rem !important; }
    .table-epargne-compact tbody tr:last-child td { border-bottom: none !important; }
    .table-epargne-compact .btn { padding: 0 0.35rem !important; font-size: 0.6rem !important; line-height: 1.2 !important; height: 22px !important; font-weight: 300 !important; font-family: 'Ubuntu', sans-serif !important; }
    .table-epargne-compact .btn i { font-size: 0.65rem !important; }
    table.table.table-epargne-compact.table-hover tbody tr { background-color: #fff !important; }
    table.table.table-epargne-compact.table-hover tbody tr:nth-child(even) { background-color: #d4dde8 !important; }
    table.table.table-epargne-compact.table-hover tbody tr:hover { background-color: #b8c7d9 !important; }
    .table-epargne-compact tbody tr.row-paid { background-color: #d1e7dd !important; color: #0f5132 !important; }
    .table-epargne-compact tbody tr.row-unpaid { background-color: #f8dbdd !important; color: #842029 !important; }
    .table-epargne-compact tbody tr.row-overdue { background-color: #f8c0c0 !important; color: #842029 !important; font-weight: 500 !important; }
    .card-epargne .card-header { font-size: 0.75rem; font-weight: 300; font-family: 'Ubuntu', sans-serif; padding: 0.5rem 0.75rem; }
</style>

<div class="page-header d-flex justify-content-between align-items-center flex-wrap mb-3">
    <h1 class="page-title">
        <i class="bi bi-piggy-bank"></i> {{ $souscription->plan->nom }}
    </h1>
    <a href="{{ route('membre.epargne.mes-epargnes') }}" class="btn btn-outline-secondary btn-sm" style="font-weight: 300; font-family: 'Ubuntu', sans-serif;">
        <i class="bi bi-arrow-left"></i> Mes tontines
    </a>
</div>

<div class="row mb-3">
    <div class="col-md-3 mb-2">
        <div class="card text-white" style="background: var(--primary-dark-blue);">
            <div class="card-body" style="padding: 0.5rem 0.75rem;">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-subtitle mb-0 text-white-50" style="font-size: 0.65rem; font-weight: 300; font-family: 'Ubuntu', sans-serif;">Solde actuel</h6>
                        <h5 class="mb-0" style="font-size: 0.9rem; font-weight: 300; font-family: 'Ubuntu', sans-serif;">{{ number_format($souscription->solde_courant, 0, ',', ' ') }} XOF</h5>
                    </div>
                    <i class="bi bi-wallet2" style="font-size: 1.25rem; opacity: 0.5;"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-2">
        <div class="card text-white" style="background: var(--primary-blue);">
            <div class="card-body" style="padding: 0.5rem 0.75rem;">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-subtitle mb-0 text-white-50" style="font-size: 0.65rem; font-weight: 300; font-family: 'Ubuntu', sans-serif;">Montant / versement</h6>
                        <h5 class="mb-0" style="font-size: 0.9rem; font-weight: 300; font-family: 'Ubuntu', sans-serif;">{{ number_format($souscription->montant, 0, ',', ' ') }} XOF</h5>
                    </div>
                    <i class="bi bi-cash-coin" style="font-size: 1.25rem; opacity: 0.5;"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-2">
        <div class="card text-white" style="background: #28a745;">
            <div class="card-body" style="padding: 0.5rem 0.75rem;">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-subtitle mb-0 text-white-50" style="font-size: 0.65rem; font-weight: 300; font-family: 'Ubuntu', sans-serif;">Date de fin</h6>
                        <h5 class="mb-0" style="font-size: 0.85rem; font-weight: 300; font-family: 'Ubuntu', sans-serif;">{{ $souscription->date_fin ? $souscription->date_fin->format('d/m/Y') : '—' }}</h5>
                    </div>
                    <i class="bi bi-calendar-event" style="font-size: 1.25rem; opacity: 0.5;"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-2">
        <div class="card text-white" style="background: #17a2b8;">
            <div class="card-body" style="padding: 0.5rem 0.75rem;">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-subtitle mb-0 text-white-50" style="font-size: 0.65rem; font-weight: 300; font-family: 'Ubuntu', sans-serif;">Montant reversé à l'échéance</h6>
                        <h5 class="mb-0" style="font-size: 0.85rem; font-weight: 300; font-family: 'Ubuntu', sans-serif;">{{ number_format($souscription->montant_total_reverse, 0, ',', ' ') }} XOF</h5>
                        <small class="text-white-50" style="font-size: 0.6rem; font-weight: 300;">épargne + rémunération ({{ number_format($souscription->plan->taux_remuneration ?? 0, 1, ',', ' ') }} %)</small>
                    </div>
                    <i class="bi bi-bank" style="font-size: 1.25rem; opacity: 0.5;"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-5 mb-3">
        <div class="card card-epargne border-primary shadow-sm h-100" style="border-width: 1px;">
            <div class="card-header bg-primary text-white" style="font-weight: 300; font-family: 'Ubuntu', sans-serif;">
                <i class="bi bi-box-arrow-right"></i> Demander un retrait de fonds
            </div>
            <div class="card-body p-3">
                @php
                    $retraits = \App\Models\EpargneRetraitDemande::where('souscription_id', $souscription->id)->orderBy('created_at', 'desc')->get();
                    $hasPending = $retraits->where('statut', 'en_attente')->count() > 0;
                    $soldeActual = (float) $souscription->solde_courant;
                    $estimationLiquidation = (float) $souscription->estimation_liquidation;
                    $isFinished = $souscription->date_fin && $souscription->date_fin->isPast();
                @endphp

                <div class="mb-3 p-2 rounded {{ $isFinished ? 'bg-success bg-opacity-10' : 'bg-info bg-opacity-10' }}">
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="small fw-bold">Valeur de liquidation estimée :</span>
                        <span class="fs-5 fw-bold {{ $isFinished ? 'text-success' : 'text-primary' }}">{{ number_format($estimationLiquidation, 0, ',', ' ') }} XOF</span>
                    </div>
                    <p class="mb-0 x-small text-muted mt-1" style="font-size: 0.65rem;">
                        @if($isFinished)
                            <i class="bi bi-check-circle-fill text-success"></i> Tontine terminée : inclut l'épargne + les rémunérations prévues.
                        @else
                            <i class="bi bi-exclamation-circle-fill text-info"></i> Tontine en cours : retrait limité au capital épargné (pénalité de sortie anticipée).
                        @endif
                    </p>
                </div>

                @if($hasPending)
                    <div class="alert alert-warning py-2 border-0 shadow-sm" style="font-size: 0.72rem;">
                        <i class="bi bi-hourglass-split me-1"></i> Vous avez déjà une demande de retrait en attente.
                    </div>
                @elseif($soldeActual <= 0)
                    <p class="text-muted small text-center mb-0 py-3" style="font-size: 0.68rem;">
                        <i class="bi bi-info-circle me-1"></i> Votre solde est nul. Epargnez davantage pour pouvoir retirer.
                    </p>
                @else
                    <form action="{{ route('membre.epargne.demande-retrait', $souscription) }}" method="POST">
                        @csrf
                        <div class="mb-2">
                            <label class="form-label small mb-1" style="font-size: 0.65rem;">Montant à retirer (Max: {{ number_format($soldeActual, 0, ',', ' ') }} XOF)</label>
                            <input type="number" name="montant_demande" class="form-control form-control-sm bg-light" value="{{ $soldeActual }}" max="{{ $soldeActual }}" min="100" required style="font-size: 0.8rem; font-weight: 500;">
                        </div>
                        <div class="mb-3">
                            <label class="form-label small mb-1" style="font-size: 0.65rem;">Moyen de réception</label>
                            <select name="mode_retrait" class="form-select form-select-sm" style="font-size: 0.75rem;">
                                <option value="virement_interne">Virement Interne (Compte Serenity)</option>
                                <option value="pispi">Transfert Instantané Pi-SPI</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary btn-sm w-100 py-2 fw-bold shadow-sm" onclick="return confirm('Soumettre cette demande de retrait ?');">
                            <i class="bi bi-send-check"></i> SOUMETTRE LA DEMANDE
                        </button>
                    </form>
                @endif
            </div>
        </div>
    </div>

    <div class="col-md-7 mb-3">
        <div class="card card-epargne h-100 shadow-sm">
            <div class="card-header bg-light d-flex justify-content-between align-items-center py-2">
                <span class="fw-bold"><i class="bi bi-clock-history"></i> Suivi des retraits</span>
                <span class="badge bg-secondary" style="font-size: 0.6rem;">{{ $retraits->count() }} demande(s)</span>
            </div>
            <div class="card-body p-0">
                @if($retraits->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-epargne-compact table-sm table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Date</th>
                                    <th>Montant</th>
                                    <th>Mode</th>
                                    <th class="text-center">Statut</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($retraits as $retrait)
                                    <tr>
                                        <td>{{ $retrait->created_at->format('d/m/Y') }}</td>
                                        <td class="fw-bold">{{ number_format($retrait->montant_demande, 0, ',', ' ') }}</td>
                                        <td class="small">{{ $retrait->mode_retrait === 'pispi' ? 'Pi-SPI' : 'Interne' }}</td>
                                        <td class="text-center">
                                            @if($retrait->statut === 'en_attente')
                                                <span class="badge bg-warning text-dark px-2">En attente</span>
                                            @elseif($retrait->statut === 'traite')
                                                <span class="badge bg-success px-2">Validé</span>
                                            @else
                                                <span class="badge bg-danger px-2">Rejeté</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="p-4 text-center text-muted small">
                        <i class="bi bi-info-circle d-block mb-1 fs-4 opacity-25"></i>
                        Aucune demande de retrait effectuée.
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<div class="card card-epargne mb-3 shadow-sm">
    <div class="card-header" style="font-weight: 300; font-family: 'Ubuntu', sans-serif;">
        <i class="bi bi-calendar-check"></i> Échéances
    </div>
    <div class="card-body p-0">
        @if($souscription->echeances->count() > 0)
            <div class="table-responsive">
                <table class="table table-epargne-compact table-striped table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Date échéance</th>
                            <th class="text-end">Montant</th>
                            <th>Statut</th>
                            <th>État</th>
                            <th>Date de paiement</th>
                            <th class="text-center">Payer</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($souscription->echeances as $echeance)
                            @php
                                $tempStatus = $echeance->temporal_status;
                                $etat = $echeance->statut;
                                $rowClass = '';
                                if ($etat === 'payee') $rowClass = 'row-paid';
                                elseif ($tempStatus === 'en_retard') $rowClass = 'row-overdue';
                            @endphp
                            <tr class="{{ $rowClass }}">
                                <td>{{ $echeance->date_echeance->format('d/m/Y') }}</td>
                                <td class="text-end">{{ number_format($echeance->montant, 0, ',', ' ') }} XOF</td>
                                <td>
                                    @if($tempStatus === 'en_retard')
                                        <span class="badge bg-danger">En retard</span>
                                    @elseif($tempStatus === 'aujourd_hui')
                                        <span class="badge bg-warning text-dark">Aujourd'hui</span>
                                    @elseif($tempStatus === 'termine')
                                        <span class="badge bg-light text-muted">Terminé</span>
                                    @else
                                        <span class="badge bg-secondary">À venir</span>
                                    @endif
                                </td>
                                <td>
                                    @if($etat === 'payee')
                                        <span class="badge bg-success">Payée</span>
                                    @elseif($etat === 'en_cours')
                                        <span class="badge bg-info-subtle text-info border border-info" style="animation: pulse 2s infinite;">En cours...</span>
                                    @else
                                        <span class="badge bg-light text-muted border">En attente</span>
                                    @endif
                                </td>
                                <td>{{ $echeance->paye_le ? $echeance->paye_le->format('d/m/Y H:i') : '—' }}</td>
                                <td class="text-center">
                                    @if(in_array($echeance->statut, ['en_attente', 'en_cours']) && $echeance->temporal_status !== 'termine')
                                        <div class="btn-group" role="group">
                                            {{-- Option PayDunya --}}
                                            @if(\App\Models\PayDunyaConfiguration::getActive()?->enabled)
                                                <form action="{{ route('membre.epargne.echeance.paydunya', $echeance) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm btn-primary" title="Payer par Mobile/Carte">
                                                        <i class="bi bi-phone"></i>
                                                    </button>
                                                </form>
                                            @endif

                                            {{-- Option Pi-SPI --}}
                                            @if(\App\Models\PiSpiConfiguration::getActive()?->enabled)
                                                <form action="{{ route('membre.epargne.echeance.pispi', $echeance) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm btn-success" title="Payer par Compte Bancaire">
                                                        <i class="bi bi-bank"></i>
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    @else
                                        —
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="p-3 text-center text-muted" style="font-size: 0.75rem; font-weight: 300; font-family: 'Ubuntu', sans-serif;">Aucune échéance pour le moment.</div>
        @endif
    </div>
</div>

</div>


@if(isset($paymentStatus))
    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            @if($paymentStatus === 'success')
                showToast('{{ $paymentMessage ?? "Paiement effectué avec succès." }}', 'success');
            @elseif($paymentStatus === 'cancelled')
                showToast('{{ $paymentMessage ?? "Paiement annulé." }}', 'warning');
            @elseif($paymentStatus === 'pending')
                showToast('{{ $paymentMessage ?? "Paiement en attente de confirmation." }}', 'info');
            @elseif($paymentStatus === 'error')
                showToast('{{ $paymentMessage ?? "Erreur lors du paiement." }}', 'error');
            @endif
        });
    </script>
    @endpush
@endif
@endsection
