@extends('layouts.app')

@section('title', 'Moyens de Paiements')

@section('content')
<div class="page-header">
    <h1><i class="bi bi-credit-card"></i> Moyens de Paiements</h1>
</div>


<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-list-ul"></i> Liste des Moyens de Paiement</span>
                <form action="{{ route('payment-methods.initialize') }}" method="POST" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-primary btn-sm" title="Initialiser les moyens de paiement">
                        <i class="bi bi-plus-circle"></i> Initialiser
                    </button>
                </form>
            </div>
            <div class="card-body">
                @if($paymentMethods->count() === 0)
                    <div class="alert alert-info" role="alert">
                        <i class="bi bi-info-circle"></i> Aucun moyen de paiement n'est configuré. Cliquez sur "Initialiser" pour créer les moyens de paiement de base.
                    </div>
                @endif
                
                @if($paymentMethods->count() > 0)
                    <style>
                        .table-payment-methods thead th {
                            padding: 0.15rem 0.35rem !important;
                            font-size: 0.6rem !important;
                            line-height: 1.05 !important;
                            vertical-align: middle !important;
                            font-weight: 300 !important;
                            font-family: 'Ubuntu', sans-serif !important;
                            color: var(--primary-dark-blue) !important;
                        }
                        .table-payment-methods tbody td {
                            padding: 0.15rem 0.35rem !important;
                            font-size: 0.65rem !important;
                            line-height: 1.05 !important;
                            vertical-align: middle !important;
                            border-bottom: 1px solid #f0f0f0 !important;
                            font-weight: 300 !important;
                            font-family: 'Ubuntu', sans-serif !important;
                            color: var(--primary-dark-blue) !important;
                        }
                        .table-payment-methods .btn {
                            padding: 0 !important;
                            font-size: 0.5rem !important;
                            line-height: 1 !important;
                            height: 18px !important;
                            width: 22px !important;
                            display: inline-flex !important;
                            align-items: center !important;
                            justify-content: center !important;
                        }
                        .table-payment-methods .btn i {
                            font-size: 0.6rem !important;
                            line-height: 1 !important;
                        }
                        .table-payment-methods .btn-group-sm > .btn,
                        .table-payment-methods .btn-group > .btn {
                            border-radius: 0.2rem !important;
                        }
                        .table-payment-methods tbody tr:last-child td {
                            border-bottom: none !important;
                        }
                        table.table.table-payment-methods.table-hover tbody tr {
                            background-color: #ffffff !important;
                            transition: background-color 0.2s ease !important;
                        }
                        table.table.table-payment-methods.table-hover tbody tr:nth-child(even) {
                            background-color: #d4dde8 !important;
                        }
                        table.table.table-payment-methods.table-hover tbody tr:hover {
                            background-color: #b8c7d9 !important;
                            cursor: pointer !important;
                        }
                        table.table.table-payment-methods.table-hover tbody tr:nth-child(even):hover {
                            background-color: #9fb3cc !important;
                        }
                    </style>
                    <div class="table-responsive">
                        <table class="table table-payment-methods table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>Moyen de Paiement</th>
                                    <th>Description</th>
                                    <th class="text-center">Statut</th>
                                    <th class="text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($paymentMethods as $method)
                                    <tr>
                                        <td>
                                            @if($method->icon)
                                                <i class="{{ $method->icon }}"></i>
                                            @endif
                                            <strong>{{ $method->name }}</strong>
                                        </td>
                                        <td>
                                            <span class="text-muted" style="font-size: 0.65rem;">
                                                {{ $method->description ?? 'Aucune description' }}
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            @if($method->enabled)
                                                <span class="badge bg-success" style="font-size: 0.6rem; font-weight: 300;">Actif</span>
                                            @else
                                                <span class="badge bg-secondary" style="font-size: 0.6rem; font-weight: 300;">Inactif</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <div class="btn-group btn-group-sm" role="group">
                                                @if($method->code === 'paydunya')
                                                    <a href="{{ route('paydunya.index') }}" class="btn btn-sm btn-outline-primary" title="Configurer">
                                                        <i class="bi bi-gear"></i>
                                                    </a>
                                                @elseif($method->code === 'paypal')
                                                    <a href="{{ route('paypal.index') }}" class="btn btn-sm btn-outline-primary" title="Configurer">
                                                        <i class="bi bi-gear"></i>
                                                    </a>
                                                @elseif($method->code === 'stripe')
                                                    <a href="{{ route('stripe.index') }}" class="btn btn-sm btn-outline-primary" title="Configurer">
                                                        <i class="bi bi-gear"></i>
                                                    </a>
                                                @elseif($method->code === 'pispi')
                                                    <a href="{{ route('pispi.index') }}" class="btn btn-sm btn-outline-primary" title="Configurer">
                                                        <i class="bi bi-gear"></i>
                                                    </a>
                                                @endif
                                                <form action="{{ route('payment-methods.toggle', $method) }}" 
                                                      method="POST" 
                                                      class="d-inline">
                                                    @csrf
                                                    @method('PATCH')
                                                    <button type="submit" 
                                                            class="btn btn-sm {{ $method->enabled ? 'btn-outline-danger' : 'btn-outline-success' }}" 
                                                            title="{{ $method->enabled ? 'Désactiver' : 'Activer' }}">
                                                        <i class="bi bi-{{ $method->enabled ? 'toggle-on' : 'toggle-off' }}"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-center py-3">
                        <i class="bi bi-inbox" style="font-size: 1.5rem; color: #ccc;"></i>
                        <p class="text-muted mt-2 mb-2" style="font-size: 0.75rem;">Aucun moyen de paiement configuré</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Section À propos -->
@endsection
