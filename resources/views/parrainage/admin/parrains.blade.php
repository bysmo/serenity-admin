@extends('layouts.app')

@section('title', 'Parrains actifs')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h2 class="mb-1 fw-bold" style="color: var(--primary-dark-blue);">
                <i class="bi bi-person-lines-fill me-2 text-primary"></i>Parrains actifs
            </h2>
            <p class="text-muted mb-0">Liste des clients avec des filleuls recrutés</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('parrainage.admin.config') }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-gear me-1"></i>Configuration
            </a>
            <a href="{{ route('parrainage.admin.commissions') }}" class="btn btn-outline-primary btn-sm">
                <i class="bi bi-cash-coin me-1"></i>Commissions
            </a>
        </div>
    </div>

    <!-- Filtre -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body py-2">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-5">
                    <input type="text" name="search" class="form-control form-control-sm"
                           placeholder="Nom, prénom, numéro, code parrainage..." value="{{ request('search') }}">
                </div>
                <div class="col-auto d-flex gap-1">
                    <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-search me-1"></i>Rechercher</button>
                    <a href="{{ route('parrainage.admin.parrains') }}" class="btn btn-outline-secondary btn-sm">Réinitialiser</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0 align-middle">
                    <thead style="background:#f8f9fa">
                        <tr>
                            <th class="px-3 py-3 fw-semibold small">Membre / Code</th>
                            <th class="fw-semibold small text-center">Filleuls</th>
                            <th class="fw-semibold small text-end">Disponible</th>
                            <th class="fw-semibold small text-end">Réclamé</th>
                            <th class="fw-semibold small text-end">Total payé</th>
                            <th class="fw-semibold small text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($parrains as $parrain)
                            <tr>
                                <td class="px-3">
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center flex-shrink-0"
                                             style="width:36px;height:36px;font-size:0.8rem">
                                            {{ strtoupper(substr($parrain->prenom, 0, 1)) }}{{ strtoupper(substr($parrain->nom, 0, 1)) }}
                                        </div>
                                        <div>
                                            <div class="fw-semibold small">{{ $parrain->nom_complet }}</div>
                                            <div class="text-muted" style="font-size:0.75rem">
                                                {{ $parrain->numero }} &nbsp;|&nbsp;
                                                Code : <span class="badge bg-light text-dark font-monospace">{{ $parrain->code_parrainage ?? 'N/A' }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-primary rounded-pill fs-6">{{ $parrain->filleuls_count }}</span>
                                </td>
                                <td class="text-end small fw-semibold text-success">
                                    {{ number_format($parrain->total_disponible ?? 0, 0, ',', ' ') }} FCFA
                                </td>
                                <td class="text-end small fw-semibold text-info">
                                    {{ number_format($parrain->total_reclame ?? 0, 0, ',', ' ') }} FCFA
                                </td>
                                <td class="text-end small fw-bold text-primary">
                                    {{ number_format($parrain->total_paye ?? 0, 0, ',', ' ') }} FCFA
                                </td>
                                <td class="text-center">
                                    <a href="{{ route('parrainage.admin.commissions', ['search' => $parrain->numero]) }}"
                                       class="btn btn-sm btn-outline-primary" title="Voir commissions">
                                        <i class="bi bi-cash-coin"></i>
                                    </a>
                                    <a href="{{ route('membres.show', $parrain) }}"
                                       class="btn btn-sm btn-outline-secondary" title="Fiche membre">
                                        <i class="bi bi-person"></i>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-5 text-muted">
                                    <i class="bi bi-people fs-2 d-block mb-2"></i>
                                    Aucun parrain trouvé
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($parrains->hasPages())
                <div class="px-3 py-2">{{ $parrains->links() }}</div>
            @endif
        </div>
    </div>
</div>
@endsection
