import { ChevronDown } from 'lucide-react';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    Collapsible,
    CollapsibleContent,
    CollapsibleTrigger,
} from '@/components/ui/collapsible';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { SelectField } from '@/components/ui/select-field';
import { TIER_OPTIONS, type TierValue } from '@/constants/form-options';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';

export type SampleUserFormProps = {
    open: boolean;
    setOpen: (next: boolean) => void;
    tier: TierValue;
    setTier: (v: TierValue) => void;
    location: string;
    setLocation: (v: string) => void;
    spendWindowDays: string;
    setSpendWindowDays: (v: string) => void;
    spendAmount: string;
    setSpendAmount: (v: string) => void;
    processing: boolean;
    lastValid: boolean | null;
    requestError: string | null;
    onCheckEligibility: () => void;
};

export function SampleUserForm({
    open,
    setOpen,
    tier,
    setTier,
    location,
    setLocation,
    spendWindowDays,
    setSpendWindowDays,
    spendAmount,
    setSpendAmount,
    processing,
    lastValid,
    requestError,
    onCheckEligibility,
}: SampleUserFormProps) {
    return (
        <Collapsible open={open} onOpenChange={setOpen}>
            <Card>
                <CardHeader className="space-y-2">
                    <div className="flex items-start justify-between gap-3">
                        <div className="space-y-1">
                            <CardTitle>Sample user</CardTitle>
                            <CardDescription>
                                Validate rules against a test user object.
                            </CardDescription>
                        </div>
                        <CollapsibleTrigger asChild>
                            <Button type="button" variant="ghost" size="icon">
                                <ChevronDown className="size-4" />
                            </Button>
                        </CollapsibleTrigger>
                    </div>
                </CardHeader>

                <CollapsibleContent>
                    <CardContent className="flex flex-col gap-4">
                        <div className="grid gap-3 sm:grid-cols-2">
                            <SelectField
                                label="Tier"
                                value={tier}
                                onChange={setTier}
                                options={TIER_OPTIONS}
                            />

                            <div className="grid gap-2">
                                <Label>Location</Label>
                                <Input
                                    value={location}
                                    onChange={(e) =>
                                        setLocation(e.target.value)
                                    }
                                    placeholder="US"
                                />
                            </div>
                        </div>

                        <div className="grid gap-3 sm:grid-cols-2">
                            <div className="grid gap-2">
                                <Label htmlFor="sampleWindowDays">
                                    Window (days)
                                </Label>
                                <Input
                                    id="sampleWindowDays"
                                    type="number"
                                    min={1}
                                    value={spendWindowDays}
                                    onChange={(e) =>
                                        setSpendWindowDays(e.target.value)
                                    }
                                    placeholder="30"
                                />
                                <p className="text-xs text-muted-foreground">
                                    Leave empty to send <code>spend_total</code>{' '}
                                    (lifetime).
                                </p>
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="sampleSpend">Spend (USD)</Label>
                                <Input
                                    id="sampleSpend"
                                    type="number"
                                    min={0}
                                    step="0.01"
                                    value={spendAmount}
                                    onChange={(e) =>
                                        setSpendAmount(e.target.value)
                                    }
                                    placeholder="250"
                                />
                            </div>
                        </div>

                        <div className="flex flex-wrap items-center gap-3">
                            <Button
                                type="button"
                                onClick={onCheckEligibility}
                                disabled={processing}
                            >
                                {processing
                                    ? 'Validating…'
                                    : 'Check eligibility'}
                            </Button>

                            {lastValid === true && (
                                <span className="text-sm font-medium text-emerald-600">
                                    Eligible
                                </span>
                            )}
                            {lastValid === false && (
                                <span className="text-sm font-medium text-rose-600">
                                    Not eligible
                                </span>
                            )}
                        </div>
                        {requestError && (
                            <p className="text-sm text-destructive">
                                {requestError}
                            </p>
                        )}
                    </CardContent>
                </CollapsibleContent>
            </Card>
        </Collapsible>
    );
}
