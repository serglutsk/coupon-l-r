<?php

declare(strict_types=1);

namespace App\Services\Coupon;

class SpendCondition implements CouponConditionInterface
{
    public function type(): string
    {
        return self::TYPE_SPEND;
    }

    public function isSatisfiedBy(array $user, array $condition): bool
    {
        $operator = $condition['operator'] ?? '';
        $amount = $condition['amount'] ?? null;
        $windowDays = $condition['window_days'] ?? null;

        if (! is_numeric($amount)) {
            return false;
        }

        $spentMeta = $this->resolveUserSpend($user, $windowDays);
        $spent = $spentMeta['spent'] ?? null;
        $isExactWindow = (bool) ($spentMeta['is_exact_window'] ?? true);

        if ($spent === null || ! is_numeric($spent)) {
            return false;
        }

        $spent = (float) $spent;
        $amount = (float) $amount;

        // For windowed spend, only "lower-bound" operators are safe with a smaller-window fallback.
        // Example: if we only know spend for 9 days, we cannot guarantee lte/lt/eq for 10 days.
        if ($windowDays !== null && ! $isExactWindow) {
            if ($operator === self::OPERATORS_COMPARISON[0] || $operator === self::OPERATORS_COMPARISON[1]) {
                // gt / gte: using smaller window is conservative (may produce false negatives, but not false positives).
            } else {
                return false;
            }
        }

        return match ($operator) {
            self::OPERATORS_COMPARISON[0] => $spent > $amount,
            self::OPERATORS_COMPARISON[1] => $spent >= $amount,
            self::OPERATORS_COMPARISON[2] => $spent < $amount,
            self::OPERATORS_COMPARISON[3] => $spent <= $amount,
            self::OPERATORS_COMPARISON[4] => $spent == $amount,
            default => false,
        };
    }

    /**
     * Resolve a user's spend for a given window.
     *
     * If $windowDays is null/absent, treat as lifetime spend and read:
     * - user.spend_total (preferred)
     * - or user.spend_by_window_days['all'] (fallback)
     *
     * If $windowDays is an int, read user.spend_by_window_days[$windowDays],
     * falling back to the closest known window <= requested window.
     *
     * @param  array<string, mixed>  $user
     */
    private function resolveUserSpend(array $user, mixed $windowDays): array
    {
        if ($windowDays === null) {
            if (array_key_exists('spend_total', $user)) {
                return ['spent' => $user['spend_total'], 'is_exact_window' => true];
            }

            $byWindow = $user['spend_by_window_days'] ?? null;
            if (is_array($byWindow) && array_key_exists('all', $byWindow)) {
                return ['spent' => $byWindow['all'], 'is_exact_window' => true];
            }

            if (is_array($byWindow)) {
                $numericValues = [];
                foreach ($byWindow as $key => $value) {
                    if ($key === 'all') {
                        continue;
                    }

                    if (is_numeric($value)) {
                        $numericValues[] = (float) $value;
                    }
                }

                if ($numericValues !== []) {
                    return ['spent' => max($numericValues), 'is_exact_window' => true];
                }
            }

            return ['spent' => null, 'is_exact_window' => true];
        }

        if (filter_var($windowDays, FILTER_VALIDATE_INT) === false) {
            return ['spent' => null, 'is_exact_window' => false];
        }

        $windowInt = (int) $windowDays;
        $byWindow = $user['spend_by_window_days'] ?? [];

        if (! is_array($byWindow)) {
            return ['spent' => null, 'is_exact_window' => false];
        }

        $exact = $byWindow[$windowInt] ?? $byWindow[(string) $windowInt] ?? null;
        if ($exact !== null) {
            return ['spent' => $exact, 'is_exact_window' => true];
        }

        $bestWindow = null;
        $bestValue = null;

        foreach ($byWindow as $key => $value) {
            if (! is_numeric($key) || ! is_numeric($value)) {
                continue;
            }

            $k = (int) $key;
            if ($k < 1 || $k > $windowInt) {
                continue;
            }

            if ($bestWindow === null || $k > $bestWindow) {
                $bestWindow = $k;
                $bestValue = $value;
            }
        }

        return ['spent' => $bestValue, 'is_exact_window' => false];
    }
}
