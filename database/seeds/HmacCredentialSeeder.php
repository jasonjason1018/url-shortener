<?php

use App\Model\HmacCredential;
use Illuminate\Database\Seeder;

class HmacCredentialSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        HmacCredential::insert([
            [
                'id_hmac_credential' => 1,
                'secret_key' => 'test-secret-key-1',
                'client_id' => 'crm'
            ]
        ]);
    }
}
