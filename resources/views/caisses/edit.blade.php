@extends('layouts.app')

@section('title', 'Modifier un Compte')

@section('content')
<div class="page-header">
    <h1><i class="bi bi-pencil"></i> Modifier le Compte</h1>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <i class="bi bi-info-circle"></i> Informations du Compte
            </div>
            <div class="card-body">
                <form action="{{ route('caisses.update', $caisse) }}" method="POST">
                    @csrf
                    @method('PUT')
                    
                    <div class="mb-3">
                        <label for="membre_id" class="form-label">
                            Propriétaire (Client) <span class="text-danger">*</span>
                        </label>
                        <select class="form-select @error('membre_id') is-invalid @enderror" 
                                id="membre_id" 
                                name="membre_id" 
                                required>
                            @foreach($membres as $membre)
                                <option value="{{ $membre->id }}" {{ old('membre_id', $caisse->membre_id) == $membre->id ? 'selected' : '' }}>
                                    {{ $membre->numero }} - {{ $membre->nom_complet }}
                                </option>
                            @endforeach
                        </select>
                        @error('membre_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="type" class="form-label">
                                Type de compte <span class="text-danger">*</span>
                            </label>
                            <select class="form-select @error('type') is-invalid @enderror" 
                                    id="type" 
                                    name="type" 
                                    required>
                                <option value="courant" {{ old('type', $caisse->type) === 'courant' ? 'selected' : '' }}>Courant</option>
                                <option value="epargne" {{ old('type', $caisse->type) === 'epargne' ? 'selected' : '' }}>Épargne</option>
                                <option value="tontine" {{ old('type', $caisse->type) === 'tontine' ? 'selected' : '' }}>Tontine</option>
                                <option value="credit" {{ old('type', $caisse->type) === 'credit' ? 'selected' : '' }}>Crédit</option>
                                <option value="impayes" {{ old('type', $caisse->type) === 'impayes' ? 'selected' : '' }}>Impayés</option>
                            </select>
                            @error('type')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="numero_core_banking" class="form-label">
                                N° Compte Core Banking
                            </label>
                            <input type="text" 
                                   class="form-control @error('numero_core_banking') is-invalid @enderror" 
                                   id="numero_core_banking" 
                                   name="numero_core_banking" 
                                   value="{{ old('numero_core_banking', $caisse->numero_core_banking) }}"
                                   placeholder="Alpha-numérique uniquement">
                            @error('numero_core_banking')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="nom" class="form-label">
                            Nom d'usage du compte <span class="text-danger">*</span>
                        </label>
                        <input type="text" 
                               class="form-control @error('nom') is-invalid @enderror" 
                               id="nom" 
                               name="nom" 
                               value="{{ old('nom', $caisse->nom) }}" 
                               required>
                        @error('nom')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control @error('description') is-invalid @enderror" 
                                  id="description" 
                                  name="description" 
                                  rows="2">{{ old('description', $caisse->description) }}</textarea>
                        @error('description')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Solde actuel</label>
                            <div class="form-control bg-light">
                                <strong>{{ number_format((float) ($caisse->solde_actuel ?? 0), 0, ',', ' ') }} XOF</strong>
                            </div>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="statut" class="form-label">
                                Statut <span class="text-danger">*</span>
                            </label>
                            <select class="form-select @error('statut') is-invalid @enderror" 
                                    id="statut" 
                                    name="statut" 
                                    required>
                                <option value="active" {{ old('statut', $caisse->statut) === 'active' ? 'selected' : '' }}>
                                    Actif
                                </option>
                                <option value="inactive" {{ old('statut', $caisse->statut) === 'inactive' ? 'selected' : '' }}
                                        {{ $caisse->solde_actuel != 0 ? 'disabled' : '' }}>
                                    Inactif
                                </option>
                            </select>
                            @if($caisse->solde_actuel != 0)
                                <small class="text-warning" style="font-size: 0.7rem;">
                                    <i class="bi bi-exclamation-triangle"></i> 
                                    Ce compte ne peut pas être désactivé car son solde est différent de 0.
                                </small>
                            @endif
                            @error('statut')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    
                    <div class="d-flex justify-content-between mt-4">
                        <a href="{{ route('caisses.index') }}" class="btn btn-secondary">
                            <i class="bi bi-arrow-left"></i> Retour
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-circle"></i> Mettre à jour
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <i class="bi bi-info-circle"></i> À propos
            </div>
            <div class="card-body">
                <h6 class="mb-3" style="font-weight: 300; font-family: 'Ubuntu', sans-serif; color: var(--primary-dark-blue);">
                    <i class="bi bi-wallet2"></i> Modification du compte
                </h6>
                <p style="font-size: 0.75rem; line-height: 1.5; font-weight: 300; font-family: 'Ubuntu', sans-serif; color: #666;">
                    Cette page vous permet de modifier les informations d'un compte. Vous pouvez notamment mettre à jour le lien avec le Core Banking ou changer le propriétaire.
                </p>
                
                <h6 class="mt-4 mb-3" style="font-weight: 300; font-family: 'Ubuntu', sans-serif; color: var(--primary-dark-blue);">
                    <i class="bi bi-shield-check"></i> Désactivation
                </h6>
                <p style="font-size: 0.75rem; line-height: 1.5; font-weight: 300; font-family: 'Ubuntu', sans-serif; color: #666;">
                    Un compte ne peut être désactivé que si son solde est égal à 0. Cette mesure de sécurité protège les fonds des clients.
                </p>
            </div>
        </div>
    </div>
</div>
@endsection