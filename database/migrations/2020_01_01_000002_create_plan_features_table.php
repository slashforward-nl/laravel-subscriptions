<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePlanFeaturesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create(config('subscriptions.tables.plan_features'), function (Blueprint $table) {
            // Columns
            $table->bigIncrements('id');
            $table->foreignId('plan_id');
            $table->string('uid');
            $table->string('name');
            $table->string('value');
            $table->enum('type', ['bool', 'usage', 'value'])->default('bool');
            $table->smallInteger('resettable_period')->unsigned()->default(0);
            $table->enum('resettable_interval', ['day', 'month', 'year'])->default('month');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->unique(['plan_id', 'uid']);
            $table->foreign('plan_id')->references('id')->on(config('subscriptions.tables.plans'))
                  ->onDelete('cascade')->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists(config('subscriptions.tables.plan_features'));
    }
}
