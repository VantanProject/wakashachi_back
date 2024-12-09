<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Menu extends Model
{
    protected $fillable = ["company_id","name"];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
    public function menuPages(): HasMany
    {
        return $this->hasMany(MenuPage::class);
    }
}
