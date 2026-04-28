export type CouponDto = {
    id: number;
    name: string;
    description: string | null;
    code: string;
    is_active: boolean;
    valid_from: string | null;
    valid_to: string | null;
    rules: { conditions: Record<string, unknown>[] };
    actions: unknown;
    discount_amount: string | number | null;
    created_at?: string | null;
    updated_at?: string | null;
};

export type PaginationLink = {
    url: string | null;
    label: string;
    active: boolean;
};

export type Pagination<T> = {
    data: T[];
    links: PaginationLink[];
};
