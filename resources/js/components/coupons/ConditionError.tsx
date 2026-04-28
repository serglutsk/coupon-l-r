import InputError from '@/components/input-error';

export type ConditionErrorProps = {
    errors: Record<string, string> | undefined;
    path: string;
    className?: string;
};

export function ConditionError({
    errors,
    path,
    className,
}: ConditionErrorProps) {
    return <InputError className={className} message={errors?.[path]} />;
}
