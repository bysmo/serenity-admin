@extends('layouts.app')

@section('title', 'Nouveau Segment')

@section('content')
<div class="page-header d-flex justify-content-between align-items-center mb-3">
    <h1><i class="bi bi-tag-fill me-2"></i>Créer un Nouveau Segment</h1>
    <a href="{{ route('segments.index') }}" class="btn btn-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i> Retour à la liste
    </a>
</div>

<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-3" style="background: var(--primary-dark-blue) !important; color: white;">
                <h5 class="card-title mb-0"><i class="bi bi-info-circle me-2"></i>Informations du segment</h5>
            </div>
            <div class="card-body p-4">
                <form action="{{ route('segments.store') }}" method="POST">
                    @csrf
                    
                    <div class="mb-3">
                        <label for="nom" class="form-label">Nom du segment <span class="text-danger">*</span></label>
                        <input type="text" name="nom" id="nom" class="form-control @error('nom') is-invalid @enderror" value="{{ old('nom') }}" required placeholder="Ex: Commerçant, Étudiant...">
                        @error('nom')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea name="description" id="description" rows="3" class="form-control @error('description') is-invalid @enderror" placeholder="Brève description de ce groupe de membres...">{{ old('description') }}</textarea>
                        @error('description')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="couleur" class="form-label">Couleur UI</label>
                            <input type="color" name="couleur" id="couleur" class="form-control form-control-color w-100 @error('couleur') is-invalid @enderror" value="{{ old('couleur', '#4a6cf7') }}">
                            @error('couleur')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="text-muted">Utilisée pour les badges et icônes.</small>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="icone" class="form-label">Icône (Bootstrap Icons)</label>
                            <select name="icone" id="icone" class="form-select @error('icone') is-invalid @enderror">
                                <option value="bi bi-people" {{ old('icone') == 'bi bi-people' ? 'selected' : '' }}>👥 Par défaut</option>
                                <option value="bi bi-cart" {{ old('icone') == 'bi bi-cart' ? 'selected' : '' }}>🛒 Commerçant</option>
                                <option value="bi bi-briefcase" {{ old('icone') == 'bi bi-briefcase' ? 'selected' : '' }}>💼 Travailleur</option>
                                <option value="bi bi-mortarboard" {{ old('icone') == 'bi bi-mortarboard' ? 'selected' : '' }}>🎓 Étudiant</option>
                                <option value="bi bi-building" {{ old('icone') == 'bi bi-building' ? 'selected' : '' }}>🏢 Entreprise</option>
                                <option value="bi bi-person-badge" {{ old('icone') == 'bi bi-person-badge' ? 'selected' : '' }}>📛 Fonctionnaire</option>
                                <option value="bi bi-shop" {{ old('icone') == 'bi bi-shop' ? 'selected' : '' }}>🏪 Artisan</option>
                                <option value="bi bi-heart-pulse" {{ old('icone') == 'bi bi-heart-pulse' ? 'selected' : '' }}>❤️ Santé</option>
                                <option value="bi bi-globe" {{ old('icone') == 'bi bi-globe' ? 'selected' : '' }}>🌎 Diaspora</option>
                            </select>
                            @error('icone')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="mb-4">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="actif" id="actif" value="1" {{ old('actif', true) ? 'checked' : '' }}>
                            <label class="form-check-label" for="actif">Segment actif (visible pour l'attribution)</label>
                        </div>
                    </div>

                    <hr>

                    <div class="d-flex justify-content-end gap-2">
                        <button type="reset" class="btn btn-light btn-sm border">Réinitialiser</button>
                        <button type="submit" class="btn btn-primary btn-sm">
                            <i class="bi bi-check-circle me-1"></i> Créer le segment
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
