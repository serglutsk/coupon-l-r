<?php

declare(strict_types=1);

use App\Models\User;
use App\Services\Coupon\CouponConditionInterface;
use App\Services\Coupon\CouponRuleEvaluator;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('guests cannot post validate-rule', function (): void {
    $response = $this->postJson(route('validate-rule'), [
        'user' => ['tier' => 'gold'],
        'rule' => ['conditions' => []],
    ]);

    $response->assertUnauthorized();
});

test('it returns false if rule requires tier and user has no tier', function () {
    $engine = new CouponRuleEvaluator;
    $user = ['location' => 'US'];
    $rule = ['conditions' => [['type' => CouponConditionInterface::TYPE_TIER, 'operator' => 'eq', 'value' => 'gold']]];

    expect($engine->evaluate($user, $rule))->toBeFalse();
});

test('it handles floating point amounts correctly', function () {
    $engine = new CouponRuleEvaluator;
    $user = ['spend_by_window_days' => ['30' => 200.00]];
    $rule = ['conditions' => [['type' => CouponConditionInterface::TYPE_SPEND, 'operator' => 'gt', 'amount' => 199.99]]];

    expect($engine->evaluate($user, $rule))->toBeTrue();
});

test('it strictly evaluates greater than operator', function () {
    $engine = new CouponRuleEvaluator;
    $user = ['spend_total' => 200];
    $rule = ['conditions' => [['type' => CouponConditionInterface::TYPE_SPEND, 'operator' => 'gt', 'amount' => 200]]];

    expect($engine->evaluate($user, $rule))->toBeFalse();
});

test('authenticated user receives isValid true for empty conditions', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->postJson(route('validate-rule'), [
        'user' => ['tier' => 'gold'],
        'rule' => ['conditions' => []],
    ]);

    $response->assertOk()
        ->assertJson(['isValid' => true]);
});

test('authenticated user receives isValid when all conditions pass', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->postJson(route('validate-rule'), [
        'user' => [
            'tier' => 'gold',
            'spend_by_window_days' => ['30' => 300],
        ],
        'rule' => [
            'conditions' => [
                ['type' => CouponConditionInterface::TYPE_TIER, 'operator' => 'eq', 'value' => 'gold'],
                ['type' => CouponConditionInterface::TYPE_SPEND, 'operator' => 'gt', 'amount' => 200, 'window_days' => 30],
            ],
        ],
    ]);

    $response->assertOk()
        ->assertJson(['isValid' => true]);
});

test('authenticated user receives isValid false when a condition fails', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->postJson(route('validate-rule'), [
        'user' => [
            'tier' => 'gold',
            'spend_by_window_days' => ['30' => 100],
        ],
        'rule' => [
            'conditions' => [
                ['type' => CouponConditionInterface::TYPE_SPEND, 'operator' => 'gt', 'amount' => 200, 'window_days' => 30],
            ],
        ],
    ]);

    $response->assertOk()
        ->assertJson(['isValid' => false]);
});

test('validation fails when user is missing', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->postJson(route('validate-rule'), [
        'rule' => ['conditions' => []],
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['user']);
});

test('validation fails when rule is missing', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->postJson(route('validate-rule'), [
        'user' => ['tier' => 'gold'],
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['rule']);
});

test('validation fails for invalid condition type', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->postJson(route('validate-rule'), [
        'user' => ['tier' => 'gold'],
        'rule' => [
            'conditions' => [
                ['type' => 'invalid_type', 'operator' => 'eq', 'value' => 'x'],
            ],
        ],
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['rule.conditions.0.type']);
});

test('validation fails for tier with bad operator', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->postJson(route('validate-rule'), [
        'user' => ['tier' => 'gold'],
        'rule' => [
            'conditions' => [
                ['type' => CouponConditionInterface::TYPE_TIER, 'operator' => 'gt', 'value' => 'gold'],
            ],
        ],
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['rule.conditions.0.operator']);
});

test('authenticated user can visit rule builder page', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    $this->get(route('coupons.create'))->assertOk();
});

test('validation fails when spend amount is not numeric', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->postJson(route('validate-rule'), [
        'user' => ['spend_by_window_days' => ['30' => 100]],
        'rule' => [
            'conditions' => [
                ['type' => CouponConditionInterface::TYPE_SPEND, 'operator' => 'gt', 'amount' => 'not-a-number', 'window_days' => 30],
            ],
        ],
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['rule.conditions.0.amount']);
});

test('validation fails when more than one tier condition is provided', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->postJson(route('validate-rule'), [
        'user' => ['tier' => 'gold'],
        'rule' => [
            'conditions' => [
                ['type' => CouponConditionInterface::TYPE_TIER, 'operator' => 'eq', 'value' => 'gold'],
                ['type' => CouponConditionInterface::TYPE_TIER, 'operator' => 'eq', 'value' => 'silver'],
            ],
        ],
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['rule.conditions.1.type']);
});

test('validation fails when more than two location conditions are provided', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->postJson(route('validate-rule'), [
        'user' => ['location' => 'US'],
        'rule' => [
            'conditions' => [
                ['type' => CouponConditionInterface::TYPE_LOCATION, 'operator' => 'in', 'values' => ['US']],
                ['type' => CouponConditionInterface::TYPE_LOCATION, 'operator' => 'not_in', 'values' => ['CA']],
                ['type' => CouponConditionInterface::TYPE_LOCATION, 'operator' => 'in', 'values' => ['UK']],
            ],
        ],
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['rule.conditions.2.type']);
});

test('validation fails when two location conditions use the same operator', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->postJson(route('validate-rule'), [
        'user' => ['location' => 'US'],
        'rule' => [
            'conditions' => [
                ['type' => CouponConditionInterface::TYPE_LOCATION, 'operator' => 'in', 'values' => ['US']],
                ['type' => CouponConditionInterface::TYPE_LOCATION, 'operator' => 'in', 'values' => ['CA']],
            ],
        ],
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors([
            'rule.conditions.0.operator',
            'rule.conditions.1.operator',
        ]);
});

test('validation fails when multiple spend conditions have mismatched window_days', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->postJson(route('validate-rule'), [
        'user' => ['spend_by_window_days' => ['30' => 250, '45' => 400]],
        'rule' => [
            'conditions' => [
                ['type' => CouponConditionInterface::TYPE_SPEND, 'operator' => 'gt', 'amount' => 200, 'window_days' => 30],
                ['type' => CouponConditionInterface::TYPE_SPEND, 'operator' => 'lte', 'amount' => 500, 'window_days' => 45],
            ],
        ],
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['rule.conditions.1.window_days']);
});

test('validation fails when multiple spend conditions use window_days but one is missing it', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->postJson(route('validate-rule'), [
        'user' => ['spend_by_window_days' => ['30' => 250]],
        'rule' => [
            'conditions' => [
                ['type' => CouponConditionInterface::TYPE_SPEND, 'operator' => 'gt', 'amount' => 200, 'window_days' => 30],
                ['type' => CouponConditionInterface::TYPE_SPEND, 'operator' => 'lte', 'amount' => 500],
            ],
        ],
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['rule.conditions.1.window_days']);
});
