<?php

namespace App\Traits;

use Illuminate\Support\Facades\Log;

trait HasChecksum
{
    /**
     * Hook Eloquent events to automatically manage the checksum.
     */
    public static function bootHasChecksum(): void
    {
        static::saving(function ($model) {
            $model->checksum = $model->calculateChecksum();
        });
    }

    /**
     * Calculate the checksum for the model's current attributes.
     */
    public function calculateChecksum(): string
    {
        // Get all attributes except checksum and timestamps
        $attributes = array_diff_key(
            $this->attributes,
            array_flip(['checksum', 'created_at', 'updated_at', 'deleted_at'])
        );

        // Sort keys to ensure consistent hashing
        ksort($attributes);

        // Convert to JSON string for hashing
        $data = json_encode($attributes);
        $key  = config('app.key');

        return hash_hmac('sha256', $data, $key);
    }

    /**
     * Verify the integrity of the model's data.
     */
    public function verifyChecksum(): bool
    {
        if (empty($this->checksum)) {
            return false;
        }

        return hash_equals($this->checksum, $this->calculateChecksum());
    }
}
