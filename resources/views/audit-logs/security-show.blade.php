@extends('layouts.app')

@section('title', 'Détail du Scan de Sécurité')

@section('content')
<div class="page-header d-flex justify-content-between align-items-center mb-4">
    <div>
        <a href="{{ route('logs.security') }}" class="btn btn-sm btn-outline-secondary mb-2">
            <i class="bi bi-arrow-left"></i> Retour à l'historique
        </a>
        <h1><i class="bi bi-shield-exclamation text-danger"></i> Rapport d'Investigation</h1>
        <p class="text-muted mb-0">Rapport de sécurité généré le <strong>{{ $log->created_at->format('d/m/Y à H:i:s') }}</strong> ({{ $log->created_at->diffForHumans() }})</p>
    </div>
    <div class="text-end">
        <span class="badge bg-dark bg-opacity-10 text-dark border p-2 mb-1 d-block fs-6">
            <i class="bi bi-database"></i> Lignes scannées: {{ number_format($log->rows_checked_count, 0, ',', ' ') }}
        </span>
        @if(!$log->is_valid)
            <span class="badge bg-danger bg-opacity-25 text-danger border border-danger p-2 fs-6">
                <i class="bi bi-bug"></i> Anomalies: {{ $log->corrupted_count }}
            </span>
        @else
            <span class="badge bg-success bg-opacity-25 text-success border border-success p-2 fs-6">
                <i class="bi bi-check-circle"></i> État: Intègre
            </span>
        @endif
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success mt-3 shadow-sm border-start border-4 border-success">
        <i class="bi bi-check-circle-fill me-2"></i> {{ session('success') }}
    </div>
@endif

@if(session('error'))
    <div class="alert alert-danger mt-3 shadow-sm border-start border-4 border-danger">
        <i class="bi bi-exclamation-triangle-fill me-2"></i> {{ session('error') }}
    </div>
@endif

