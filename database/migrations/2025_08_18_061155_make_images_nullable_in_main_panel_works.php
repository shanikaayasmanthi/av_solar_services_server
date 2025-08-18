<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
public function up()
{
    Schema::table('main_panel_works', function (Blueprint $table) {
        $table->string('images')->nullable()->change();
    });
}

public function down()
{
    Schema::table('main_panel_works', function (Blueprint $table) {
        $table->string('images')->nullable(false)->change();
    });
}

};
