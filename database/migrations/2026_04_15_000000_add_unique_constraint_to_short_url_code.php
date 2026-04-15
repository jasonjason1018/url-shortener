<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddUniqueConstraintToShortUrlCode extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('short_url', function (Blueprint $table) {
            $table->unique('code', 'Uniq_short_url_code');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('short_url', function (Blueprint $table) {
            $table->dropUnique('Uniq_short_url_code');
        });
    }
}
