<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Document extends Model
{
    protected $fillable = ['workspace_id', 'title', 'version'];

    public function versions(): HasMany
    {
        return $this->hasMany(DocumentVersion::class)->orderByDesc('number');
    }

    public function passages(): HasMany
    {
        return $this->hasMany(Passage::class)->orderBy('position');
    }
}
