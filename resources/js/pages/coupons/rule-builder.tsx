import { Form, Head } from '@inertiajs/react';
import { useMemo, useState } from 'react';
import CouponController from '@/actions/App/Http/Controllers/Coupon/CouponController';
import { ConditionError } from '@/components/coupons/ConditionError';
import { ConditionList } from '@/components/coupons/ConditionList';
import { CouponGeneralFields } from '@/components/coupons/CouponGeneralFields';
import type { DiscountApply } from '@/components/coupons/CouponGeneralFields';
import { LivePreviewSection } from '@/components/coupons/LivePreviewSection';
import { SampleUserForm } from '@/components/coupons/SampleUserForm';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import type { TierValue } from '@/constants/form-options';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { useRuleBuilder } from '@/hooks/use-rule-builder';
import { dashboard } from '@/routes';
import { create as createCoupon } from '@/routes/coupons';
import type { CouponDto } from '@/types';

function discountApplyToActionsJson(
    apply: DiscountApply,
    amount: number,
): string {
    if (apply === 'percent') {
        return JSON.stringify(
            {
                type: 'discount',
                mode: 'percent',
                percent: amount,
            },
            null,
            2,
        );
    }

    return JSON.stringify(
        {
            type: 'discount',
            mode: 'fixed',
            amount,
        },
        null,
        2,
    );
}

function actionsJsonToDiscountApply(actions: unknown): DiscountApply {
    if (!actions || typeof actions !== 'object') {
        return 'fixed';
    }

    const mode = (actions as { mode?: unknown }).mode;

    return mode === 'percent' ? 'percent' : 'fixed';
}

const DEFAULT_ACTIONS_JSON = discountApplyToActionsJson('fixed', 10);

export default function RuleBuilder({
    mode = 'create',
    coupon = null,
}: {
    mode?: 'create' | 'edit';
    coupon?: CouponDto | null;
}) {
    const key = `${mode}:${coupon?.id ?? 'new'}`;

    return <RuleBuilderInner key={key} mode={mode} coupon={coupon} />;
}

