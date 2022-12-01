<?php

namespace Slashforward\Subscriptions\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Staudenmeir\EloquentHasManyDeep\HasRelationships;
use Staudenmeir\EloquentHasManyDeep\HasManyDeep;

class PlanSubscription extends Model
{
    use SoftDeletes;
    use HasRelationships;

    protected $fillable = [
        'subscriber_id',
        'subscriber_type',
        'plan_id',
        'trial_ends_at',
        'starts_at',
        'ends_at',
        'canceled_at',
    ];

    /**
     * {@inheritdoc}
     */
    protected $casts = [
        'plan_id' => 'integer',
        'subscriber_id' => 'integer',
        'subscriber_type' => 'string',
        'trial_ends_at' => 'datetime',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'canceled_at' => 'datetime',
        'deleted_at' => 'datetime',
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
            config('subscriptions.tables.plan_subscriptions')
        );
    }

   /**
     * The "booted" method of the model.
     *
     * @return void
     */
    protected static function booted()
    {
        static::created(function ($subscription) {
            $subscription->syncFeatures();
        });
    }

    public function syncFeatures() 
    {
        foreach($this->plan->features as $plan_feature) {
            $found = false;

            foreach($this->features as $subscription_feature) {
                // Feature has same id as parent, check if we need to update the row.
                if ($subscription_feature->feature->id == $plan_feature->id)
                {
                    $subscription_feature->value = $plan_feature->pivot->value;

                    if($subscription_feature->plan_feature_plan_id != $plan_feature->pivot->id)
                    {
                        $subscription_feature->plan_feature_plan_id = $plan_feature->pivot->id;
                    }

                    $subscription_feature->save();
                    
                    $found = true;
                    break;
                }

            }

            if (!$found)
            {
                $this->features()->create([
                    'plan_feature_plan_id' => $plan_feature->pivot->id,
                    'value' => $plan_feature->pivot->value,
                ]);
            }
        }

        // Delete unused features from switching plans.
        foreach($this->features->pluck('plan_feature_plan_id')->diff($this->plan->features->pluck('pivot.id')) as $plan_feature_plan_id)
        {
            $this->features()->where('plan_feature_plan_id', $plan_feature_plan_id)->delete();
        }
    }

    /**
     * Get the plan
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    /**
     * Get the owning subscriber.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo
     */
    public function subscriber(): MorphTo
    {
        return $this->morphTo('subscriber', 'subscriber_type', 'subscriber_id', 'id');
    }

    /**
     * The subscription may have many usage.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function usage(): HasManyDeep
    {
        return $this->hasManyDeep(
            PlanSubscriptionFeatureUsage::class,
            [PlanSubscriptionFeature::class], // Intermediate models, beginning at the far parent (Country).
            [
               'plan_subscription_id', // Foreign key on the "users" table.
               'subscription_feature_id',
            ],
            [
                'id',
                'id',
            ]
        )
        ->withIntermediate(PlanSubscriptionFeature::class, ['id', 'value']);
    }

    /**
     * The subscription may have many usage.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function features(): HasMany
    {
        return $this->hasMany(
            PlanSubscriptionFeature::class
        );
    }

    /**
     * The subscription may have many usage.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    protected function deep_features(): HasManyDeep
    {
        return $this->hasManyDeep(
            PlanFeature::class,
            [PlanSubscriptionFeature::class, PlanFeaturePlan::class], // Intermediate models, beginning at the far parent (Country).
            [
               'plan_subscription_id', // Foreign key on the "users" table.
               'id',
               'id'
            ],
            [
                'id',
                'plan_feature_plan_id',
                'plan_feature_id'
            ]
        )
        ->withIntermediate(PlanSubscriptionFeature::class, ['id', 'value'])
        ->withIntermediate(PlanFeaturePlan::class, ['id', 'value']);
    }

    /**
     * Get bookings of the given subscriber.
     *
     * @param \Illuminate\Database\Eloquent\Builder $builder
     * @param \Illuminate\Database\Eloquent\Model   $subscriber
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOfSubscriber(Builder $builder, Model $subscriber): Builder
    {
        return $builder->where('subscriber_type', $subscriber->getMorphClass())
                        ->where('subscriber_id', $subscriber->id);
    }

    /**
     * Scope subscriptions with ending trial.
     *
     * @param \Illuminate\Database\Eloquent\Builder $builder
     * @param int                                   $dayRange
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeFindEndingTrial(Builder $builder, int $dayRange = 3): Builder
    {
        $from = Carbon::now();
        $to = Carbon::now()->addDays($dayRange);

        return $builder->whereBetween('trial_ends_at', [$from, $to]);
    }

    /**
     * Scope subscriptions with ended trial.
     *
     * @param \Illuminate\Database\Eloquent\Builder $builder
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeFindEndedTrial(Builder $builder): Builder
    {
        return $builder->where('trial_ends_at', '<=', now());
    }

    /**
     * Scope subscriptions with ending periods.
     *
     * @param \Illuminate\Database\Eloquent\Builder $builder
     * @param int                                   $dayRange
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeFindEndingPeriod(Builder $builder, int $dayRange = 3): Builder
    {
        $from = Carbon::now();
        $to = Carbon::now()->addDays($dayRange);

        return $builder->whereBetween('ends_at', [$from, $to]);
    }

    /**
     * Scope subscriptions with ended periods.
     *
     * @param \Illuminate\Database\Eloquent\Builder $builder
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeFindEndedPeriod(Builder $builder): Builder
    {
        return $builder->where('ends_at', '<=', now());
    }

    /**
     * Check if subscription is active.
     *
     * @return bool
     */
    public function isActive(): bool
    {
        return ! $this->isEnded() || $this->isOnTrial();
    }

    // /**
    //  * Check if subscription is inactive.
    //  *
    //  * @return bool
    //  */
    public function isInactive(): bool
    {
        return ! $this->isActive();
    }

    // /**
    //  * Check if subscription is currently on trial.
    //  *
    //  * @return bool
    //  */
    public function isOnTrial(): bool
    {
        return $this->trial_ends_at ? Carbon::now()->lt($this->trial_ends_at) : false;
    }

    // /**
    //  * Check if subscription is canceled.
    //  *
    //  * @return bool
    //  */
    public function isCanceled(): bool
    {
        return $this->canceled_at ? Carbon::now()->gte($this->canceled_at) : false;
    }

    // /**
    //  * Check if subscription period has ended.
    //  *
    //  * @return bool
    //  */
    public function isEnded(): bool
    {
        return $this->ends_at ? Carbon::now()->gte($this->ends_at) : false;
    }

    // /**
    //  * Cancel subscription.
    //  *
    //  * @param bool $immediately
    //  *
    //  * @return $this
    //  */
    public function cancel($immediately = false) : PlanSubscription
    {
        $this->canceled_at = $this->canceled_at ?? Carbon::now();

        if ($immediately) {
            $this->ends_at = $this->canceled_at;
        }

        $this->save();

        return $this;
    }

    // /**
    //  * Uncancel subscription.
    //  *
    //  * @param bool $immediately
    //  *
    //  * @return $this
    //  */
    public function uncancel() : PlanSubscription
    {
        if ($this->isEnded()) {
            throw new LogicException('Unable to uncancel an ended subscription.');
        }

        $this->canceled_at = null;
        $this->save();

        return $this;
    }

    /**
     * Change subscription plan.
     *
     * @param \Slashforward\Subscriptions\Models\Plan $plan
     *
     * @return $this
     */
    public function changeToPlan(string $uid)
    {
        $newPlan = Plan::where('uid', $uid)->first();

        if (is_null($newPlan)) {
            return null;
        }

        // If plans does not have the same billing frequency
        // (e.g., invoice_interval and invoice_period) we will update
        // the billing dates starting today
        if ($this->plan->invoice_interval !== $newPlan->invoice_interval || $this->plan->invoice_period !== $newPlan->invoice_period) {
            $this->setNewPeriod($newPlan->invoice_interval, $newPlan->invoice_period);
        }

        // Attach new plan to subscription
        $this->plan_id = $newPlan->id;
        $this->save();
        $this->load('plan');

        // Sync the features of this plan.
        $this->syncFeatures();

        return $this;
    }

    /**
     * Renew subscription period.
     *
     * @throws \LogicException
     *
     * @return $this
     */
    public function renew()
    {
        // if ($this->isEnded() && $this->isCanceled()) {
        //     throw new \Exception('Unable to renew canceled ended subscription.');
        // }

        $subscription = $this;

        \DB::transaction(function () use ($subscription) {
            // Renew period
            $subscription->setNewPeriod(
                null, 
                null, 
                $subscription->ends_at > now() ? $subscription->ends_at : null
            );
            $subscription->canceled_at = null;
            $subscription->save();

            // Clear cache so we use new subscription instead of old.
            \Cache::store('array')->put(
                "subscription.{$subscription->subscriber_id}.{$subscription->plan->uid}",
                $subscription,
                10
            );

        });

        return $this;
    }

    /**
     * Set new subscription period.
     *
     * @param string $invoice_interval
     * @param int    $invoice_period
     * @param string $start
     *
     * @return $this
     */
    protected function setNewPeriod(?string $invoice_interval = null, ?int $invoice_period = null, ?Carbon $startDate = null)
    {
        $startDate = $endDate = $startDate ?? now();

        if ( is_null($invoice_interval) ) {
            $invoice_interval = $this->plan->invoice_interval;
        }

        if ( is_null($invoice_period) ) {
            $invoice_period = $this->plan->invoice_period;
        }

        // If ended at date is in future we use that as our startdate
        if($this->ends_at > $startDate) {
            $endDate = $this->ends_at;
        }
        
        $endDate = $endDate->copy()->add($invoice_period, $invoice_interval);

        $this->starts_at = $startDate;
        $this->ends_at = $endDate;

        return $this;
    }

    /**
     * Record feature usage.
     *
     * @param string $featureSlug
     * @param int    $uses
     *
     * @return \Slashforward\Subscriptions\Models\PlanSubscriptionFeatureUsage
     */
    public function recordFeatureUsage(string $uid, int $uses = 1, bool $incremental = true): PlanSubscriptionFeatureUsage
    {
        $usage = $this->getFeatureUsage($uid);

        $usage->used = ($incremental ? $usage->used + $uses : $uses);
        $usage->save();

        \Cache::store('array')->put(
            "subscription.{$this->subscription_id}.feature.{$uid}.usage",
            $usage,
            10
        );

        return $usage;
    }

    /**
     * Reduce usage.
     *
     * @param string $featureSlug
     * @param int    $uses
     *
     * @return \Slashforward\Subscriptions\Models\PlanSubscriptionUsage|null
     */
    public function reduceFeatureUsage(string $uid, int $uses = 1): ?PlanSubscriptionFeatureUsage
    {
        return $this->recordFeatureUsage($uid, -1 * abs($uses));
    }

    /**
     * Determine if the feature is toggled to this subscription
     *
     * @param string $featureSlug
     *
     * @return bool
     */
    public function hasFeature(string $uid): bool
    {
        return !is_null($this->getFeature($uid));
    }

    /**
     * Determine if the feature can be used.
     *
     * @param string $featureSlug
     *
     * @return bool
     */
    public function updateFeatureValue(string $uid, string $value)
    {
        // We cant use feature if subscription is ended
        if ( $this->isInactive() ) {
            return false;
        }

        $feature = $this->getFeature($uid);

        if ( ! $feature ) {
            return false;
        }

        if ( $feature->type == 'bool' ) {
            $value = (bool) $value;
        } 
        elseif ( $feature->type == 'usage' ) {
            $value = abs( intval( $value ) );
        }

        $feature->plan_subscription_feature->update([
            'value' => $value
        ]);

        return $feature;
    }


    /**
     * Determine if the feature can be used.
     *
     * @param string $featureSlug
     *
     * @return bool
     */
    public function canUseFeature(string $uid, bool $skipUsage = false): bool
    {
        // We cant use feature if subscription is ended
        if ( $this->isInactive() ) {
            return false;
        }

        $feature = $this->getFeature($uid);

        if ( ! $feature ) {
            return false;
        }

        if ( empty($feature->plan_subscription_feature) ) {
            return false;
        }

        if ($feature->type == 'bool' || $feature->type == "value") {
            return (bool) $feature->plan_subscription_feature->value;
        }

        // Counters can go to 0. You can't use it then. Skip the usage check and return true.
        if ( $skipUsage ) {
            return true;
        }
        
        return $this->getFeatureUsageRemaining($uid) > 0;
    }

    public function getFeatureUsage(string $uid): PlanSubscriptionFeatureUsage
    {
        // log($id);
        $usage = \Cache::store('array')->remember("subscription.{$this->subscription_id}.feature.{$uid}.usage", 10, function () use ($uid) {
            $feature = $this->getFeature($uid);
            
            if ( is_null($feature) ) {
                throw new \Exception("Feature ({$uid}) does not belong to this subscription.");
            }
    
            if ( $feature->type != "usage" ) {
                throw new \Exception("Trying to receive usage of non usage feature");
            }
            
            $usage = $feature->plan_subscription_feature->usage()->with('feature')->first();

            // First time use or expired, we reset or add the usage
            if (! $usage || $usage->expired() )
            {
                $usage = $feature->plan_subscription_feature->usage()->firstOrNew();

                if (! $usage->id) {
                    $usage->used = 0;
                }

                if ($feature->resettable_period) {

                    // Set expiration date when the usage record is new or doesn't have one.
                    if (is_null($usage->valid_until)) {
                        // Set date from subscription creation date so the reset
                        // period match the period specified by the subscription's plan.
                        $usage->valid_until = $feature->getResetDate($this->created_at ?? now());
                    } 
                    elseif ($usage->expired()) {
                        // If the usage record has been expired, let's assign
                        // a new expiration date and reset the uses to zero.
                        $usage->valid_until = $feature->getResetDate($this->created_at ?? now());
                        $usage->used = 0;
                    }
                    
                }

                $usage->save();
                $usage->load('feature');
            }

            return $usage;
        });

        return $usage;
    }

    /**
     * Get how many times the feature has been used.
     *
     * @param string $featureSlug
     *
     * @return int
     */
    public function getFeatureUsageAmount(string $uid): int
    {
        $usage = $this->getFeatureUsage($uid);

        return $usage->used;
    }

    /**
     * Get the available uses.
     *
     * @param string $featureSlug
     *
     * @return int
     */
    public function getFeatureUsageRemaining(string $uid): int
    {
        $usage = $this->getFeatureUsage($uid);

        return ($usage->feature->value - $usage->used);
    }

    /**
     * Get feature value.
     *
     * @param string $featureSlug
     *
     * @return mixed
     */
    public function getFeature(string $uid) : ?PlanFeature
    {
        $feature = \Cache::store('array')->remember("subscription.{$this->subscription_id}.feature.{$uid}", 10, function () use ($uid) {

            return $this->deep_features()->byFeature($uid)->first();

        });

        return $feature;
    }

    /**
     * Get feature value.
     *
     * @param string $uid
     *
     * @return mixed
     */
    public function getFeatureValue($uid)
    {
        $feature = $this->getFeature($uid);

        return $feature->plan_subscription_feature->value ?? null;
    }
}
