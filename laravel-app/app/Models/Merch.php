<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Merch extends Model
{
    protected $fillable = [
        'img_url',
        'price',
        'company_id',
    ];
}
