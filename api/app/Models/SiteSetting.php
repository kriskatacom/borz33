<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SiteSetting extends Model
{
    protected $fillable = ['logo_media_file_id'];

    protected function casts(): array
    {
        return ['logo_media_file_id' => 'integer'];
    }

    public function logo(): BelongsTo
    {
        return $this->belongsTo(MediaFile::class, 'logo_media_file_id');
    }
}
