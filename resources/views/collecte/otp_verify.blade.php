@extends('layouts.app')

@section('title', 'Validation OTP')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-5">
        <div class="card shadow-lg border-0 mt-5">
            <div class="card-header bg-white text-center py-4 border-0">
                <div class="mb-3">
                    <i class="bi bi-shield-lock display-1 text-primary"></i>
                </div>
                <h3 class="fw-bold">Validation de la collecte</h3>
                <p class="text-muted">Un code OTP a été envoyé au membre <strong>{{ $membre->nom_complet }}</strong> par SMS/Email.</p>
            </div>
            <div class="card-body px-5 pb-5">
                <div class="alert alert-info text-center small mb-4">
                    <i class="bi bi-info-circle me-1"></i> Demandez au membre de vous communiquer le code à 6 chiffres qu'il vient de recevoir.
                </div>

                <form action="{{ route('collecte.confirm') }}" method="POST">
                    @csrf
                    <input type="hidden" name="collecte_id" value="{{ $collecte->id }}">
                    
                    <div class="mb-4">
                        <label for="otp_code" class="form-label text-center d-block fw-bold mb-3">Code OTP</label>
                        <input type="text" 
                               name="otp_code" 
                               id="otp_code" 
                               class="form-control form-control-lg text-center fw-bold fs-2" 
                               style="letter-spacing: 10px;"
                               maxlength="6" 
                               placeholder="000000" 
                               required 
                               autofocus>
                    </div>

                    <div class="bg-light p-3 rounded mb-4">
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted small">Transaction</span>
                            <span class="fw-bold small">{{ strtoupper($collecte->type_collecte) }}</span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span class="text-muted small">Montant à encaisser</span>
                            <span class="fw-bold text-primary">{{ number_format($collecte->montant, 0, ',', ' ') }} XOF</span>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary btn-lg w-100 py-3 rounded-pill shadow">
                        <i class="bi bi-check-circle-fill me-2"></i> Confirmer l'encaissement
                    </button>
                    
                    <div class="text-center mt-4">
                        <a href="{{ route('collecte.membre.show', $membre->id) }}" class="text-decoration-none text-muted">
                            <i class="bi bi-x-circle me-1"></i> Annuler la transaction
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const otpInput = document.getElementById('otp_code');
        otpInput.focus();
        
        // Ensure only numbers are entered
        otpInput.addEventListener('input', function() {
            this.value = this.value.replace(/[^0-9]/g, '');
        });
    });
</script>
@endpush
