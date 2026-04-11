@extends('layouts.app')

@section('title', 'Impayés Tontines')

@section('content')
<div class="page-header d-flex justify-content-between align-items-center mb-3">
    <h1><i class="bi bi-exclamation-triangle-fill text-danger me-2"></i>Impayés des Tontines</h1>
    <a href="{{ route('admin.tontines.dashboard') }}" class="btn btn-outline-primary btn-sm">
        <i class="bi bi-arrow-left"></i> Retour au tableau de bord
    </a>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="bg-light">
                    <tr>
                        <th>Membre</th>
                        <th>Plan</th>
                        <th>Date Échéance</th>
                        <th>Montant</th>
                        <th>Statut</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($impayes as $echeance)
                        <tr>
                            <td>
                                <div class="fw-bold">{{ $echeance->souscription->membre->nom ?? 'N/A' }}</div>
                                <small class="text-muted">{{ $echeance->souscription->membre->id_national ?? '' }}</small>
                            </td>
                            <td>{{ $echeance->souscription->plan->nom ?? 'N/A' }}</td>
                            <td>{{ \Carbon\Carbon::parse($echeance->date_echeance)->format('d/m/Y') }}</td>
                            <td class="fw-bold">{{ number_format($echeance->montant, 0, ',', ' ') }} FCFA</td>
                            <td>
                                <span class="badge bg-danger">En retard</span>
                            </td>
                            <td class="text-end">
                                <a href="{{ route('membres.show', $echeance->souscription->membre_id) }}" class="btn btn-sm btn-info text-white">
                                    <i class="bi bi-person"></i> Profil
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-4 text-muted">Auncon impayé trouvé.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($impayes->hasPages())
        <div class="card-footer bg-white border-top">
            {{ $impayes->links() }}
        </div>
    @endif
</div>
@endsection
