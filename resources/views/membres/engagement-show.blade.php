@extends('layouts.membre')

@section('title', 'Détails de l\'Engagement')

@section('content')

<div class="page-header" style="background-color: white; padding: 0.6rem 1rem; margin-bottom: 1rem; border-radius: 4px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
    <h1 style="font-weight: 300; font-family: 'Ubuntu', sans-serif; font-size: 0.9rem; margin: 0;">
        <i class="bi bi-clipboard-check"></i> Détails de l'Engagement
    </h1>
</div>

<div class="card mb-3">
    <div class="card-header" style="font-weight: 300; font-family: 'Ubuntu', sans-serif;">
        <i class="bi bi-info-circle"></i> Informations de l'Engagement
    </div>
    <div class="card-body">
        <style>
            .info-grid {
                display: grid;
                grid-template-columns: repeat(4, 1fr);
                gap: 0;
                font-weight: 300;
                font-family: 'Ubuntu', sans-serif;
                font-size: 0.75rem;
            }
            .info-item {
                padding: 0.6rem 0.8rem;
                border-bottom: 1px solid #e9ecef;
                border-right: 1px solid #e9ecef;
                display: flex;
                align-items: center;
                gap: 0.5rem;
            }
            .info-item:nth-child(4n) {
                border-right: none;
            }
            .info-item:nth-child(4n+1),
            .info-item:nth-child(4n+2),
            .info-item:nth-child(4n+3),
            .info-item:nth-child(4n+4) {
                background-color: #ffffff;
            }
            .info-item:nth-child(8n+5),
            .info-item:nth-child(8n+6),
            .info-item:nth-child(8n+7),
            .info-item:nth-child(8n+8) {
                background-color: #f8f9fa;
            }
            .info-label {
                font-weight: 300;
                color: #6c757d;
                font-size: 0.7rem;
                white-space: nowrap;
            }
            .info-value {
                font-weight: 300;
                color: #212529;
                flex: 1;
            }
        </style>
        <div class="info-grid">
            <div class="info-item">
                <span class="info-label">Numéro :</span>
                <span class="info-value">{{ $engagement->numero ?? '-' }}</span>
            </div>
            <div class="info-item">
                <span class="info-label">Cotisation :</span>
                <span class="info-value">{{ $engagement->cotisation->nom ?? '-' }}</span>
            </div>
            <div class="info-item">
                <span class="info-label">Montant engagé :</span>
                <span class="info-value"><strong>{{ number_format($engagement->montant_engage, 0, ',', ' ') }} XOF</strong></span>
            </div>
            <div class="info-item">
                <span class="info-label">Périodicité :</span>
                <span class="info-value">{{ ucfirst($engagement->periodicite ?? '-') }}</span>
            </div>
            <div class="info-item">
                <span class="info-label">Période début :</span>
                <span class="info-value">{{ $engagement->periode_debut ? $engagement->periode_debut->format('d/m/Y') : '-' }}</span>
            </div>
            <div class="info-item">
                <span class="info-label">Période fin :</span>
                <span class="info-value">{{ $engagement->periode_fin ? $engagement->periode_fin->format('d/m/Y') : '-' }}</span>
            </div>
            <div class="info-item">
                <span class="info-label">Statut :</span>
                <span class="info-value">
                    @if($engagement->statut === 'en_cours')
                        <span style="color: #ffc107;">En cours</span>
                    @elseif($engagement->statut === 'en_retard')
                        <span style="color: #dc3545;">En retard</span>
                    @elseif($engagement->statut === 'honore')
                        <span style="color: #28a745;">Honoré</span>
                    @else
                        <span style="color: #dc3545;">{{ ucfirst(str_replace('_', ' ', $engagement->statut)) }}</span>
                    @endif
                </span>
            </div>
            <div class="info-item">
                <span class="info-label">Caisse :</span>
                <span class="info-value">{{ $engagement->cotisation->caisse->nom ?? '-' }}</span>
            </div>
            <div class="info-item">
                <span class="info-label">Montant payé :</span>
                <span class="info-value"><strong style="color: #28a745;">{{ number_format($montantPaye, 0, ',', ' ') }} XOF</strong></span>
            </div>
            <div class="info-item">
                <span class="info-label">Reste à payer :</span>
                <span class="info-value"><strong style="color: #dc3545;">{{ number_format($resteAPayer, 0, ',', ' ') }} XOF</strong></span>
            </div>
            @if($engagement->tag)
            <div class="info-item">
                <span class="info-label">Tag :</span>
                <span class="info-value">{{ $engagement->tag }}</span>
            </div>
            @endif
            @if($engagement->notes)
            <div class="info-item">
                <span class="info-label">Notes :</span>
                <span class="info-value">{{ $engagement->notes }}</span>
            </div>
            @endif
        </div>
    </div>
