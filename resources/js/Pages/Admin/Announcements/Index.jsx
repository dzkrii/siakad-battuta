import HeaderTitle from '@/Components/HeaderTitle';
import PaginationTable from '@/Components/PaginationTable';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/Components/ui/table';
import AppLayout from '@/Layouts/AppLayout';
import { Link } from '@inertiajs/react';
import { IconBellRinging, IconCheck, IconEdit, IconPlus, IconTrash, IconX } from '@tabler/icons-react';
import { format } from 'date-fns';
import { id } from 'date-fns/locale';
import React from 'react';
import { toast } from 'sonner';

export default function Index({ page_settings, announcements }) {
    React.useEffect(() => {
        if (window?.flash?.success) {
            toast.success(window.flash.success);
        }
    }, []);

    return (
        <div className="flex flex-col gap-y-8">
            <div className="flex flex-col items-start justify-between gap-y-4 lg:flex-row lg:items-center">
                <HeaderTitle title={page_settings.title} subtitle={page_settings.subtitle} icon={IconBellRinging} />
                <div className="flex w-full flex-col gap-4 sm:w-auto sm:flex-row">
                    <Link href={route('admin.announcements.create')}>
                        <Button className="w-full sm:w-auto">
                            <IconPlus className="mr-2 h-4 w-4" />
                            Tambah Pengumuman
                        </Button>
                    </Link>
                </div>
            </div>

            <div className="rounded-md border">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Judul</TableHead>
                            <TableHead>Target</TableHead>
                            <TableHead>Status</TableHead>
                            <TableHead>Tanggal Publikasi</TableHead>
                            <TableHead>Tanggal Kedaluwarsa</TableHead>
                            <TableHead>Aksi</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {announcements.data.length === 0 ? (
                            <TableRow>
                                <TableCell colSpan={6} className="text-center">
                                    Tidak ada data pengumuman
                                </TableCell>
                            </TableRow>
                        ) : (
                            announcements.data.map((announcement) => (
                                <TableRow key={announcement.id}>
                                    <TableCell className="font-medium">{announcement.title}</TableCell>
                                    <TableCell>
                                        <div className="flex flex-col gap-1">
                                            {announcement.for_student && (
                                                <Badge variant="outline" className="bg-blue-50">
                                                    Mahasiswa
                                                </Badge>
                                            )}
                                            {announcement.for_teacher && (
                                                <Badge variant="outline" className="bg-green-50">
                                                    Dosen
                                                </Badge>
                                            )}
                                        </div>
                                    </TableCell>
                                    <TableCell>
                                        {announcement.is_active ? (
                                            <div className="flex items-center">
                                                <IconCheck className="mr-2 h-4 w-4 text-green-500" />
                                                <span>Aktif</span>
                                            </div>
                                        ) : (
                                            <div className="flex items-center">
                                                <IconX className="mr-2 h-4 w-4 text-red-500" />
                                                <span>Tidak Aktif</span>
                                            </div>
                                        )}
                                    </TableCell>
                                    <TableCell>
                                        {announcement.published_at
                                            ? format(new Date(announcement.published_at), 'dd MMM yyyy', { locale: id })
                                            : '-'}
                                    </TableCell>
                                    <TableCell>
                                        {announcement.expired_at
                                            ? format(new Date(announcement.expired_at), 'dd MMM yyyy', { locale: id })
                                            : '-'}
                                    </TableCell>
                                    <TableCell>
                                        <div className="flex gap-2">
                                            <Link href={route('admin.announcements.edit', announcement.id)}>
                                                <Button variant="outline" size="icon">
                                                    <IconEdit className="h-4 w-4" />
                                                </Button>
                                            </Link>
                                            <Link
                                                href={route('admin.announcements.destroy', announcement.id)}
                                                method="delete"
                                                as="button"
                                                data={{ _method: 'DELETE' }}
                                            >
                                                <Button variant="outline" size="icon" className="text-red-500">
                                                    <IconTrash className="h-4 w-4" />
                                                </Button>
                                            </Link>
                                        </div>
                                    </TableCell>
                                </TableRow>
                            ))
                        )}
                    </TableBody>
                </Table>
            </div>

            <PaginationTable meta={announcements.meta} links={announcements.links} />
        </div>
    );
}

Index.layout = (page) => <AppLayout children={page} title={page.props.page_settings.title} />;
