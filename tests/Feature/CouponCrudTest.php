<?php

declare(strict_types=1);

use App\Models\Coupon;
use App\Models\User;
use App\Services\Coupon\CouponConditionInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function validCouponPayload(array $overrides = []): array
{
    return array_merge([
        'name' => 'Spring Sale',
        'description' => 'Gold members only',
        'code' => 'SPRING-2026',
        'is_active' => true,
        'valid_from' => '2026-04-01',
        'valid_to' => '2026-04-30',
        'discount_amount' => 10,
        'rules' => json_encode([
            'conditions' => [
                ['type' => CouponConditionInterface::TYPE_TIER, 'operator' => 'eq', 'value' => 'gold'],
            ],
        ], JSON_THROW_ON_ERROR),
        'actions' => json_encode([
            'type' => 'discount',
            'mode' => 'fixed',
            'amount' => 10,
        ], JSON_THROW_ON_ERROR),
    ], $overrides);
}

test('authenticated user can create a coupon', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->post(route('coupons.store'), validCouponPayload());

    $coupon = Coupon::query()->where('code', 'SPRING-2026')->firstOrFail();

    $response->assertRedirect(route('coupons.edit', $coupon));
    expect($coupon->name)->toBe('Spring Sale');
    expect($coupon->rules)->toBeArray();
    expect($coupon->actions)->toBeArray();
});

test('authenticated user can update a coupon', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    $coupon = Coupon::create([
        'name' => 'Old name',
        'description' => null,
        'is_active' => true,
        'valid_from' => null,
        'valid_to' => null,
        'code' => 'OLD-2026',
        'rules' => ['conditions' => []],
        'actions' => ['type' => 'discount', 'mode' => 'fixed', 'amount' => 5],
        'discount_amount' => 5,
    ]);

    $response = $this->put(route('coupons.update', $coupon), validCouponPayload([
        'name' => 'New name',
        'code' => 'OLD-2026',
    ]));

    $response->assertRedirect();
    $coupon->refresh();

    expect($coupon->name)->toBe('New name');
});

test('code must be unique', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    Coupon::create([
        'name' => 'Existing',
        'description' => null,
        'is_active' => true,
        'valid_from' => null,
        'valid_to' => null,
        'code' => 'DUP-2026',
        'rules' => ['conditions' => []],
        'actions' => ['type' => 'discount', 'mode' => 'fixed', 'amount' => 5],
        'discount_amount' => 5,
    ]);

    $response = $this->post(route('coupons.store'), validCouponPayload([
        'code' => 'DUP-2026',
    ]));

    $response->assertSessionHasErrors(['code']);
});

test('authenticated user can view coupons index', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    Coupon::create([
        'name' => 'Index coupon',
        'description' => null,
        'is_active' => true,
        'valid_from' => null,
        'valid_to' => null,
        'code' => 'INDEX-2026',
        'rules' => ['conditions' => []],
        'actions' => ['type' => 'discount', 'mode' => 'fixed', 'amount' => 5],
        'discount_amount' => 5,
    ]);

    $this->get(route('coupons.index'))->assertOk();
});

test('authenticated user can delete a coupon', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    $coupon = Coupon::create([
        'name' => 'Delete me',
        'description' => null,
        'is_active' => true,
        'valid_from' => null,
        'valid_to' => null,
        'code' => 'DEL-2026',
        'rules' => ['conditions' => []],
        'actions' => ['type' => 'discount', 'amount' => 5],
        'discount_amount' => 5,
    ]);

    $this->delete(route('coupons.destroy', $coupon))
        ->assertRedirect(route('coupons.index'));

    expect(Coupon::query()->whereKey($coupon->id)->exists())->toBeFalse();
});

test('cannot create coupon with more than one tier condition', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->post(route('coupons.store'), validCouponPayload([
        'rules' => json_encode([
            'conditions' => [
                ['type' => CouponConditionInterface::TYPE_TIER, 'operator' => 'eq', 'value' => 'gold'],
                ['type' => CouponConditionInterface::TYPE_TIER, 'operator' => 'eq', 'value' => 'silver'],
            ],
        ], JSON_THROW_ON_ERROR),
    ]));

    $response->assertSessionHasErrors(['rules.conditions.1.type']);
});

test('cannot create coupon with three location conditions', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->post(route('coupons.store'), validCouponPayload([
        'rules' => json_encode([
            'conditions' => [
                ['type' => CouponConditionInterface::TYPE_LOCATION, 'operator' => 'in', 'values' => ['US']],
                ['type' => CouponConditionInterface::TYPE_LOCATION, 'operator' => 'not_in', 'values' => ['CA']],
                ['type' => CouponConditionInterface::TYPE_LOCATION, 'operator' => 'in', 'values' => ['UK']],
            ],
        ], JSON_THROW_ON_ERROR),
    ]));

    $response->assertSessionHasErrors(['rules.conditions.2.type']);
});

test('cannot create coupon with two location conditions using same operator', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->post(route('coupons.store'), validCouponPayload([
        'rules' => json_encode([
            'conditions' => [
                ['type' => CouponConditionInterface::TYPE_LOCATION, 'operator' => 'in', 'values' => ['US']],
                ['type' => CouponConditionInterface::TYPE_LOCATION, 'operator' => 'in', 'values' => ['CA']],
            ],
        ], JSON_THROW_ON_ERROR),
    ]));

    $response->assertSessionHasErrors([
        'rules.conditions.0.operator',
        'rules.conditions.1.operator',
    ]);
});

test('cannot create coupon with multiple spend conditions using different window_days', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->post(route('coupons.store'), validCouponPayload([
        'rules' => json_encode([
            'conditions' => [
                ['type' => CouponConditionInterface::TYPE_SPEND, 'operator' => 'gt', 'amount' => 200, 'window_days' => 30],
                ['type' => CouponConditionInterface::TYPE_SPEND, 'operator' => 'lte', 'amount' => 500, 'window_days' => 45],
            ],
        ], JSON_THROW_ON_ERROR),
    ]));

    $response->assertSessionHasErrors(['rules.conditions.1.window_days']);
});
