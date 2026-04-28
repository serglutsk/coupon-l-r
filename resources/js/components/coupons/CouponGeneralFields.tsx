import InputError from '@/components/input-error';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import type { CouponDto } from '@/types';

export type DiscountApply = 'fixed' | 'percent';

export type CouponGeneralFieldsProps = {
    coupon: CouponDto | null;
    discountAmount: string;
    setDiscountAmount: (value: string) => void;
    discountApply: DiscountApply;
    setDiscountApply: (value: DiscountApply) => void;
    actionsJson: string;
    setActionsJson: (value: string) => void;
    discountApplyToActionsJson: (
        apply: DiscountApply,
        amount: number,
    ) => string;
    errors: Record<string, string>;
};

export function CouponGeneralFields({
    coupon,
    discountAmount,
    setDiscountAmount,
    discountApply,
    setDiscountApply,
    actionsJson,
    setActionsJson,
    discountApplyToActionsJson,
    errors,
}: CouponGeneralFieldsProps) {
    return (
        <Card>
            <CardHeader>
                <CardTitle>Coupon details</CardTitle>
                <CardDescription>
                    These fields map to the <code>coupons</code> table.
                </CardDescription>
            </CardHeader>
            <CardContent className="flex flex-col gap-4">
                <div className="grid gap-2">
                    <Label htmlFor="name">Name</Label>
                    <Input
                        id="name"
                        name="name"
                        defaultValue={coupon?.name ?? ''}
                        required
                        placeholder="Spring Sale"
                    />
                    <InputError message={errors.name} />
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="description">Description</Label>
                    <Input
                        id="description"
                        name="description"
                        defaultValue={coupon?.description ?? ''}
                        placeholder="Optional"
                    />
                    <InputError message={errors.description} />
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="code">Code</Label>
                    <Input
                        id="code"
                        name="code"
                        defaultValue={coupon?.code ?? ''}
                        required
                        placeholder="SPRING-2026"
                    />
                    <InputError message={errors.code} />
                </div>

                <div className="grid gap-2 sm:grid-cols-2">
                    <div className="grid gap-2">
                        <Label htmlFor="valid_from">Valid from</Label>
                        <Input
                            id="valid_from"
                            name="valid_from"
                            type="date"
                            defaultValue={coupon?.valid_from ?? ''}
                        />
                        <InputError message={errors.valid_from} />
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="valid_to">Valid to</Label>
                        <Input
                            id="valid_to"
                            name="valid_to"
                            type="date"
                            defaultValue={coupon?.valid_to ?? ''}
                        />
                        <InputError message={errors.valid_to} />
                    </div>
                </div>

                <div className="grid gap-2 sm:grid-cols-2">
                    <div className="grid gap-2">
                        <Label htmlFor="discount_amount">Discount amount</Label>
                        <Input
                            id="discount_amount"
                            name="discount_amount"
                            type="number"
                            min={0}
                            step="0.01"
                            value={discountAmount}
                            onChange={(e) => {
                                const next = e.target.value;
                                setDiscountAmount(next);

                                const n = Number(next);

                                if (Number.isFinite(n)) {
                                    setActionsJson(
                                        discountApplyToActionsJson(
                                            discountApply,
                                            n,
                                        ),
                                    );
                                }
                            }}
                        />
                        <InputError message={errors.discount_amount} />
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="discount_apply">Apply</Label>
                        <Select
                            value={discountApply}
                            onValueChange={(v) => {
                                const apply = v as DiscountApply;
                                setDiscountApply(apply);

                                const n = Number(discountAmount);
                                setActionsJson(
                                    discountApplyToActionsJson(
                                        apply,
                                        Number.isFinite(n) ? n : 0,
                                    ),
                                );
                            }}
                        >
                            <SelectTrigger
                                id="discount_apply"
                                className="w-full"
                            >
                                <SelectValue placeholder="Select" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="fixed">
                                    Fixed amount discount
                                </SelectItem>
                                <SelectItem value="percent">
                                    Percent(s)
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                </div>

                <div className="grid gap-2">
                    <Label>Active</Label>
                    <div className="flex items-center gap-2">
                        <input type="hidden" name="is_active" value="0" />
                        <input
                            type="checkbox"
                            name="is_active"
                            value="1"
                            defaultChecked={coupon?.is_active ?? true}
                        />
                        <span className="text-sm text-muted-foreground">
                            Enabled
                        </span>
                    </div>
                    <InputError message={errors.is_active} />
                </div>

                <input type="hidden" name="actions" value={actionsJson} />
                <InputError message={errors.actions} />
            </CardContent>
        </Card>
    );
}
