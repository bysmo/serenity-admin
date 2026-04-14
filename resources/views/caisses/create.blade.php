@extends('layouts.app')

@section('title', 'Créer un Compte')

@section('content')
<div class="page-header">
    <h1><i class="bi bi-plus-circle"></i> Créer un Nouveau Compte</h1>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <i class="bi bi-info-circle"></i> Informations du Compte
            </div>
            <div class="card-body">
                <form action="{{ route('caisses.store') }}" method="POST">
                    @csrf
                    
                    <div class="mb-3">
                        <label for="membre_id" class="form-label">
                            Propriétaire (Client) <span class="text-danger">*</span>
                        </label>
                        <select class="form-select @error('membre_id') is-invalid @enderror" 
                                id="membre_id" 
                                name="membre_id" 
                                required>
                            <option value="">Sélectionnez un client...</option>
                            @foreach($membres as $membre)
                                <option value="{{ $membre->id }}" {{ old('membre_id') == $membre->id ? 'selected' : '' }}>
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
                                <option value="courant" {{ old('type') === 'courant' ? 'selected' : '' }}>Courant</option>
                                <option value="epargne" {{ old('type') === 'epargne' ? 'selected' : '' }}>Épargne</option>
                                <option value="tontine" {{ old('type') === 'tontine' ? 'selected' : '' }}>Tontine</option>
                                <option value="credit" {{ old('type') === 'credit' ? 'selected' : '' }}>Crédit</option>
                                <option value="impayes" {{ old('type') === 'impayes' ? 'selected' : '' }}>Impayés</option>
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
                                   value="{{ old('numero_core_banking') }}"
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
                               value="{{ old('nom') }}" 
                               placeholder="Ex: Compte personnel, Épargne projet..."
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
                                  rows="2">{{ old('description') }}</textarea>
                        @error('description')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div class="mb-3">
                        <label for="statut" class="form-label">
                            Statut <span class="text-danger">*</span>
                        </label>
                        <select class="form-select @error('statut') is-invalid @enderror" 
                                id="statut" 
                                name="statut" 
                                required>
                            <option value="active" {{ old('statut') === 'active' ? 'selected' : '' }}>
                                Actif
                            </option>
                            <option value="inactive" {{ old('statut') === 'inactive' ? 'selected' : '' }}>
                                Inactif
                            </option>
                        </select>
                        @error('statut')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <input type="hidden" name="solde_initial" value="0">
                    
                    <div class="d-flex justify-content-between mt-4">
                        <a href="{{ route('caisses.index') }}" class="btn btn-secondary">
                            <i class="bi bi-arrow-left"></i> Retour
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-circle"></i> Enregistrer
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <i class="bi bi-info-circle"></i> À propos des Comptes
            </div>
            <div class="card-body">
                <h6 class="mb-3" style="font-weight: 300; font-family: 'Ubuntu', sans-serif; color: var(--primary-dark-blue);">
                    <i class="bi bi-wallet2"></i> Qu'est-ce qu'un compte ?
                </h6>
                <p style="font-size: 0.75rem; line-height: 1.5; font-weight: 300; font-family: 'Ubuntu', sans-serif; color: #666;">
                    Un compte représente un espace financier dédié à un client. Il permet de suivre les soldes par type d'activité (Épargne, Courant, Tontine, etc.).
                </p>
                
                <h6 class="mt-4 mb-3" style="font-weight: 300; font-family: 'Ubuntu', sans-serif; color: var(--primary-dark-blue);">
                    <i class="bi bi-link"></i> Core Banking
                </h6>
                <p style="font-size: 0.75rem; line-height: 1.5; font-weight: 300; font-family: 'Ubuntu', sans-serif; color: #666;">
                    Le champ <strong>N° Compte Core Banking</strong> permet de lier ce compte à une référence externe dans votre système bancaire central, facilitant ainsi les synchronisations automatiques via API.
                </p>
                
                <h6 class="mt-4 mb-3" style="font-weight: 300; font-family: 'Ubuntu', sans-serif; color: var(--primary-dark-blue);">
                    <i class="bi bi-shield-check"></i> Bonnes pratiques
                </h6>
                <p style="font-size: 0.75rem; line-height: 1.5; font-weight: 300; font-family: 'Ubuntu', sans-serif; color: #666;">
                    Assurez-vous de sélectionner le bon type de compte, car cela influencera les rapports financiers et les modules d'opérations futurs.
                </p>
            </div>
        </div>
    </div>
</div>
@endsection
