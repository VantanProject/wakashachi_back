<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MenuItem extends Model
{
    protected $fillable = ["merch_id","manu_page_id","width","height","top","left"];

    public function menuPage(): BelongsTo
    {
        return $this->belongsTo(MenuPage::class);
    }
    public function merche(): BelongsTo
    {
        return $this->belongsTo(Merche::class);
    }
}
