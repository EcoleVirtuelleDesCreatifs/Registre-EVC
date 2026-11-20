<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentRevocation extends Model
{
    public $timestamps = false;

    protected $fillable = ['document_id', 'reason', 'decision_reference', 'revoked_by', 'revoked_at'];

    protected function casts(): array
    {
        return ['revoked_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'revoked_by');
    }
}
