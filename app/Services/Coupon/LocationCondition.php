<?php

declare(strict_types=1);

namespace App\Services\Coupon;

class LocationCondition implements CouponConditionInterface
{
    public function type(): string
    {
        return self::TYPE_LOCATION;
    }

    public function isSatisfiedBy(array $user, array $condition): bool
    {
        $operator = $condition['operator'] ?? '';
        $values = $condition['values'] ?? null;

        if (! is_array($values) || $values === []) {
            return false;
        }

        $normalizedValues = array_map(
            static fn (mixed $v): string => strtolower((string) $v),
            $values,
        );

        $actual = isset($user['location']) ? strtolower((string) $user['location']) : '';

        return match ($operator) {
            self::OPERATORS_LOGICAL[0] => in_array($actual, $normalizedValues, true),
            self::OPERATORS_LOGICAL[1] => ! in_array($actual, $normalizedValues, true),
            default => false,
        };
    }
}
