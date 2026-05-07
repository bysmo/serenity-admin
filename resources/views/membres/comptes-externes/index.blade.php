@extends('layouts.membre')

@section('title', 'Mes Comptes Externes')

@section('content')
<div class="container-fluid py-4">

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show rounded-3 border-0 shadow-sm" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show rounded-3 border-0 shadow-sm" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row g-4">
        {{-- ── Colonne principale ─────────────────────────────────────────────── --}}
        <div class="col-lg-8">
            <div class="card shadow-sm border-0 rounded-4">
                <div class="card-header bg-white border-0 py-3 px-4 d-flex justify-content-between align-items-center rounded-top-4">
                    <h5 class="card-title mb-0 fw-semibold" style="color: var(--primary-dark-blue);">
                        <i class="bi bi-bank2 me-2 text-primary"></i>Mes Comptes Externes
                    </h5>
                    <button class="btn btn-primary btn-sm rounded-pill px-3 shadow-sm"
                            data-bs-toggle="modal" data-bs-target="#addCompteModal">
                        <i class="bi bi-plus-lg me-1"></i> Ajouter
                    </button>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead style="background: #f0f4f8;">
                                <tr style="font-size: .75rem; font-weight: 600; color: #4a5568; text-transform: uppercase; letter-spacing: .05em;">
                                    <th class="ps-4 py-3">Nom</th>
                                    <th class="py-3">Type</th>
                                    <th class="py-3">Identifiant</th>
                                    <th class="py-3">Pays</th>
                                    <th class="py-3">Statut</th>
                                    <th class="text-end pe-4 py-3">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($comptesExternes as $compte)
                                    <tr>
                                        <td class="ps-4">
                                            <div class="fw-semibold text-dark" style="font-size:.88rem;">{{ $compte->nom }}</div>
                                            @if($compte->description)
                                                <div class="text-muted" style="font-size:.72rem;">{{ Str::limit($compte->description, 40) }}</div>
                                            @endif
                                        </td>
                                        <td>
                                            @if($compte->type_identifiant === 'alias')
                                                <span class="badge rounded-pill" style="background:#dbeafe; color:#1d4ed8; font-size:.7rem;">
                                                    <i class="bi bi-fingerprint me-1"></i>Alias Pi-SPI
                                                </span>
                                            @elseif($compte->type_identifiant === 'telephone')
                                                <span class="badge rounded-pill" style="background:#dcfce7; color:#166534; font-size:.7rem;">
                                                    <i class="bi bi-phone me-1"></i>Téléphone
                                                </span>
                                            @else
                                                <span class="badge rounded-pill" style="background:#fef3c7; color:#92400e; font-size:.7rem;">
                                                    <i class="bi bi-bank me-1"></i>IBAN
                                                </span>
                                            @endif
                                        </td>
                                        <td>
                                            <code class="text-muted" style="font-size:.78rem;">
                                                {{ $compte->identifiant_masque }}
                                            </code>
                                        </td>
                                        <td class="text-muted" style="font-size:.82rem;">{{ strtoupper($compte->pays ?? '—') }}</td>
                                        <td>
                                            @if($compte->is_default)
                                                <span class="badge rounded-pill px-3" style="background:#dcfce7; color:#166534; font-size:.72rem;">
                                                    <i class="bi bi-star-fill me-1"></i>Par défaut
                                                </span>
                                            @else
                                                <span class="badge rounded-pill px-3" style="background:#f1f5f9; color:#64748b; font-size:.72rem;">Secondaire</span>
                                            @endif
                                        </td>
                                        <td class="text-end pe-4">
                                            <div class="btn-group">
                                                {{-- Modifier --}}
                                                <button type="button" class="btn btn-sm btn-outline-primary border-0"
                                                        data-bs-toggle="modal"
                                                        data-bs-target="#editCompteModal{{ $compte->id }}"
                                                        title="Modifier">
                                                    <i class="bi bi-pencil"></i>
                                                </button>

                                                {{-- Définir par défaut --}}
                                                @if(!$compte->is_default)
                                                    <form action="{{ route('membre.comptes-externes.default', $compte) }}"
                                                          method="POST" class="d-inline">
                                                        @csrf
                                                        <button type="submit" class="btn btn-sm btn-outline-success border-0" title="Définir par défaut">
                                                            <i class="bi bi-check-circle"></i>
                                                        </button>
                                                    </form>
                                                @endif

                                                {{-- Supprimer --}}
                                                <form action="{{ route('membre.comptes-externes.destroy', $compte) }}"
                                                      method="POST" class="d-inline"
                                                      onsubmit="return confirm('Supprimer ce compte externe ?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-outline-danger border-0" title="Supprimer">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </form>
                                            </div>

                                            {{-- Modal Modifier --}}
                                            <div class="modal fade" id="editCompteModal{{ $compte->id }}" tabindex="-1" aria-hidden="true">
                                                <div class="modal-dialog modal-dialog-centered">
                                                    <div class="modal-content border-0 shadow-lg rounded-4">
                                                        <div class="modal-header border-0 pb-0">
                                                            <h5 class="modal-title fw-bold">Modifier le compte</h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                        </div>
                                                        <form action="{{ route('membre.comptes-externes.update', $compte) }}" method="POST">
                                                            @csrf
                                                            @method('PUT')
                                                            <div class="modal-body py-3">
                                                                <div class="mb-3">
                                                                    <label class="form-label small fw-semibold">Nom <span class="text-danger">*</span></label>
                                                                    <input type="text" name="nom" class="form-control rounded-pill px-3"
                                                                           value="{{ $compte->nom }}" required maxlength="100">
                                                                </div>
                                                                <div class="mb-3">
                                                                    <label class="form-label small fw-semibold">Description</label>
                                                                    <input type="text" name="description" class="form-control rounded-pill px-3"
                                                                           value="{{ $compte->description }}" maxlength="255"
                                                                           placeholder="Ex: Mon compte Orange Money principal">
                                                                </div>
                                                            </div>
                                                            <div class="modal-footer border-0 pt-0">
                                                                <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Annuler</button>
                                                                <button type="submit" class="btn btn-primary rounded-pill px-4">Enregistrer</button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="py-5 text-center text-muted">
                                            <i class="bi bi-bank2 display-4 d-block mb-3 opacity-25"></i>
                                            <span style="font-size:.85rem;">Aucun compte externe enregistré.</span><br>
                                            <span style="font-size:.75rem;">Ajoutez un alias Pi-SPI, un numéro de téléphone ou un IBAN pour effectuer des paiements.</span>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        {{-- ── Colonne latérale ──────────────────────────────────────────────── --}}
        <div class="col-lg-4">
            {{-- Carte info types --}}
            <div class="card border-0 rounded-4 shadow-sm mb-3" style="background: linear-gradient(135deg, #1e40af, #3b82f6);">
                <div class="card-body p-4 text-white position-relative">
                    <div class="position-absolute top-0 end-0 p-3 opacity-15">
                        <i class="bi bi-info-circle-fill" style="font-size: 3rem;"></i>
                    </div>
                    <h6 class="fw-bold mb-3"><i class="bi bi-lightbulb-fill me-2"></i>Les types de comptes</h6>
                    <ul class="list-unstyled small mb-0">
                        <li class="mb-2">
                            <i class="bi bi-fingerprint me-2 opacity-75"></i>
                            <strong>Alias Pi-SPI</strong> — UUID fourni par votre banque pour les paiements bancaires BCEAO.
                        </li>
                        <li class="mb-2">
                            <i class="bi bi-phone me-2 opacity-75"></i>
                            <strong>Téléphone</strong> — Numéro au format international (+226…) pour Mobile Money.
                        </li>
                        <li class="mb-0">
                            <i class="bi bi-bank me-2 opacity-75"></i>
                            <strong>IBAN</strong> — Compte bancaire international pour opérations bancaires.
                        </li>
                    </ul>
                </div>
            </div>

            {{-- Carte statistiques --}}
            <div class="card border-0 rounded-4 shadow-sm">
                <div class="card-body p-4">
                    <h6 class="fw-bold mb-3 text-muted text-uppercase" style="font-size:.72rem; letter-spacing:.08em;">Résumé</h6>
                    <div class="d-flex gap-3 flex-wrap">
                        <div class="text-center">
                            <div class="fw-bold fs-4 text-primary">{{ $comptesExternes->count() }}</div>
                            <div class="text-muted" style="font-size:.72rem;">Total</div>
                        </div>
                        <div class="text-center">
                            <div class="fw-bold fs-4 text-info">{{ $comptesExternes->where('type_identifiant','alias')->count() }}</div>
                            <div class="text-muted" style="font-size:.72rem;">Alias</div>
                        </div>
                        <div class="text-center">
                            <div class="fw-bold fs-4 text-success">{{ $comptesExternes->where('type_identifiant','telephone')->count() }}</div>
                            <div class="text-muted" style="font-size:.72rem;">Tél.</div>
                        </div>
                        <div class="text-center">
                            <div class="fw-bold fs-4 text-warning">{{ $comptesExternes->where('type_identifiant','iban')->count() }}</div>
                            <div class="text-muted" style="font-size:.72rem;">IBAN</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ═══════════════════════════════════════════════════════════════════════════ --}}
{{-- Modal Ajouter un compte externe                                            --}}
{{-- ═══════════════════════════════════════════════════════════════════════════ --}}
<div class="modal fade" id="addCompteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold">
                    <i class="bi bi-bank2 me-2 text-primary"></i>Ajouter un Compte Externe
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('membre.comptes-externes.store') }}" method="POST" id="addCompteForm">
                @csrf
                <div class="modal-body py-3">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Nom du compte <span class="text-danger">*</span></label>
                            <input type="text" name="nom" class="form-control rounded-pill px-3" required
                                   maxlength="100" placeholder="Ex: Mon Orange Money">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Pays <span class="text-danger">*</span></label>
                            <select name="pays" class="form-select rounded-pill px-3" required>
                                <option value="">— Sélectionner —</option>
                                <option value="BF">Burkina Faso</option>
                                <option value="SN">Sénégal</option>
                                <option value="CI">Côte d'Ivoire</option>
                                <option value="ML">Mali</option>
                                <option value="TG">Togo</option>
                                <option value="BJ">Bénin</option>
                                <option value="NE">Niger</option>
                                <option value="GN">Guinée</option>
                                <option value="CM">Cameroun</option>
                                <option value="FR">France</option>
                                <option value="BE">Belgique</option>
                                <option value="CH">Suisse</option>
                                <option value="OTHER">Autre</option>
                            </select>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label small fw-semibold">Description</label>
                            <input type="text" name="description" class="form-control rounded-pill px-3"
                                   maxlength="255" placeholder="Ex: Compte principal pour paiements Serenity">
                        </div>
                        <div class="col-md-12">
                            <label class="form-label small fw-semibold">Type d'identifiant <span class="text-danger">*</span></label>
                            <div class="d-flex gap-3 flex-wrap mt-1">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="type_identifiant"
                                           id="type_alias" value="alias" checked onchange="onTypeChange(this.value)">
                                    <label class="form-check-label small" for="type_alias">
                                        <i class="bi bi-fingerprint text-primary me-1"></i>Alias Pi-SPI (UUID)
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="type_identifiant"
                                           id="type_telephone" value="telephone" onchange="onTypeChange(this.value)">
                                    <label class="form-check-label small" for="type_telephone">
                                        <i class="bi bi-phone text-success me-1"></i>Numéro de téléphone
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="type_identifiant"
                                           id="type_iban" value="iban" onchange="onTypeChange(this.value)">
                                    <label class="form-check-label small" for="type_iban">
                                        <i class="bi bi-bank text-warning me-1"></i>IBAN
                                    </label>
                                </div>
                            </div>
                        </div>

                        {{-- Champ identifiant dynamique --}}
                        <div class="col-md-12" id="identifiantField">
                            <label class="form-label small fw-semibold" id="identifiantLabel">
                                Alias UUID <span class="text-danger">*</span>
                            </label>
                            <input type="text" name="valeur_identifiant" id="identifiantInput"
                                   class="form-control font-monospace px-3"
                                   required
                                   placeholder="00000000-0000-0000-0000-000000000000"
                                   autocomplete="off">
                            <div class="form-text small" id="identifiantHelp">
                                Format UUID : 8-4-4-4-12 caractères hexadécimaux.
                            </div>
                        </div>

                        <div class="col-md-12">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="is_default" id="isDefault" value="1">
                                <label class="form-check-label small" for="isDefault">
                                    Définir comme compte par défaut
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4">
                        <i class="bi bi-plus-circle me-1"></i>Enregistrer
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
const typeConfig = {
    alias: {
        label: 'Alias UUID <span class="text-danger">*</span>',
        placeholder: '00000000-0000-0000-0000-000000000000',
        help: 'Format UUID : 8-4-4-4-12 caractères hexadécimaux.',
        class: 'font-monospace',
    },
    telephone: {
        label: 'Numéro de téléphone <span class="text-danger">*</span>',
        placeholder: '+22670000000',
        help: 'Format E.164 international. Exemple : +22670000000',
        class: '',
    },
    iban: {
        label: 'IBAN <span class="text-danger">*</span>',
        placeholder: 'FR7612345987650123456789014',
        help: 'IBAN sans espaces. Exemple : FR7630004000031234567890143',
        class: 'font-monospace text-uppercase',
    },
};

function onTypeChange(type) {
    const cfg = typeConfig[type];
    const label = document.getElementById('identifiantLabel');
    const input = document.getElementById('identifiantInput');
    const help  = document.getElementById('identifiantHelp');

    label.innerHTML = cfg.label;
    input.placeholder = cfg.placeholder;
    input.className = 'form-control px-3 ' + cfg.class;
    help.textContent = cfg.help;

    // Forcer uppercase pour IBAN
    if (type === 'iban') {
        input.addEventListener('input', () => { input.value = input.value.toUpperCase(); });
    }
}

// Init on page load
document.addEventListener('DOMContentLoaded', () => {
    const checked = document.querySelector('input[name="type_identifiant"]:checked');
    if (checked) onTypeChange(checked.value);
});
</script>
@endpush
@endsection
