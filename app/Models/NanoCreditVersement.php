<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

use App\Traits\HasChecksum;

class NanoCreditVersement extends Model
{
    use HasChecksum;
    protected $table = 'nano_credit_versements';

    protected $fillable = [
        'nano_credit_id',
        'nano_credit_echeance_id',
        'montant',
        'date_versement',
        'mode_paiement',
        'reference',
        'checksum',
    ];

    protected $casts = [
        'montant' => \App\Casts\EncryptedDecimal::class,
        'date_versement' => 'date',
    ];

    public function nanoCredit(): BelongsTo
    {
        return $this->belongsTo(NanoCredit::class);
    }

    public function echeance(): BelongsTo
    {
        return $this->belongsTo(NanoCreditEcheance::class, 'nano_credit_echeance_id');
    }
}
