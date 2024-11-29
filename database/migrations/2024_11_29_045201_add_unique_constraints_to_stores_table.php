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
        Schema::table('stores', function (Blueprint $table) {
            $table->unique('store_name'); // Unique constraint for store_name
            $table->unique('user_id');   // Unique constraint for user_id
        });
    }

    public function down()
    {
        Schema::table('stores', function (Blueprint $table) {
            $table->dropUnique(['store_name']);
            $table->dropUnique(['user_id']);
        });
    }
};
