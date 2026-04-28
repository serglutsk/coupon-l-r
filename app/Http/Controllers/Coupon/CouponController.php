<?php

declare(strict_types=1);

namespace App\Http\Controllers\Coupon;

use App\Http\Controllers\Controller;
use App\Http\Requests\Coupon\CouponUpsertRequest;
use App\Http\Resources\Coupon\CouponResource;
use App\Models\Coupon;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class CouponController extends Controller
{
    public function index(): Response
    {
        $coupons = Coupon::query()
            ->orderByDesc('created_at')
            ->paginate(config('app.pagination.per_page'))
            ->withQueryString();

        return Inertia::render('coupons/index', [
            // Resolve resources to plain arrays for Inertia (avoid JsonResource wrapping).
            'coupons' => $coupons->through(
                fn (Coupon $coupon) => CouponResource::make($coupon)->resolve(),
            ),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('coupons/rule-builder', [
            'mode' => 'create',
            'coupon' => null,
        ]);
    }

    public function store(CouponUpsertRequest $request): RedirectResponse
    {
        $coupon = Coupon::create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Coupon created.')]);

        return to_route('coupons.edit', $coupon);
    }

    public function edit(Coupon $coupon): Response
    {
        return Inertia::render('coupons/rule-builder', [
            'mode' => 'edit',
            // Resolve to plain array (avoid { data: ... } resource wrapping).
            'coupon' => CouponResource::make($coupon)->resolve(),
        ]);
    }

    public function update(CouponUpsertRequest $request, Coupon $coupon): RedirectResponse
    {
        $coupon->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Coupon updated.')]);

        return back();
    }

    public function destroy(Coupon $coupon): RedirectResponse
    {
        $coupon->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Coupon deleted.')]);

        return to_route('coupons.index');
    }
}
