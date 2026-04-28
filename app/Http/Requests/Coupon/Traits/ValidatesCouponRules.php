<?php

declare(strict_types=1);

namespace App\Http\Requests\Coupon\Traits;

use App\Services\Coupon\CouponConditionInterface;
use Illuminate\Support\Collection;
use Illuminate\Validation\Validator;

trait ValidatesCouponRules
{
    /**
     * Validate constraints across the full set of conditions (business rules).
     *
     * @param  array<int, mixed>  $conditions
     */
    private function validateConditionsSet(Validator $validator, string $conditionsPath, array $conditions): void
    {
        $items = collect($conditions)
            ->filter(static fn (mixed $c): bool => is_array($c));

        /** @var Collection<string|null, Collection<int, array<string, mixed>>> $grouped */
        $grouped = $items->groupBy(static fn (array $c): ?string => $c['type'] ?? null);

        $this->checkTierCount(
            $validator,
            $conditionsPath,
            $grouped->get(CouponConditionInterface::TYPE_TIER),
        );

        $this->checkLocationLogic(
            $validator,
            $conditionsPath,
            $grouped->get(CouponConditionInterface::TYPE_LOCATION),
        );

        $this->checkSpendWindowConsistency(
            $validator,
            $conditionsPath,
            $grouped->get(CouponConditionInterface::TYPE_SPEND),
        );
    }

    /**
     * @param  Collection<int, array<string, mixed>>|null  $tiers
     */
    private function checkTierCount(Validator $validator, string $conditionsPath, ?Collection $tiers): void
    {
        if (! $tiers || $tiers->count() <= 1) {
            return;
        }

        $tiers->slice(1)->each(function (array $condition, int $index) use ($validator, $conditionsPath): void {
            $validator->errors()->add(
                "{$conditionsPath}.{$index}.type",
                __('Only one tier condition is allowed.'),
            );
        });
    }

    /**
     * @param  Collection<int, array<string, mixed>>|null  $locations
     */
    private function checkLocationLogic(Validator $validator, string $conditionsPath, ?Collection $locations): void
    {
        if (! $locations || $locations->isEmpty()) {
            return;
        }

        if ($locations->count() > 2) {
            $locations->slice(2)->each(function (array $condition, int $index) use ($validator, $conditionsPath): void {
                $validator->errors()->add(
                    "{$conditionsPath}.{$index}.type",
                    __('No more than two location conditions are allowed.'),
                );
            });
        }

        if ($locations->count() !== 2) {
            return;
        }

        $ops = $locations->mapWithKeys(static function (array $c, int $index): array {
            $op = is_string($c['operator'] ?? null) ? $c['operator'] : null;

            return [$index => $op];
        });

        $op1 = $ops->values()->get(0);
        $op2 = $ops->values()->get(1);

        // Only apply this check when both operators are present and valid (type-level validation handles invalid).
        if ($op1 === null || $op2 === null || $op1 !== $op2) {
            return;
        }

        $ops->keys()->each(function (int $index) use ($validator, $conditionsPath): void {
            $validator->errors()->add(
                "{$conditionsPath}.{$index}.operator",
                __('When two location conditions are present, their operators must differ (in vs not_in).'),
            );
        });
    }

    /**
     * @param  Collection<int, array<string, mixed>>|null  $spends
     */
    private function checkSpendWindowConsistency(Validator $validator, string $conditionsPath, ?Collection $spends): void
    {
        if (! $spends || $spends->count() <= 1) {
            return;
        }

        $windows = $spends->mapWithKeys(static function (array $c, int $index): array {
            return [$index => (array_key_exists('window_days', $c) ? $c['window_days'] : null)];
        });

        $nonNull = $windows->filter(static fn (mixed $v): bool => $v !== null);
        if ($nonNull->isEmpty()) {
            return;
        }

        $first = $nonNull->first();
        $firstInt = filter_var($first, FILTER_VALIDATE_INT) !== false ? (int) $first : null;

        $windows->each(function (mixed $value, int $index) use ($validator, $conditionsPath, $firstInt): void {
            if ($value === null) {
                $validator->errors()->add(
                    "{$conditionsPath}.{$index}.window_days",
                    __('When multiple spend conditions are present and window_days is used, window_days is required for all spend conditions.'),
                );

                return;
            }

            $int = filter_var($value, FILTER_VALIDATE_INT) !== false ? (int) $value : null;
            if ($firstInt !== null && $int !== $firstInt) {
                $validator->errors()->add(
                    "{$conditionsPath}.{$index}.window_days",
                    __('All spend conditions must use the same window_days value.'),
                );
            }
        });
    }

    /**
     * @param  array<string, mixed>  $condition
     */
    private function validateTierCondition(Validator $validator, string $prefix, array $condition): void
    {
        $operator = $condition['operator'] ?? null;

        if (! in_array($operator, CouponConditionInterface::OPERATORS_IDENTITY, true)) {
            $validator->errors()->add("{$prefix}.operator", __('Tier operator must be eq or neq.'));
        }

        if (! isset($condition['value']) || ! is_string($condition['value'])) {
            $validator->errors()->add("{$prefix}.value", __('Tier condition requires a string value.'));
        }
    }

    /**
     * @param  array<string, mixed>  $condition
     */
    private function validateSpendCondition(Validator $validator, string $prefix, array $condition): void
    {
        $operator = $condition['operator'] ?? null;

        if (! in_array($operator, CouponConditionInterface::OPERATORS_COMPARISON, true)) {
            $validator->errors()->add("{$prefix}.operator", __('Spend operator must be gt, gte, lt, lte, or eq.'));
        }

        if (! isset($condition['amount']) || ! is_numeric($condition['amount'])) {
            $validator->errors()->add("{$prefix}.amount", __('Spend condition requires a numeric amount.'));
        }

        if (array_key_exists('window_days', $condition) && $condition['window_days'] !== null) {
            if (filter_var($condition['window_days'], FILTER_VALIDATE_INT) === false || (int) $condition['window_days'] < 1) {
                $validator->errors()->add("{$prefix}.window_days", __('Spend condition window_days must be a positive integer when provided.'));
            }
        }
    }

    /**
     * @param  array<string, mixed>  $condition
     */
    private function validateLocationCondition(Validator $validator, string $prefix, array $condition): void
    {
        $operator = $condition['operator'] ?? null;

        if (! in_array($operator, CouponConditionInterface::OPERATORS_LOGICAL, true)) {
            $validator->errors()->add("{$prefix}.operator", __('Location operator must be in or not_in.'));
        }

        $values = $condition['values'] ?? null;

        if (! is_array($values) || $values === []) {
            $validator->errors()->add("{$prefix}.values", __('Location condition requires a non-empty values array.'));

            return;
        }

        foreach ($values as $i => $value) {
            if (! is_string($value)) {
                $validator->errors()->add("{$prefix}.values.{$i}", __('Each location value must be a string.'));
            }
        }
    }
}
