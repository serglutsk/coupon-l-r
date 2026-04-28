<?php

declare(strict_types=1);

namespace App\Http\Controllers\Coupon;

use App\Http\Controllers\Controller;
use App\Http\Requests\Coupon\ValidateCouponRuleRequest;
use App\Services\Coupon\CouponRuleEvaluator;
use Illuminate\Http\JsonResponse;

class ValidateRuleController extends Controller
{
    public function __invoke(ValidateCouponRuleRequest $request, CouponRuleEvaluator $evaluator): JsonResponse
    {
        /** @var array<string, mixed> $user */
        $user = $request->input('user');
        /** @var array<string, mixed> $rule */
        $rule = $request->input('rule');

        return response()->json([
            'isValid' => $evaluator->evaluate($user, $rule),
        ]);
    }
}
