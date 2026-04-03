@extends('layouts.membre')

@section('title', 'Créer une cagnotte')

@section('content')
<style>
.form-create-cotisation .form-label { font-weight: 300; font-family: 'Ubuntu', sans-serif; font-size: 0.75rem; margin-bottom: 0.35rem; }
.form-create-cotisation .form-control,
.form-create-cotisation .form-select { font-weight: 300; font-family: 'Ubuntu', sans-serif; font-size: 0.8rem; padding: 0.4rem 0.6rem; }
.form-create-cotisation .form-control::placeholder { font-weight: 300; font-family: 'Ubuntu', sans-serif; }
.form-create-cotisation .text-muted.small,
.form-create-cotisation small.text-muted { font-size: 0.7rem; }
</style>
<div class="page-header">
    <h1 style="font-weight: 300; font-family: 'Ubuntu', sans-serif;"><i class="bi bi-plus-circle"></i> Créer une cagnotte</h1>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header" style="font-weight: 300; font-family: 'Ubuntu', sans-serif;">
                <i class="bi bi-info-circle"></i> Informations de la cagnotte
            </div>
            <div class="card-body">
                <p class="text-muted small mb-3" style="font-weight: 300; font-family: 'Ubuntu', sans-serif;">Une caisse sera automatiquement créée pour collecter les fonds. Un code unique sera généré pour que les membres puissent rechercher et demander à adhérer.</p>

                <form action="{{ route('membre.mes-cotisations.store') }}" method="POST" class="form-create-cotisation">
                    @csrf

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="nom" class="form-label">Nom de la cagnotte <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('nom') is-invalid @enderror" id="nom" name="nom" value="{{ old('nom') }}" required>
                            @error('nom')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="tag" class="form-label">Tag</label>
                            <select class="form-select @error('tag') is-invalid @enderror" id="tag" name="tag">
                                <option value="">-- Aucun --</option>
                                @foreach($tags as $tag)
                                    <option value="{{ $tag }}" {{ old('tag') === $tag ? 'selected' : '' }}>{{ $tag }}</option>
                                @endforeach
                            </select>
                            @error('tag')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="visibilite" class="form-label">Visibilité <span class="text-danger">*</span></label>
                            <select class="form-select @error('visibilite') is-invalid @enderror" id="visibilite" name="visibilite" required>
                                <option value="publique" {{ old('visibilite', 'publique') === 'publique' ? 'selected' : '' }}>Public</option>
                                <option value="privee" {{ old('visibilite') === 'privee' ? 'selected' : '' }}>Privé</option>
                            </select>
                            @error('visibilite')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="type" class="form-label">Type <span class="text-danger">*</span></label>
                            <select class="form-select @error('type') is-invalid @enderror" id="type" name="type" required>
                                <option value="reguliere" {{ old('type') === 'reguliere' ? 'selected' : '' }}>Régulière</option>
                                <option value="ponctuelle" {{ old('type') === 'ponctuelle' ? 'selected' : '' }}>Ponctuelle</option>
                                <option value="exceptionnelle" {{ old('type') === 'exceptionnelle' ? 'selected' : '' }}>Exceptionnelle</option>
                            </select>
                            @error('type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="frequence" class="form-label">Fréquence <span class="text-danger">*</span></label>
                            <select class="form-select @error('frequence') is-invalid @enderror" id="frequence" name="frequence" required>
                                <option value="mensuelle" {{ old('frequence') === 'mensuelle' ? 'selected' : '' }}>Mensuelle</option>
                                <option value="trimestrielle" {{ old('frequence') === 'trimestrielle' ? 'selected' : '' }}>Trimestrielle</option>
                                <option value="semestrielle" {{ old('frequence') === 'semestrielle' ? 'selected' : '' }}>Semestrielle</option>
                                <option value="annuelle" {{ old('frequence') === 'annuelle' ? 'selected' : '' }}>Annuelle</option>
                                <option value="unique" {{ old('frequence') === 'unique' ? 'selected' : '' }}>Unique</option>
                            </select>
                            @error('frequence')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="type_montant" class="form-label">Type de montant <span class="text-danger">*</span></label>
                            <select class="form-select @error('type_montant') is-invalid @enderror" id="type_montant" name="type_montant" required>
                                <option value="fixe" {{ old('type_montant', 'fixe') === 'fixe' ? 'selected' : '' }}>Fixe</option>
                                <option value="libre" {{ old('type_montant') === 'libre' ? 'selected' : '' }}>Libre</option>
                            </select>
                            @error('type_montant')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4 mb-3" id="montant-container">
                            <label for="montant" class="form-label">Montant (XOF) <span id="montant-required" class="text-danger">*</span></label>
                            <input type="number" class="form-control @error('montant') is-invalid @enderror" id="montant" name="montant" value="{{ old('montant') }}" min="1" step="1">
                            @error('montant')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description" rows="2">{{ old('description') }}</textarea>
                        @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="d-flex justify-content-between">
                        <a href="{{ route('membre.mes-cotisations') }}" class="btn btn-secondary"><i class="bi bi-arrow-left"></i> Retour</a>
                        <button type="submit" class="btn btn-primary"><i class="bi bi-check-circle"></i> Créer la cagnotte</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card">
            <div class="card-header" style="font-weight: 300; font-family: 'Ubuntu', sans-serif;">
                <i class="bi bi-info-circle"></i> À propos
            </div>
            <div class="card-body">
                <h6 class="mb-3" style="font-weight: 300; font-family: 'Ubuntu', sans-serif; color: var(--primary-dark-blue);">
                    <i class="bi bi-wallet2"></i> Créer une cagnotte
                </h6>
                <p style="font-size: 0.75rem; line-height: 1.5; font-weight: 300; font-family: 'Ubuntu', sans-serif; color: #666;">
                    Une cagnotte permet de collecter des fonds auprès des membres. Une caisse dédiée est créée automatiquement et un code unique permet aux autres de la retrouver et de demander à adhérer.
                </p>

                <h6 class="mt-4 mb-3" style="font-weight: 300; font-family: 'Ubuntu', sans-serif; color: var(--primary-dark-blue);">
                    <i class="bi bi-globe"></i> Public ou Privé
                </h6>
                <ul style="font-size: 0.75rem; line-height: 1.8; font-weight: 300; font-family: 'Ubuntu', sans-serif; color: #666; padding-left: 1.2rem;">
                    <li><strong>Public :</strong> visible par tous les membres ; chacun peut demander l'adhésion.</li>
                    <li><strong>Privé :</strong> accessible uniquement via le code ; les demandes d'adhésion doivent être approuvées par vous (administrateur).</li>
                </ul>

                <h6 class="mt-4 mb-3" style="font-weight: 300; font-family: 'Ubuntu', sans-serif; color: var(--primary-dark-blue);">
                    <i class="bi bi-lightbulb"></i> Type de montant
                </h6>
                <ul style="font-size: 0.75rem; line-height: 1.8; font-weight: 300; font-family: 'Ubuntu', sans-serif; color: #666; padding-left: 1.2rem;">
                    <li><strong>Fixe :</strong> montant identique pour tous</li>
                    <li><strong>Libre :</strong> les membres choisissent le montant</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const typeMontant = document.getElementById('type_montant');
    const montantInput = document.getElementById('montant');
    const montantRequired = document.getElementById('montant-required');
    function toggle() {
        if (typeMontant.value === 'libre') {
            montantInput.removeAttribute('required');
            montantInput.value = '';
            montantRequired.style.display = 'none';
        } else {
            montantInput.setAttribute('required', 'required');
            montantRequired.style.display = 'inline';
        }
    }
    toggle();
    typeMontant.addEventListener('change', toggle);
});
</script>
@endsection
