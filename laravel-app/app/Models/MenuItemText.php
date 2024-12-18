<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class MenuItemText extends Model
{
    protected $fillable = [
        'menu_item_id',
        'text',
        'color',
    ];

    public function menuItem(): HasOne
    {
        return $this->hasOne(MenuItem::class);
    }
}
