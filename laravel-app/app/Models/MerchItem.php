<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MerchItem extends Model
{
    protected $fillable = [
        'merch_id',
        'lang_id',
        'name',
        'detail',
    ];
}
