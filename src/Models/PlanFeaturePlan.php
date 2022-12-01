<?php

namespace Slashforward\Subscriptions\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\Pivot;


class PlanFeaturePlan extends Pivot
{
    protected $fillable = [
        'plan_id',
        'plan_feature_id',
        'value',
        'is_active'
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
            config('subscriptions.tables.plan_feature_plan')
        );
    }
}
