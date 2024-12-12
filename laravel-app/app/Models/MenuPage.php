<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MenuPage extends Model
{
    protected $fillable = ["menu_id","count"];

    public function menu(): BelongsTo
    {
        return $this->belongsTo(Menu::class);
    }
    
    public function menuItems(): HasMany
    {
        return $this->hasMany(MenuItem::class);
    }
}
