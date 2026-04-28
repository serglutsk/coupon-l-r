import { Trash2 } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import type {
    ConditionRow,
    ConditionRowUpdate,
    ConditionType,
    LocationOperator,
    SpendOperator,
    TierOperator,
} from '@/hooks/use-rule-builder';
import { ConditionError } from './ConditionError';

export type ConditionItemProps = {
    index: number;
    row: ConditionRow;
    canRemove: boolean;
    onRemove: () => void;
    onUpdate: (patch: ConditionRowUpdate) => void;
    errors: Record<string, string>;
    disableTierOption?: boolean;
    disableLocationOption?: boolean;
    otherLocationOperator?: LocationOperator | null;
};

export function ConditionItem({
    index,
    row,
    canRemove,
    onRemove,
    onUpdate,
    errors,
    disableTierOption = false,
    disableLocationOption = false,
    otherLocationOperator = null,
}: ConditionItemProps) {
    return (
        <div
            id={`condition-${row.id}`}
            className="flex flex-col gap-3 rounded-lg border border-border/80 p-4"
        >
            <div className="flex flex-wrap items-end justify-between gap-2">
                <p className="text-sm font-medium text-muted-foreground">
                    Condition {index + 1}
                </p>
                <Button
                    type="button"
                    variant="ghost"
                    size="icon"
                    onClick={onRemove}
                    disabled={!canRemove}
                    aria-label="Remove condition"
                >
                    <Trash2 className="size-4" />
                </Button>
            </div>

            <div className="grid gap-2">
                <Label htmlFor={`cat-${row.id}`}>Category</Label>
                <Select
                    value={row.type}
                    onValueChange={(v) =>
                        onUpdate({ type: v as ConditionType })
                    }
                >
                    <SelectTrigger
                        id={`cat-${row.id}`}
                        className="w-full max-w-xs"
                    >
                        <SelectValue placeholder="Category" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="tier" disabled={disableTierOption}>
                            Tier
                        </SelectItem>
                        <SelectItem value="spend">Spend</SelectItem>
                        <SelectItem value="location" disabled={disableLocationOption}>
                            Location
                        </SelectItem>
                    </SelectContent>
                </Select>
                <ConditionError
                    errors={errors}
                    path={`rules.conditions.${index}.type`}
                />
            </div>

            {row.type === 'tier' && (
                <div className="grid gap-3 sm:grid-cols-2">
                    <div className="grid gap-2">
                        <Label>Operator</Label>
                        <Select
                            value={row.tierOperator}
                            onValueChange={(v) =>
                                onUpdate({ tierOperator: v as TierOperator })
                            }
                        >
                            <SelectTrigger>
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="eq">Equals</SelectItem>
                                <SelectItem value="neq">Not equals</SelectItem>
                            </SelectContent>
                        </Select>
                        <ConditionError
                            errors={errors}
                            path={`rules.conditions.${index}.operator`}
                        />
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor={`tier-${row.id}`}>Tier</Label>
                        <Select
                            value={row.tierValue}
                            onValueChange={(v) => onUpdate({ tierValue: v })}
                        >
                            <SelectTrigger id={`tier-${row.id}`}>
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="bronze">Bronze</SelectItem>
                                <SelectItem value="silver">Silver</SelectItem>
                                <SelectItem value="gold">Gold</SelectItem>
                                <SelectItem value="platinum">
                                    Platinum
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <ConditionError
                            errors={errors}
                            path={`rules.conditions.${index}.value`}
                        />
                    </div>
                </div>
            )}

            {row.type === 'spend' && (
                <div className="grid gap-3 sm:grid-cols-2">
                    <div className="grid gap-2">
                        <Label>Operator</Label>
                        <Select
                            value={row.spendOperator}
                            onValueChange={(v) =>
                                onUpdate({
                                    spendOperator: v as SpendOperator,
                                })
                            }
                        >
                            <SelectTrigger>
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="gt">&gt;</SelectItem>
                                <SelectItem value="gte">≥</SelectItem>
                                <SelectItem value="lt">&lt;</SelectItem>
                                <SelectItem value="lte">≤</SelectItem>
                                <SelectItem value="eq">=</SelectItem>
                            </SelectContent>
                        </Select>
                        <ConditionError
                            errors={errors}
                            path={`rules.conditions.${index}.operator`}
                        />
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor={`amt-${row.id}`}>Amount (USD)</Label>
                        <Input
                            id={`amt-${row.id}`}
                            type="number"
                            min={0}
                            step="0.01"
                            value={row.spendAmount}
                            onChange={(e) =>
                                onUpdate({ spendAmount: e.target.value })
                            }
                        />
                        <ConditionError
                            errors={errors}
                            path={`rules.conditions.${index}.amount`}
                        />
                    </div>
                    <div className="grid gap-2 sm:col-span-2">
                        <Label htmlFor={`win-${row.id}`}>Window (days)</Label>
                        <Input
                            id={`win-${row.id}`}
                            type="number"
                            value={row.spendWindowDays}
                            onChange={(e) =>
                                onUpdate({ spendWindowDays: e.target.value })
                            }
                        />
                        <ConditionError
                            errors={errors}
                            path={`rules.conditions.${index}.window_days`}
                        />
                        <p className="text-xs text-muted-foreground">
                            Leave empty for lifetime spend (all time).
                        </p>
                    </div>
                </div>
            )}

            {row.type === 'location' && (
                <div className="grid gap-3">
                    <div className="grid gap-2">
                        <Label>Operator</Label>
                        <Select
                            value={row.locationOperator}
                            onValueChange={(v) =>
                                onUpdate({
                                    locationOperator: v as LocationOperator,
                                })
                            }
                        >
                            <SelectTrigger>
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem
                                    value="in"
                                    disabled={otherLocationOperator === 'in'}
                                >
                                    In list
                                </SelectItem>
                                <SelectItem
                                    value="not_in"
                                    disabled={otherLocationOperator === 'not_in'}
                                >
                                    Not in list
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <ConditionError
                            errors={errors}
                            path={`rules.conditions.${index}.operator`}
                        />
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor={`loc-${row.id}`}>
                            Regions (comma-separated)
                        </Label>
                        <Input
                            id={`loc-${row.id}`}
                            placeholder="US, CA, UK"
                            value={row.locationValues}
                            onChange={(e) =>
                                onUpdate({ locationValues: e.target.value })
                            }
                        />
                        <ConditionError
                            errors={errors}
                            path={`rules.conditions.${index}.values`}
                        />
                    </div>
                </div>
            )}
        </div>
    );
}
