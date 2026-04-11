@extends('layouts.app')

@section('title', 'Souscriptions Tontines')

@section('content')
<div class="page-header d-flex justify-content-between align-items-center mb-3">
    <h1><i class="bi bi-list-ul me-2"></i>Liste des Souscriptions</h1>
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
                        <th>Montant</th>
                        <th>Début</th>
                        <th>Fin</th>
                        <th>Statut</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($souscriptions as $souscription)
                        <tr>
                            <td>
                                <div class="fw-bold">{{ $souscription->membre->nom ?? 'N/A' }}</div>
                                <small class="text-muted">{{ $souscription->membre->id_national ?? '' }}</small>
                            </td>
                            <td>{{ $souscription->plan->nom ?? 'N/A' }}</td>
                            <td class="fw-bold">{{ number_format($souscription->montant, 0, ',', ' ') }} FCFA</td>
                            <td>{{ $souscription->date_debut->format('d/m/Y') }}</td>
                            <td>{{ $souscription->date_fin->format('d/m/Y') }}</td>
                            <td>
                                @php
                                    $badgeClass = match($souscription->statut) {
                                        'active' => 'bg-success',
                                        'terminee', 'cloturee' => 'bg-primary',
                                        'en_attente' => 'bg-warning text-dark',
                                        default => 'bg-secondary'
                                    };
                                @endphp
                                <span class="badge {{ $badgeClass }}">{{ ucfirst($souscription->statut) }}</span>
                            </td>
                            <td class="text-end">
                                <a href="{{ route('membres.show', $souscription->membre_id) }}" class="btn btn-sm btn-info text-white">
                                    <i class="bi bi-person"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-4 text-muted">Aucune souscription trouvée.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($souscriptions->hasPages())
        <div class="card-footer bg-white border-top">
            {{ $souscriptions->links() }}
        </div>
    @endif
</div>
@endsection
