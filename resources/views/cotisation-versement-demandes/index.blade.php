@extends('layouts.app')

@section('title', 'Demandes de versement des fonds')

@section('content')
<div class="page-header">
    <h1><i class="bi bi-cash-stack"></i> Demandes de versement des fonds</h1>
</div>

<p class="text-muted small mb-3">Demandes envoyées par les administrateurs de cotisations (membres créateurs) pour verser le solde de la caisse à l'administration.</p>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <span><i class="bi bi-list-ul"></i> Liste des demandes</span>
        <form method="GET" class="d-flex gap-2 align-items-center">
            <select name="statut" class="form-select form-select-sm" style="width: auto;" onchange="this.form.submit()">
                <option value="">Tous les statuts</option>
                <option value="en_attente" {{ request('statut') === 'en_attente' ? 'selected' : '' }}>En attente</option>
                <option value="traite" {{ request('statut') === 'traite' ? 'selected' : '' }}>Traité</option>
                <option value="rejete" {{ request('statut') === 'rejete' ? 'selected' : '' }}>Rejeté</option>
            </select>
        </form>
    </div>
    <div class="card-body">
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show">{{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if($demandes->count() > 0)
            <div class="table-responsive">
                <table class="table table-sm table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Cotisation</th>
                            <th>Demandé par</th>
                            <th>Montant</th>
                            <th>Date demande</th>
                            <th class="text-end pe-3">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($demandes as $demande)
                            <tr>
                                <td>{{ $demande->cotisation->nom ?? '-' }}<br><small class="text-muted">{{ $demande->cotisation->code ?? '' }}</small></td>
                                <td>{{ $demande->demandeParMembre->nom_complet ?? '-' }}<br><small class="text-muted">{{ $demande->demandeParMembre->email ?? '' }}</small></td>
                                <td class="fw-bold">{{ number_format($demande->montant_demande ?? 0, 0, ',', ' ') }} XOF</td>
                                <td>{{ $demande->created_at->format('d/m/Y H:i') }}</td>
                                <td>
                                    @if($demande->statut === 'en_attente')
                                        <span class="badge bg-warning text-dark px-2 py-1">En attente</span>
                                    @elseif($demande->statut === 'traite')
                                        <span class="badge bg-success px-2 py-1">Traité</span>
                                    @else
                                        <span class="badge bg-secondary px-2 py-1">Rejeté</span>
                                    @endif
                                </td>
                                <td class="text-end pe-3">
                                    <a href="{{ route('cotisation-versement-demandes.show', $demande) }}" class="btn btn-sm btn-outline-primary" title="Voir les détails">
                                        <i class="bi bi-eye"></i> Détails
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            {{ $demandes->withQueryString()->links() }}
        @else
            <div class="text-center py-4">
                <i class="bi bi-inbox text-muted" style="font-size: 2rem;"></i>
                <p class="text-muted mt-2 mb-0">Aucune demande de versement.</p>
            </div>
        @endif
    </div>
</div>
@endsection
