@extends('layouts.membre')

@section('title', 'Sollicitations de Garantie')

@section('content')
<style>
    .sollicitation-card {
        border-radius: 12px;
        border: 1px solid #f0f0f0;
        transition: box-shadow 0.2s;
    }
    .sollicitation-card:hover {
        box-shadow: 0 4px 12px rgba(0,0,0,0.05);
    }
</style>

<div class="page-header mb-4">
    <h1 style="font-weight: 300; font-family: 'Ubuntu', sans-serif;">
        <i class="bi bi-person-plus"></i> Sollicitations en attente
    </h1>
    <p class="text-muted" style="font-weight: 300; font-family: 'Ubuntu', sans-serif;">Répondez aux demandes de garantie reçues.</p>
</div>

@if($sollicitations->count() > 0)
    <div class="row">
        @foreach($sollicitations as $sollicitation)
            @php
                $nanoCredit = $sollicitation->nanoCredit;
                $emprunteur = $nanoCredit->membre;
                $type = $nanoCredit->nanoCreditType;
            @endphp
            <div class="col-md-6 mb-4">
                <div class="card sollicitation-card h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="mb-0 text-primary" style="font-weight: 300;">Demande #{{ $nanoCredit->id }}</h6>
                            <span class="badge bg-warning text-dark rounded-pill">En attente</span>
                        </div>
                        
                        <div class="mb-3">
                            <div class="small text-muted" style="font-size: 0.75rem;">Emprunteur :</div>
                            <div style="font-weight: 300;">{{ $emprunteur->nom_complet }}</div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-6">
                                <div class="small text-muted" style="font-size: 0.75rem;">Type :</div>
                                <div style="font-weight: 300;">{{ $type->nom }}</div>
                            </div>
                            <div class="col-6">
                                <div class="small text-muted" style="font-size: 0.75rem;">Montant :</div>
                                <div style="font-weight: 400; font-size: 1.1rem; color: var(--primary-dark-blue);">{{ number_format($nanoCredit->montant, 0, ',', ' ') }} XOF</div>
                            </div>
                        </div>

                        <div class="d-flex gap-2 mt-4">
                            <form action="{{ route('membre.garant.accepter', $sollicitation) }}" method="POST" class="flex-grow-1">
                                @csrf
                                <button type="submit" class="btn btn-success btn-sm w-100" onclick="return confirm('En acceptant, vous engagez votre solde de tontine comme garantie. Confirmer ?')">
                                    Accepter
                                </button>
                            </form>
                            <button type="button" class="btn btn-outline-danger btn-sm" data-bs-toggle="modal" data-bs-target="#refuseModal{{ $sollicitation->id }}">
                                Refuser
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modal Refus -->
            <div class="modal fade" id="refuseModal{{ $sollicitation->id }}" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <form action="{{ route('membre.garant.refuser', $sollicitation) }}" method="POST">
                            @csrf
                            <div class="modal-header">
                                <h5 class="modal-title" style="font-weight: 300;">Refuser la sollicitation</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <p class="small text-muted">Souhaitez-vous indiquer un motif de refus à l'emprunteur ? (Optionnel)</p>
                                <textarea name="motif_refus" class="form-control" rows="3" placeholder="Ex: Solde insuffisant, trop de garanties en cours..."></textarea>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Annuler</button>
                                <button type="submit" class="btn btn-danger btn-sm">Confirmer le refus</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="mt-4">
        {{ $sollicitations->links() }}
    </div>
@else
    <div class="card text-center py-5">
        <div class="card-body">
            <i class="bi bi-inbox" style="font-size: 3rem; color: #ccc;"></i>
            <h5 class="mt-3" style="font-weight: 300;">Aucune sollicitation en attente</h5>
            <p class="text-muted small">Vous recevrez une notification ici quand un membre vous sollicitera comme garant.</p>
            <a href="{{ route('membre.garant.index') }}" class="btn btn-primary btn-sm mt-3">Retour à l'espace garant</a>
        </div>
    </div>
@endif

@endsection
