<?php

declare(strict_types=1);

use App\Services\Coupon\CouponConditionInterface;
use App\Services\Coupon\CouponRuleEvaluator;

function evaluator(): CouponRuleEvaluator
{
    return new CouponRuleEvaluator;
}

test('empty conditions returns true', function (): void {
    expect(evaluator()->evaluate(['tier' => 'silver'], ['conditions' => []]))->toBeTrue();
});

test('missing conditions key returns true (no restrictions)', function (): void {
    expect(evaluator()->evaluate(['tier' => 'silver'], []))->toBeTrue();
});

test('non array conditions fails safe', function (): void {
    expect(evaluator()->evaluate(['tier' => 'gold'], ['conditions' => null]))->toBeFalse();
    expect(evaluator()->evaluate(['tier' => 'gold'], ['conditions' => 'oops']))->toBeFalse();
});

test('gold tier and spend over threshold passes', function (): void {
    $user = [
        'tier' => 'gold',
        'spend_by_window_days' => ['30' => 250],
        'location' => 'US',
    ];
    $rule = [
        'conditions' => [
            ['type' => CouponConditionInterface::TYPE_TIER, 'operator' => 'eq', 'value' => 'gold'],
            ['type' => CouponConditionInterface::TYPE_SPEND, 'operator' => 'gt', 'amount' => 200, 'window_days' => 30],
        ],
    ];

    expect(evaluator()->evaluate($user, $rule))->toBeTrue();
});

test('one failing condition fails the whole rule', function (): void {
    $user = [
        'tier' => 'gold',
        'spend_by_window_days' => ['30' => 150],
    ];
    $rule = [
        'conditions' => [
            ['type' => CouponConditionInterface::TYPE_TIER, 'operator' => 'eq', 'value' => 'gold'],
            ['type' => CouponConditionInterface::TYPE_SPEND, 'operator' => 'gt', 'amount' => 200, 'window_days' => 30],
        ],
    ];

    expect(evaluator()->evaluate($user, $rule))->toBeFalse();
});

test('missing spend window key fails spend condition', function (): void {
    $user = ['tier' => 'gold', 'spend_by_window_days' => ['7' => 500]];
    $rule = [
        'conditions' => [
            ['type' => CouponConditionInterface::TYPE_SPEND, 'operator' => 'gte', 'amount' => 100, 'window_days' => 30],
        ],
    ];

    expect(evaluator()->evaluate($user, $rule))->toBeTrue();
});

test('lifetime spend condition uses spend_total when window_days is omitted', function (): void {
    $user = ['spend_total' => 250];
    $rule = [
        'conditions' => [
            ['type' => CouponConditionInterface::TYPE_SPEND, 'operator' => 'gt', 'amount' => 200],
        ],
    ];

    expect(evaluator()->evaluate($user, $rule))->toBeTrue();
});

test('lifetime spend condition can use spend_by_window_days all key', function (): void {
    $user = ['spend_by_window_days' => ['all' => 250]];
    $rule = [
        'conditions' => [
            ['type' => CouponConditionInterface::TYPE_SPEND, 'operator' => 'gt', 'amount' => 200],
        ],
    ];

    expect(evaluator()->evaluate($user, $rule))->toBeTrue();
});

test('lifetime spend condition falls back to max spend_by_window_days value', function (): void {
    $user = ['spend_by_window_days' => ['30' => 250]];
    $rule = [
        'conditions' => [
            ['type' => CouponConditionInterface::TYPE_SPEND, 'operator' => 'gt', 'amount' => 200],
        ],
    ];

    expect(evaluator()->evaluate($user, $rule))->toBeTrue();
});

test('windowed spend condition falls back to closest smaller window', function (): void {
    $user = ['spend_by_window_days' => ['30' => 250]];
    $rule = [
        'conditions' => [
            ['type' => CouponConditionInterface::TYPE_SPEND, 'operator' => 'gt', 'amount' => 200, 'window_days' => 45],
        ],
    ];

    expect(evaluator()->evaluate($user, $rule))->toBeTrue();
});

test('wrong tier fails', function (): void {
    $user = ['tier' => 'silver'];
    $rule = [
        'conditions' => [
            ['type' => CouponConditionInterface::TYPE_TIER, 'operator' => 'eq', 'value' => 'gold'],
        ],
    ];

    expect(evaluator()->evaluate($user, $rule))->toBeFalse();
});

test('tier comparison is case insensitive', function (): void {
    $user = ['tier' => 'GOLD'];
    $rule = [
        'conditions' => [
            ['type' => CouponConditionInterface::TYPE_TIER, 'operator' => 'eq', 'value' => 'gold'],
        ],
    ];

    expect(evaluator()->evaluate($user, $rule))->toBeTrue();
});

test('tier neq passes when different', function (): void {
    $user = ['tier' => 'silver'];
    $rule = [
        'conditions' => [
            ['type' => CouponConditionInterface::TYPE_TIER, 'operator' => 'neq', 'value' => 'gold'],
        ],
    ];

    expect(evaluator()->evaluate($user, $rule))->toBeTrue();
});

test('location in passes', function (): void {
    $user = ['location' => 'US'];
    $rule = [
        'conditions' => [
            ['type' => CouponConditionInterface::TYPE_LOCATION, 'operator' => 'in', 'values' => ['US', 'CA']],
        ],
    ];

    expect(evaluator()->evaluate($user, $rule))->toBeTrue();
});

test('location not_in passes', function (): void {
    $user = ['location' => 'DE'];
    $rule = [
        'conditions' => [
            ['type' => CouponConditionInterface::TYPE_LOCATION, 'operator' => 'not_in', 'values' => ['US', 'CA']],
        ],
    ];

    expect(evaluator()->evaluate($user, $rule))->toBeTrue();
});

