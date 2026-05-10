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
                        <th>Client</th>
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
                            <td>{{ $souscription->date_debut ? $souscription->date_debut->format('d/m/Y') : 'N/A' }}</td>
                            <td>{{ $souscription->date_fin ? $souscription->date_fin->format('d/m/Y') : 'N/A' }}</td>
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
                             <td class="text-end d-flex gap-1 justify-content-end align-items-center">
                                @php $nbEch = $souscription->echeances()->count(); $nbPayees = $souscription->echeances()->where('statut','payee')->count(); @endphp
                                <span class="badge bg-light text-muted border me-1" title="Échéances payées / total">{{ $nbPayees }}/{{ $nbEch }}</span>
                                @if($souscription->membre_id)
                                    <a href="{{ route('membres.show', $souscription->membre_id) }}" class="btn btn-sm btn-info text-white">
                                        <i class="bi bi-person"></i>
                                    </a>
                                @else
                                    <button class="btn btn-sm btn-secondary" disabled>
                                        <i class="bi bi-person-x"></i>
                                    </button>
                                @endif
                                <form method="POST" action="{{ route('admin.epargne.regenerer-echeances', $souscription) }}"
                                      onsubmit="return confirm('Régénérer les {{ $nbEch - $nbPayees }} échéances non payées ? Les échéances déjà payées seront conservées.')">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-warning" title="Régénérer les échéances non payées">
                                        <i class="bi bi-arrow-clockwise"></i>
                                    </button>
                                </form>
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
