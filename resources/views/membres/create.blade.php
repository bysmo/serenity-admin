@extends('layouts.app')

@section('title', 'Créer un Membre')

@section('content')
<div class="page-header">
    <h1><i class="bi bi-plus-circle"></i> Créer un Nouveau Membre</h1>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card">
    <div class="card-header">
        <i class="bi bi-info-circle"></i> Informations du Membre
    </div>
    <div class="card-body">
        <form action="{{ route('membres.store') }}" method="POST">
            @csrf
            
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label for="nom" class="form-label">
                        Nom <span class="text-danger">*</span>
                    </label>
                    <input type="text" 
                           class="form-control @error('nom') is-invalid @enderror" 
                           id="nom" 
                           name="nom" 
                           value="{{ old('nom') }}" 
                           required>
                    @error('nom')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                
                <div class="col-md-4 mb-3">
                    <label for="prenom" class="form-label">
                        Prénom <span class="text-danger">*</span>
                    </label>
                    <input type="text" 
                           class="form-control @error('prenom') is-invalid @enderror" 
                           id="prenom" 
                           name="prenom" 
                           value="{{ old('prenom') }}" 
                           required>
                    @error('prenom')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-4 mb-3">
                    <label for="sexe" class="form-label">
                        Sexe <span class="text-danger">*</span>
                    </label>
                    <select class="form-select @error('sexe') is-invalid @enderror" 
                            id="sexe" 
                            name="sexe" 
                            required>
                        <option value="">-- Sélectionner --</option>
                        <option value="M" {{ old('sexe') === 'M' ? 'selected' : '' }}>Masculin (M)</option>
                        <option value="F" {{ old('sexe') === 'F' ? 'selected' : '' }}>Féminin (F)</option>
                    </select>
                    @error('sexe')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="email" class="form-label">
                        Email <span class="text-danger">*</span>
                    </label>
                    <input type="email" 
                           class="form-control @error('email') is-invalid @enderror" 
                           id="email" 
                           name="email" 
                           value="{{ old('email') }}" 
                           required>
                    @error('email')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                
                <div class="col-md-6 mb-3">
                    <label for="telephone" class="form-label">Téléphone</label>
                    <div class="input-group">
                        <select class="form-select" name="country_code" id="country_code" style="max-width: 140px;" required>
                            @foreach($countries as $code => $country)
                                <option value="{{ $code }}" 
                                    data-dial="{{ $country['dial'] }}"
                                    {{ (old('country_code', $default_country) == $code) ? 'selected' : '' }}>
                                    {{ $country['name'] }} (+{{ $country['dial'] }})
                                </option>
                            @endforeach
                        </select>
                        <input type="text" 
                               class="form-control @error('telephone') is-invalid @enderror" 
                               id="telephone" 
                               name="telephone" 
                               value="{{ old('telephone') }}">
                    </div>
                    @error('telephone')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>
            </div>
            
            <div class="mb-3">
                <label for="adresse" class="form-label">Adresse</label>
                <textarea class="form-control @error('adresse') is-invalid @enderror" 
                          id="adresse" 
                          name="adresse" 
                          rows="2">{{ old('adresse') }}</textarea>
                @error('adresse')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="pays" class="form-label">Pays</label>
                    <input type="text" 
                           class="form-control @error('pays') is-invalid @enderror" 
                           id="pays" 
                           name="pays" 
                           value="{{ old('pays', 'Burkina Faso') }}">
                    @error('pays')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-md-6 mb-3">
                    <label for="ville" class="form-label">Ville</label>
                    <input type="text" 
                           class="form-control @error('ville') is-invalid @enderror" 
                           id="ville" 
                           name="ville" 
                           value="{{ old('ville') }}">
                    @error('ville')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="quartier" class="form-label">Quartier</label>
                    <input type="text" 
                           class="form-control @error('quartier') is-invalid @enderror" 
                           id="quartier" 
                           name="quartier" 
                           value="{{ old('quartier') }}">
                    @error('quartier')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-md-6 mb-3">
                    <label for="secteur" class="form-label">Secteur</label>
                    <input type="text" 
                           class="form-control @error('secteur') is-invalid @enderror" 
                           id="secteur" 
                           name="secteur" 
                           value="{{ old('secteur') }}">
                    @error('secteur')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="date_adhesion" class="form-label">
                        Date d'adhésion <span class="text-danger">*</span>
                    </label>
                    <input type="date" 
                           class="form-control @error('date_adhesion') is-invalid @enderror" 
                           id="date_adhesion" 
                           name="date_adhesion" 
                           value="{{ old('date_adhesion', date('Y-m-d')) }}" 
                           required>
                    @error('date_adhesion')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                
                <div class="col-md-6 mb-3">
                    <label for="statut" class="form-label">
                        Statut <span class="text-danger">*</span>
                    </label>
                    <select class="form-select @error('statut') is-invalid @enderror" 
                            id="statut" 
                            name="statut" 
                            required>
                        <option value="actif" {{ old('statut', 'actif') === 'actif' ? 'selected' : '' }}>Actif</option>
                        <option value="en_attente" {{ old('statut') === 'en_attente' ? 'selected' : '' }}>En attente</option>
                        <option value="inactif" {{ old('statut') === 'inactif' ? 'selected' : '' }}>Inactif</option>
                        <option value="suspendu" {{ old('statut') === 'suspendu' ? 'selected' : '' }}>Suspendu</option>
                    </select>
                    @error('statut')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>
            
            <div class="mb-4">
                <label for="segment_id" class="form-label">Segment / Classification</label>
                <select class="form-select @error('segment_id') is-invalid @enderror" 
                        id="segment_id" 
                        name="segment_id">
                    <option value="">-- Sans segment --</option>
                    @foreach($segments as $segment)
                        <option value="{{ $segment->id }}" {{ old('segment_id') == $segment->id ? 'selected' : '' }}>
                            {{ $segment->nom }}
                        </option>
                    @endforeach
                </select>
                @error('segment_id')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
                <div class="form-text small">Catégorisation utile pour les rapports et cotisations ciblées.</div>
            </div>
            
            
            <div class="d-flex justify-content-between">
                <a href="{{ route('membres.index') }}" class="btn btn-secondary">
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
                <i class="bi bi-info-circle"></i> À propos des Membres
            </div>
            <div class="card-body">
                <h6 class="mb-3" style="font-weight: 300; font-family: 'Ubuntu', sans-serif; color: var(--primary-dark-blue);">
                    <i class="bi bi-person"></i> Qu'est-ce qu'un membre ?
                </h6>
                <p style="font-size: 0.75rem; line-height: 1.5; font-weight: 300; font-family: 'Ubuntu', sans-serif; color: #666;">
                    Un membre est une personne inscrite dans votre organisation qui peut effectuer des paiements de cotisations. Chaque membre reçoit un numéro unique et peut se connecter pour consulter ses paiements.
                </p>
                
                <ul style="font-size: 0.75rem; line-height: 1.8; font-weight: 300; font-family: 'Ubuntu', sans-serif; color: #666; padding-left: 1.2rem;">
                    <li><strong>Nom et prénom :</strong> Identité complète</li>
                    <li><strong>Email :</strong> Pour les notifications et connexion</li>
                    <li><strong>Date d'adhésion :</strong> Date d'inscription</li>
                    <li><strong>Mot de passe :</strong> Géré par le membre (Lien de reset)</li>
                </ul>
                
                <h6 class="mt-4 mb-3" style="font-weight: 300; font-family: 'Ubuntu', sans-serif; color: var(--primary-dark-blue);">
                    <i class="bi bi-lightbulb"></i> Statuts
                </h6>
                <p style="font-size: 0.75rem; line-height: 1.5; font-weight: 300; font-family: 'Ubuntu', sans-serif; color: #666;">
                    <strong>Actif :</strong> Membre pouvant effectuer des paiements<br>
                    <strong>En attente :</strong> Membre en attente de validation OTP<br>
                    <strong>Inactif :</strong> Membre temporairement désactivé<br>
                    <strong>Suspendu :</strong> Membre suspendu par décision administrative
                </p>
            </div>
        </div>
    </div>
</div>
@endsection
