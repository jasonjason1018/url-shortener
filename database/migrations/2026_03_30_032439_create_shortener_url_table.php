<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateShortenerUrlTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('short_url', function (Blueprint $table) {
            $table->id('id_short_url');
            $table->text('origin_url');
            $table->string('code', 50);
            $table->string('source', 50)
                ->comment('來源');
            $table->timestamps();
            $table->index('code', 'Idx_shortener_url_code_index');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('shortener_url');
    }
}
