import { Button } from '@/Components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/Components/ui/dialog';
import { router } from '@inertiajs/react';
import { IconTrash } from '@tabler/icons-react';
import { useState } from 'react';

export default function Delete({ name, action }) {
    const [open, setOpen] = useState(false);

    const handleDelete = () => {
        router.delete(action, {
            onSuccess: () => {
                setOpen(false);
            },
        });
    };

    return (
        <>
            <Button variant="ghost" size="icon" onClick={() => setOpen(true)}>
                <IconTrash className="size-4 text-red-500" />
            </Button>

            <Dialog open={open} onOpenChange={setOpen}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Hapus Kartu Rencana Studi</DialogTitle>
                        <DialogDescription>
                            Apakah Anda yakin ingin menghapus kartu rencana studi untuk mahasiswa{' '}
                            <span className="font-semibold">{name}</span>? Tindakan ini juga akan menghapus kartu hasil
                            studi yang terkait dan tidak dapat dibatalkan.
                        </DialogDescription>
                    </DialogHeader>
                    <DialogFooter>
                        <Button variant="outline" onClick={() => setOpen(false)}>
                            Batal
                        </Button>
                        <Button variant="destructive" onClick={handleDelete}>
                            Hapus
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </>
    );
}
