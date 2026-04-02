<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class HmacCredential extends Model
{
    protected $table = 'hmac_credentials';
    protected $primaryKey = 'id_hmac_credential';
    protected $fillable = [
        'client_id',
        'secret_key'
    ];
}
