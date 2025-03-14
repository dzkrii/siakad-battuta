import { Button } from '@/Components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/Components/ui/dialog';
import { Input } from '@/Components/ui/input';
import { useForm } from '@inertiajs/react';
import { IconDownload } from '@tabler/icons-react';
import { useState } from 'react';
import { toast } from 'sonner';

export default function ImportModal({ isOpen, onClose, importRoute, downloadTemplateRoute, title, description }) {
    const [isUploading, setIsUploading] = useState(false);
    const { data, setData, post, processing, errors, reset } = useForm({
        file: null,
    });

    const handleFileChange = (e) => {
        const file = e.target.files[0];
        if (file) {
            // Check if file is Excel
            if (!file.name.endsWith('.xlsx') && !file.name.endsWith('.xls')) {
                toast.error('File harus berformat Excel (.xlsx atau .xls)');
                return;
            }
            setData('file', file);
        }
    };

    const handleSubmit = (e) => {
        e.preventDefault();
        setIsUploading(true);

        post(importRoute, {
            preserveScroll: true,
            onSuccess: () => {
                setIsUploading(false);
                toast.success('Data berhasil diimpor');
                reset();
                onClose();
            },
            onError: (errors) => {
                setIsUploading(false);
                toast.error(errors.file || 'Terjadi kesalahan saat mengimpor data');
            },
        });
    };

    const downloadTemplate = () => {
        window.location.href = downloadTemplateRoute;
    };

    return (
        <Dialog open={isOpen} onOpenChange={onClose}>
            <DialogContent className="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>{title}</DialogTitle>
                    <DialogDescription>{description}</DialogDescription>
                </DialogHeader>
                <form onSubmit={handleSubmit}>
                    <div className="space-y-4 py-4">
                        <div className="flex flex-col space-y-2">
                            <Button type="button" variant="outline" onClick={downloadTemplate} className="w-full">
                                <IconDownload className="mr-2 size-4" />
                                Download Template
                            </Button>
                        </div>
                        <div className="flex flex-col space-y-2">
                            <Input
                                id="file"
                                type="file"
                                onChange={handleFileChange}
                                accept=".xlsx, .xls"
                                className="w-full"
                            />
                            {errors.file && <p className="text-sm text-red-500">{errors.file}</p>}
                        </div>
                    </div>
                    <DialogFooter>
                        <Button type="button" variant="outline" onClick={onClose} disabled={processing || isUploading}>
                            Batal
                        </Button>
                        <Button type="submit" disabled={!data.file || processing || isUploading}>
                            {processing || isUploading ? 'Mengimpor...' : 'Import'}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
