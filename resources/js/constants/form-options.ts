export const TIER_OPTIONS = [
    { value: 'bronze', label: 'Bronze' },
    { value: 'silver', label: 'Silver' },
    { value: 'gold', label: 'Gold' },
    { value: 'platinum', label: 'Platinum' },
] as const;

export type TierValue = typeof TIER_OPTIONS[number]['value'];
