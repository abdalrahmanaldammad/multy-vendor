<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddOrderStatusToOrderItemsTable extends Migration
{
    public function up()
    {
        Schema::table('order_items', function (Blueprint $table) {
            // Adding the order_status column with enum values
            $table->enum('order_status', ['pending', 'processing', 'completed', 'canceled'])->default('pending');
   
        });
    }

    public function down()
    {
        Schema::table('order_items', function (Blueprint $table) {
            // Dropping the order_status column if the migration is rolled back
            $table->dropColumn('order_status');
        });
    }
}
