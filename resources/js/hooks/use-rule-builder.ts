import { useHttp } from '@inertiajs/react';
import { useCallback, useEffect, useMemo, useState } from 'react';
import { validateRule } from '@/routes';

export type ConditionType = 'tier' | 'spend' | 'location';

export type TierOperator = 'eq' | 'neq';
export type SpendOperator = 'gt' | 'gte' | 'lt' | 'lte' | 'eq';
export type LocationOperator = 'in' | 'not_in';

export type TierCondition = {
    type: 'tier';
    tierOperator: TierOperator;
    tierValue: string;
};

export type SpendCondition = {
    type: 'spend';
    spendOperator: SpendOperator;
    spendAmount: string;
    spendWindowDays: string;
};

export type LocationCondition = {
    type: 'location';
    locationOperator: LocationOperator;
    locationValues: string;
};

export type ConditionRow = { id: string } & (
    | TierCondition
    | SpendCondition
    | LocationCondition
);

export type ConditionRowUpdate = Partial<{
    type: ConditionType;
    tierOperator: TierOperator;
    tierValue: string;
    spendOperator: SpendOperator;
    spendAmount: string;
    spendWindowDays: string;
    locationOperator: LocationOperator;
    locationValues: string;
}>;

function defaultsFor(
    type: ConditionType,
): TierCondition | SpendCondition | LocationCondition {
    if (type === 'tier') {
        return { type, tierOperator: 'eq', tierValue: 'gold' };
    }

    if (type === 'spend') {
        return {
            type,
            spendOperator: 'gt',
            spendAmount: '200',
            spendWindowDays: '',
        };
    }

    return { type, locationOperator: 'in', locationValues: 'US, CA' };
}

export function createConditionRow(type: ConditionType = 'tier'): ConditionRow {
    return { id: crypto.randomUUID(), ...defaultsFor(type) } as ConditionRow;
}

export function rulesToRows(
    rules: { conditions?: Record<string, unknown>[] } | null,
): ConditionRow[] {
    if (!rules?.conditions?.length) {
        return [createConditionRow('tier')];
    }

    return rules.conditions.map((c) => {
        const type = (c.type as ConditionType | undefined) ?? 'tier';

        if (type === 'tier') {
            return {
                ...createConditionRow('tier'),
                tierOperator: (c.operator as TierOperator) ?? 'eq',
                tierValue: (c.value as string) ?? 'gold',
            };
        }

        if (type === 'spend') {
            return {
                ...createConditionRow('spend'),
                spendOperator: (c.operator as SpendOperator) ?? 'gt',
                spendAmount: c.amount !== undefined ? String(c.amount) : '200',
                spendWindowDays:
                    c.window_days !== undefined ? String(c.window_days) : '',
            };
        }

        return {
            ...createConditionRow('location'),
            locationOperator: (c.operator as LocationOperator) ?? 'in',
            locationValues: Array.isArray(c.values) ? c.values.join(', ') : '',
        };
    });
}

function rowToCondition(row: ConditionRow): Record<string, unknown> {
    if (row.type === 'tier') {
        return {
            type: 'tier',
            operator: row.tierOperator,
            value: row.tierValue,
        };
    }

    if (row.type === 'spend') {
        const base: Record<string, unknown> = {
            type: 'spend',
            operator: row.spendOperator,
            amount: Number(row.spendAmount),
        };

        const windowDays = row.spendWindowDays.trim();

        if (windowDays !== '') {
            base.window_days = Number.parseInt(windowDays, 10);
        }

        return base;
    }

    return {
        type: 'location',
        operator: row.locationOperator,
        values: row.locationValues
            .split(',')
            .map((s) => s.trim())
            .filter(Boolean),
    };
}

export type UseRuleBuilderParams = {
    initialRules?: { conditions?: Record<string, unknown>[] } | null;
    sampleUser: Record<string, unknown>;
};

export type UseRuleBuilderReturn = {
    rows: ConditionRow[];
    rule: { conditions: Record<string, unknown>[] };
    setRowsFromRules: (
        rules: { conditions?: Record<string, unknown>[] } | null,
    ) => void;
    addRow: () => void;
    removeRow: (id: string) => void;
    updateRow: (id: string, patch: ConditionRowUpdate) => void;
    processing: boolean;
    lastValid: boolean | null;
    requestError: string | null;
    checkEligibility: () => Promise<void>;
};