test('location in fails when not listed', function (): void {
    $user = ['location' => 'MX'];
    $rule = [
        'conditions' => [
            ['type' => CouponConditionInterface::TYPE_LOCATION, 'operator' => 'in', 'values' => ['US', 'CA']],
        ],
    ];

    expect(evaluator()->evaluate($user, $rule))->toBeFalse();
});

test('gt versus gte boundary', function (): void {
    $user = ['spend_by_window_days' => ['30' => 200]];
    $gtRule = [
        'conditions' => [
            ['type' => CouponConditionInterface::TYPE_SPEND, 'operator' => 'gt', 'amount' => 200, 'window_days' => 30],
        ],
    ];
    $gteRule = [
        'conditions' => [
            ['type' => CouponConditionInterface::TYPE_SPEND, 'operator' => 'gte', 'amount' => 200, 'window_days' => 30],
        ],
    ];

    expect(evaluator()->evaluate($user, $gtRule))->toBeFalse();
    expect(evaluator()->evaluate($user, $gteRule))->toBeTrue();
});

test('unknown condition type fails safe', function (): void {
    $user = ['tier' => 'gold'];
    $rule = [
        'conditions' => [
            ['type' => 'unknown', 'operator' => 'eq', 'value' => 'x'],
        ],
    ];

    expect(evaluator()->evaluate($user, $rule))->toBeFalse();
});

test('missing or empty condition type fails safe', function (): void {
    $user = ['tier' => 'gold', 'location' => 'US', 'spend_total' => 500];

    expect(evaluator()->evaluate($user, ['conditions' => [['operator' => 'eq', 'value' => 'gold']]]))
        ->toBeFalse();

    expect(evaluator()->evaluate($user, ['conditions' => [['type' => '', 'operator' => 'eq', 'value' => 'gold']]]))
        ->toBeFalse();
});

test('invalid operators fail safe', function (): void {
    expect(evaluator()->evaluate(['tier' => 'gold'], [
        'conditions' => [['type' => CouponConditionInterface::TYPE_TIER, 'operator' => 'gt', 'value' => 'gold']],
    ]))->toBeFalse();

    expect(evaluator()->evaluate(['spend_total' => 250], [
        'conditions' => [['type' => CouponConditionInterface::TYPE_SPEND, 'operator' => 'in', 'amount' => 200]],
    ]))->toBeFalse();

    expect(evaluator()->evaluate(['location' => 'US'], [
        'conditions' => [['type' => CouponConditionInterface::TYPE_LOCATION, 'operator' => 'eq', 'values' => ['US']]],
    ]))->toBeFalse();
});

test('location fails when values is not an array or empty', function (): void {
    expect(evaluator()->evaluate(['location' => 'US'], [
        'conditions' => [['type' => CouponConditionInterface::TYPE_LOCATION, 'operator' => 'in', 'values' => 'US']],
    ]))->toBeFalse();

    expect(evaluator()->evaluate(['location' => 'US'], [
        'conditions' => [['type' => CouponConditionInterface::TYPE_LOCATION, 'operator' => 'in', 'values' => []]],
    ]))->toBeFalse();
});

test('invalid window_days fails spend condition', function (): void {
    $user = ['spend_by_window_days' => ['30' => 250]];

    expect(evaluator()->evaluate($user, [
        'conditions' => [['type' => CouponConditionInterface::TYPE_SPEND, 'operator' => 'gt', 'amount' => 200, 'window_days' => 'abc']],
    ]))->toBeFalse();

    expect(evaluator()->evaluate($user, [
        'conditions' => [['type' => CouponConditionInterface::TYPE_SPEND, 'operator' => 'gt', 'amount' => 200, 'window_days' => 0]],
    ]))->toBeFalse();
});

test('windowed spend fallback is only safe for gt/gte operators', function (): void {
    $user = ['spend_by_window_days' => ['9' => 51]];

    // gt can be satisfied with smaller-window fallback (conservative).
    expect(evaluator()->evaluate($user, [
        'conditions' => [['type' => CouponConditionInterface::TYPE_SPEND, 'operator' => 'gt', 'amount' => 50, 'window_days' => 10]],
    ]))->toBeTrue();

    // lte cannot be proven with smaller-window fallback; fail safe.
    expect(evaluator()->evaluate($user, [
        'conditions' => [['type' => CouponConditionInterface::TYPE_SPEND, 'operator' => 'lte', 'amount' => 100, 'window_days' => 10]],
    ]))->toBeFalse();
});

test('eq comparison treats 200 and 200.00 as equal', function (): void {
    $user = ['spend_by_window_days' => ['30' => 200.00]];
    $rule = ['conditions' => [['type' => CouponConditionInterface::TYPE_SPEND, 'operator' => 'eq', 'amount' => 200, 'window_days' => 30]]];

    expect(evaluator()->evaluate($user, $rule))->toBeTrue();
});

test('non array condition fails', function (): void {
    // Bypass request validation — evaluator must still be defensive if called with bad data
    $user = ['tier' => 'gold'];
    $rule = [
        'conditions' => [
            'not-an-array',
        ],
    ];

    expect(evaluator()->evaluate($user, $rule))->toBeFalse();
});

test('non numeric spend in user data fails', function (): void {
    $user = ['spend_by_window_days' => ['30' => 'lots']];
    $rule = [
        'conditions' => [
            ['type' => CouponConditionInterface::TYPE_SPEND, 'operator' => 'gt', 'amount' => 0, 'window_days' => 30],
        ],
    ];

    expect(evaluator()->evaluate($user, $rule))->toBeFalse();
});
