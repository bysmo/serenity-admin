@extends('layouts.app')

@section('title', 'Saisie Collecte - ' . $membre->nom_complet)

@section('content')
<div class="page-header d-flex align-items-center">
    <a href="{{ route('collecte.index') }}" class="btn btn-outline-secondary btn-sm me-3"><i class="bi bi-arrow-left"></i></a>
    <h1>Saisie Collecte : <span class="text-primary">{{ $membre->nom_complet }}</span></h1>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white py-3">
                <h5 class="mb-0 fw-bold"><i class="bi bi-calendar-check me-2 text-primary"></i> Échéances de Tontine</h5>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th class="ps-4">Date</th>
                            <th>Plan</th>
                            <th>Montant</th>
                            <th>Statut</th>
                            <th class="text-end pe-4">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($tontineEcheances as $ech)
                            <tr>
                                <td class="ps-4">{{ $ech->date_echeance->format('d/m/Y') }}</td>
                                <td>{{ $ech->souscription->plan->nom ?? 'Tontine' }}</td>
                                <td class="fw-bold">{{ number_format($ech->montant, 0, ',', ' ') }} XOF</td>
                                <td>
                                    <span class="badge {{ $ech->temporal_status == 'en_retard' ? 'bg-danger' : ($ech->temporal_status == 'aujourd_hui' ? 'bg-primary' : 'bg-secondary') }}">
                                        {{ strtoupper(str_replace('_', ' ', $ech->temporal_status)) }}
                                    </span>
                                </td>
                                <td class="text-end pe-4">
                                    <form action="{{ route('collecte.store') }}" method="POST">
                                        @csrf
                                        <input type="hidden" name="membre_id" value="{{ $membre->id }}">
                                        <input type="hidden" name="type_collecte" value="tontine">
                                        <input type="hidden" name="echeance_id" value="{{ $ech->id }}">
                                        <input type="hidden" name="montant" value="{{ $ech->montant }}">
                                        <button type="submit" class="btn btn-success btn-sm px-3 rounded-pill">Encaisser</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center py-4 text-muted small">Aucune échéance de tontine à payer.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3">
                <h5 class="mb-0 fw-bold"><i class="bi bi-cash-stack me-2 text-warning"></i> Échéances de Nano-Crédit</h5>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th class="ps-4">Date</th>
                            <th>Crédit</th>
                            <th>Montant</th>
                            <th>Statut</th>
                            <th class="text-end pe-4">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($creditEcheances as $ech)
                            <tr>
                                <td class="ps-4">{{ $ech->date_echeance->format('d/m/Y') }}</td>
                                <td>{{ $ech->nanoCredit->palier->nom ?? 'Nano-Crédit' }}</td>
                                <td class="fw-bold">{{ number_format($ech->montant, 0, ',', ' ') }} XOF</td>
                                <td>
                                    <span class="badge {{ $ech->temporal_status == 'en_retard' ? 'bg-danger' : ($ech->temporal_status == 'aujourd_hui' ? 'bg-primary' : 'bg-secondary') }}">
                                        {{ strtoupper(str_replace('_', ' ', $ech->temporal_status)) }}
                                    </span>
                                </td>
                                <td class="text-end pe-4">
                                    <form action="{{ route('collecte.store') }}" method="POST">
                                        @csrf
                                        <input type="hidden" name="membre_id" value="{{ $membre->id }}">
                                        <input type="hidden" name="type_collecte" value="nano_credit">
                                        <input type="hidden" name="echeance_id" value="{{ $ech->id }}">
                                        <input type="hidden" name="montant" value="{{ $ech->montant }}">
                                        <button type="submit" class="btn btn-success btn-sm px-3 rounded-pill">Encaisser</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center py-4 text-muted small">Aucune échéance de crédit à payer.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card shadow-sm border-0 sticky-top" style="top: 20px;">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="bi bi-person-circle me-2"></i> Profil Membre</h5>
            </div>
            <div class="card-body text-center">
                <div class="avatar-xl bg-primary text-white rounded-circle mx-auto mb-3 d-flex align-items-center justify-content-center" style="width: 100px; height: 100px; font-size: 3rem;">
                    {{ strtoupper(substr($membre->prenom, 0, 1)) }}
                </div>
                <h4 class="fw-bold mb-1">{{ $membre->nom_complet }}</h4>
                <p class="text-muted mb-3">{{ $membre->numero }}</p>
                
                <div class="list-group list-group-flush text-start border-top">
                    <div class="list-group-item d-flex justify-content-between px-0">
                        <span class="text-muted">Téléphone</span>
                        <span class="fw-bold">{{ $membre->telephone }}</span>
                    </div>
                    <div class="list-group-item d-flex justify-content-between px-0">
                        <span class="text-muted">Segment</span>
                        <span class="badge bg-info">{{ $membre->segment_label }}</span>
                    </div>
                    <div class="list-group-item d-flex justify-content-between px-0">
                        <span class="text-muted">Statut</span>
                        <span class="badge {{ $membre->isActif() ? 'bg-success' : 'bg-danger' }}">
                            {{ strtoupper($membre->statut) }}
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
