<?php

declare(strict_types=1);

namespace App\Services\Coupon;

class TierCondition implements CouponConditionInterface
{
    public function type(): string
    {
        return self::TYPE_TIER;
    }

    public function isSatisfiedBy(array $user, array $condition): bool
    {
        $operator = $condition['operator'] ?? '';
        $expected = isset($condition['value']) ? strtolower((string) $condition['value']) : '';
        $actual = isset($user['tier']) ? strtolower((string) $user['tier']) : '';

        return match ($operator) {
            self::OPERATORS_IDENTITY[0] => $actual === $expected,
            self::OPERATORS_IDENTITY[1] => $actual !== $expected,
            default => false,
        };
    }
}
