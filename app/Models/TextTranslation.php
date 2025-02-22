<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TextTranslation extends Model
{
    protected $fillable = [
        'menu_item_text_id',
        'language_id',
        'text',
    ];

    public function menuItemText(): BelongsTo
    {
        return $this->belongsTo(MenuItemText::class);
    }
}
