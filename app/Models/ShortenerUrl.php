<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShortenerUrl extends Model
{
    protected $table = 'shortener_url';
    protected $primaryKey = 'id_shortener_url';

    protected $fillable = [
        'origin_url',
        'code',
        'source'
    ];
}
