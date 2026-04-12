@extends('layouts.app')

@section('title', 'Membres du Segment : ' . $segment->nom)

@section('content')
<div class="page-header d-flex justify-content-between align-items-center mb-3">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1 small" style="--bs-breadcrumb-divider: '>';">
                <li class="breadcrumb-item"><a href="{{ route('segments.index') }}" class="text-decoration-none">Segments</a></li>
                <li class="breadcrumb-item active" aria-current="page">{{ $segment->nom }}</li>
            </ol>
        </nav>
        <h1 class="h3 mb-0"><i class="{{ $segment->icone ?: 'bi bi-people' }} me-2" style="color: {{ $segment->couleur ?: 'var(--primary-blue)' }};"></i>Segment : {{ $segment->nom }}</h1>
    </div>
    <div class="btn-group">
        <a href="{{ route('segments.edit', $segment) }}" class="btn btn-outline-primary btn-sm rounded-start">
            <i class="bi bi-pencil me-1"></i> Modifier
        </a>
        <a href="{{ route('segments.index') }}" class="btn btn-secondary btn-sm rounded-end">
            <i class="bi bi-arrow-left me-1"></i> Retour
        </a>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body p-3">
                <div class="d-flex align-items-center mb-2">
                    <div class="rounded-circle bg-primary-subtle p-2 me-2">
                        <i class="bi bi-person-check text-primary"></i>
                    </div>
                    <h6 class="text-muted mb-0 small text-uppercase fw-bold">Total Membres</h6>
                </div>
                <h2 class="fw-bold mb-0 text-primary">{{ number_format($membres->total(), 0, ',', ' ') }}</h2>
                <p class="text-muted small mt-2 mb-0">Membres actuellement assignés.</p>
            </div>
        </div>
    </div>
    <div class="col-md-8">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body p-3">
                <div class="d-flex align-items-center mb-2">
                    <div class="rounded-circle bg-secondary-subtle p-2 me-2">
                        <i class="bi bi-info-circle text-secondary"></i>
                    </div>
                    <h6 class="text-muted mb-0 small text-uppercase fw-bold">Description du Segment</h6>
                </div>
                <p class="mb-0 small">{{ $segment->description ?: 'Aucune description disponible pour ce segment.' }}</p>
                @if($segment->is_default)
                    <div class="mt-2">
                        <span class="badge bg-secondary-subtle text-secondary border border-secondary" style="font-size: 0.6rem;">SEGMENT PAR DÉFAUT</span>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white py-3">
        <h5 class="card-title mb-0" style="font-size: 1rem; color: var(--primary-dark-blue); font-family: 'Ubuntu', sans-serif;">Liste des membres du segment</h5>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light text-uppercase" style="font-size: 0.65rem; color: #666;">
                    <tr>
                        <th class="ps-4">Membre</th>
                        <th>Email / Téléphone</th>
                        <th>Date d'adhésion</th>
                        <th class="text-center">Statut</th>
                        <th class="text-center pe-4">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($membres as $membre)
                        <tr>
                            <td class="ps-4">
                                <div class="d-flex align-items-center">
                                    <div class="avatar-sm me-3 border rounded-circle d-flex align-items-center justify-content-center bg-light" style="width: 32px; height: 32px;">
                                        <i class="bi bi-person text-secondary"></i>
                                    </div>
                                    <div>
                                        <div class="fw-bold" style="font-size: 0.85rem; color: var(--primary-dark-blue);">{{ $membre->nom }} {{ $membre->prenom }}</div>
                                        <small class="text-muted" style="font-size: 0.7rem;">{{ $membre->numero }}</small>
                                    </div>
                                </div>
                            </td>
                            <td style="font-size: 0.8rem;">
                                <div>{{ $membre->email }}</div>
                                <div class="text-muted small">{{ $membre->telephone ?: 'N/A' }}</div>
                            </td>
                            <td style="font-size: 0.8rem;">
                                {{ $membre->date_adhesion ? $membre->date_adhesion->format('d/m/Y') : 'N/A' }}
                            </td>
                            <td class="text-center">
                                @if($membre->statut === 'actif')
                                    <span class="badge bg-success-subtle text-success border border-success" style="font-size: 0.65rem;">ACTIF</span>
                                @elseif($membre->statut === 'suspendu')
                                    <span class="badge bg-danger-subtle text-danger border border-danger" style="font-size: 0.65rem;">SUSPENDU</span>
                                @else
                                    <span class="badge bg-warning-subtle text-warning border border-warning" style="font-size: 0.65rem;">{{ strtoupper($membre->statut) }}</span>
                                @endif
                            </td>
                            <td class="text-center pe-4">
                                <a href="{{ route('membres.show', $membre) }}" class="btn btn-light btn-sm border" title="Voir détails">
                                    <i class="bi bi-eye"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center py-5">
                                <i class="bi bi-people fs-2 text-muted d-block mb-2"></i>
                                <p class="text-muted small">Aucun membre n'appartient à ce segment.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($membres->hasPages())
        <div class="card-footer bg-white py-3">
            {{ $membres->links() }}
        </div>
    @endif
</div>
@endsection
@endsection
