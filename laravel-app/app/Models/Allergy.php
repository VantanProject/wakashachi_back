<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Allergy extends Model
{
    protected $fillable = ['name'];

    public function merches(): BelongsToMany
    {
        return $this->belongsToMany(Merch::class);
    }
}
