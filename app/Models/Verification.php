<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Verification extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['query', 'method', 'document_id', 'result', 'ip_hash'];
}
