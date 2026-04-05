@extends('layouts.membre')

@section('title', 'Mes Engagements de Garantie')

@section('content')
<style>
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

<div class="page-header mb-4 d-flex justify-content-between align-items-center">
    <div>
        <h1 style="font-weight: 300; font-family: 'Ubuntu', sans-serif;">
            <i class="bi bi-journal-check"></i> Historique de mes garanties
        </h1>
        <p class="text-muted" style="font-weight: 300; font-family: 'Ubuntu', sans-serif;">Suivi de tous les crédits pour lesquels vous avez été garant.</p>
    </div>
    <a href="{{ route('membre.garant.index') }}" class="btn btn-outline-primary btn-sm">
        <i class="bi bi-arrow-left"></i> Retour
    </a>
</div>

<div class="card card-stats">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-compact mb-0">
                <thead>
                    <tr>
                        <th>Réf. Crédit</th>
                        <th>Emprunteur</th>
                        <th>Montant</th>
                        <th>Type</th>
                        <th>Date d'acceptation</th>
                        <th>Date de libération</th>
                        <th>Statut Garant</th>
                        <th>Gain Partagé</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($engagements as $engagement)
                        @php
                            $nanoCredit = $engagement->nanoCredit;
                            $emprunteur = $nanoCredit->membre;
                            $type = $nanoCredit->nanoCreditType;
                        @endphp
                        <tr>
                            <td><code>#{{ $nanoCredit->id }}</code></td>
                            <td>{{ $emprunteur->nom_complet }}</td>
                            <td>{{ number_format($nanoCredit->montant, 0, ',', ' ') }} XOF</td>
                            <td>{{ $type->nom }}</td>
                            <td>{{ $engagement->accepte_le ? $engagement->accepte_le->format('d/m/Y') : '-' }}</td>
                            <td>{{ $engagement->libere_le ? $engagement->libere_le->format('d/m/Y') : '-' }}</td>
                            <td>
                                @if($engagement->statut === 'en_attente')
                                    <span class="badge bg-warning text-dark">En attente</span>
                                @elseif($engagement->statut === 'accepte')
                                    <span class="badge bg-success">Actif</span>
                                @elseif($engagement->statut === 'refuse')
                                    <span class="badge bg-danger">Refusé</span>
                                @elseif($engagement->statut === 'preleve')
                                    <span class="badge bg-secondary">Prélevé</span>
                                @elseif($engagement->statut === 'libere')
                                    <span class="badge bg-info text-white">Libéré</span>
                                @else
                                    <span class="badge bg-light text-dark">{{ $engagement->statut_label }}</span>
                                @endif
                            </td>
                            <td>
                                @if($engagement->gain_partage > 0)
                                    <span class="text-success" style="font-weight: 400;">+{{ number_format($engagement->gain_partage, 0, ',', ' ') }} XOF</span>
                                @else
                                    -
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-5">
                                <i class="bi bi-journal-x" style="font-size: 2rem; color: #ccc;"></i>
                                <p class="text-muted mt-2 small">Aucun engagement historique trouvé.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="mt-4">
    {{ $engagements->links() }}
</div>

@endsection