@if($errors->any())
    <div class="alert alert-warning mt-3 shadow-sm border-start border-4 border-warning">
        <ul class="mb-0">
            @foreach($errors->all() as $error)
                <li><i class="bi bi-x-circle"></i> {{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif


@if($log->is_valid)
<div class="card border-success border-2 shadow-sm">
    <div class="card-body text-center py-5">
        <i class="bi bi-shield-check text-success" style="font-size: 4rem;"></i>
        <h3 class="mt-3">Aucune anomalie détectée lors de ce scan.</h3>
        <p class="text-muted">Les sommes de contrôles cryptographiques (Checksum) de toutes les tables sont valides.</p>
    </div>
</div>
@else

<div class="card border-danger shadow-sm">
    <div class="card-header bg-danger text-white d-flex align-items-center">
        <i class="bi bi-search me-2"></i> <!-- ICON -->
        <h5 class="mb-0">Détail des {{ $log->corrupted_count }} altérations détectées</h5>
    </div>
    
    <div class="card-body p-4 bg-light">
        @if(is_array($log->corrupted_data) && count($log->corrupted_data) > 0)
            <div class="accordion" id="accordionErrorsFull">
                @foreach($log->corrupted_data as $index => $err)
                    <div class="accordion-item border-danger mb-3 shadow-sm" style="border-radius: 6px; overflow: hidden;">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed py-3 text-danger bg-danger bg-opacity-10 fw-bold" type="button" data-bs-toggle="collapse" data-bs-target="#collapseErr_{{ $index }}">
                            <i class="bi bi-bug-fill me-2 fs-5"></i>
                            Anomalie #{{ $index + 1 }} &mdash; Table : <code class="mx-2 fs-6">{{ $err['table'] }}</code> | ID : <span class="badge bg-danger ms-2 fs-6">{{ $err['id'] }}</span>
                            </button>
                        </h2>
                        
                        <div id="collapseErr_{{ $index }}" class="accordion-collapse collapse" data-bs-parent="#accordionErrorsFull">
                            <div class="accordion-body p-4 bg-white">
                            
                                <div class="row">
                                    <div class="col-md-5">
                                        <div class="mb-3 p-3 rounded bg-light border">
                                            <h6 class="text-danger fw-bold"><i class="bi bi-person-x"></i> Diagnostic et Origine probable</h6>
                                            <p class="text-muted mb-0 fs-6 mt-2">
                                                {{ $err['origin'] ?? 'Non déterminée (Manipulation SQL directe ou modification tierce).' }}
                                            </p>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-7">
                                    @if(isset($err['impacted_columns']) && is_array($err['impacted_columns']) && count($err['impacted_columns']) > 0)
                                        <h6 class="text-info fw-bold mb-3"><i class="bi bi-search"></i> Différentiels (Écarts détectés)</h6>
                                        <div class="table-responsive">
                                            <table class="table table-bordered table-striped" style="font-size: 0.9rem;">
                                                <thead class="table-dark">
                                                    <tr>
                                                        <th>Colonne modifiée</th>
                                                        <th>Valeur Légale (Trace d'Audit)</th>
                                                        <th>Valeur Falsifiée (Relevée Actuellement)</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                @foreach($err['impacted_columns'] as $col)
                                                    <tr>
                                                        <td class="fw-bold">{{ $col['field'] }}</td>
                                                        <td class="text-success"><span class="badge bg-success bg-opacity-10 text-success border border-success">{{ $col['expected'] ?? 'NULL' }}</span></td>
                                                        <td class="text-danger fw-bold"><span class="badge bg-danger bg-opacity-10 text-danger border border-danger">{{ $col['actual'] ?? 'NULL' }}</span></td>
                                                    </tr>
                                                @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    @else
                                        <div class="alert alert-warning mb-0">
                                            <i class="bi bi-exclamation-triangle-fill"></i> Aucune trace historique de cette ligne n'a été trouvée dans le journal applicatif. Il s'agit problablement d'une ligne insérée directement de l'extérieur.
                                        </div>
                                    @endif
                                    </div>
                                </div>

                                <!-- Actions de Remédiation -->
                                @if(!empty($err['model']))
                                    <h6 class="mt-4 mb-3 fw-bold text-dark"><i class="bi bi-tools"></i> Actions de remédiation disponibles</h6>
                                    <div class="d-flex gap-3 p-3 bg-light rounded border border-secondary border-opacity-25">
                                        <form method="POST" action="{{ route('logs.security.remediate') }}" class="d-inline-block">
                                            @csrf
                                            <input type="hidden" name="model" value="{{ $err['model'] }}">
                                            <input type="hidden" name="id" value="{{ $err['id'] ?? '' }}">
                                            <input type="hidden" name="action" value="restore">
                                            
                                            <button type="submit" class="btn btn-outline-success" onclick="return confirm('Confirmer la restauration avec annulation des falsifications ?')">
                                                <i class="bi bi-arrow-counterclockwise fs-5 d-block mb-1"></i> <strong>Restaurer les données</strong><br>
                                                <small style="font-size:0.7rem;">Écraser la DB et resécuriser</small>
                                            </button>
                                        </form>

                                        <form method="POST" action="{{ route('logs.security.remediate') }}" class="d-inline-block">
                                            @csrf
                                            <input type="hidden" name="model" value="{{ $err['model'] }}">
                                            <input type="hidden" name="id" value="{{ $err['id'] ?? '' }}">
                                            <input type="hidden" name="action" value="suspend">
                                            
                                            <button type="submit" class="btn btn-outline-danger" onclick="return confirm('Suspendre le membre lié ?')">
                                                <i class="bi bi-person-lock fs-5 d-block mb-1"></i> <strong>Suspendre Compte</strong><br>
                                                <small style="font-size:0.7rem;">Mettre le fraudeur en quarantaine</small>
                                            </button>
                                        </form>

                                        <form method="POST" action="{{ route('logs.security.remediate') }}" class="d-inline-block">
                                            @csrf
                                            <input type="hidden" name="model" value="{{ $err['model'] }}">
                                            <input type="hidden" name="id" value="{{ $err['id'] ?? '' }}">
                                            <input type="hidden" name="action" value="accept">
                                            
                                            <button type="submit" class="btn btn-outline-secondary" onclick="return confirm('Attention : Le HASH sera recalculé autour de la fausse donnée. Confirmer ?')">
                                                <i class="bi bi-check-all fs-5 d-block mb-1"></i> <strong>Amnistier (Accepter)</strong><br>
                                                <small style="font-size:0.7rem;">Valider informatiquement l'altération</small>
                                            </button>
                                        </form>
                                    </div>
                                @else
                                    <div class="text-muted mt-4"><i class="bi bi-info-circle"></i> Actions tactiques non disponibles (Modèle de donnée non traçable pour cet ancien log historique).</div>
                                @endif

                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="text-muted p-4 text-center">
                Détails de corruption manquants (Format de journal archaïque ne contenant pas le dump technique).
            </div>
        @endif
    </div>
</div>
@endif

@endsection
