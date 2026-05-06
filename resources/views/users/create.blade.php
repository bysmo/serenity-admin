@extends('layouts.app')

@section('title', 'Créer un Utilisateur')

@section('content')
<div class="page-header">
    <h1><i class="bi bi-person-plus"></i> Créer un Utilisateur</h1>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <i class="bi bi-file-earmark-plus"></i> Informations de l'utilisateur
            </div>
            <div class="card-body">
                <form action="{{ route('users.store') }}" method="POST">
                    @csrf
                    
                    <div class="mb-3">
                        <label for="name" class="form-label">Nom complet <span class="text-danger">*</span></label>
                        <input type="text" 
                               class="form-control @error('name') is-invalid @enderror" 
                               id="name" 
                               name="name" 
                               value="{{ old('name') }}" 
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
                               value="{{ old('email') }}" 
                               required>
                        @error('email')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div class="mb-3">
                        <label for="password" class="form-label">Mot de passe <span class="text-danger">*</span></label>
                        <input type="password" 
                               class="form-control @error('password') is-invalid @enderror" 
                               id="password" 
                               name="password" 
                               required>
                        @error('password')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="text-muted">Minimum 6 caractères</small>
                    </div>
                    
                    <div class="mb-3">
                        <label for="password_confirmation" class="form-label">Confirmer le mot de passe <span class="text-danger">*</span></label>
                        <input type="password" 
                               class="form-control" 
                               id="password_confirmation" 
                               name="password_confirmation" 
                               required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="role_id" class="form-label">Rôle</label>
                        @if($roles->count() > 0)
                            <select class="form-select @error('role_id') is-invalid @enderror" 
                                    id="role_id" 
                                    name="role_id">
                                <option value="">Sélectionner un rôle</option>
                                @foreach($roles as $role)
                                    <option value="{{ $role->id }}" {{ old('role_id') == $role->id ? 'selected' : '' }}>
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
                               value="{{ old('alias') }}" 
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
                            <i class="bi bi-check-circle"></i> Créer l'utilisateur
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
                <p><strong>Gestion des utilisateurs</strong></p>
                <p>Les utilisateurs sont les administrateurs et gestionnaires de l'application qui ont accès au panneau d'administration.</p>
                
                <p class="mt-3"><strong><i class="bi bi-shield-check"></i> Rôles et Permissions</strong></p>
                <p>Chaque utilisateur peut avoir un ou plusieurs rôles qui définissent ses permissions d'accès aux différentes fonctionnalités de l'application.</p>
                
                <p class="mt-3"><strong><i class="bi bi-key"></i> Sécurité</strong></p>
                <p>Les mots de passe sont cryptés de manière sécurisée. Assurez-vous de créer des mots de passe forts et de ne jamais les partager.</p>
                
                <p class="mt-3"><strong><i class="bi bi-person-gear"></i> Conseils</strong></p>
                <ul class="mb-0" style="padding-left: 1.2rem;">
                    <li>Associez les rôles appropriés à chaque utilisateur</li>
                    <li>Vérifiez que l'email est valide et unique</li>
                    <li>Utilisez des mots de passe complexes</li>
                </ul>
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
