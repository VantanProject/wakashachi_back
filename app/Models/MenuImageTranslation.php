<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MenuImageTranslation extends Model
{
    protected $fillable = [
        'menu_page_id',
        'merch_id',
        'img_url',
    ];

    public function menuPage(): BelongsTo
    {
        return $this->belongsTo(MenuPage::class);
    }
}
