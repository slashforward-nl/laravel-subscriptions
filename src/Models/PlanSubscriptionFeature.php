<?php

namespace Slashforward\Subscriptions\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlanSubscriptionFeature extends Model
{
    // use SoftDeletes;
    // use ValidatingTrait;

    /**
     * {@inheritdoc}
     */
    protected $fillable = [
        'plan_subscription_id',
        'plan_feature_plan_id',
        'value',
        // 'valid_until',
    ];

    /**
     * {@inheritdoc}
     */
    protected $casts = [
        'plan_subscription_id' => 'integer',
        'plan_feature_plan_id' => 'integer',
        'value' => 'string',
    ];

    /**
     * Create a new Eloquent model instance.
     *
     * @param array $attributes
     */
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $this->setTable(
            config('subscriptions.tables.plan_subscription_feature')
        );
    }

    /**
     * Subscription usage always belongs to a plan feature.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function feature()
    {
        return $this->hasOneThrough(
            PlanFeature::class, 
            PlanFeaturePlan::class, 
            'id', 
            'id', 
            'plan_feature_plan_id',
            'plan_feature_id'
            // 'feature'
        );
    }

    public function usage()
    {
        return $this->hasOne(
            PlanSubscriptionFeatureUsage::class, 
            'subscription_feature_id',
        );
    }

}
