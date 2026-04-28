import { Form, Head, Link } from '@inertiajs/react';
import { Pencil, Plus, Trash2 } from 'lucide-react';
import CouponController from '@/actions/App/Http/Controllers/Coupon/CouponController';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { dashboard } from '@/routes';
import {
    create as createCoupon,
    index as couponsIndex,
} from '@/routes/coupons';
import type { CouponDto, Pagination } from '@/types';

export default function CouponsIndex({
    coupons,
}: {
    coupons: Pagination<CouponDto>;
}) {
    return (
        <>
            <Head title="Coupons" />
            <div className="mx-auto flex w-full max-w-screen-2xl flex-col gap-6 p-4">
                <Heading
                    title="Coupons"
                    description="Create, edit, and delete coupons."
                />

                <div className="flex flex-wrap items-center justify-between gap-3">
                    <Button asChild>
                        <Link href={createCoupon()}>
                            <Plus className="size-4" />
                            Create coupon
                        </Link>
                    </Button>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle>All coupons</CardTitle>
                    </CardHeader>
                    <CardContent>
                        {coupons.data.length === 0 ? (
                            <p className="text-sm text-muted-foreground">
                                No coupons yet.
                            </p>
                        ) : (
                            <div className="-mx-2 overflow-x-auto px-2">
                                <table className="w-full min-w-[900px] text-sm">
                                    <thead className="text-muted-foreground">
                                        <tr className="border-b">
                                            <th className="py-2 text-left font-medium">
                                                Name
                                            </th>
                                            <th className="py-2 text-left font-medium">
                                                Code
                                            </th>
                                            <th className="py-2 text-left font-medium">
                                                Active
                                            </th>
                                            <th className="py-2 text-left font-medium">
                                                Validity
                                            </th>
                                            <th className="py-2 text-right font-medium">
                                                Actions
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {coupons.data.map((c) => (
                                            <tr key={c.id} className="border-b">
                                                <td className="py-3">
                                                    <div className="font-medium">
                                                        #{c.id} {c.name}
                                                    </div>
                                                </td>
                                                <td className="py-3 font-mono text-xs whitespace-nowrap">
                                                    {c.code}
                                                </td>
                                                <td className="py-3">
                                                    {c.is_active ? 'Yes' : 'No'}
                                                </td>
                                                <td className="py-3 text-xs whitespace-nowrap text-muted-foreground">
                                                    {(c.valid_from ?? '—') +
                                                        ' → ' +
                                                        (c.valid_to ?? '—')}
                                                </td>
                                                <td className="py-3">
                                                    <div className="flex justify-end gap-2">
                                                        <Button
                                                            variant="outline"
                                                            size="sm"
                                                            asChild
                                                        >
                                                            <Link
                                                                href={CouponController.edit.url(
                                                                    c,
                                                                )}
                                                            >
                                                                <Pencil className="size-4" />
                                                                Edit
                                                            </Link>
                                                        </Button>

                                                        <Form
                                                            {...CouponController.destroy.form(
                                                                c,
                                                            )}
                                                            onBefore={() =>
                                                                confirm(
                                                                    'Are you sure?',
                                                                )
                                                            }
                                                            options={{
                                                                preserveScroll: true,
                                                            }}
                                                        >
                                                            {({
                                                                processing,
                                                            }) => (
                                                                <Button
                                                                    type="submit"
                                                                    variant="destructive"
                                                                    size="sm"
                                                                    disabled={
                                                                        processing
                                                                    }
                                                                >
                                                                    <Trash2 className="size-4" />
                                                                    Delete
                                                                </Button>
                                                            )}
                                                        </Form>
                                                    </div>
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        )}

                        {coupons.links?.length ? (
                            <div className="mt-4 flex flex-wrap gap-2">
                                {coupons.links.map((link) => (
                                    <Button
                                        key={link.label}
                                        variant={
                                            link.active ? 'default' : 'outline'
                                        }
                                        size="sm"
                                        asChild
                                        disabled={!link.url}
                                    >
                                        <Link href={link.url ?? couponsIndex()}>
                                            <span
                                                dangerouslySetInnerHTML={{
                                                    __html: link.label,
                                                }}
                                            />
                                        </Link>
                                    </Button>
                                ))}
                            </div>
                        ) : null}
                    </CardContent>
                </Card>
            </div>
        </>
    );
}

CouponsIndex.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: dashboard() },
        { title: 'Coupons', href: couponsIndex() },
    ],
};
