<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePlanFeaturePlanTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create(config('subscriptions.tables.plan_feature_plan'), function (Blueprint $table)
        {
            $table->bigIncrements('id');
            $table->foreignId('plan_id');
            $table->foreignId('plan_feature_id');
            $table->string('value');
            $table->boolean('is_active')->default(true);

            $table->unique(['plan_id', 'plan_feature_id']);

            $table->foreign('plan_id')->references('id')->on(config('subscriptions.tables.plans'))
                  ->onDelete('cascade')->onUpdate('cascade');

            $table->foreign('plan_feature_id')->references('id')->on(config('subscriptions.tables.plan_features'))
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
        Schema::dropIfExists(config('subscriptions.tables.plan_feature_plan'));
    }
}
