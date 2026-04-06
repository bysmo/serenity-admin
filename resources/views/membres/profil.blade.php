@extends('layouts.membre')

@section('title', 'Mes Infos Personnelles')

@section('content')
<div class="page-header">
    <h1><i class="bi bi-person-circle"></i> Mes Infos Personnelles</h1>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <i class="bi bi-pencil-square"></i> Modifier mes informations
            </div>
            <div class="card-body">
                <form action="{{ route('membre.profil.update') }}" method="POST">
                    @csrf
                    @method('PUT')
                    
                    <div class="mb-3">
                        <label for="numero" class="form-label">Numéro de membre</label>
                        <input type="text" 
                               class="form-control" 
                               id="numero" 
                               value="{{ $membre->numero }}" 
                               disabled>
                        <small class="text-muted">Le numéro de membre ne peut pas être modifié</small>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="nom" class="form-label">Nom</label>
                            <input type="text" 
                                   class="form-control" 
                                   id="nom" 
                                   value="{{ $membre->nom }}" 
                                   disabled>
                            <small class="text-muted">Le nom ne peut pas être modifié</small>
                        </div>
                        <div class="col-md-6">
                            <label for="prenom" class="form-label">Prénom</label>
                            <input type="text" 
                                   class="form-control" 
                                   id="prenom" 
                                   value="{{ $membre->prenom }}" 
                                   disabled>
                            <small class="text-muted">Le prénom ne peut pas être modifié</small>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                        <input type="email" 
                               class="form-control @error('email') is-invalid @enderror" 
                               id="email" 
                               name="email" 
                               value="{{ old('email', $membre->email) }}" 
                               required>
                        @error('email')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div class="mb-3">
                        <label for="telephone" class="form-label">Téléphone</label>
                        <input type="text" 
                               class="form-control @error('telephone') is-invalid @enderror" 
                               id="telephone" 
                               name="telephone" 
                               value="{{ old('telephone', $membre->telephone) }}">
                        @error('telephone')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div class="mb-3">
                        <label for="adresse" class="form-label">Adresse</label>
                        <textarea class="form-control @error('adresse') is-invalid @enderror" 
                                  id="adresse" 
                                  name="adresse" 
                                  rows="2">{{ old('adresse', $membre->adresse) }}</textarea>
                        @error('adresse')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div class="mb-3">
                        <label for="date_adhesion" class="form-label">Date d'adhésion</label>
                        <input type="text" 
                               class="form-control" 
                               id="date_adhesion" 
                               value="{{ $membre->date_adhesion->format('d/m/Y') }}" 
                               disabled>
                        <small class="text-muted">La date d'adhésion ne peut pas être modifiée</small>
                    </div>
                    
                    <hr class="my-4">
                    
                    <h6 class="mb-3" style="font-weight: 300; color: var(--primary-dark-blue);">Changer mon mot de passe</h6>
                    <p class="text-muted" style="font-size: 0.8rem;">Laissez vide si vous ne souhaitez pas modifier votre mot de passe</p>
                    
                    <div class="mb-3">
                        <label for="password" class="form-label">Nouveau mot de passe</label>
                        <input type="password" 
                               class="form-control @error('password') is-invalid @enderror" 
                               id="password" 
                               name="password">
                        @error('password')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="text-muted">Minimum 6 caractères</small>
                    </div>
                    
                    <div class="mb-3">
                        <label for="password_confirmation" class="form-label">Confirmer le mot de passe</label>
                        <input type="password" 
                               class="form-control" 
                               id="password_confirmation" 
                               name="password_confirmation">
                    </div>
                    
                    <div class="d-flex justify-content-end">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-circle"></i> Enregistrer les modifications
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card mb-3">
            <div class="card-header" style="background-color: var(--primary-dark-blue); color: white; font-weight: 300; font-family: 'Ubuntu', sans-serif;">
                <i class="bi bi-info-circle"></i> À propos
            </div>
            <div class="card-body" style="font-size: 0.75rem; font-weight: 300; font-family: 'Ubuntu', sans-serif;">
                <p><strong>Modification des informations personnelles</strong></p>
                <p>Vous pouvez modifier votre email, téléphone et adresse. Le nom, prénom, numéro de membre et date d'adhésion ne peuvent pas être modifiés.</p>
                
                <p class="mt-3"><strong><i class="bi bi-key"></i> Mot de passe</strong></p>
                <p>Laissez les champs de mot de passe vides si vous ne souhaitez pas le modifier. Utilisez un mot de passe fort pour votre sécurité.</p>
            </div>
        </div>

        {{-- ─── Bloc Parrainage ─────────────────────────────────────── --}}
        @if($parrainageActif)
        <div class="card" style="border-left: 4px solid #fd7e14;">
            <div class="card-header d-flex justify-content-between align-items-center"
                 style="background:linear-gradient(135deg,#fd7e14 0%,#e8590c 100%); color:#fff; font-weight:300; font-family:'Ubuntu',sans-serif; padding:0.5rem 0.75rem;">
                <span style="font-size:0.8rem;"><i class="bi bi-people-fill me-1"></i> Mon Parrainage</span>
                <a href="{{ route('membre.parrainage.index') }}" class="btn btn-sm btn-light" style="font-size:0.65rem; padding:0.15rem 0.4rem;">
                    <i class="bi bi-arrow-right"></i> Détails
                </a>
            </div>
            <div class="card-body" style="padding:0.75rem;">

                {{-- Code --}}
                <p class="mb-1" style="font-size:0.68rem; font-weight:300; font-family:'Ubuntu',sans-serif; color:#666;">
                    Code de parrainage
                </p>
                <div class="input-group input-group-sm mb-3">
                    <input type="text"
                           id="codeParrainageProfil"
                           class="form-control"
                           value="{{ $codeParrainage ?? 'Non généré' }}"
                           readonly
                           style="font-family:monospace; font-size:0.85rem; font-weight:600; letter-spacing:0.1em; color:#fd7e14;">
                    <button class="btn btn-outline-secondary" type="button" onclick="copyCodeProfil()" title="Copier">
                        <i class="bi bi-clipboard" id="copyIconProfil"></i>
                    </button>
                </div>

                {{-- Stats compactes --}}
                <div class="row g-2 mb-2">
                    <div class="col-4">
                        <div class="text-center p-1 rounded" style="background:#fff3e0; border:1px solid #ffcc80;">
                            <div style="font-size:1.1rem; font-weight:600; color:#fd7e14;">{{ $nbFilleuls }}</div>
                            <div style="font-size:0.6rem; color:#888; font-family:'Ubuntu',sans-serif;">Filleul(s)</div>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="text-center p-1 rounded" style="background:#e8f5e9; border:1px solid #a5d6a7;">
                            <div style="font-size:0.85rem; font-weight:600; color:#28a745;">{{ number_format($commissionsDisponibles, 0, ',', ' ') }}</div>
                            <div style="font-size:0.6rem; color:#888; font-family:'Ubuntu',sans-serif;">Dispo (XOF)</div>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="text-center p-1 rounded" style="background:#e3f2fd; border:1px solid #90caf9;">
                            <div style="font-size:0.85rem; font-weight:600; color:#1565c0;">{{ number_format($commissionsTotales, 0, ',', ' ') }}</div>
                            <div style="font-size:0.6rem; color:#888; font-family:'Ubuntu',sans-serif;">Total (XOF)</div>
                        </div>
                    </div>
                </div>

                {{-- Liens rapides --}}
                <div class="d-grid gap-1">
                    <a href="{{ route('membre.parrainage.filleuls') }}"
                       class="btn btn-sm btn-outline-warning"
                       style="font-size:0.7rem; font-weight:300; font-family:'Ubuntu',sans-serif;">
                        <i class="bi bi-people me-1"></i> Voir mes filleuls ({{ $nbFilleuls }})
                    </a>
                    <a href="{{ route('membre.parrainage.commissions') }}"
                       class="btn btn-sm btn-outline-success"
                       style="font-size:0.7rem; font-weight:300; font-family:'Ubuntu',sans-serif;">
                        <i class="bi bi-cash-coin me-1"></i> Voir mes commissions
                    </a>
                </div>
            </div>
        </div>
        @endif
        {{-- ─── Fin Bloc Parrainage ──────────────────────────────────── --}}
    </div>
</div>

@push('scripts')
<script>
function copyCodeProfil() {
    const input = document.getElementById('codeParrainageProfil');
    if (!input) return;
    navigator.clipboard.writeText(input.value).then(function() {
        const icon = document.getElementById('copyIconProfil');
        if (icon) {
            icon.classList.replace('bi-clipboard', 'bi-check2');
            setTimeout(() => icon.classList.replace('bi-check2', 'bi-clipboard'), 1500);
        }
    });
}
</script>
@endpush
@endsection
