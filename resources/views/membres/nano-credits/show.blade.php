@extends('layouts.membre')

@section('title', 'Nano crédit #' . $nanoCredit->id)

@section('content')
<style>
    .nc-table thead th {
        padding: 0.2rem 0.5rem !important; font-size: 0.62rem !important; font-weight: 300 !important;
        font-family: 'Ubuntu', sans-serif !important; color: #fff !important;
        background-color: var(--primary-dark-blue) !important;
    }
    .nc-table tbody td {
        padding: 0.2rem 0.5rem !important; font-size: 0.68rem !important; font-weight: 300 !important;
        font-family: 'Ubuntu', sans-serif !important; color: var(--primary-dark-blue) !important;
        border-bottom: 1px solid #f0f0f0 !important;
    }
    .nc-table .btn { padding: 0 0.35rem !important; font-size: 0.6rem !important; height: 22px !important; font-weight: 300 !important; }
    .nc-stat-card { border-radius: 10px; padding: 0.6rem 0.9rem; }
    .nc-stat-label { font-size: 0.62rem; font-weight: 300; font-family: 'Ubuntu', sans-serif; opacity: 0.75; }
    .nc-stat-value { font-size: 1rem; font-weight: 400; font-family: 'Ubuntu', sans-serif; }
</style>

<div class="page-header d-flex justify-content-between align-items-center flex-wrap mb-3">
    <h1 style="font-size: 1.05rem; font-weight: 300; font-family: 'Ubuntu', sans-serif; color: var(--primary-dark-blue); margin: 0;">
        <i class="bi bi-phone"></i> Nano crédit #{{ $nanoCredit->id }}
    </h1>
    <a href="{{ route('membre.nano-credits.mes') }}" class="btn btn-outline-secondary btn-sm" style="font-weight:300; font-family:'Ubuntu',sans-serif;">
        <i class="bi bi-arrow-left"></i> Mes nano crédits
    </a>
</div>

{{-- Cartes de synthèse --}}
<div class="row mb-3">
    <div class="col-6 col-md-3 mb-2">
        <div class="nc-stat-card text-white" style="background: var(--primary-dark-blue);">
            <div class="nc-stat-label">Montant accordé</div>
            <div class="nc-stat-value">{{ number_format($nanoCredit->montant, 0, ',', ' ') }} <small style="font-size:.6em;">XOF</small></div>
        </div>
    </div>
    <div class="col-6 col-md-3 mb-2">
        <div class="nc-stat-card text-white" style="background: var(--primary-blue);">
            <div class="nc-stat-label">Statut</div>
            <div class="nc-stat-value" style="font-size:.85rem;">{{ $nanoCredit->statut_label }}</div>
        </div>
    </div>
    @if($nanoCredit->date_octroi)
    <div class="col-6 col-md-3 mb-2">
        <div class="nc-stat-card text-white" style="background: #17a2b8;">
            <div class="nc-stat-label">Date d'octroi</div>
            <div class="nc-stat-value" style="font-size:.85rem;">{{ $nanoCredit->date_octroi->format('d/m/Y') }}</div>
        </div>
    </div>
    @endif
    @if($nanoCredit->date_fin_remboursement)
    <div class="col-6 col-md-3 mb-2">
        <div class="nc-stat-card text-white" style="background: {{ $nanoCredit->date_fin_remboursement->isPast() ? '#dc3545' : '#28a745' }};">
            <div class="nc-stat-label">Fin remboursement</div>
            <div class="nc-stat-value" style="font-size:.85rem;">{{ $nanoCredit->date_fin_remboursement->format('d/m/Y') }}</div>
        </div>
    </div>
    @endif
</div>

{{-- Alertes session --}}
@if(session('success'))
    <div class="alert alert-success" style="font-size:.78rem; font-weight:300; font-family:'Ubuntu',sans-serif;">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="alert alert-danger" style="font-size:.78rem; font-weight:300; font-family:'Ubuntu',sans-serif;">{{ session('error') }}</div>
@endif
@if(session('info'))
    <div class="alert alert-info" style="font-size:.78rem; font-weight:300; font-family:'Ubuntu',sans-serif;">{{ session('info') }}</div>
@endif