export function useRuleBuilder({
    initialRules = null,
    sampleUser,
}: UseRuleBuilderParams): UseRuleBuilderReturn {
    const [rows, setRows] = useState<ConditionRow[]>(() =>
        rulesToRows(initialRules),
    );
    const [lastValid, setLastValid] = useState<boolean | null>(null);
    const [requestError, setRequestError] = useState<string | null>(null);

    const rule = useMemo(() => {
        const conditions = rows
            .map((r) => rowToCondition(r))
            .filter((c): c is Record<string, unknown> => c !== null);

        return { conditions };
    }, [rows]);

    const { submit, processing, setData } = useHttp<any>(validateRule.post(), {
        user: sampleUser,
        rule,
    });

    useEffect(() => {
        setData({ user: sampleUser, rule });
    }, [setData, sampleUser, rule]);

    const updateRow = useCallback((id: string, patch: ConditionRowUpdate) => {
        setRows((prev) => {
            const existingTierId = prev.find((r) => r.type === 'tier')?.id ?? null;
            const locationCount = prev.filter((r) => r.type === 'location').length;

            const next = prev.map((row) => {
                if (row.id !== id) {
                    return row;
                }

                // Enforce type-level business constraints in the UI.
                if (patch.type === 'tier' && existingTierId !== null && existingTierId !== row.id) {
                    return row;
                }

                if (patch.type === 'location' && row.type !== 'location' && locationCount >= 2) {
                    return row;
                }

                // If the type changes, reset to defaults for that type.
                if (patch.type && patch.type !== row.type) {
                    return {
                        id: row.id,
                        ...defaultsFor(patch.type),
                        ...patch,
                    } as ConditionRow;
                }

                return { ...row, ...patch } as ConditionRow;
            });

            // Spend window_days sync: if multiple spend rows exist and any has a window, make all match.
            const spendRows = next.filter((r) => r.type === 'spend');

            if (spendRows.length > 1) {
                // If user clears window on any spend row, clear it for all (lifetime spend).
                const cleared = spendRows.some((r) => r.spendWindowDays.trim() === '');
                if (cleared) {
                    return next.map((r) =>
                        r.type === 'spend' ? { ...r, spendWindowDays: '' } : r,
                    );
                }

                const canonical = spendRows
                    .map((r) => r.spendWindowDays.trim())
                    .find((v) => v !== '');

                if (canonical !== undefined) {
                    return next.map((r) =>
                        r.type === 'spend' ? { ...r, spendWindowDays: canonical } : r,
                    );
                }
            }

            // Location operator constraint: if exactly 2 locations, keep operators different by preventing duplicates.
            const locations = next.filter((r) => r.type === 'location');

            if (locations.length === 2) {
                const [a, b] = locations;

                if (a.locationOperator === b.locationOperator) {
                    // Revert the change on the edited row if it caused duplication.
                    return prev;
                }
            }

            return next;
        });
    }, []);

    const removeRow = useCallback((id: string) => {
        setRows((prev) =>
            prev.length <= 1 ? prev : prev.filter((r) => r.id !== id),
        );
    }, []);

    const addRow = useCallback(() => {
        setRows((prev) => {
            const hasTier = prev.some((r) => r.type === 'tier');
            const locationCount = prev.filter((r) => r.type === 'location').length;

            const type: ConditionType = !hasTier
                ? 'tier'
                : locationCount < 2
                  ? 'spend'
                  : 'spend';

            return [...prev, createConditionRow(type)];
        });
    }, []);

    const setRowsFromRules = useCallback(
        (rules: { conditions?: Record<string, unknown>[] } | null) => {
            setRows(rulesToRows(rules));
        },
        [],
    );

    const checkEligibility = useCallback(async (): Promise<void> => {
        setRequestError(null);
        setLastValid(null);

        try {
            const res = (await submit()) as { isValid: boolean };
            setLastValid(res.isValid);
        } catch {
            setRequestError(
                'Could not validate. Fix rule fields or adjust the sample user, then try again.',
            );
        }
    }, [submit]);

    return {
        rows,
        rule,
        setRowsFromRules,
        addRow,
        removeRow,
        updateRow,
        processing,
        lastValid,
        requestError,
        checkEligibility,
    };
}
