<?php

declare(strict_types=1);

namespace App\Services\Coupon;

/**
 * Evaluates coupon eligibility rules against a user payload.
 *
 * Rule shape: `{ "conditions": [ ... ] }` — all conditions are combined with AND.
 * Empty `conditions` yields true (no restrictions).
 * Unknown condition `type` yields false (fail-safe).
 */
class CouponRuleEvaluator
{
    /**
     * @var array<string, CouponConditionInterface>
     */
    private array $strategies;

    /**
     * @param  iterable<int, CouponConditionInterface>  $strategies
     */
    public function __construct(iterable $strategies = [])
    {
        $map = [];

        foreach ($strategies as $strategy) {
            $map[$strategy->type()] = $strategy;
        }

        if ($map === []) {
            $map = [
                CouponConditionInterface::TYPE_TIER => new TierCondition,
                CouponConditionInterface::TYPE_SPEND => new SpendCondition,
                CouponConditionInterface::TYPE_LOCATION => new LocationCondition,
            ];
        }

        $this->strategies = $map;
    }

    /**
     * @param  array<string, mixed>  $user
     * @param  array<string, mixed>  $rule
     */
    public function evaluate(array $user, array $rule): bool
    {
        $conditions = array_key_exists('conditions', $rule) ? $rule['conditions'] : [];

        if (! is_array($conditions)) {
            return false;
        }

        if ($conditions === []) {
            return true;
        }

        foreach ($conditions as $condition) {
            if (! is_array($condition)) {
                return false;
            }

            if (! $this->evaluateCondition($user, $condition)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  array<string, mixed>  $user
     * @param  array<string, mixed>  $condition
     */
    private function evaluateCondition(array $user, array $condition): bool
    {
        $type = (string) ($condition['type'] ?? '');
        $strategy = $this->strategies[$type] ?? null;

        if (! $strategy) {
            return false;
        }

        return $strategy->isSatisfiedBy($user, $condition);
    }
}
