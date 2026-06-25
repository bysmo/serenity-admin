@extends('layouts.membre')

@section('title', 'Mes filleuls')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex align-items-center gap-3 mb-4">
        <a href="{{ route('membre.parrainage.index') }}" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i>Retour
        </a>
        <div>
            <h2 class="mb-1 fw-bold" style="color: var(--primary-dark-blue);">
                <i class="bi bi-person-check me-2 text-success"></i>Mes filleuls
            </h2>
            <p class="text-muted mb-0">Membres recrutés grâce à votre code de parrainage</p>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-bottom py-3 d-flex align-items-center justify-content-between">
            <h6 class="mb-0 fw-semibold">
                Total : <span class="badge bg-primary">{{ $filleuls->total() }}</span> filleul(s)
            </h6>
            <div class="small text-muted">
                Code parrainage : <span class="badge bg-light text-dark font-monospace">{{ $membre->code_parrainage }}</span>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0 align-middle">
                    <thead style="background:#f8f9fa">
                        <tr>
                            <th class="px-3 py-3 fw-semibold small">Membre</th>
                            <th class="fw-semibold small">Contact</th>
                            <th class="fw-semibold small text-center">Statut</th>
                            <th class="fw-semibold small text-center">Commission</th>
                            <th class="fw-semibold small">Inscription</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($filleuls as $filleul)
                            <tr>
                                <td class="px-3">
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="rounded-circle bg-success text-white d-flex align-items-center justify-content-center flex-shrink-0"
                                             style="width:36px;height:36px;font-size:0.75rem">
                                            {{ strtoupper(substr($filleul->prenom, 0, 1)) }}{{ strtoupper(substr($filleul->nom, 0, 1)) }}
                                        </div>
                                        <div>
                                            <div class="small fw-semibold">{{ $filleul->nom_complet }}</div>
                                            <div class="text-muted" style="font-size:0.75rem">{{ $filleul->numero }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="small text-muted">
                                    {{ $filleul->email }}<br>
                                    <span style="font-size:0.75rem">{{ $filleul->telephone }}</span>
                                </td>
                                <td class="text-center">
                                    <span class="badge {{ $filleul->statut === 'actif' ? 'bg-success' : 'bg-warning text-dark' }}">
                                        {{ ucfirst($filleul->statut) }}
                                    </span>
                                </td>
                                <td class="text-center">
                                    @if($filleul->commissionFilleul)
                                        <span class="badge bg-{{ $filleul->commissionFilleul->badge_statut }}">
                                            {{ $filleul->commissionFilleul->label_statut }}
                                        </span>
                                        <div class="small fw-bold mt-1">
                                            {{ number_format($filleul->commissionFilleul->montant, 0, ',', ' ') }} FCFA
                                        </div>
                                    @else
                                        <span class="badge bg-light text-muted">—</span>
                                    @endif
                                </td>
                                <td class="small text-muted">{{ $filleul->created_at->format('d/m/Y') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-5 text-muted">
                                    <i class="bi bi-people fs-2 d-block mb-2"></i>
                                    Vous n'avez pas encore de filleuls.
                                    <div class="mt-2">
                                        <a href="{{ route('membre.parrainage.index') }}" class="btn btn-sm btn-primary">
                                            <i class="bi bi-share me-1"></i>Partager mon code
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($filleuls->hasPages())
                <div class="px-3 py-2">{{ $filleuls->links() }}</div>
            @endif
        </div>
    </div>
</div>
@endsection
