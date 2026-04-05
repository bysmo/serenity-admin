@extends('layouts.membre')

@section('title', 'Espace Garant')

@section('content')
<style>
    .card-stats {
        border-radius: 12px;
        border: none;
        box-shadow: 0 4px 6px rgba(0,0,0,0.05);
        transition: transform 0.2s;
    }
    .card-stats:hover {
        transform: translateY(-5px);
    }
    .quality-badge {
        font-size: 2rem;
        font-weight: 300;
        color: var(--primary-dark-blue);
        font-family: 'Ubuntu', sans-serif;
    }
    .table-compact {
        font-size: 0.75rem;
    }
    .table-compact th {
        background-color: var(--primary-dark-blue);
        color: white;
        font-weight: 300;
        padding: 8px;
    }
    .table-compact td {
        padding: 8px;
        vertical-align: middle;
    }
</style>

<div class="page-header mb-4">
    <h1 style="font-weight: 300; font-family: 'Ubuntu', sans-serif;">
        <i class="bi bi-shield-check"></i> Mon Espace Garant
    </h1>
    <p class="text-muted" style="font-weight: 300; font-family: 'Ubuntu', sans-serif;">Gérez vos garanties et vos gains accumulés.</p>
</div>

<div class="row mb-4">
    <!-- Score de Qualité -->
    <div class="col-md-4 mb-3">
        <div class="card card-stats h-100">
            <div class="card-body text-center d-flex flex-column justify-content-center">
                <h6 class="text-muted mb-2" style="font-size: 0.8rem; font-weight: 300;">Ma Qualité Garant</h6>
                <div class="quality-badge">{{ $stats['qualite'] }}</div>
                <p class="small text-muted mt-2 mb-0">Plus votre score est élevé, plus vous pouvez supporter de crédits simultanément.</p>
            </div>
        </div>
    </div>

    <!-- Solde de Gains -->
    <div class="col-md-4 mb-3">
        <div class="card card-stats h-100 bg-primary text-white">
            <div class="card-body text-center d-flex flex-column justify-content-center">
                <h6 class="text-white-50 mb-2" style="font-size: 0.8rem; font-weight: 300;">Mon Solde de Gains</h6>
                <div class="display-6" style="font-weight: 300;">{{ number_format($stats['total_gains'], 0, ',', ' ') }} <small style="font-size: 1rem;">XOF</small></div>
                <div class="mt-3">
                    <button type="button" class="btn btn-light btn-sm w-100" data-bs-toggle="modal" data-bs-target="#withdrawModal">
                        <i class="bi bi-wallet2"></i> Retirer mes gains
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistiques -->
    <div class="col-md-4 mb-3">
        <div class="card card-stats h-100">
            <div class="card-body">
                <h6 class="text-muted mb-3" style="font-size: 0.8rem; font-weight: 300;">Statistiques de Garantie</h6>
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span style="font-size: 0.8rem; font-weight: 300;">Garanties actives</span>
                    <span class="badge bg-success rounded-pill">{{ $stats['garanties_actives'] }}</span>
                </div>
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span style="font-size: 0.8rem; font-weight: 300;">Total crédits supportés</span>
                    <span class="badge bg-info rounded-pill text-white">{{ $stats['total_credits_supportes'] }}</span>
                </div>
                <div class="d-flex justify-content-between align-items-center">
                    <span style="font-size: 0.8rem; font-weight: 300;">Sollicitations en attente</span>
                    <span class="badge bg-warning rounded-pill">{{ $stats['nb_sollicitations'] }}</span>
                </div>
                <div class="mt-3">
                    <a href="{{ route('membre.garant.sollicitations') }}" class="btn btn-outline-primary btn-sm w-100">
                        Voir les sollicitations
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Retraits Récents -->
    <div class="col-md-8">
        <div class="card card-stats">
            <div class="card-header bg-transparent border-0 py-3">
                <h6 class="mb-0" style="font-weight: 300;"><i class="bi bi-clock-history"></i> Mes demandes de retrait récentes</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-compact mb-0">
                        <thead>
                            <tr>
                                <th>Référence</th>
                                <th>Montant</th>
                                <th>Date</th>
                                <th>Statut</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($retraitsRecents as $retrait)
                                <tr>
                                    <td><code>{{ $retrait->reference }}</code></td>
                                    <td>{{ number_format($retrait->montant, 0, ',', ' ') }} XOF</td>
                                    <td>{{ $retrait->created_at->format('d/m/Y H:i') }}</td>
                                    <td>
                                        @if($retrait->statut === 'en_attente')
                                            <span class="badge bg-warning text-dark">En attente</span>
                                        @elseif($retrait->statut === 'approuve')
                                            <span class="badge bg-success">Approuvé</span>
                                        @else
                                            <span class="badge bg-danger">Refusé</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center py-4 text-muted">Auncon retrait effectué.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Aide/Infos -->
    <div class="col-md-4">
        <div class="card card-stats" style="background-color: #f8f9fa;">
            <div class="card-body">
                <h6 style="font-weight: 400; font-size: 0.9rem;"><i class="bi bi-info-circle text-primary"></i> Comment ça marche ?</h6>
                <ul class="small text-muted ps-3 mb-0" style="font-weight: 300;">
                    <li class="mb-2">Chaque fois qu'un crédit que vous garantissez est remboursé, vous recevez une part des intérêts.</li>
                    <li class="mb-2">Votre qualité augmente à chaque remboursement réussi, vous permettant de garantir plus de crédits.</li>
                    <li>En cas de défaut de paiement de l'emprunteur, votre solde de tontine peut être sollicité.</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Retrait -->
<div class="modal fade" id="withdrawModal" tabindex="-1" aria-labelledby="withdrawModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form action="{{ route('membre.garant.withdraw') }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="withdrawModalLabel" style="font-weight: 300;">Demander un retrait</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3 text-center">
                        <small class="text-muted">Solde disponible</small>
                        <div class="h4 text-primary" style="font-weight: 300;">{{ number_format($membre->garant_solde, 0, ',', ' ') }} XOF</div>
                    </div>
                    <div class="mb-3">
                        <label for="montant" class="form-label" style="font-size: 0.8rem;">Montant à retirer (XOF)</label>
                        <input type="number" class="form-control" id="montant" name="montant" required min="500" max="{{ $membre->garant_solde }}" placeholder="Ex: 5000">
                        <div class="form-text small">Le montant sera vérifié et validé par l'administrateur.</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary btn-sm">Confirmer la demande</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection
