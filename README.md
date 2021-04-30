# Slashforward Subscriptions

**Slashforward Subscriptions** is a port of the Rinvex Subscription package. Almost fully rewritten. flexible plans and subscription management system for Laravel, with the required tools to run your SAAS like services efficiently. It's simple architecture, accompanied by powerful underlying to afford solid platform for your business.

## Considerations

- Payments are out of scope for this package.
- You may want to extend some of the core models, in case you need to override the logic behind some helper methods like `renew()`, `cancel()` etc. E.g.: when cancelling a subscription you may want to also cancel the recurring payment attached.


## Installation

1. Install the package via composer:
    ```shell
    composer require slashforward-nl/laravel-subscriptions
    ```

2. Publish resources (migrations and config files):
    ```shell
    php artisan slashforward:publish:subscriptions
    ```

3. Execute migrations via the following command:
    ```shell
    php artisan slashforward:migrate:subscriptions
    ```

4. Done!


## Usage

### Add Subscriptions to User model

**Slashforward Subscriptions** has been specially made for Eloquent and simplicity has been taken very serious as in any other Laravel related aspect. To add Subscription functionality to your User model just use the `\Slashforward\Subscriptions\Traits\HasSubscriptions` trait like this:

```php
namespace App\Models;

use Slashforward\Subscriptions\Traits\HasSubscriptions;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use HasSubscriptions;
}
```

That's it, we only have to use that trait in our User model! Now your users may subscribe to plans.

> **Note:** you can use `HasSubscriptions` trait on any subscriber model, it doesn't have to be the user model, in fact any model will do.

### Create a Plan

```php
use \Slashforward\Subscriptions\Models\Plan;
use \Slashforward\Subscriptions\Models\PlanFeature;

$plan = Plan::create([
    'uid' => 'pro',
    'name' => 'Pro',
    'price' => 9.99,
    'signup_fee' => 1.99,
    'invoice_period' => 1,
    'invoice_interval' => 'month',
    'trial_period' => 15,
    'trial_interval' => 'day',
    'currency' => 'EUR',
]);

// Create multiple plan features at once
$plan->features()->saveMany([
    new PlanFeature(['uid' => 'sms-service', 'name' => 'SMS Repremiuming', 'type' => 'usage', 'value' => 10]),
    new PlanFeature(['uid' => 'custom-welcome-message', 'name' => 'Custom Welcome Message', 'type' => 'bool', 'value' => 1]),
]);

User
public function subscriptions(): MorphMany
public function hasSubscriptions(): bool
public function activeSubscriptions(): Collection
public function subscription($id): ?PlanSubscription
public function subscribedPlans(): Collection
public function subscribedTo($id): bool
public function subscribeTo(Plan $plan, Carbon $startDate = null): PlanSubscription

Plan
public function features(): BelongsToMany
public function subscriptions(): HasMany
public function isFree(): bool
public function isActive(): bool
public function hasTrial(): bool
public function getFeature($id): ?PlanFeature

PlanFeature
public function getResetDate(Carbon $dateFrom): Carbon
public function scopeByFeature(Builder $builder, $feature): Builder

PlanSubscription
public function plan(): BelongsTo
public function subscriber(): MorphTo
public function usage(): HasManyDeep
public function features(): HasMany
public function isActive(): bool
public function isInactive(): bool
public function isOnTrial(): bool
public function isCanceled(): bool

```

### Models

**Slashforward Subscriptions** uses 4 models:

```php
Slashforward\Subscriptions\Models\Plan;
Slashforward\Subscriptions\Models\PlanFeature;
Slashforward\Subscriptions\Models\PlanSubscription;
Slashforward\Subscriptions\Models\PlanSubscriptionUsage;
```

## Changelog

Refer to the [Changelog](CHANGELOG.md) for a full history of the project.


## Support

The following support channels are available at your fingertips:

- [Help on Email](mailto:help@slashforward.nl)

## Contributing & Protocols

Thank you for considering contributing to this project! The contribution guide can be found in [CONTRIBUTING.md](CONTRIBUTING.md).

Bug reports, feature requests, and pull requests are very welcome.

- [Versioning](CONTRIBUTING.md#versioning)
- [Pull Requests](CONTRIBUTING.md#pull-requests)
- [Coding Standards](CONTRIBUTING.md#coding-standards)
- [Feature Requests](CONTRIBUTING.md#feature-requests)
- [Git Flow](CONTRIBUTING.md#git-flow)

## Security Vulnerabilities

If you discover a security vulnerability within this project, please send an e-mail to [help@slashforward.nl](help@slashforward.nl). All security vulnerabilities will be promptly addressed.

## License

This software is released under [The MIT License (MIT)](LICENSE).

(c) 2016-2021 Slashforward LLC, Some rights reserved.
