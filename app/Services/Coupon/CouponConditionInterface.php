<?php

declare(strict_types=1);

namespace App\Services\Coupon;

interface CouponConditionInterface
{
    /**
     * Supported condition types.
     */
    public const TYPE_TIER = 'tier';
    public const TYPE_SPEND = 'spend';
    public const TYPE_LOCATION = 'location';

    /**
     * Numeric comparison operators (spend, etc.).
     *
     * @var array<int, string>
     */
    public const OPERATORS_COMPARISON = ['gt', 'gte', 'lt', 'lte', 'eq'];

    /**
     * Logical list membership operators (location, etc.).
     *
     * @var array<int, string>
     */
    public const OPERATORS_LOGICAL = ['in', 'not_in'];

    /**
     * Identity operators (tier, etc.).
     *
     * @var array<int, string>
     */
    public const OPERATORS_IDENTITY = ['eq', 'neq'];

    public function type(): string;

    /**
     * @param  array<string, mixed>  $user
     * @param  array<string, mixed>  $condition
     */
    public function isSatisfiedBy(array $user, array $condition): bool;
}
