{{-- ─── Sécurité PIN ─────────────────────────────────────── --}}
<hr class="my-4">
<h6 class="mb-3" id="security-pin" style="font-weight: 300; color: var(--primary-dark-blue);">
    <i class="bi bi-shield-lock"></i> Sécurité : Code PIN 
</h6>

@if(!$membre->hasPin())
    {{-- Etape 1: Définir le PIN --}}
    <div class="alert alert-info" style="font-size: 0.85rem;">
        Vous n'avez pas encore configuré votre code PIN à 4 chiffres. Il permet de sécuriser vos transactions (souscriptions, crédits, garanties).
    </div>
    
    <form action="{{ route('membre.pin.setup') }}" method="POST" id="form-setup-pin">
        @csrf
        <div class="row mb-3">
            <div class="col-md-6">
                <label for="pin" class="form-label">Code PIN (4 chiffres)</label>
                <input type="password" 
                       class="form-control @error('pin') is-invalid @enderror" 
                       id="pin" 
                       name="pin"
                       pattern="\d{4}" 
                       maxlength="4"
                       required>
                @error('pin')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <div class="col-md-6">
                <label for="pin_confirmation" class="form-label">Confirmer le PIN</label>
                <input type="password" 
                       class="form-control" 
                       id="pin_confirmation" 
                       name="pin_confirmation"
                       pattern="\d{4}" 
                       maxlength="4"
                       required>
            </div>
        </div>
        <div class="d-flex justify-content-end">
            <button type="submit" class="btn btn-primary btn-sm">Enregistrer le PIN</button>
        </div>
    </form>
@else
    {{-- Le PIN est défini --}}
    
    @if($membre->isPinLocked())
        <div class="alert alert-danger" style="font-size: 0.85rem;">
            <i class="bi bi-exclamation-triangle"></i> Votre code PIN est temporairement verrouillé suite à de trop nombreuses tentatives incorrectes.
        </div>
    @else
        <div class="alert {{ $membre->isPinEnabled() ? 'alert-success' : 'alert-secondary' }}" style="font-size: 0.85rem;">
            Protection PIN : 
            <strong>{{ $membre->isPinEnabled() ? 'ACTIVÉE' : 'DÉSACTIVÉE' }}</strong>
            @if($membre->isPinEnabled())
                <br>
                Mode : <strong>{{ $membre->isPinModeSession() ? 'Session (Valide 5 minutes)' : 'Exigé à chaque opération' }}</strong>
            @endif
        </div>
    @endif

    {{-- Formulaire: Activer/Modifier le Mode --}}
    <div class="card mb-3" style="border: 1px solid #dee2e6; box-shadow: none;">
        <div class="card-body p-3">
            <form action="{{ route('membre.pin.enable') }}" method="POST">
                @csrf
                <div class="row align-items-center">
                    <div class="col-md-4">
                        <label class="form-label mb-0" style="font-size: 0.85rem;">Paramétrage du PIN</label>
                    </div>
                    <div class="col-md-4">
                        <select class="form-select form-select-sm" name="mode">
                            <option value="session" {{ ($membre->pin_mode ?? '') === 'session' ? 'selected' : '' }}>Mode Session 5 minutes</option>
                            <option value="each_time" {{ ($membre->pin_mode ?? '') === 'each_time' ? 'selected' : '' }}>PIN à chaque opération</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <input type="password" class="form-control form-control-sm" name="pin" placeholder="Code PIN" maxlength="4" required>
                    </div>
                    <div class="col-md-2 text-end">
                        <button type="submit" class="btn btn-success btn-sm w-100">
                            {{ $membre->isPinEnabled() ? 'Mettre à jour' : 'Activer' }}
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- Formulaire: Désactiver --}}
    @if($membre->isPinEnabled())
    <div class="card mb-3 border-danger" style="box-shadow: none;">
        <div class="card-body p-3">
            <form action="{{ route('membre.pin.disable') }}" method="POST">
                @csrf
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <span class="text-danger" style="font-size: 0.85rem;">Désactiver la protection : vos transactions ne seront plus protégées par le PIN.</span>
                    </div>
                    <div class="col-md-2">
                        <input type="password" class="form-control form-control-sm" name="pin" placeholder="Code PIN" maxlength="4" required>
                    </div>
                    <div class="col-md-2 text-end">
                        <button type="submit" class="btn btn-outline-danger btn-sm w-100">Désactiver</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    @endif

    {{-- Formulaire: Changer le PIN --}}
    <div class="card" style="box-shadow: none;">
        <div class="card-body p-3">
            <form action="{{ route('membre.pin.change') }}" method="POST">
                @csrf
                <h6 style="font-size: 0.85rem;">Modifier le code PIN</h6>
                <div class="row">
                    <div class="col-md-4">
                        <input type="password" class="form-control form-control-sm" name="old_pin" placeholder="Ancien PIN" maxlength="4" required>
                    </div>
                    <div class="col-md-3">
                        <input type="password" class="form-control form-control-sm" name="pin" placeholder="Nouveau PIN" maxlength="4" required>
                    </div>
                    <div class="col-md-3">
                        <input type="password" class="form-control form-control-sm" name="pin_confirmation" placeholder="Confirmer" maxlength="4" required>
                    </div>
                    <div class="col-md-2 text-end">
                        <button type="submit" class="btn btn-primary btn-sm w-100">Modifier</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endif
