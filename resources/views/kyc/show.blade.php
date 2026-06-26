@extends('layouts.app')

@section('title', 'KYC - ' . ($kyc->membre->nom_complet ?? 'Client'))

@section('content')
<style>
    .kyc-info-card .card-body,
    .kyc-info-card dl,
    .kyc-info-card dt,
    .kyc-info-card dd {
        font-family: 'Ubuntu', sans-serif !important;
        font-weight: 300 !important;
    }
</style>
<div class="page-header d-flex justify-content-between align-items-center">
    <h1><i class="bi bi-shield-check"></i> Dossier KYC - {{ $kyc->membre->nom_complet ?? 'Client' }}</h1>
    <a href="{{ route('kyc.index') }}" class="btn btn-secondary btn-sm">
        <i class="bi bi-arrow-left"></i> Retour
    </a>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card mb-3 kyc-info-card">
            <div class="card-header">
                <i class="bi bi-info-circle"></i> Informations KYC
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <dl class="row mb-0" style="font-size: 0.8rem; font-weight: 300; font-family: 'Ubuntu', sans-serif;">
                            <dt class="col-sm-5">Client</dt>
                            <dd class="col-sm-7">{{ $kyc->membre->nom_complet }} ({{ $kyc->membre->numero }})</dd>
                            <dt class="col-sm-5">Email</dt>
                            <dd class="col-sm-7">{{ $kyc->membre->email }}</dd>
                            <dt class="col-sm-5">Type de pièce</dt>
                            <dd class="col-sm-7">{{ ucfirst($kyc->type_piece ?? '-') }}</dd>
                            <dt class="col-sm-5">Numéro de pièce</dt>
                            <dd class="col-sm-7">{{ $kyc->numero_piece ?? '-' }}</dd>
                            <dt class="col-sm-5">Date de naissance</dt>
                            <dd class="col-sm-7">{{ $kyc->date_naissance ? $kyc->date_naissance->format('d/m/Y') : '-' }}</dd>
                            <dt class="col-sm-5">Lieu de naissance</dt>
                            <dd class="col-sm-7">{{ $kyc->lieu_naissance ?? '-' }}</dd>
                            <dt class="col-sm-5">Adresse (KYC)</dt>
                            <dd class="col-sm-7">{{ $kyc->adresse_kyc ?? '-' }}</dd>
                        </dl>
                    </div>
                    <div class="col-md-6">
                        <dl class="row mb-0" style="font-size: 0.8rem; font-weight: 300; font-family: 'Ubuntu', sans-serif;">
                            <dt class="col-sm-5">Métier</dt>
                            <dd class="col-sm-7">{{ $kyc->metier ?? '-' }}</dd>
                            <dt class="col-sm-5">Localisation</dt>
                            <dd class="col-sm-7">{{ $kyc->localisation ?? '-' }}</dd>
                            <dt class="col-sm-5">Contact 1</dt>
                            <dd class="col-sm-7">{{ $kyc->contact_1 ?? '-' }}</dd>
                            <dt class="col-sm-5">Contact 2</dt>
                            <dd class="col-sm-7">{{ $kyc->contact_2 ?? '-' }}</dd>
                            <dt class="col-sm-5">Date soumission</dt>
                            <dd class="col-sm-7">{{ $kyc->created_at->format('d/m/Y H:i') }}</dd>
                            <dt class="col-sm-5">Statut</dt>
                            <dd class="col-sm-7">
                                @if($kyc->statut === 'en_attente')
                                    En attente
                                @elseif($kyc->statut === 'valide')
                                    Validé
                                    @if($kyc->validated_at)
                                        <small class="text-muted d-block">le {{ $kyc->validated_at->format('d/m/Y') }}
                                        @if($kyc->validatedByUser)
                                            par {{ $kyc->validatedByUser->name }}
                                        @endif
                                        </small>
                                    @endif
                                @else
                                    Rejeté
                                    @if($kyc->rejected_at)
                                        <small class="text-muted d-block">le {{ $kyc->rejected_at->format('d/m/Y') }}
                                        @if($kyc->rejectedByUser)
                                            par {{ $kyc->rejectedByUser->name }}
                                        @endif
                                        </small>
                                    @endif
                                @endif
                            </dd>
                            @if($kyc->statut === 'rejete' && $kyc->motif_rejet)
                                <dt class="col-sm-5">Motif du rejet</dt>
                                <dd class="col-sm-7"><span class="text-danger">{{ $kyc->motif_rejet }}</span></dd>
                            @endif
                        </dl>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header">
                <i class="bi bi-paperclip"></i> Documents
            </div>
            <div class="card-body">
                @if($kyc->documents->count() > 0)
                    <div class="row">
                        @foreach($kyc->documents as $doc)
                            @php
                                $docUrl = asset('storage/' . $doc->path);
                                $ext = strtolower(pathinfo($doc->nom_original ?? $doc->path, PATHINFO_EXTENSION));
                                $isPdf = ($ext === 'pdf');
                            @endphp
                            <div class="col-md-6 mb-2">
                                <div class="d-flex align-items-center border rounded px-2 py-2" style="font-size: 0.8rem; font-weight: 300;">
                                    <i class="bi bi-file-earmark me-2"></i>
                                    <span class="me-2 flex-grow-1 text-truncate" title="{{ $doc->nom_original }}">
                                        {{ \App\Models\KycDocument::types()[$doc->type] ?? $doc->type }} : {{ $doc->nom_original }}
                                    </span>
                                    <button type="button" class="btn btn-outline-primary btn-sm flex-shrink-0 btn-view-doc" data-url="{{ $docUrl }}" data-type="{{ $isPdf ? 'pdf' : 'image' }}" data-title="{{ \App\Models\KycDocument::types()[$doc->type] ?? $doc->type }}">
                                        <i class="bi bi-eye"></i> Voir
                                    </button>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-muted mb-0" style="font-size: 0.8rem;">Aucun document.</p>
                @endif
            </div>
        </div>
    </div>

    <div class="col-md-4">
        @if($kyc->isEnAttente())
            <div class="card mb-3">
                <div class="card-header">
                    <i class="bi bi-check2-circle"></i> Décision
                </div>
                <div class="card-body">
                    <form action="{{ route('kyc.validate', $kyc) }}" method="POST" class="mb-2">
                        @csrf
                        <button type="submit" class="btn btn-success w-100">
                            <i class="bi bi-check-circle"></i> Valider le KYC
                        </button>
                    </form>
                    <button type="button" class="btn btn-danger w-100" data-bs-toggle="modal" data-bs-target="#rejectModal">
                        <i class="bi bi-x-circle"></i> Rejeter (avec motif)
                    </button>
                </div>
            </div>
        @endif

        <div class="card">
            <div class="card-header">
                <i class="bi bi-info-circle"></i> À propos
            </div>
            <div class="card-body">
                <p style="font-size: 0.75rem; font-weight: 300; color: #666;">
                    Validez le KYC pour autoriser le client à faire une demande de nano crédit. En cas de rejet, indiquez obligatoirement un motif ; le client pourra soumettre à nouveau son dossier.
                </p>
            </div>
        </div>
    </div>
