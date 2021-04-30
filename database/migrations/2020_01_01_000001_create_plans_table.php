<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePlansTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create(config('subscriptions.tables.plans'), function (Blueprint $table) {
            // Columns
            $table->bigIncrements('id');
            $table->string('uid');
            $table->string('name');
            $table->string('currency', 3);
            $table->decimal('price')->default('0.00');
            $table->decimal('signup_fee')->default('0.00');
            $table->smallInteger('trial_period')->unsigned()->default(0);
            $table->enum('trial_interval', ['day', 'month', 'year'])->default('day');
            $table->smallInteger('invoice_period')->unsigned()->default(0);
            $table->enum('invoice_interval', ['day', 'month', 'year'])->default('month');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->unique(['uid', 'currency']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists(config('subscriptions.tables.plans'));
    }
}
