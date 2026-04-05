@extends('layouts.membre')

@section('title', 'Demander un nano crédit')

@section('content')
<style>
    .page-nano-demander,
    .page-nano-demander .card,
    .page-nano-demander .form-label,
    .page-nano-demander .form-control,
    .page-nano-demander .form-select,
    .page-nano-demander .btn,
    .page-nano-demander .card-header,
    .page-nano-demander .card-body,
    .page-nano-demander small,
    .page-nano-demander p {
        font-family: 'Ubuntu', sans-serif !important;
        font-weight: 300 !important;
    }
    .page-nano-demander .card-header {
        font-size: 0.9rem;
        color: var(--primary-dark-blue);
    }
    .page-nano-demander .form-label {
        font-size: 0.85rem;
        color: var(--primary-dark-blue);
    }
    .page-nano-demander .form-control,
    .page-nano-demander .form-select {
        font-size: 0.9rem;
        font-weight: 300;
    }
</style>

<div class="page-header page-nano-demander">
    <h1 style="font-weight: 300; font-family: 'Ubuntu', sans-serif;">
        <i class="bi bi-send"></i> Nouvelle demande de nano crédit
    </h1>
</div>

@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show">{{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

<div class="row page-nano-demander">
    <div class="col-md-8">
        <div class="card border-0 shadow-sm" style="border-radius: 12px;">
            <div class="card-header bg-white border-bottom py-3"><i class="bi bi-info-circle me-2"></i> Votre demande (Palier actuel : {{ $palier->nom }})</div>
            <div class="card-body p-4">
                <p class="small text-muted mb-4">
                    Saisissez le montant souhaité. Ce montant ne peut excéder votre plafond actuel de <strong>{{ number_format($palier->montant_plafond, 0, ',', ' ') }} FCFA</strong>.
                    Une fois validée par l'admin, vous aurez besoin de <strong>{{ $palier->nombre_garants }} garant(s)</strong> pour débloquer les fonds.
                </p>
                
                <form action="{{ route('membre.nano-credits.demander.store') }}" method="POST">
                    @csrf

                    <div class="mb-4">
                        <label for="montant" class="form-label fw-bold">Montant souhaité (XOF) <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="number" step="1" class="form-control form-control-lg @error('montant') is-invalid @enderror" 
                                   id="montant" name="montant" value="{{ old('montant', 1000) }}" required 
                                   min="1000" max="{{ (int) $palier->montant_plafond }}" 
                                   style="font-weight: 400; font-family: 'Ubuntu', sans-serif;">
                            <span class="input-group-text bg-light fw-bold">XOF</span>
                        </div>
                        <div class="d-flex justify-content-between mt-1">
                            <small class="text-muted">Minimum : 1 000 XOF</small>
                            <small class="text-primary fw-bold">Maximum : {{ number_format($palier->montant_plafond, 0, ',', ' ') }} XOF</small>
                        </div>
                        @error('montant')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>

                    <div class="alert alert-info border-0 bg-light p-3 small mb-4">
                        <i class="bi bi-lightbulb me-2"></i>
                        Note : Les fonds seront envoyés sur le numéro de mobile money enregistré dans votre profil après validation des garants et de l'administration.
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary px-4 py-2" style="font-weight: 400;"><i class="bi bi-send me-2"></i> Soumettre la demande</button>
                        <a href="{{ route('membre.nano-credits') }}" class="btn btn-outline-secondary px-4 py-2">Annuler</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm bg-primary text-white" style="border-radius: 12px; height: 100%;">
            <div class="card-body p-4">
                <h6 class="text-uppercase fw-bold mb-4 opacity-75 small" style="letter-spacing: 1px;">Récapitulatif {{ $palier->nom }}</h6>
                
                <div class="mb-3">
                    <label class="small opacity-75 d-block">Plafond d'emprunt</label>
                    <span class="fs-5 fw-bold">{{ number_format($palier->montant_plafond, 0, ',', ' ') }} XOF</span>
                </div>

                <div class="mb-3">
                    <label class="small opacity-75 d-block">Durée totale</label>
                    <span class="fs-5 fw-bold">{{ $palier->duree_jours }} Jours</span>
                </div>

                <div class="mb-3">
                    <label class="small opacity-75 d-block">Taux d'intérêt</label>
                    <span class="fs-5 fw-bold">{{ number_format($palier->taux_interet ?? 0, 1, ',', ' ') }} %</span>
                </div>

                <div class="mb-3">
                    <label class="small opacity-75 d-block">Remboursement</label>
                    <span class="fs-5 fw-bold text-capitalize">{{ $palier->frequence_remboursement_label }}</span>
                </div>

                <hr class="opacity-25 my-4">

                <div class="d-flex align-items-center">
                    <div class="bg-white bg-opacity-25 rounded-circle p-2 me-3">
                        <i class="bi bi-shield-check fs-4"></i>
                    </div>
                    <div>
                        <small class="d-block opacity-75">Sécurité</small>
                        <span class="fw-bold">{{ $palier->nombre_garants }} garants requis</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
