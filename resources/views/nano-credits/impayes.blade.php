@extends('layouts.app')

@section('title', 'Gestion des Impayés Nano-Crédits')

@section('content')
<div class="page-header d-flex justify-content-between align-items-center">
    <h1><i class="bi bi-exclamation-triangle text-danger"></i> Gestion des Impayés</h1>
    <a href="{{ route('admin.nano-credits.dashboard') }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-speedometer2"></i> Tableau de bord</a>
</div>

<div class="card shadow-sm">
    <div class="card-header bg-danger text-white">
        <i class="bi bi-list-ul"></i> Crédits en défaut de paiement
    </div>
    <div class="card-body p-0">
        @if($impayes->count() > 0)
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>N° Crédit</th>
                        <th>Membre</th>
                        <th>Montant Initial</th>
                        <th class="text-danger">Total Dû (Pénalités incl.)</th>
                        <th>Retard</th>
                        <th>Garants</th>
                        <th class="text-center">Actions de Recouvrement</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($impayes as $credit)
                    @php
                        $totalVerse = $credit->versements->sum(function($v) { return (float) $v->montant; });
                        $du = (float) $credit->montant + (float) $credit->montant_penalite;
                        $restant = $du - $totalVerse;
                    @endphp
                    <tr>
                        <td><strong>#{{ $credit->id }}</strong></td>
                        <td>
                            {{ $credit->membre->nom_complet }}<br>
                            <small class="text-muted"><i class="bi bi-telephone"></i> {{ $credit->telephone ?? $credit->membre->telephone }}</small>
                        </td>
                        <td>{{ number_format($credit->montant, 0, ',', ' ') }} FCFA</td>
                        <td class="text-danger fw-bold">{{ number_format($restant, 0, ',', ' ') }} FCFA</td>
                        <td>
                            <span class="badge bg-danger">{{ $credit->jours_retard }} jours</span>
                        </td>
                        <td>
                            @php $garantsCount = $credit->garants->where('statut', 'accepte')->count(); @endphp
                            @if($garantsCount > 0)
                                <span class="badge bg-info text-dark"><i class="bi bi-shield-check"></i> {{ $garantsCount }} garant(s)</span>
                            @else
                                <span class="badge bg-secondary">Aucun</span>
                            @endif
                        </td>
                        <td class="text-center">
                            <form action="{{ route('nano-credits.impayes.relancer', $credit) }}" method="POST" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-outline-warning mb-1" title="Envoyer notification au membre">
                                    <i class="bi bi-bell"></i> Relancer membre
                                </button>
                            </form>

                            @if($garantsCount > 0)
                            <form action="{{ route('nano-credits.impayes.prevenir-garants', $credit) }}" method="POST" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-outline-info mb-1" title="Avertir les garants">
                                    <i class="bi bi-envelope"></i> Alerter garants
                                </button>
                            </form>
                            <form action="{{ route('nano-credits.impayes.recouvrer', $credit) }}" method="POST" class="d-inline" onsubmit="return confirm('Êtes-vous sûr de vouloir prélever directement sur la tontine des garants pour couvrir cet impayé ?');">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-danger mb-1" title="Débiter les garants">
                                    <i class="bi bi-cash-stack"></i> Débiter garants
                                </button>
                            </form>
                            @endif
                            <a href="{{ route('nano-credits.show', $credit) }}" class="btn btn-sm btn-outline-primary mb-1">Détails</a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="p-3 border-top">
            {{ $impayes->links() }}
        </div>
        @else
            <div class="text-center py-5">
                <i class="bi bi-emoji-smile text-success" style="font-size: 3rem;"></i>
                <h5 class="mt-3 text-success">Aucun crédit en souffrance</h5>
                <p class="text-muted">La situation du portefeuille est saine.</p>
            </div>
        @endif
    </div>
</div>
@endsection
