<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

use App\Traits\HasChecksum;

class NanoCreditEcheance extends Model
{
    use HasChecksum;
    protected $table = 'nano_credit_echeances';

    protected $fillable = [
        'nano_credit_id',
        'date_echeance',
        'montant',
        'statut',
        'paye_le',
        'checksum',
    ];

    protected $casts = [
        'montant' => \App\Casts\EncryptedDecimal::class,
        'date_echeance' => 'date',
        'paye_le' => 'datetime',
    ];

    public function nanoCredit(): BelongsTo
    {
        return $this->belongsTo(NanoCredit::class);
    }

    public function versements(): HasMany
    {
        return $this->hasMany(NanoCreditVersement::class, 'nano_credit_echeance_id');
    }
}