</div>

{{-- Modal rejet avec motif --}}
@if($kyc->isEnAttente())
<div class="modal fade" id="rejectModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('kyc.reject', $kyc) }}" method="POST">
                @csrf
                <div class="modal-header" style="background: var(--primary-dark-blue); color: white;">
                    <h5 class="modal-title" style="font-weight: 300;">Rejeter le KYC</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted small">Le motif du rejet est obligatoire. Il sera communiqué au client.</p>
                    <div class="mb-3">
                        <label for="motif_rejet" class="form-label">Motif du rejet <span class="text-danger">*</span></label>
                        <textarea class="form-control @error('motif_rejet') is-invalid @enderror" id="motif_rejet" name="motif_rejet" rows="4" required placeholder="Ex : Document d'identité illisible, justificatif de domicile non conforme...">{{ old('motif_rejet') }}</textarea>
                        @error('motif_rejet')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-danger">
                        <i class="bi bi-x-circle"></i> Rejeter le KYC
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

{{-- Modal pour afficher le document (sans télécharger) --}}
<div class="modal fade" id="documentModal" tabindex="-1" aria-labelledby="documentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header" style="background: var(--primary-dark-blue); color: white;">
                <h5 class="modal-title" id="documentModalLabel" style="font-weight: 300; font-family: 'Ubuntu', sans-serif;">Document</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <div class="modal-body p-0" style="min-height: 70vh;">
                <div id="documentModalContent" class="d-flex align-items-center justify-content-center bg-light" style="min-height: 70vh;">
                    <div class="text-center text-muted py-5">
                        <i class="bi bi-file-earmark" style="font-size: 3rem;"></i>
                        <p class="mt-2 mb-0">Chargement...</p>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <a id="documentModalDownload" href="#" target="_blank" class="btn btn-outline-primary btn-sm">
                    <i class="bi bi-download"></i> Télécharger
                </a>
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Fermer</button>
            </div>
        </div>
    </div>
