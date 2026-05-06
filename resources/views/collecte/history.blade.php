@extends('layouts.app')

@section('title', 'Historique des Sessions de Collecte')

@section('content')
<div class="page-header d-flex align-items-center">
    <a href="{{ route('collecte.index') }}" class="btn btn-outline-secondary btn-sm me-3"><i class="bi bi-arrow-left"></i></a>
    <h1>Historique des Sessions</h1>
</div>

<div class="card shadow-sm border-0">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="bg-light">
                <tr>
                    <th class="ps-4">Date</th>
                    <th>Ouverture</th>
                    <th>Fermeture</th>
                    <th>Collectes</th>
                    <th>Montant Total</th>
                    <th>Statut</th>
                </tr>
            </thead>
            <tbody>
                @forelse($sessions as $sess)
                    <tr>
                        <td class="ps-4 fw-bold">{{ $sess->date_session->format('d/m/Y') }}</td>
                        <td>{{ $sess->opened_at->format('H:i') }}</td>
                        <td>{{ $sess->closed_at ? $sess->closed_at->format('H:i') : '-' }}</td>
                        <td><span class="badge bg-light text-dark border">{{ $sess->collectes_count }} collectes</span></td>
                        <td class="fw-bold text-primary">{{ number_format($sess->montant_total_collecte, 0, ',', ' ') }} XOF</td>
                        <td>
                            <span class="badge {{ $sess->statut == 'ouvert' ? 'bg-success' : 'bg-secondary' }}">
                                {{ strtoupper($sess->statut) }}
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center py-5 text-muted">Aucune session enregistrée.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer bg-white py-3">
        {{ $sessions->links() }}
    </div>
</div>
@endsection