{{-- Tableau d'amortissement --}}
@if($nanoCredit->isDebourse() && $nanoCredit->echeances->count() > 0)
    <div class="card mb-3">
        <div class="card-header" style="font-weight:300; font-family:'Ubuntu',sans-serif; font-size:.78rem;">
            <i class="bi bi-calendar-check"></i> Tableau d'amortissement
            @if($nanoCredit->statut !== 'rembourse')
                <span class="badge bg-warning text-dark ms-2" style="font-size:.6rem;">
                    {{ $nanoCredit->echeances->where('statut', 'a_venir')->count() + $nanoCredit->echeances->where('statut', 'en_retard')->count() }} échéance(s) restante(s)
                </span>
            @endif
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table nc-table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Date échéance</th>
                            <th class="text-end">Montant (XOF)</th>
                            <th>Statut</th>
                            <th>Date paiement</th>
                            @if($nanoCredit->statut !== 'rembourse')
                                <th class="text-center">Payer</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($nanoCredit->echeances as $e)
                            <tr>
                                <td>{{ $e->numero_echeance }}</td>
                                <td>{{ $e->date_echeance->format('d/m/Y') }}</td>
                                <td class="text-end">{{ number_format($e->montant_du, 0, ',', ' ') }}</td>
                                <td>
                                    @if($e->statut === 'payee')
                                        <span class="badge bg-success" style="font-size:.58rem;">Payée</span>
                                    @elseif($e->statut === 'en_retard')
                                        <span class="badge bg-danger" style="font-size:.58rem;">En retard</span>
                                    @else
                                        <span class="badge bg-secondary" style="font-size:.58rem;">À venir</span>
                                    @endif
                                </td>
                                <td>{{ $e->paye_le ? \Carbon\Carbon::parse($e->paye_le)->format('d/m/Y H:i') : '—' }}</td>
                                @if($nanoCredit->statut !== 'rembourse')
                                    <td class="text-center">
                                        @if(in_array($e->statut, ['a_venir', 'en_retard']))
                                            {{-- On ne montre les boutons que pour la 1re échéance impayée --}}
                                            @if($loop->first || $nanoCredit->echeances->where('statut', '!=', 'payee')->first()?->id === $e->id)
                                                <div class="btn-group" role="group">
                                                    @if($paydunyaEnabled)
                                                        <form action="{{ route('membre.nano-credits.rembourser.paydunya', $nanoCredit) }}" method="POST" class="d-inline">
                                                            @csrf
                                                            <button type="submit" class="btn btn-primary" title="Rembourser via PayDunya (Mobile Money)">
                                                                <i class="bi bi-phone-fill"></i>
                                                            </button>
                                                        </form>
                                                    @endif
                                                    @if($pispiEnabled)
                                                        <form action="{{ route('membre.nano-credits.rembourser.pispi', $nanoCredit) }}" method="POST" class="d-inline">
                                                            @csrf
                                                            <button type="submit" class="btn btn-success" title="Rembourser via Pi-SPI">
                                                                <i class="bi bi-bank"></i>
                                                            </button>
                                                        </form>
                                                    @endif
                                                </div>
                                            @else
                                                <span class="text-muted" style="font-size:.6rem;">—</span>
                                            @endif
                                        @else
                                            <i class="bi bi-check-circle text-success"></i>
                                        @endif
                                    </td>
                                @endif
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endif

{{-- Historique des versements --}}
<div class="card">
    <div class="card-header" style="font-weight:300; font-family:'Ubuntu',sans-serif; font-size:.78rem;">
        <i class="bi bi-cash-coin"></i> Historique des remboursements
    </div>
    <div class="card-body p-0">
        @if($nanoCredit->versements->count() > 0)
            <div class="table-responsive">
                <table class="table nc-table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th class="text-end">Montant (XOF)</th>
                            <th>Mode</th>
                            <th>Référence</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($nanoCredit->versements->sortByDesc('date_versement') as $v)
                            <tr>
                                <td>{{ $v->date_versement->format('d/m/Y') }}</td>
                                <td class="text-end fw-bold text-success">+{{ number_format($v->montant, 0, ',', ' ') }}</td>
                                <td>{{ $v->mode_paiement }}</td>
                                <td style="font-size:.58rem; color:#6c757d;">{{ $v->reference ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="text-center text-muted p-4" style="font-size:.75rem; font-weight:300; font-family:'Ubuntu',sans-serif;">
                @if($nanoCredit->isDebourse())
                    <i class="bi bi-clock-history fs-3 d-block mb-2 opacity-40"></i>
                    Aucun remboursement enregistré. Utilisez les boutons ci-dessus pour rembourser une échéance.
                @else
                    Le crédit n'a pas encore été décaissé.
                @endif
            </div>
        @endif
    </div>
</div>

@if(isset($paymentStatus))
    @push('scripts')
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        @if($paymentStatus === 'success')
            showToast('{{ $paymentMessage ?? "Remboursement enregistré avec succès." }}', 'success');
        @elseif($paymentStatus === 'cancelled')
            showToast('Paiement annulé.', 'warning');
        @elseif($paymentStatus === 'pending')
            showToast('Paiement en attente de confirmation.', 'info');
        @elseif($paymentStatus === 'error')
            showToast('{{ $paymentMessage ?? "Erreur lors du paiement." }}', 'error');
        @endif
    });
    </script>
    @endpush
@endif
@endsection
