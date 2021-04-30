<?php

declare(strict_types=1);

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePlanSubscriptionFeaturesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create(config('subscriptions.tables.plan_subscription_features'), function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('plan_subscription_id');
            $table->foreignId('plan_feature_plan_id');
            $table->string('value');
            $table->timestamps();

            $table->unique(['plan_subscription_id', 'plan_feature_plan_id'], 'unique_plan_subscription_id_plan_feature_plan_id');

            $table->foreign('plan_subscription_id')->references('id')->on(config('subscriptions.tables.plan_subscriptions'))
                  ->onDelete('cascade')->onUpdate('cascade');
            
            $table->foreign('plan_feature_plan_id')->references('id')->on(config('subscriptions.tables.plan_feature_plan'))
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
        Schema::dropIfExists(config('subscriptions.tables.plan_subscription_features'));
    }
}
