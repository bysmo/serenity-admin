@extends('layouts.app')

@section('title', 'Chaîne Merkle — Audit d\'Intégrité')

@section('content')
<div class="page-header d-flex justify-content-between align-items-center mb-3">
    <div>
        <h1><i class="bi bi-link-45deg text-primary"></i> Chaîne d'Intégrité Merkle</h1>
        <p class="text-muted mb-0" style="font-size: 0.75rem;">
            Chaque enregistrement ci-dessous est un maillon de la chaîne cryptographique globale.
            Un écart de hash révèle une manipulation SQL directe sur le Ledger.
        </p>
    </div>
    <div class="text-end">
        @if($chainOk)
            <span class="badge bg-success p-2 fs-6"><i class="bi bi-shield-check me-1"></i> Chaîne intègre</span>
        @else
            <span class="badge bg-danger p-2 fs-6"><i class="bi bi-shield-x me-1"></i> RUPTURE au maillon #{{ $chainBrokenAt }}</span>
        @endif
    </div>
</div>

{{-- Stats globales --}}
<div class="row g-2 mb-3">
    <div class="col-6 col-md-3">
        <div class="card text-center border-0 shadow-sm">
            <div class="card-body py-2">
                <div class="fw-bold text-primary" style="font-size: 1.2rem;">{{ number_format($stats['total']) }}</div>
                <div class="text-muted" style="font-size: 0.7rem;"><i class="bi bi-link"></i> Total d'évènements</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card text-center border-0 shadow-sm">
            <div class="card-body py-2">
                <div class="fw-bold text-success" style="font-size: 1.2rem;">{{ number_format($stats['created']) }}</div>
                <div class="text-muted" style="font-size: 0.7rem;"><i class="bi bi-plus-circle"></i> Créations</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card text-center border-0 shadow-sm">
            <div class="card-body py-2">
                <div class="fw-bold text-warning" style="font-size: 1.2rem;">{{ number_format($stats['updated']) }}</div>
                <div class="text-muted" style="font-size: 0.7rem;"><i class="bi bi-pencil-square"></i> Modifications</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card text-center border-0 shadow-sm">
            <div class="card-body py-2">
                <div class="fw-bold text-danger" style="font-size: 1.2rem;">{{ number_format($stats['deleted']) }}</div>
                <div class="text-muted" style="font-size: 0.7rem;"><i class="bi bi-trash"></i> Suppressions</div>
            </div>
        </div>
    </div>
</div>

{{-- Filtres --}}
<div class="card shadow-sm mb-3">
    <div class="card-body py-2">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-4">
                <label class="form-label">Table</label>
                <select name="table" class="form-select form-select-sm">
                    <option value="">Toutes les tables</option>
                    @foreach($availableTables as $t)
                        <option value="{{ $t }}" {{ $filterTable == $t ? 'selected' : '' }}>{{ $t }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Action</label>
                <select name="action" class="form-select form-select-sm">
                    <option value="">Toutes</option>
                    @foreach($availableActions as $a)
                        <option value="{{ $a }}" {{ $filterAction == $a ? 'selected' : '' }}>{{ ucfirst($a) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary btn-sm w-100"><i class="bi bi-funnel"></i> Filtrer</button>
            </div>
            <div class="col-md-2">
                <a href="{{ route('audit.integrity.ledger') }}" class="btn btn-outline-secondary btn-sm w-100"><i class="bi bi-x"></i> Réinitialiser</a>
            </div>
        </form>
    </div>
</div>

{{-- Tableau Ledger --}}
<div class="card shadow-sm">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="bi bi-link-45deg"></i> Journal du Ledger Cryptographique</span>
        <span class="badge bg-light text-dark border">{{ $ledgers->total() }} entrées</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive" style="max-height: 60vh;">
            <table class="table table-hover table-sm mb-0" style="font-size: 0.75rem;">
                <thead class="table-dark sticky-top">
                    <tr>
                        <th>#</th>
                        <th>Table</th>
                        <th>ID Enreg.</th>
                        <th>Action</th>
                        <th style="width: 120px;">Checksum ligne</th>
                        <th style="width: 120px;">Hash Chain</th>
                        <th>Horodatage</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($ledgers as $ledger)
                    <tr>
                        <td class="text-muted">{{ $ledger->id }}</td>
                        <td><code style="font-size: 0.7rem;">{{ $ledger->table_name }}</code></td>
                        <td><span class="badge bg-secondary">{{ $ledger->record_id }}</span></td>
                        <td>
                            @php
                                $actionBadgeClass = match($ledger->action) {
                                    'created' => 'bg-success',
                                    'updated' => 'bg-warning text-dark',
                                    'deleted' => 'bg-danger',
                                    default   => 'bg-secondary',
                                };
                            @endphp
                            <span class="badge {{ $actionBadgeClass }}">{{ $ledger->action }}</span>
                        </td>
                        <td>
                            <span class="font-monospace text-muted" style="font-size: 0.65rem;" title="{{ $ledger->record_checksum }}">
                                {{ $ledger->record_checksum ? substr($ledger->record_checksum, 0, 16).'…' : '—' }}
                            </span>
                        </td>
                        <td>
                            <span class="font-monospace text-primary" style="font-size: 0.65rem;" title="{{ $ledger->hash_chain }}">
                                {{ substr($ledger->hash_chain, 0, 16) }}…
                            </span>
                        </td>
                        <td class="text-muted">{{ $ledger->created_at->format('d/m/Y H:i:s') }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center text-muted py-4">
                            <i class="bi bi-link-45deg" style="font-size: 2rem;"></i>
                            <p class="mt-2">Le Ledger est vide. Lancez <code>php artisan audit:initialize</code> pour créer la baseline.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($ledgers->hasPages())
    <div class="card-footer">
        <div class="pagination-custom">{{ $ledgers->withQueryString()->links() }}</div>
    </div>
    @endif
</div>
@endsection
