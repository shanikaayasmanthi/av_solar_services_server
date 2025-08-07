<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('external_customer', function (Blueprint $table) {
            $table->string('email')->nullable()->after('nic');
        });
    }

    public function down()
    {
        Schema::table('external_customer', function (Blueprint $table) {
            $table->dropColumn('email');
        });
    }
};
