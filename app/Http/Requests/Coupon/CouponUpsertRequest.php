<?php

declare(strict_types=1);

namespace App\Http\Requests\Coupon;

use App\Http\Requests\Coupon\Traits\ValidatesCouponRules;
use App\Services\Coupon\CouponConditionInterface;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class CouponUpsertRequest extends FormRequest
{
    use ValidatesCouponRules;

    protected function prepareForValidation(): void
    {
        $payload = [];

        foreach (['rules', 'actions'] as $key) {
            $value = $this->input($key);

            if (is_string($value)) {
                $decoded = json_decode($value, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $payload[$key] = $decoded;
                }
            }
        }

        if ($this->input('discount_amount') === '') {
            $payload['discount_amount'] = null;
        }

        if ($payload !== []) {
            $this->merge($payload);
        }
    }

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $couponId = $this->route('coupon')?->id;

        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:255'],
            'code' => [
                'required',
                'string',
                'max:255',
                Rule::unique('coupons', 'code')->ignore($couponId),
            ],
            'is_active' => ['sometimes', 'boolean'],
            'valid_from' => ['nullable', 'date'],
            'valid_to' => ['nullable', 'date', 'after_or_equal:valid_from'],

            'rules' => ['required', 'array'],
            'rules.conditions' => ['present', 'array'],
            'rules.conditions.*' => ['required', 'array'],
            'rules.conditions.*.type' => ['required', 'string', 'in:'.implode(',', [
                CouponConditionInterface::TYPE_TIER,
                CouponConditionInterface::TYPE_SPEND,
                CouponConditionInterface::TYPE_LOCATION,
            ])],
            'rules.conditions.*.operator' => ['sometimes'],
            'rules.conditions.*.value' => ['sometimes'],
            'rules.conditions.*.amount' => ['sometimes'],
            'rules.conditions.*.window_days' => ['sometimes'],
            'rules.conditions.*.values' => ['sometimes'],

            'actions' => ['required', 'array'],
            'actions.type' => ['required', 'string', 'in:discount'],
            'actions.mode' => ['required', 'string', 'in:fixed,percent'],
            'actions.amount' => ['required_if:actions.mode,fixed', 'numeric', 'min:0'],
            'actions.percent' => ['required_if:actions.mode,percent', 'numeric', 'min:0', 'max:100'],
            'discount_amount' => ['nullable', 'numeric', 'min:0'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $data = $validator->getData();
            $conditions = $data['rules']['conditions'] ?? null;

            if (! is_array($conditions)) {
                return;
            }

            foreach ($conditions as $index => $condition) {
                if (! is_array($condition)) {
                    $validator->errors()->add(
                        "rules.conditions.{$index}",
                        __('Each rule condition must be an object.'),
                    );

                    continue;
                }

                $prefix = "rules.conditions.{$index}";

                match ($condition['type'] ?? null) {
                    CouponConditionInterface::TYPE_TIER => $this->validateTierCondition($validator, $prefix, $condition),
                    CouponConditionInterface::TYPE_SPEND => $this->validateSpendCondition($validator, $prefix, $condition),
                    CouponConditionInterface::TYPE_LOCATION => $this->validateLocationCondition($validator, $prefix, $condition),
                    default => null,
                };
            }

            $this->validateConditionsSet($validator, 'rules.conditions', $conditions);
        });
    }
}
