import { Plus } from 'lucide-react';
import { useEffect, useRef } from 'react';
import { Button } from '@/components/ui/button';
import type {
    ConditionRow,
    ConditionRowUpdate,
    LocationOperator,
} from '@/hooks/use-rule-builder';
import { ConditionItem } from './ConditionItem';

export type ConditionListProps = {
    rows: ConditionRow[];
    onAdd: () => void;
    onRemove: (id: string) => void;
    onUpdate: (id: string, patch: ConditionRowUpdate) => void;
    errors: Record<string, string>;
};

export function ConditionList({
    rows,
    onAdd,
    onRemove,
    onUpdate,
    errors,
}: ConditionListProps) {
    const prevLengthRef = useRef(rows.length);
    const tierId = rows.find((r) => r.type === 'tier')?.id ?? null;
    const locationRows = rows.filter((r) => r.type === 'location');
    const locationCount = locationRows.length;

    useEffect(() => {
        const prev = prevLengthRef.current;
        prevLengthRef.current = rows.length;

        if (rows.length <= prev) {
            return;
        }

        const last = rows.at(-1);

        if (!last) {
            return;
        }

        // Let the new row render before scrolling/focusing.
        queueMicrotask(() => {
            document
                .getElementById(`condition-${last.id}`)
                ?.scrollIntoView({ behavior: 'smooth', block: 'start' });

            const trigger = document.getElementById(`cat-${last.id}`);

            if (trigger instanceof HTMLElement) {
                trigger.focus();
            }
        });
    }, [rows]);

    return (
        <div className="flex flex-col gap-4">
            <div className="flex flex-wrap items-center justify-between gap-2">
                <div>
                    <h2 className="text-base font-semibold">Rules</h2>
                    <p className="text-sm text-muted-foreground">
                        Add conditions (AND). Empty list means “always valid”.
                    </p>
                </div>
                <Button
                    type="button"
                    onClick={() => {
                        onAdd();
                    }}
                >
                    <Plus className="mr-2 size-4" />
                    Add condition
                </Button>
            </div>

            <div className="flex flex-col gap-4">
                {rows.map((row, index) => (
                    (() => {
                        const disableTierOption = tierId !== null && row.type !== 'tier';
                        const disableLocationOption = locationCount >= 2 && row.type !== 'location';

                        const otherLocationOperator: LocationOperator | null =
                            row.type === 'location' && locationCount === 2
                                ? (locationRows.find((r) => r.id !== row.id)?.locationOperator ?? null)
                                : null;

                        return (
                    <ConditionItem
                        key={row.id}
                        index={index}
                        row={row}
                        canRemove={rows.length > 1}
                        onRemove={() => onRemove(row.id)}
                        onUpdate={(patch) => onUpdate(row.id, patch)}
                        errors={errors}
                        disableTierOption={disableTierOption}
                        disableLocationOption={disableLocationOption}
                        otherLocationOperator={otherLocationOperator}
                    />
                        );
                    })()
                ))}
            </div>
        </div>
    );
}
