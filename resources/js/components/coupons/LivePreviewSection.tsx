import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Label } from '@/components/ui/label';

export type LivePreviewSectionProps = {
    previewJson: string;
    actionsJson: string;
};

export function LivePreviewSection({
    previewJson,
    actionsJson,
}: LivePreviewSectionProps) {
    return (
        <Card>
            <CardHeader>
                <CardTitle>Live preview</CardTitle>
                <CardDescription>
                    This is the JSON payload stored in the database.
                </CardDescription>
            </CardHeader>
            <CardContent className="flex flex-col gap-4">
                <div className="grid gap-2">
                    <Label>Rules JSON</Label>
                    <pre className="max-h-[360px] overflow-auto rounded-md bg-muted p-3 text-xs">
                        {previewJson}
                    </pre>
                </div>
                <div className="grid gap-2">
                    <Label>Actions JSON</Label>
                    <pre className="max-h-[360px] overflow-auto rounded-md bg-muted p-3 text-xs">
                        {actionsJson}
                    </pre>
                </div>
            </CardContent>
        </Card>
    );
}
