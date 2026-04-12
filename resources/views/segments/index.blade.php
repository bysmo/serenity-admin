@extends('layouts.app')

@section('title', 'Gestion des Segments')

@section('content')
<div class="page-header d-flex justify-content-between align-items-center mb-3">
    <h1><i class="bi bi-tags me-2"></i>Gestion des Segments de Membres</h1>
    <a href="{{ route('segments.create') }}" class="btn btn-primary btn-sm">
        <i class="bi bi-plus-circle me-1"></i> Nouveau Segment
    </a>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white py-3">
        <div class="row align-items-center">
            <div class="col">
                <h5 class="card-title mb-0">Segments enregistrés</h5>
            </div>
            <div class="col-auto">
                <form action="{{ route('segments.index') }}" method="GET" class="d-flex gap-2">
                    <input type="text" name="search" class="form-control form-control-sm" placeholder="Rechercher..." value="{{ request('search') }}">
                    <button type="submit" class="btn btn-light btn-sm border">
                        <i class="bi bi-search"></i>
                    </button>
                    @if(request('search'))
                        <a href="{{ route('segments.index') }}" class="btn btn-light btn-sm border">
                            <i class="bi bi-x-circle"></i>
                        </a>
                    @endif
                </form>
            </div>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4">Segment</th>
                        <th>Description</th>
                        <th class="text-center">Membres</th>
                        <th class="text-center">Statut</th>
                        <th class="text-center pe-4">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($segments as $segment)
                        <tr>
                            <td class="ps-4">
                                <div class="d-flex align-items-center">
                                    <div class="rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 35px; height: 35px; background-color: {{ $segment->couleur }}22; color: {{ $segment->couleur }};">
                                        <i class="{{ $segment->icone ?: 'bi bi-people' }}"></i>
                                    </div>
                                    <div>
                                        <div class="fw-bold">{{ $segment->nom }}</div>
                                        @if($segment->is_default)
                                            <span class="badge bg-secondary-subtle text-secondary border border-secondary" style="font-size: 0.6rem;">PAR DÉFAUT</span>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="text-muted small">{{ Str::limit($segment->description, 60) ?: 'Aucune description' }}</span>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-primary rounded-pill">{{ $segment->membres_count }}</span>
                            </td>
                            <td class="text-center">
                                @if($segment->actif)
                                    <span class="badge bg-success-subtle text-success border border-success">Actif</span>
                                @else
                                    <span class="badge bg-danger-subtle text-danger border border-danger">Inactif</span>
                                @endif
                            </td>
                            <td class="text-center pe-4">
                                <div class="btn-group">
                                    <a href="{{ route('segments.show', $segment) }}" class="btn btn-light btn-sm border" title="Voir les membres">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <a href="{{ route('segments.edit', $segment) }}" class="btn btn-light btn-sm border" title="Modifier">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    @if(!$segment->is_default)
                                        <form action="{{ route('segments.destroy', $segment) }}" method="POST" class="delete-form d-inline" data-message="Voulez-vous vraiment supprimer ce segment ?">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-light btn-sm border text-danger" title="Supprimer" {{ $segment->membres_count > 0 ? 'disabled' : '' }}>
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center py-5 text-muted">
                                <i class="bi bi-info-circle fs-2 d-block mb-2"></i>
                                Aucun segment trouvé
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
