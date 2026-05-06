@extends('layouts.membre')

@section('title', 'Mes comptes')

@section('content')
<div class="page-header">
    <h1 style="font-weight: 300; font-family: 'Ubuntu', sans-serif;"><i class="bi bi-wallet2"></i> Mes Comptes</h1>
</div>

<div class="row mb-4">
    <div class="col-md-6">
        <div class="card" style="background: linear-gradient(135deg, var(--primary-dark-blue), var(--primary-blue)); color: white; border: none;">
            <div class="card-body py-4">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-1 fw-light" style="opacity: 0.8; font-size: 0.9rem;">Solde Global Combiné</h6>
                        <h3 class="mb-0 fw-bold" style="font-family: 'Ubuntu', sans-serif;">{{ number_format($soldeGlobal, 0, ',', ' ') }} <small style="font-size: 0.6em; opacity: 0.8;">XOF</small></h3>
                    </div>
                    <div style="background: rgba(255,255,255,0.15); width: 60px; height: 60px; border-radius: 12px; display: flex; align-items: center; justify-content: center;">
                        <i class="bi bi-bank2" style="font-size: 2rem;"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card" style="background: linear-gradient(135deg, #0dcaf0, #0a9cb9); color: white; border: none;">
            <div class="card-body py-4">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-1 fw-light" style="opacity: 0.8; font-size: 0.9rem;">Nombre Total de Comptes</h6>
                        <h3 class="mb-0 fw-bold" style="font-family: 'Ubuntu', sans-serif;">{{ $nbComptes }}</h3>
                    </div>
                    <div style="background: rgba(255,255,255,0.15); width: 60px; height: 60px; border-radius: 12px; display: flex; align-items: center; justify-content: center;">
                        <i class="bi bi-credit-card-2-back" style="font-size: 2rem;"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card shadow-sm border-0">
    <div class="card-header bg-white py-3" style="border-bottom: 1px solid #f0f0f0;">
        <h6 class="mb-0 fw-bold" style="color: var(--primary-dark-blue); font-family: 'Ubuntu', sans-serif;">
            <i class="bi bi-list-stars me-2"></i>Liste de mes comptes et soldes
        </h6>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" style="font-size: 0.85rem;">
                <thead>
                    <tr style="background-color: var(--primary-dark-blue); color: white;">
                        <th class="ps-4 py-3 border-0 text-white text-uppercase" style="font-size: 0.75rem; letter-spacing: 0.5px; font-weight: 500;">N° de Compte</th>
                        <th class="py-3 border-0 text-white text-uppercase" style="font-size: 0.75rem; letter-spacing: 0.5px; font-weight: 500;">N° Bancaire</th>
                        <th class="py-3 border-0 text-white text-uppercase" style="font-size: 0.75rem; letter-spacing: 0.5px; font-weight: 500;">Libellé / Nom</th>
                        <th class="py-3 border-0 text-white text-uppercase text-center" style="font-size: 0.75rem; letter-spacing: 0.5px; font-weight: 500;">Type</th>
                        <th class="py-3 border-0 text-white text-uppercase text-end pe-4" style="font-size: 0.75rem; letter-spacing: 0.5px; font-weight: 500;">Solde</th>
                        <th class="py-3 border-0 text-white text-uppercase text-center" style="font-size: 0.75rem; letter-spacing: 0.5px; font-weight: 500;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($comptes as $compte)
                    <tr>
                        <td class="ps-4 py-3">
                            <span class="badge bg-light text-dark border fw-normal px-2 py-1" style="font-family: monospace; font-size: 0.85rem;">{{ $compte->numero }}</span>
                        </td>
                        <td class="py-3">
                            @if($compte->numero_core_banking)
                                <span class="text-primary fw-bold" style="font-family: monospace;">{{ $compte->numero_core_banking }}</span>
                            @else
                                <span class="text-muted italic small">Non défini</span>
                            @endif
                        </td>
                        <td class="py-3">
                            <div class="fw-bold" style="color: var(--primary-dark-blue); font-size: 0.9rem;">{{ $compte->nom }}</div>
                            <small class="text-muted" style="font-size: 0.7rem;">Créé le {{ $compte->created_at->format('d/m/Y') }}</small>
                        </td>
                        <td class="py-3 text-center">
                            @switch($compte->type)
                                @case('courant')
                                    <span class="badge rounded-pill bg-primary px-3 py-1" style="font-weight: 400; font-size: 0.65rem;">COURANT</span>
                                    @break
                                @case('epargne')
                                    <span class="badge rounded-pill bg-success px-3 py-1" style="font-weight: 400; font-size: 0.65rem;">ÉPARGNE</span>
                                    @break
                                @case('tontine')
                                    <span class="badge rounded-pill bg-info px-3 py-1" style="font-weight: 400; font-size: 0.65rem;">TONTINE</span>
                                    @break
                                @case('nano_credit')
                                @case('credit')
                                    <span class="badge rounded-pill bg-warning text-dark px-3 py-1" style="font-weight: 400; font-size: 0.65rem;">CRÉDIT</span>
                                    @break
                                @case('garant')
                                    <span class="badge rounded-pill bg-purple px-3 py-1 text-white" style="font-weight: 400; font-size: 0.65rem; background-color: #6f42c1;">GARANT</span>
                                    @break
                                @case('impayes')
                                    <span class="badge rounded-pill bg-danger px-3 py-1" style="font-weight: 400; font-size: 0.65rem;">IMPAYÉS</span>
                                    @break
                                @default
                                    <span class="badge rounded-pill bg-secondary px-3 py-1" style="font-weight: 400; font-size: 0.65rem;">{{ strtoupper($compte->type) }}</span>
                            @endswitch
                        </td>
                        <td class="py-3 text-end pe-4 fw-bold" style="font-size: 1.05rem; color: var(--primary-dark-blue);">
                            {{ number_format($compte->solde_actuel, 0, ',', ' ') }} <small style="font-size: 0.6em; font-weight: 400;">XOF</small>
                        </td>
                        <td class="py-3 text-center">
                            <a href="{{ route('membre.comptes.show', $compte->id) }}" class="btn btn-sm btn-primary shadow-sm px-3" style="font-size: 0.7rem; border-radius: 20px;">
                                <i class="bi bi-eye-fill me-1"></i>DÉTAILS
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="py-5 text-center text-muted">
                            <i class="bi bi-inbox mb-2" style="font-size: 2.5rem; opacity: 0.2; display: block;"></i>
                            Aucun compte trouvé.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
    .bg-primary-subtle { background-color: rgba(13, 110, 253, 0.1) !important; }
    .bg-success-subtle { background-color: rgba(25, 135, 84, 0.1) !important; }
    .bg-info-subtle { background-color: rgba(13, 202, 240, 0.1) !important; }
    .bg-warning-subtle { background-color: rgba(255, 193, 7, 0.1) !important; }
    .bg-secondary-subtle { background-color: rgba(108, 117, 125, 0.1) !important; }
</style>
@endsection
