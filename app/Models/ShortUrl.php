<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShortUrl extends Model
{
    protected $table = 'short_url';
    protected $primaryKey = 'id_short_url';

    protected $fillable = [
        'origin_url',
        'code',
        'source'
    ];
}
