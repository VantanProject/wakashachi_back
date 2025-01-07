<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Merch extends Model
{
    protected $fillable = [
        'img_url',
        'price',
        'company_id',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function allergies(): BelongsToMany
    {
        return $this->belongsToMany(Allergy::class);
    }

    public function menuItemMerch(): HasOne
    {
        return $this->hasOne(MenuItemMerch::class);
    }

    public function merchItems(): HasMany
    {
        return $this->hasMany(MerchItem::class);
    }
}
