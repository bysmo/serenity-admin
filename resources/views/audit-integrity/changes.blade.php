@extends('layouts.app')

@section('title', 'Modifications Traçées — Audit d\'Intégrité')

@section('content')
<div class="page-header d-flex justify-content-between align-items-center mb-3">
    <div>
        <h1><i class="bi bi-person-lock text-warning"></i> Modifications Traçées</h1>
        <p class="text-muted mb-0" style="font-size: 0.75rem;">
            Journal détaillé de toutes les opérations CRUD effectuées via l'application Serenity.
            Chaque ligne contient l'auteur, l'IP, et le différentiel exact (Avant / Après).
        </p>
    </div>
</div>

{{-- Stats --}}
<div class="row g-2 mb-3">
    <div class="col-6 col-md-2">
        <div class="card text-center border-0 shadow-sm">
            <div class="card-body py-2">
                <div class="fw-bold text-primary" style="font-size: 1.1rem;">{{ number_format($stats['total']) }}</div>
                <div class="text-muted" style="font-size: 0.7rem;">Total</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-2">
        <div class="card text-center border-0 shadow-sm">
            <div class="card-body py-2">
                <div class="fw-bold text-info" style="font-size: 1.1rem;">{{ number_format($stats['today']) }}</div>
                <div class="text-muted" style="font-size: 0.7rem;">Aujourd'hui</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-2">
        <div class="card text-center border-0 shadow-sm">
            <div class="card-body py-2">
                <div class="fw-bold text-success" style="font-size: 1.1rem;">{{ number_format($stats['created']) }}</div>
                <div class="text-muted" style="font-size: 0.7rem;">Créations</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-2">
        <div class="card text-center border-0 shadow-sm">
            <div class="card-body py-2">
                <div class="fw-bold text-warning" style="font-size: 1.1rem;">{{ number_format($stats['updated']) }}</div>
                <div class="text-muted" style="font-size: 0.7rem;">Modifications</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-2">
        <div class="card text-center border-0 shadow-sm">
            <div class="card-body py-2">
                <div class="fw-bold text-danger" style="font-size: 1.1rem;">{{ number_format($stats['deleted']) }}</div>
                <div class="text-muted" style="font-size: 0.7rem;">Suppressions</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-2">
        <div class="card text-center border-0 shadow-sm bg-light">
            <div class="card-body py-2">
                <div class="fw-bold" style="font-size: 1.1rem;">{{ $activeUsers->count() }}</div>
                <div class="text-muted" style="font-size: 0.7rem;">Utilisateurs actifs</div>
            </div>
        </div>
    </div>
</div>

