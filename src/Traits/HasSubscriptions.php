<?php

namespace Slashforward\Subscriptions\Traits;

use Carbon\Carbon;
use Slashforward\Subscriptions\Models\Plan;
use Slashforward\Subscriptions\Models\PlanSubscription;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait HasSubscriptions
{
    /**
     * The subscriber may have many subscriptions.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function subscriptions(): MorphMany
    {
        return $this->morphMany(PlanSubscription::class, 'subscriber');
    }

    /**
     * A model may have many active subscriptions.
     *
     * @return bool
     */
    public function hasSubscriptions(): bool
    {
        $subscriptions = \Cache::store('array')->remember("subscription.{$this->id}.hasSubscriptions", 10, function () {
            return $this->activeSubscriptions();
        });

        return $subscriptions && $subscriptions->isNotEmpty();
    }

    /**
     * A model may have many active subscriptions.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function activeSubscriptions(): Collection
    {
        $subscriptions = \Cache::store('array')->remember("subscription.{$this->id}.activeSubscriptions", 10, function () {
            return $this->subscriptions->reject->isInactive();
        });

        return $subscriptions;
    }

    /**
     * Get a subscription by slug.
     *
     * @param string $subscriptionSlug
     *
     * @return \Slashforward\Subscriptions\Models\PlanSubscription|null
     */
    public function subscription(string $uid): ?PlanSubscription
    {
        $subscription = \Cache::store('array')->remember("subscription.{$this->id}.{$uid}", 10, function () use ($uid) {

            $plan_subscription_table = config('subscriptions.tables.plan_subscriptions');
            $plan_table = config('subscriptions.tables.plans');
            
            return $this->subscriptions()->join(
                \DB::raw("{$plan_table} a"), function($join) use($uid, $plan_subscription_table) {
                    $join->on('a.id', '=', \DB::raw("{$plan_subscription_table}.plan_id"));
                    $join->where('a.uid', $uid);
                })
                ->with('plan')
                ->select("{$plan_subscription_table}.*")
                ->first();

        });

        return $subscription;
    }

    public function subscribedPlans(): Collection
    {
        $plans = \Cache::store('array')->remember("subscription.{$this->subscriber_id}.subscribedPlans", 10, function () {
        
            $planIds = $this->activeSubscriptions()->pluck('plan_id')->unique();

            return Plan::whereIn('id', $planIds)->get();

        });

        return $plans;
    }

    /**
     * Check if the subscriber subscribed to the given plan.
     *
     * @param string $id
     *
     * @return bool
     */
    public function subscribedTo($uid): bool
    {
        $subscription = $this->subscription($uid);

        return $subscription && $subscription->isActive();
    }

    /**
     * Subscribe subscriber to a new plan.
     *
     * @param string                            $subscription
     * @param \Slashforward\Subscriptions\Models\Plan $plan
     * @param \Carbon\Carbon|null               $startDate
     *
     * @return \Slashforward\Subscriptions\Models\PlanSubscription
     */
    public function subscribeTo(string $uid, Carbon $startDate = null): PlanSubscription
    {
        $plan = Plan::where('uid', $uid);

        if (is_null($plan)) {
            throw new \Exception("Plan not found for subscription");
        }

        $startDate = $startDate ?? Carbon::now();

        if ( !$plan->invoice_period && !$plan->trial_period ) {
            throw new \Exception("Subscription to this plan will end immediately. No invoice or trial period set.");
        }

        $trialEndDate = $plan->trial_period ? 
                            $startDate->copy()->add($plan->trial_period, $plan->trial_interval) :
                            null;
        
        $endDate = (!$trialEndDate) ?
                        $startDate->copy()->add($plan->invoice_period, $plan->invoice_interval) :
                        $trialEndDate;

        return $this->subscriptions()->create([
            'plan_id' => $plan->id,
            'trial_ends_at' => $trialEndDate,
            'starts_at' => $startDate,
            'ends_at' => $endDate,
        ]);
    }
}
