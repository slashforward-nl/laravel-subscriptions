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
            $table->unique(['uid']);
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