{{-- Filtres --}}
<div class="card shadow-sm mb-3">
    <div class="card-body py-2">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label">Modèle / Entité</label>
                <select name="model" class="form-select form-select-sm">
                    <option value="">Tous les modèles</option>
                    @foreach($availableModels as $m)
                        <option value="{{ $m }}" {{ Str::contains($filterModel ?? '', $m) ? 'selected' : '' }}>{{ $m }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Action</label>
                <select name="action" class="form-select form-select-sm">
                    <option value="">Toutes</option>
                    @foreach($availableActions as $a)
                        <option value="{{ $a }}" {{ $filterAction == $a ? 'selected' : '' }}>{{ ucfirst($a) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Utilisateur</label>
                <select name="user_id" class="form-select form-select-sm">
                    <option value="">Tous</option>
                    <option value="0" {{ $filterUser === '0' ? 'selected' : '' }}>Système / CLI</option>
                    @foreach($activeUsers as $u)
                        <option value="{{ $u->id }}" {{ $filterUser == $u->id ? 'selected' : '' }}>{{ $u->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary btn-sm w-100"><i class="bi bi-funnel"></i> Filtrer</button>
            </div>
            <div class="col-md-2">
                <a href="{{ route('audit.integrity.changes') }}" class="btn btn-outline-secondary btn-sm w-100"><i class="bi bi-x"></i> Réinitialiser</a>
            </div>
        </form>
    </div>
</div>

{{-- Tableau des logs --}}
<div class="card shadow-sm">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="bi bi-person-lock"></i> Journal des Opérations CRUD</span>
        <span class="badge bg-light text-dark border">{{ $logs->total() }} entrées</span>
    </div>
    <div class="card-body p-0">
        <div class="accordion accordion-flush" id="auditAccordion">
            @forelse($logs as $i => $log)
            @php
                $actionClass = match($log->action) {
                    'created' => 'success',
                    'updated' => 'warning',
                    'deleted' => 'danger',
                    default   => 'secondary',
                };
                $modelBasename = class_basename($log->model ?? 'Inconnu');
            @endphp
            <div class="accordion-item border-bottom border-0" style="border-radius: 0;">
                <h2 class="accordion-header">
                    <button class="accordion-button collapsed py-2" type="button"
                            data-bs-toggle="collapse" data-bs-target="#change_{{ $log->id }}"
                            style="font-size: 0.75rem; background: white;">
                        <div class="d-flex align-items-center gap-2 w-100">
                            <span class="badge bg-{{ $actionClass }}" style="font-size: 0.65rem; min-width: 55px;">{{ strtoupper($log->action) }}</span>
                            <code style="font-size: 0.7rem; color: #1e3a5f;">{{ $modelBasename }}</code>
                            <span class="text-muted" style="font-size: 0.7rem;">ID: {{ $log->model_id }}</span>
                            <span class="ms-auto d-flex gap-2 align-items-center">
                                @if($log->user)
                                    <span class="badge bg-light text-dark border" style="font-size: 0.65rem;">
                                        <i class="bi bi-person"></i> {{ $log->user->name }}
                                    </span>
                                @else
                                    <span class="badge bg-secondary" style="font-size: 0.65rem;"><i class="bi bi-terminal"></i> Système</span>
                                @endif
                                @if($log->ip_address)
                                    <span class="text-muted" style="font-size: 0.65rem;"><i class="bi bi-geo"></i> {{ $log->ip_address }}</span>
                                @endif
                                <span class="text-muted" style="font-size: 0.65rem;">{{ $log->created_at->format('d/m/Y H:i:s') }}</span>
                            </span>
                        </div>
                    </button>
                </h2>
                <div id="change_{{ $log->id }}" class="accordion-collapse collapse" data-bs-parent="">
                    <div class="accordion-body py-2 bg-light" style="font-size: 0.75rem;">
                        <div class="row g-3">
                            @if(!empty($log->old_values))
                            <div class="col-md-6">
                                <h6 class="text-danger fw-bold mb-2" style="font-size: 0.75rem;"><i class="bi bi-dash-circle"></i> Avant (Ancien état)</h6>
                                <div class="bg-danger bg-opacity-10 border border-danger border-opacity-25 rounded p-2">
                                    @foreach($log->old_values as $key => $val)
                                        <div class="d-flex justify-content-between mb-1">
                                            <code class="text-danger" style="font-size: 0.65rem;">{{ $key }}</code>
                                            <span style="font-size: 0.7rem; max-width: 55%; text-align: right; word-break: break-all;">{{ is_array($val) ? json_encode($val) : $val }}</span>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                            @endif
                            @if(!empty($log->new_values))
                            <div class="col-md-6">
                                <h6 class="text-success fw-bold mb-2" style="font-size: 0.75rem;"><i class="bi bi-plus-circle"></i> Après (Nouvel état)</h6>
                                <div class="bg-success bg-opacity-10 border border-success border-opacity-25 rounded p-2">
                                    @foreach($log->new_values as $key => $val)
                                        <div class="d-flex justify-content-between mb-1">
                                            <code class="text-success" style="font-size: 0.65rem;">{{ $key }}</code>
                                            <span style="font-size: 0.7rem; max-width: 55%; text-align: right; word-break: break-all;">{{ is_array($val) ? json_encode($val) : $val }}</span>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                            @endif
                            @if(empty($log->old_values) && empty($log->new_values))
                            <div class="col-12 text-muted text-center py-1">Aucun différentiel enregistré pour cette opération.</div>
                            @endif
                        </div>
                        @if($log->description)
                        <p class="text-muted mt-2 mb-0" style="font-size: 0.7rem;"><i class="bi bi-info-circle"></i> {{ $log->description }}</p>
                        @endif
                    </div>
                </div>
            </div>
            @empty
            <div class="p-5 text-center text-muted">
                <i class="bi bi-person-lock" style="font-size: 2rem;"></i>
                <p class="mt-2">Aucune modification traçée pour les critères sélectionnés.</p>
            </div>
            @endforelse
        </div>
    </div>
    @if($logs->hasPages())
    <div class="card-footer">
        <div class="pagination-custom">{{ $logs->withQueryString()->links() }}</div>
    </div>
    @endif
</div>
@endsection
