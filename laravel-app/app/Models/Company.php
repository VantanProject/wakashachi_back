<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Company extends Model
{
    protected $fillable = ["name"];

    public function merches(): HasMany
    {
        return $this->hasMany(Merche::class);
    }
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
    public function menus(): HasMany
    {
        return $this->hasMany(Menu::class);
    }
}
