@extends('layouts.app')

@section('title', 'Modifier un Utilisateur')

@section('content')
<div class="page-header">
    <h1><i class="bi bi-person-check"></i> Modifier un Utilisateur</h1>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <i class="bi bi-pencil-square"></i> Informations de l'utilisateur
            </div>
            <div class="card-body">
                <form action="{{ route('users.update', $user) }}" method="POST">
                    @csrf
                    @method('PUT')
                    
                    <div class="mb-3">
                        <label for="name" class="form-label">Nom complet <span class="text-danger">*</span></label>
                        <input type="text" 
                               class="form-control @error('name') is-invalid @enderror" 
                               id="name" 
                               name="name" 
                               value="{{ old('name', $user->name) }}" 
                               required>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div class="mb-3">
                        <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                        <input type="email" 
                               class="form-control @error('email') is-invalid @enderror" 
                               id="email" 
                               name="email" 
                               value="{{ old('email', $user->email) }}" 
                               required>
                        @error('email')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div class="mb-3">
                        <label for="password" class="form-label">Nouveau mot de passe</label>
                        <input type="password" 
                               class="form-control @error('password') is-invalid @enderror" 
                               id="password" 
                               name="password">
                        @error('password')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="text-muted">Laissez vide pour conserver le mot de passe actuel. Minimum 6 caractères si modifié.</small>
                    </div>
                    
                    <div class="mb-3">
                        <label for="password_confirmation" class="form-label">Confirmer le nouveau mot de passe</label>
                        <input type="password" 
                               class="form-control" 
                               id="password_confirmation" 
                               name="password_confirmation">
                    </div>
                    
                    <div class="mb-3">
                        <label for="role_id" class="form-label">Rôle</label>
                        @if($roles->count() > 0)
                            <select class="form-select @error('role_id') is-invalid @enderror" 
                                    id="role_id" 
                                    name="role_id">
                                <option value="">Sélectionner un rôle</option>
                                @foreach($roles as $role)
                                    <option value="{{ $role->id }}" 
                                            {{ (old('role_id') == $role->id) || (empty(old('role_id')) && $user->roles->contains($role->id)) ? 'selected' : '' }}>
                                        {{ $role->nom }}
                                        @if($role->description)
                                            - {{ $role->description }}
                                        @endif
                                    </option>
                                @endforeach
                            </select>
                            @error('role_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="text-muted">Sélectionnez le rôle à attribuer à cet utilisateur</small>
                        @else
                            <div class="alert alert-info">
                                <i class="bi bi-info-circle"></i> Aucun rôle disponible. Créez d'abord des rôles dans la section <a href="{{ route('roles.index') }}">Rôles et Permissions</a>.
                            </div>
                        @endif
                    </div>

                    <div class="mb-3" id="alias_field" style="display: none;">
                        <label for="alias" class="form-label">Alias Mobile Money (Pi-SPI) <span class="text-danger">*</span></label>
                        <input type="text" 
                               class="form-control @error('alias') is-invalid @enderror" 
                               id="alias" 
                               name="alias" 
                               value="{{ old('alias', $user->collectorAccount?->alias) }}" 
                               placeholder="Ex: M001">
                        @error('alias')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="text-muted">Requis pour les collecteurs pour identifier leur compte de reversement.</small>
                    </div>
                    
                    <div class="d-flex justify-content-between">
                        <a href="{{ route('users.index') }}" class="btn btn-secondary">
                            <i class="bi bi-arrow-left"></i> Annuler
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
            <div class="card-header" style="background-color: var(--primary-dark-blue); color: white; font-weight: 300; font-family: 'Ubuntu', sans-serif;">
                <i class="bi bi-info-circle"></i> À propos des Utilisateurs
            </div>
            <div class="card-body" style="font-size: 0.75rem; font-weight: 300; font-family: 'Ubuntu', sans-serif;">
                <p><strong>Modification d'un utilisateur</strong></p>
                <p>Vous pouvez modifier les informations de l'utilisateur et ses rôles. Si vous ne souhaitez pas changer le mot de passe, laissez les champs vides.</p>
                
                <p class="mt-3"><strong><i class="bi bi-shield-check"></i> Rôles et Permissions</strong></p>
                <p>Les rôles définissent les permissions d'accès aux différentes fonctionnalités de l'application.</p>
                
                <p class="mt-3"><strong><i class="bi bi-key"></i> Mot de passe</strong></p>
                <p>Laissez les champs de mot de passe vides si vous ne souhaitez pas le modifier.</p>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const roleSelect = document.getElementById('role_id');
        const aliasField = document.getElementById('alias_field');
        const aliasInput = document.getElementById('alias');

        function toggleAliasField() {
            const selectedOption = roleSelect.options[roleSelect.selectedIndex];
            const roleText = selectedOption ? selectedOption.text.toLowerCase() : '';
            
            if (roleText.includes('collecteur')) {
                aliasField.style.display = 'block';
                aliasInput.setAttribute('required', 'required');
            } else {
                aliasField.style.display = 'none';
                aliasInput.removeAttribute('required');
            }
        }

        roleSelect.addEventListener('change', toggleAliasField);
        
        // Initial check
        toggleAliasField();
    });
</script>
@endpush
