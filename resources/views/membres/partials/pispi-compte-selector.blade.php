{{--
    Partial : sélecteur de compte externe pour les modals de paiement Pi-SPI.
    Variables attendues :
        $comptesExternes  — collection de CompteExterne (filtrée ou non)
    Usage:
        @include('membres.partials.pispi-compte-selector')
--}}
@php
    $comptesPiSpiDispo = isset($comptesExternes)
        ? $comptesExternes->whereIn('type_identifiant', ['alias', 'telephone'])
        : collect();
@endphp

<div id="pispiWalletGroup" class="mb-3" style="display: none;">
    <label class="form-label small fw-bold">
        <i class="bi bi-bank2 me-1 text-success"></i>Sélectionnez votre compte externe :
    </label>
    @if($comptesPiSpiDispo->count() > 0)
        <select id="compte_externe_id" class="form-select rounded-pill px-3">
            @foreach($comptesPiSpiDispo as $compte)
                <option value="{{ $compte->id }}" {{ $compte->is_default ? 'selected' : '' }}>
                    @if($compte->type_identifiant === 'alias')
                        <span>🔑</span>
                    @else
                        <span>📱</span>
                    @endif
                    {{ $compte->nom }}
                    ({{ $compte->getIdentifiantMasque() }})
                    {{ $compte->is_default ? '★' : '' }}
                </option>
            @endforeach
        </select>
        <div class="form-text text-muted" style="font-size:.72rem;">
            <i class="bi bi-info-circle me-1"></i>
            Seuls les comptes de type Alias ou Téléphone sont utilisables avec Pi-SPI.
            <a href="{{ route('membre.comptes-externes.index') }}" class="text-primary fw-semibold ms-1">Gérer mes comptes</a>
        </div>
    @else
        <div class="alert alert-warning small py-2 mb-0 rounded-3">
            <i class="bi bi-exclamation-triangle me-2"></i>
            Vous n'avez aucun compte externe compatible Pi-SPI (Alias ou Téléphone).
            <a href="{{ route('membre.comptes-externes.index') }}" class="fw-bold">Ajoutez-en un ici</a>.
        </div>
    @endif
</div>