function RuleBuilderInner({
    mode,
    coupon,
}: {
    mode: 'create' | 'edit';
    coupon: CouponDto | null;
}) {
    const [sampleOpen, setSampleOpen] = useState(true);
    const [sampleTier, setSampleTier] = useState<TierValue>('gold');
    const [sampleLocation, setSampleLocation] = useState('US');
    const [sampleSpendWindowDays, setSampleSpendWindowDays] = useState('30');
    const [sampleSpendAmount, setSampleSpendAmount] = useState('250');
    const [discountAmount, setDiscountAmount] = useState<string>(() =>
        coupon?.discount_amount !== null &&
        coupon?.discount_amount !== undefined
            ? String(coupon.discount_amount)
            : '10',
    );
    const [discountApply, setDiscountApply] = useState<DiscountApply>(() =>
        actionsJsonToDiscountApply(coupon?.actions),
    );
    const [actionsJson, setActionsJson] = useState<string>(() =>
        coupon?.actions
            ? JSON.stringify(coupon.actions, null, 2)
            : DEFAULT_ACTIONS_JSON,
    );

    const sampleUser = useMemo((): Record<string, unknown> => {
        const windowDays = Number.parseInt(sampleSpendWindowDays, 10);
        const n = Number(sampleSpendAmount);

        const spend_by_window_days: Record<number, number> = {};
        const hasWindow =
            sampleSpendWindowDays.trim() !== '' &&
            Number.isFinite(windowDays) &&
            windowDays > 0;
        const hasSpend = sampleSpendAmount.trim() !== '' && Number.isFinite(n);

        return {
            tier: sampleTier,
            location: sampleLocation,
            ...(hasSpend && !hasWindow ? { spend_total: n } : {}),
            ...(hasSpend && hasWindow
                ? {
                      spend_by_window_days: {
                          ...spend_by_window_days,
                          [windowDays]: n,
                      },
                  }
                : { spend_by_window_days }),
        };
    }, [sampleTier, sampleLocation, sampleSpendWindowDays, sampleSpendAmount]);

    const {
        rows,
        rule: builtRule,
        addRow,
        removeRow,
        updateRow,
        processing,
        lastValid,
        requestError,
        checkEligibility,
    } = useRuleBuilder({
        initialRules: coupon?.rules ?? null,
        sampleUser,
    });

    const previewJson = useMemo(
        () => JSON.stringify(builtRule, null, 2),
        [builtRule],
    );

    return (
        <>
            <Head title={mode === 'edit' ? 'Edit coupon' : 'Create coupon'} />
            <div className="mx-auto flex max-w-6xl flex-col gap-6 p-4">
                <Heading
                    title={mode === 'edit' ? 'Edit coupon' : 'Create coupon'}
                    description="Set coupon details, build rules and actions, preview JSON, and validate against a sample user."
                />

                <Form
                    {...(mode === 'edit' && coupon
                        ? CouponController.update.form(coupon)
                        : CouponController.store.form())}
                    options={{ preserveScroll: true }}
                    className="grid gap-6 lg:grid-cols-2"
                >
                    {({ processing: saving, errors }) => (
                        <>
                            {/*
                             * Inertia returns nested validation errors as dot-notated keys
                             * (e.g. "rules.conditions.1.values"). We render these next to
                             * their matching inputs below.
                             */}
                            <CouponGeneralFields
                                coupon={coupon}
                                discountAmount={discountAmount}
                                setDiscountAmount={setDiscountAmount}
                                discountApply={discountApply}
                                setDiscountApply={setDiscountApply}
                                actionsJson={actionsJson}
                                setActionsJson={setActionsJson}
                                discountApplyToActionsJson={
                                    discountApplyToActionsJson
                                }
                                errors={errors as Record<string, string>}
                            />

                            <Card>
                                <CardHeader>
                                    <CardTitle>Conditions</CardTitle>
                                    <CardDescription>
                                        All conditions must pass (AND). Choose a
                                        category per row.
                                    </CardDescription>
                                </CardHeader>
                                <CardContent className="flex flex-col gap-4">
                                    <input
                                        type="hidden"
                                        name="rules"
                                        value={JSON.stringify(builtRule)}
                                    />
                                    <InputError message={errors.rules} />
                                    <ConditionError
                                        errors={
                                            errors as Record<string, string>
                                        }
                                        path="rules.conditions"
                                    />
                                    <ConditionList
                                        rows={rows}
                                        onAdd={addRow}
                                        onRemove={removeRow}
                                        onUpdate={updateRow}
                                        errors={
                                            errors as Record<string, string>
                                        }
                                    />
                                </CardContent>
                            </Card>

                            <div className="flex flex-col gap-4 lg:col-span-2">
                                <div className="grid gap-6">
                                    <LivePreviewSection
                                        previewJson={previewJson}
                                        actionsJson={actionsJson}
                                    />
                                </div>

                                <div className="flex flex-wrap items-center gap-3">
                                    <Button type="submit" disabled={saving}>
                                        {saving
                                            ? 'Saving…'
                                            : mode === 'edit'
                                              ? 'Save changes'
                                              : 'Create coupon'}
                                    </Button>

                                    {lastValid !== null && (
                                        <p
                                            className={`text-sm font-medium ${lastValid ? 'text-green-600 dark:text-green-400' : 'text-destructive'}`}
                                        >
                                            {lastValid
                                                ? 'Eligible: isValid is true for this user and rule.'
                                                : 'Not eligible: isValid is false.'}
                                        </p>
                                    )}
                                </div>

                                <SampleUserForm
                                    open={sampleOpen}
                                    setOpen={setSampleOpen}
                                    tier={sampleTier}
                                    setTier={setSampleTier}
                                    location={sampleLocation}
                                    setLocation={setSampleLocation}
                                    spendWindowDays={sampleSpendWindowDays}
                                    setSpendWindowDays={
                                        setSampleSpendWindowDays
                                    }
                                    spendAmount={sampleSpendAmount}
                                    setSpendAmount={setSampleSpendAmount}
                                    processing={processing}
                                    lastValid={lastValid}
                                    requestError={requestError}
                                    onCheckEligibility={checkEligibility}
                                />
                            </div>
                        </>
                    )}
                </Form>
            </div>
        </>
    );
}

RuleBuilder.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: dashboard() },
        { title: 'Create coupon', href: createCoupon() },
    ],
};
