<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Passage extends Model
{
    public $timestamps = false;

    protected $fillable = ['workspace_id', 'document_id', 'document_version_id', 'position', 'title', 'body'];
}