</div>

<div class="card mb-3">
    <div class="card-header d-flex justify-content-between align-items-center" style="font-weight: 300; font-family: 'Ubuntu', sans-serif;">
        <span><i class="bi bi-list-ul"></i> Historique des Paiements</span>
        @if(in_array($engagement->statut, ['en_cours', 'en_retard']) && $resteAPayer > 0)
            <div class="d-flex gap-2">
                @if($paydunyaEnabled)
                    <button type="button" 
                            class="btn btn-primary btn-sm" 
                            onclick="initierPaiementPayDunya({{ $engagement->id }}, '{{ $engagement->cotisation->nom }}', {{ $resteAPayer }})"
                            style="font-weight: 300; font-family: 'Ubuntu', sans-serif;">
                        <i class="bi bi-phone"></i> PayDunya
                    </button>
                @endif
                
                @if($pispiEnabled)
                    <button type="button" 
                            class="btn btn-success btn-sm" 
                            onclick="initierPaiementPiSpi({{ $engagement->id }}, '{{ $engagement->cotisation->nom }}', {{ $resteAPayer }})"
                            style="font-weight: 300; font-family: 'Ubuntu', sans-serif;">
                        <i class="bi bi-bank"></i> Pi-SPI
                    </button>
                @endif
            </div>
        @endif
    </div>
    <div class="card-body">
        @if($paiements->count() > 0)
            <style>
                .table-paiements {
                    margin-bottom: 0;
                }
                .table-paiements thead th {
                    padding: 0.15rem 0.35rem !important;
                    font-size: 0.7rem !important;
                    line-height: 1.05 !important;
                    vertical-align: middle !important;
                    font-weight: 300 !important;
                    font-family: 'Ubuntu', sans-serif !important;
                    color: var(--primary-dark-blue) !important;
                    background-color: #e9ecef !important;
                    border-bottom: 2px solid #dee2e6 !important;
                }
                .table-paiements tbody td {
                    padding: 0.15rem 0.35rem !important;
                    font-size: 0.75rem !important;
                    line-height: 1.05 !important;
                    vertical-align: middle !important;
                    border-bottom: 1px solid #f0f0f0 !important;
                    font-weight: 300 !important;
                    font-family: 'Ubuntu', sans-serif !important;
                    color: var(--primary-dark-blue) !important;
                }
                .table-paiements tbody tr:last-child td {
                    border-bottom: none !important;
                }
                table.table.table-paiements.table-hover tbody tr {
                    background-color: #ffffff !important;
                    transition: background-color 0.2s ease !important;
                }
                table.table.table-paiements.table-hover tbody tr:nth-child(even) {
                    background-color: #d4dde8 !important;
                }
                table.table.table-paiements.table-hover tbody tr:hover {
                    background-color: #b8c7d9 !important;
                    cursor: pointer !important;
                }
                table.table.table-paiements.table-hover tbody tr:nth-child(even):hover {
                    background-color: #9fb3cc !important;
                }
            </style>
            <div class="table-responsive">
                <table class="table table-hover table-paiements mb-0">
                    <thead>
                        <tr>
                            <th>Numéro</th>
                            <th>Date de paiement</th>
                            <th>Montant</th>
                            <th>Mode de paiement</th>
                            <th>Caisse</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($paiements as $paiement)
                            <tr>
                                <td>{{ $paiement->numero ?? '-' }}</td>
                                <td>{{ $paiement->date_paiement->format('d/m/Y') }}</td>
                                <td><strong>{{ number_format($paiement->montant, 0, ',', ' ') }} XOF</strong></td>
                                <td>{{ ucfirst(str_replace('_', ' ', $paiement->mode_paiement ?? 'N/A')) }}</td>
                                <td>{{ $paiement->caisse->nom ?? '-' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr style="background-color: #e9ecef !important;">
                            <td colspan="2" style="font-weight: 400; font-family: 'Ubuntu', sans-serif;"><strong>Total :</strong></td>
                            <td colspan="3" style="font-weight: 400; font-family: 'Ubuntu', sans-serif;">
                                <strong style="color: #28a745;">{{ number_format($montantPaye, 0, ',', ' ') }} XOF</strong>
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        @else
            <div class="text-center py-3">
                <i class="bi bi-inbox" style="font-size: 1.5rem; color: #ccc;"></i>
                <p class="text-muted mt-2 mb-0" style="font-size: 0.65rem; font-weight: 300; font-family: 'Ubuntu', sans-serif;">
                    Aucun paiement effectué pour cet engagement
                </p>
            </div>
        @endif
    </div>
</div>

<div class="d-flex justify-content-between align-items-center">
    <a href="{{ route('membre.engagements') }}" class="btn btn-secondary" style="font-weight: 300; font-family: 'Ubuntu', sans-serif;">
        <i class="bi bi-arrow-left"></i> Retour à la liste
    </a>
    @if(in_array($engagement->statut, ['en_cours', 'en_retard']) && $resteAPayer > 0)
        @if($paymentMethods && $paymentMethods->count() > 0)
            <div class="d-flex gap-2 align-items-center">
                <span style="font-size: 0.75rem; font-weight: 300; font-family: 'Ubuntu', sans-serif; color: var(--primary-dark-blue);">Moyen de paiement :</span>
                <div class="btn-group" role="group">
                    @foreach($paymentMethods as $method)
                        @if($method->code === 'paydunya' && $paydunyaEnabled)
                            <button type="button" 
                                    class="btn btn-primary" 
                                    onclick="initierPaiementPayDunya({{ $engagement->id }}, '{{ $engagement->cotisation->nom }}', {{ $resteAPayer }})"
                                    style="font-weight: 300; font-family: 'Ubuntu', sans-serif;">
                                @if($method->icon)
                                    <i class="{{ $method->icon }}"></i>
                                @endif
                                {{ $method->name }}
                            </button>
                        @elseif($method->code === 'pispi' && $pispiEnabled)
                            <button type="button" 
                                    class="btn btn-success" 
                                    onclick="initierPaiementPiSpi({{ $engagement->id }}, '{{ $engagement->cotisation->nom }}', {{ $resteAPayer }})"
                                    style="font-weight: 300; font-family: 'Ubuntu', sans-serif;">
                                @if($method->icon)
                                    <i class="{{ $method->icon }}"></i>
                                @endif
                                {{ $method->name }}
                            </button>
                        @elseif($method->code === 'paypal')
                            <button type="button" 
                                    class="btn btn-primary" 
                                    disabled
                                    title="PayPal sera bientôt disponible"
                                    style="font-weight: 300; font-family: 'Ubuntu', sans-serif;">
                                @if($method->icon)
                                    <i class="{{ $method->icon }}"></i>
                                @endif
                                {{ $method->name }}
                            </button>
                        @elseif($method->code === 'stripe')
                            <button type="button" 
                                    class="btn btn-primary" 
                                    disabled
                                    title="Stripe sera bientôt disponible"
                                    style="font-weight: 300; font-family: 'Ubuntu', sans-serif;">
                                @if($method->icon)
                                    <i class="{{ $method->icon }}"></i>
                                @endif
                                {{ $method->name }}
                            </button>
                        @endif
                    @endforeach
                </div>
            </div>
        @else
            <button type="button" 
                    class="btn btn-primary" 
                    disabled
                    title="Aucun moyen de paiement activé. Veuillez contacter l'administration."
                    style="font-weight: 300; font-family: 'Ubuntu', sans-serif;">
                <i class="bi bi-credit-card"></i> Payer mon engagement
            </button>
        @endif
    @endif
</div>

<!-- Modal de confirmation de paiement PayDunya -->
<div class="modal fade" id="paydunyaConfirmModal" tabindex="-1" aria-labelledby="paydunyaConfirmModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header" style="background: var(--primary-dark-blue); color: white;">
                <h5 class="modal-title" id="paydunyaConfirmModalLabel" style="font-weight: 300; font-family: 'Ubuntu', sans-serif;">
                    <i class="bi bi-phone"></i> Confirmation de paiement
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" style="font-weight: 300; font-family: 'Ubuntu', sans-serif;">
                <p id="paydunyaConfirmMessage"></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" style="font-weight: 300; font-family: 'Ubuntu', sans-serif;">Annuler</button>
                <button type="button" class="btn btn-primary" id="paydunyaConfirmButton" style="font-weight: 300; font-family: 'Ubuntu', sans-serif;">
                    <i class="bi bi-check-circle"></i> Confirmer le paiement
                </button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
@if($paydunyaEnabled)
<script>
let currentEngagementId = null;

function initierPaiementPayDunya(engagementId, nomEngagement, montant) {
    currentEngagementId = engagementId;
    
    // Mettre à jour le message du modal
    const message = 'Voulez-vous payer l\'engagement "<strong>' + nomEngagement + '</strong>" d\'un montant de <strong>' + new Intl.NumberFormat('fr-FR').format(montant) + ' XOF</strong> ?';
    document.getElementById('paydunyaConfirmMessage').innerHTML = message;
    
    // Afficher le modal
    const modal = new bootstrap.Modal(document.getElementById('paydunyaConfirmModal'));
    modal.show();
}

// Gérer le clic sur le bouton de confirmation
document.addEventListener('DOMContentLoaded', function() {
    const confirmButton = document.getElementById('paydunyaConfirmButton');
    if (confirmButton) {
        confirmButton.addEventListener('click', function() {
            if (currentEngagementId) {
                // Créer un formulaire pour soumettre la requête POST
                const form = document.createElement('form');
                form.method = 'POST';
                const routeName = window.pispiMode ? "membre.engagements.pispi" : "membre.engagements.paydunya";
                form.action = (window.pispiMode ? '{{ route("membre.engagements.pispi", ":id") }}' : '{{ route("membre.engagements.paydunya", ":id") }}').replace(':id', currentEngagementId);
                
                // Ajouter le token CSRF
                const csrfToken = document.createElement('input');
                csrfToken.type = 'hidden';
                csrfToken.name = '_token';
                csrfToken.value = '{{ csrf_token() }}';
                form.appendChild(csrfToken);
                
                // Soumettre le formulaire
                document.body.appendChild(form);
                form.submit();
            }
        });
    }
});

function initierPaiementPiSpi(engagementId, nomEngagement, montant) {
    currentEngagementId = engagementId;
    window.pispiMode = true;
    
    // Mettre à jour le message du modal
    const message = 'Voulez-vous payer l\'engagement "<strong>' + nomEngagement + '</strong>" d\'un montant de <strong>' + new Intl.NumberFormat('fr-FR').format(montant) + ' XOF</strong> via Pi-SPI ? Un message sera envoyé sur votre mobile.';
    document.getElementById('paydunyaConfirmMessage').innerHTML = message;
    document.getElementById('paydunyaConfirmModalLabel').innerHTML = '<i class="bi bi-bank"></i> Paiement Pi-SPI (BCEAO)';
    
    // Afficher le modal
    const modal = new bootstrap.Modal(document.getElementById('paydunyaConfirmModal'));
    modal.show();
}

// Afficher une notification toast selon le statut du paiement
@if(isset($paymentStatus))
    @if($paymentStatus === 'success')
        if (typeof showToast === 'function') {
            showToast('{{ $paymentMessage ?? "Paiement effectué avec succès !" }}', 'success');
        } else {
            console.error('showToast function not available');
        }
    @elseif($paymentStatus === 'cancelled')
        if (typeof showToast === 'function') {
            showToast('{{ $paymentMessage ?? "Paiement annulé. Vous pouvez réessayer à tout moment." }}', 'warning');
        }
    @elseif($paymentStatus === 'pending')
        if (typeof showToast === 'function') {
            showToast('{{ $paymentMessage ?? "Paiement en attente de confirmation." }}', 'info');
        }
    @elseif($paymentStatus === 'error')
        if (typeof showToast === 'function') {
            showToast('{{ $paymentMessage ?? "Erreur lors du paiement. Veuillez réessayer." }}', 'error');
        }
    @endif
@endif
</script>
@endif
@endpush
@endsection
