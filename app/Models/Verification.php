<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Verification extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['query', 'method', 'document_id', 'result', 'ip_hash'];

    public function document(): BelongsTo
    {
        return $this->belongsTo(OfficialDocument::class, 'document_id')->withTrashed();
    }
}
