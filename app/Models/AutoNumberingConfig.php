<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\HasChecksum;

class AutoNumberingConfig extends Model
{
    use HasFactory, HasChecksum;

    protected $fillable = [
        'object_type',
        'description',
        'definition',
        'current_value',
        'is_active',
        'checksum',
    ];

    /**
     * Cast attributes to native types.
     *
     * @var array
     */
    protected $casts = [
        'definition' => 'array',
        'is_active' => 'boolean',
        'current_value' => 'integer',
    ];

    /**
     * Get the next sequence value and increment it atomically.
     */
    public function incrementSequence(): int
    {
        $this->refresh();
        $this->current_value = (int)$this->current_value + 1;
        $this->save();
        return $this->current_value;
    }

    /**
     * Scope for active configurations.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
