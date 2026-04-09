<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditChecksumLog extends Model
{
    protected $table = 'audit_checksum_logs';

    protected $fillable = [
        'is_valid',
        'rows_checked_count',
        'corrupted_count',
        'corrupted_data',
    ];

    protected function casts(): array
    {
        return [
            'is_valid'           => 'boolean',
            'rows_checked_count' => 'integer',
            'corrupted_count'    => 'integer',
            'corrupted_data'     => 'array',
        ];
    }
}