</div>

<script>
/**
 * Vérifie que l'URL appartient à la même origine que l'application.
 * Protège contre les injections XSS/open-redirect via data-url.
 */
function isSameOriginUrl(url) {
    try {
        const parsed = new URL(url, window.location.origin);
        return parsed.origin === window.location.origin;
    } catch (e) {
        return false;
    }
}

document.addEventListener('DOMContentLoaded', function() {
    const content = document.getElementById('documentModalContent');
    const modalEl = document.getElementById('documentModal');
    if (!modalEl || !content) return;

    // Réinitialise le contenu du modal à la fermeture (sans innerHTML)
    document.getElementById('documentModal').addEventListener('hidden.bs.modal', function() {
        while (content.firstChild) content.removeChild(content.firstChild);
        const placeholder = document.createElement('div');
        placeholder.className = 'text-center text-muted py-5';
        const icon = document.createElement('i');
        icon.className = 'bi bi-file-earmark';
        icon.style.fontSize = '3rem';
        const p = document.createElement('p');
        p.className = 'mt-2 mb-0';
        p.textContent = 'Chargement...';
        placeholder.appendChild(icon);
        placeholder.appendChild(p);
        content.appendChild(placeholder);
    });

    document.querySelectorAll('.btn-view-doc').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var url   = this.getAttribute('data-url');
            var type  = this.getAttribute('data-type');
            var title = this.getAttribute('data-title') || 'Document';

            // Sécurité : n'autoriser que les URLs de la même origine
            if (!isSameOriginUrl(url)) {
                console.error('URL de document non autorisée :', url);
                return;
            }

            document.getElementById('documentModalLabel').textContent = title;

            const dlLink = document.getElementById('documentModalDownload');
            dlLink.href = url;
            dlLink.style.display = 'inline-block';

            // Vider le conteneur
            while (content.firstChild) content.removeChild(content.firstChild);

            if (type === 'pdf') {
                // Création sécurisée de l'iframe (url validée)
                const iframe = document.createElement('iframe');
                iframe.src   = url + '#toolbar=1';
                iframe.style.cssText = 'width:100%;height:70vh;border:none;';
                iframe.title = 'Document PDF';
                content.appendChild(iframe);
            } else {
                // Création sécurisée de l'image (url validée)
                const img = document.createElement('img');
                img.src   = url;
                img.alt   = 'Document';
                img.style.cssText = 'max-width:100%;max-height:70vh;object-fit:contain;';
                content.appendChild(img);
            }

            new bootstrap.Modal(modalEl).show();
        });
    });
});
</script>
@endsection
