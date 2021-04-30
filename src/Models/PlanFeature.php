<?php
namespace Slashforward\Subscriptions\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class PlanFeature extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'plan_id',
        'uid',
        'name',
        'value',
        'type',
        'resettable_period',
        'resettable_interval',
        'is_active',
    ];

    protected $casts = [
        'plan_id' => 'integer',
        'uid' => 'string',
        'name' => 'string',
        'value' => 'string',
        'resettable_period' => 'integer',
        'resettable_interval' => 'string',
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
            config('subscriptions.tables.plan_features')
        );
    }

    /**
     * Scope subscription usage by feature slug.
     *
     * @param \Illuminate\Database\Eloquent\Builder $builder
     * @param string                                $featureSlug
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByFeature(Builder $builder, $feature): Builder
    {
        $feature_table = $this->getTable();

        return 
            (gettype($feature) == "integer" || $feature instanceof \Illuminate\Database\Eloquent\Model) 
            ?
                ($feature instanceof \Illuminate\Database\Eloquent\Model && $feature->exists) ?
                    $builder->where("{$feature_table}.id", $feature->id) : 
                    $builder->where("{$feature_table}.id", $feature) 
            :
                $builder->where("{$feature_table}.uid", $feature);
    }

    /**
     * Get feature's reset date.
     *
     * @param string $dateFrom
     *
     * @return \Carbon\Carbon
     */
    public function getResetDate(Carbon $dateFrom): Carbon
    {
        $endDate = $dateFrom ?? now();

        do {
            $endDate = $endDate->copy()->add($this->resettable_period, $this->resettable_interval);
        }
        while($endDate < now());
        
        return $endDate;
    }
}
