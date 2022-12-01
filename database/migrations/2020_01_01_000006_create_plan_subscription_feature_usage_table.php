<?php

declare(strict_types=1);

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePlanSubscriptionFeatureUsageTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create(config('subscriptions.tables.plan_subscription_feature_usage'), function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('subscription_feature_id');
            $table->integer('used')->default(0)->unsigned();
            $table->dateTime('valid_until')->nullable();
            $table->timestamps();

            // $table->unique(['subscription_feature_id']);
            
            $table->foreign('subscription_feature_id')
                    ->references('id')
                    ->on(config('subscriptions.tables.plan_subscription_features'))
                    ->onDelete('cascade')
                    ->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists(config('subscriptions.tables.plan_subscription_feature_usage'));
    }
}
