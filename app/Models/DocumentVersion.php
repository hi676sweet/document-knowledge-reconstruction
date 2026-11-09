<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DocumentVersion extends Model
{
    public $timestamps = false;

    protected $fillable = ['document_id', 'number', 'title', 'content', 'checksum', 'created_at'];

    protected function casts(): array
    {
        return ['created_at' => 'datetime'];
    }
}
