import type { ReactNode } from 'react';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Label } from '@/components/ui/label';

export type SelectOption<T extends string> = {
    value: T;
    label: string;
};

export type SelectFieldProps<T extends string> = {
    label: string;
    value: T;
    onChange: (value: T) => void;
    options: readonly SelectOption<T>[];
    placeholder?: string;
};

export function SelectField<T extends string>({
    label,
    value,
    onChange,
    options,
    placeholder,
}: SelectFieldProps<T>) {
    return (
        <div className="grid gap-2">
            <Label>{label}</Label>
            <Select value={value} onValueChange={onChange}>
                <SelectTrigger>
                    <SelectValue placeholder={placeholder} />
                </SelectTrigger>
                <SelectContent>
                    {options.map((option) => (
                        <SelectItem key={option.value} value={option.value}>
                            {option.label}
                        </SelectItem>
                    ))}
                </SelectContent>
            </Select>
        </div>
    );
}