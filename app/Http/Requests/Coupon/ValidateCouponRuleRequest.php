<?php

declare(strict_types=1);

namespace App\Http\Requests\Coupon;

use App\Http\Requests\Coupon\Traits\ValidatesCouponRules;
use App\Services\Coupon\CouponConditionInterface;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class ValidateCouponRuleRequest extends FormRequest
{
    use ValidatesCouponRules;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'user' => ['required', 'array'],
            'user.tier' => ['sometimes', 'nullable', 'string'],
            'user.location' => ['sometimes', 'nullable', 'string'],
            'user.spend_total' => ['sometimes', 'numeric'],
            'user.spend_by_window_days' => ['sometimes', 'array'],
            'user.spend_by_window_days.*' => ['numeric'],
            'rule' => ['required', 'array'],
            'rule.conditions' => ['present', 'array'],
            'rule.conditions.*' => ['required', 'array'],
            'rule.conditions.*.type' => ['required', 'string', 'in:'.implode(',', [
                CouponConditionInterface::TYPE_TIER,
                CouponConditionInterface::TYPE_SPEND,
                CouponConditionInterface::TYPE_LOCATION,
            ])],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $data = $validator->getData();
            $conditions = $data['rule']['conditions'] ?? null;

            if (! is_array($conditions)) {
                return;
            }

            foreach ($conditions as $index => $condition) {
                if (! is_array($condition)) {
                    $validator->errors()->add(
                        "rule.conditions.{$index}",
                        __('Each rule condition must be an object.'),
                    );

                    continue;
                }

                $prefix = "rule.conditions.{$index}";

                match ($condition['type'] ?? null) {
                    CouponConditionInterface::TYPE_TIER => $this->validateTierCondition($validator, $prefix, $condition),
                    CouponConditionInterface::TYPE_SPEND => $this->validateSpendCondition($validator, $prefix, $condition),
                    CouponConditionInterface::TYPE_LOCATION => $this->validateLocationCondition($validator, $prefix, $condition),
                    default => null,
                };
            }

            $this->validateConditionsSet($validator, 'rule.conditions', $conditions);
        });
    }
}
