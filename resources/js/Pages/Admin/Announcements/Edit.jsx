import HeaderTitle from '@/Components/HeaderTitle';
import InputError from '@/Components/InputError';
import { Button } from '@/Components/ui/button';
import { Calendar } from '@/Components/ui/calendar';
import { Card, CardContent } from '@/Components/ui/card';
import { Checkbox } from '@/Components/ui/checkbox';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Popover, PopoverContent, PopoverTrigger } from '@/Components/ui/popover';
import { Switch } from '@/Components/ui/switch';
import AppLayout from '@/Layouts/AppLayout';
import { cn, flashMessage } from '@/lib/utils';
import { Link, useForm } from '@inertiajs/react';
import { IconArrowLeft, IconBellRinging, IconCalendar, IconCheck } from '@tabler/icons-react';
import { format } from 'date-fns';
import { id } from 'date-fns/locale';
import { useEffect, useState } from 'react';
import ReactQuill from 'react-quill';
import 'react-quill/dist/quill.snow.css'; // Import Quill styles
import { toast } from 'sonner';

export default function Edit({ page_settings, announcement }) {
    const { data, setData, put, processing, errors } = useForm({
        title: announcement.title || '',
        content: announcement.content || '',
        for_student: announcement.for_student || false,
        for_teacher: announcement.for_teacher || false,
        published_at: announcement.published_at ? new Date(announcement.published_at) : null,
        expired_at: announcement.expired_at ? new Date(announcement.expired_at) : null,
        is_active: announcement.is_active || false,
    });

    // For client-side only rendering of ReactQuill
    const [isMounted, setIsMounted] = useState(false);

    useEffect(() => {
        setIsMounted(true);
    }, []);

    const handleSubmit = (e) => {
        e.preventDefault();
        put(route('admin.announcements.update', announcement.id), {
            preserveScroll: true,
            preserveState: true,
            onSuccess: (page) => {
                const flash = flashMessage(page);
                if (flash) toast.success(flash.message);
            },
        });
    };

    // Configure Quill modules
    const modules = {
        toolbar: [
            [{ header: [1, 2, 3, 4, 5, 6, false] }],
            ['bold', 'italic', 'underline', 'strike'],
            [{ list: 'ordered' }, { list: 'bullet' }],
            [{ color: [] }, { background: [] }],
            ['link'],
            ['clean'],
        ],
    };

    // Configure Quill formats
    const formats = [
        'header',
        'bold',
        'italic',
        'underline',
        'strike',
        'list',
        'bullet',
        'color',
        'background',
        'link',
    ];

    return (
        <div className="flex flex-col gap-y-8">
            <div className="flex flex-col items-start justify-between gap-y-4 lg:flex-row lg:items-center">
                <HeaderTitle title={page_settings.title} subtitle={page_settings.subtitle} icon={IconBellRinging} />
                <div className="flex w-full flex-col gap-4 sm:w-auto sm:flex-row">
                    <Link href={route('admin.announcements.index')}>
                        <Button variant="outline" className="w-full sm:w-auto">
                            <IconArrowLeft className="mr-2 h-4 w-4" />
                            Kembali
                        </Button>
                    </Link>
                </div>
            </div>

            <Card>
                <CardContent className="p-6">
                    <form onSubmit={handleSubmit} className="space-y-6">
                        <div className="space-y-4">
                            <div>
                                <Label htmlFor="title">Judul Pengumuman</Label>
                                <Input
                                    id="title"
                                    type="text"
                                    value={data.title}
                                    onChange={(e) => setData('title', e.target.value)}
                                    className="mt-1"
                                    placeholder="Masukkan judul pengumuman"
                                />
                                {errors.title && <InputError message={errors.title} />}
                            </div>

                            <div>
                                <Label htmlFor="content">Isi Pengumuman</Label>
                                {isMounted && (
                                    <ReactQuill
                                        theme="snow"
                                        value={data.content}
                                        onChange={(content) => setData('content', content)}
                                        modules={modules}
                                        formats={formats}
                                        className="mt-1 min-h-[200px]"
                                        placeholder="Tulis isi pengumuman di sini..."
                                    />
                                )}
                                {errors.content && <InputError message={errors.content} />}
                            </div>

                            <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                                <div>
                                    <Label>Tanggal Publikasi</Label>
                                    <div className="mt-1">
                                        <Popover>
                                            <PopoverTrigger asChild>
                                                <Button
                                                    variant="outline"
                                                    className={cn(
                                                        'w-full justify-start text-left font-normal',
                                                        !data.published_at && 'text-muted-foreground',
                                                    )}
                                                >
                                                    <IconCalendar className="mr-2 h-4 w-4" />
                                                    {data.published_at ? (
                                                        format(data.published_at, 'PPP', { locale: id })
                                                    ) : (
                                                        <span>Pilih tanggal</span>
                                                    )}
                                                </Button>
                                            </PopoverTrigger>
                                            <PopoverContent className="w-auto p-0" align="start">
                                                <Calendar
                                                    mode="single"
                                                    selected={data.published_at}
                                                    onSelect={(date) => setData('published_at', date)}
                                                    initialFocus
                                                />
                                            </PopoverContent>
                                        </Popover>
                                    </div>
                                    {errors.published_at && <InputError message={errors.published_at} />}
                                </div>

                                <div>
                                    <Label>Tanggal Kedaluwarsa</Label>
                                    <div className="mt-1">
                                        <Popover>
                                            <PopoverTrigger asChild>
                                                <Button
                                                    variant="outline"
                                                    className={cn(
                                                        'w-full justify-start text-left font-normal',
                                                        !data.expired_at && 'text-muted-foreground',
                                                    )}
                                                >
                                                    <IconCalendar className="mr-2 h-4 w-4" />
                                                    {data.expired_at ? (
                                                        format(data.expired_at, 'PPP', { locale: id })
                                                    ) : (
                                                        <span>Pilih tanggal</span>
                                                    )}
                                                </Button>
                                            </PopoverTrigger>
                                            <PopoverContent className="w-auto p-0" align="start">
                                                <Calendar
                                                    mode="single"
                                                    selected={data.expired_at}
                                                    onSelect={(date) => setData('expired_at', date)}
                                                    initialFocus
                                                />
                                            </PopoverContent>
                                        </Popover>
                                    </div>
                                    {errors.expired_at && <InputError message={errors.expired_at} />}
                                </div>
                            </div>

                            <div className="space-y-2 border-t pt-4">
                                <h3 className="text-sm font-medium">Target Penerima</h3>
                                <div className="space-y-2">
                                    <div className="flex items-center space-x-2">
                                        <Checkbox
                                            id="for_student"
                                            checked={data.for_student}
                                            onCheckedChange={(checked) => setData('for_student', checked)}
                                        />
                                        <label
                                            htmlFor="for_student"
                                            className="text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70"
                                        >
                                            Tampilkan untuk Mahasiswa
                                        </label>
                                    </div>
                                    <div className="flex items-center space-x-2">
                                        <Checkbox
                                            id="for_teacher"
                                            checked={data.for_teacher}
                                            onCheckedChange={(checked) => setData('for_teacher', checked)}
                                        />
                                        <label
                                            htmlFor="for_teacher"
                                            className="text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70"
                                        >
                                            Tampilkan untuk Dosen
                                        </label>
                                    </div>
                                </div>
                                {(errors.for_student || errors.for_teacher) && (
                                    <InputError message={errors.for_student || errors.for_teacher} />
                                )}
                            </div>

                            <div className="border-t pt-4">
                                <div className="flex items-center space-x-2">
                                    <Switch
                                        id="is_active"
                                        checked={data.is_active}
                                        onCheckedChange={(checked) => setData('is_active', checked)}
                                    />
                                    <label
                                        htmlFor="is_active"
                                        className="text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70"
                                    >
                                        Status Aktif
                                    </label>
                                </div>
                                <p className="mt-1 text-xs text-muted-foreground">
                                    Pengumuman yang tidak aktif tidak akan ditampilkan meskipun dalam periode publikasi
                                </p>
                                {errors.is_active && <InputError message={errors.is_active} />}
                            </div>
                        </div>

                        <div className="flex justify-end">
                            <Button type="submit" disabled={processing}>
                                <IconCheck className="mr-2 h-4 w-4" />
                                {processing ? 'Menyimpan...' : 'Perbarui Pengumuman'}
                            </Button>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </div>
    );
}

Edit.layout = (page) => <AppLayout children={page} title={page.props.page_settings.title} />;
