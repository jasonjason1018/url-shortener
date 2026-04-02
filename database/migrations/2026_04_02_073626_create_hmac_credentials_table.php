<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateHmacCredentialsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('hmac_credentials', function (Blueprint $table) {
            $table->id('id_hmac_credential');
            $table->string('client_id', 50)
                ->unique('Idx_hmac_credentials_client_id');
            $table->string('secret_key', 50);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('hmac_credentials');
    }
}
