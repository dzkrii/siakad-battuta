import EmptyState from '@/Components/EmptyState';
import HeaderTitle from '@/Components/HeaderTitle';
import PaginationTable from '@/Components/PaginationTable';
import ShowFilter from '@/Components/ShowFilter';
import { Alert, AlertDescription, AlertTitle } from '@/Components/ui/alert';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/ui/card';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/Components/ui/dialog';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { RadioGroup, RadioGroupItem } from '@/Components/ui/radio-group';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/Components/ui/table';
import UseFilter from '@/hooks/UseFilter.';
import StudentLayout from '@/Layouts/StudentLayout';
import { formatDateIndo, STUDYPLANSTATUSVARIANT } from '@/lib/utils';
import { Link, useForm, usePage } from '@inertiajs/react';
import {
    IconAlertCircle,
    IconArrowsDownUp,
    IconBuilding,
    IconClock,
    IconDownload,
    IconEye,
    IconHistory,
    IconPlus,
    IconRefresh,
    IconSchool,
} from '@tabler/icons-react';
import { useState } from 'react';

export default function Index() {
    const {
        page_settings,
        studyPlans,
        state,
        can_create_study_plan,
        can_create_backdated_study_plan,
        student,
        block_reason,
        semester_mismatch,
        missed_semesters = [],
        academic_years = [],
    } = usePage().props;

    const { data, meta, links } = studyPlans;
    const [params, setParams] = useState(state);
    const [backdatedDialogOpen, setBackdatedDialogOpen] = useState(false);

    // Form for backdated KRS
    const {
        data: backdatedData,
        setData: setBackdatedData,
        post: postBackdated,
        processing: backdatedProcessing,
    } = useForm({
        semester: missed_semesters.length > 0 ? missed_semesters[0] : '',
        academic_year_id: academic_years.length > 0 ? academic_years[0].id : '',
    });

    const submitBackdatedForm = (e) => {
        e.preventDefault();
        postBackdated(route('students.study-plans.create-backdated'), {
            preserveScroll: true,
        });
    };

    const onSortable = (field) => {
        setParams({
            ...params,
            field: field,
            direction: params.direction === 'asc' ? 'desc' : 'asc',
        });
    };

    UseFilter({
        route: route('students.study-plans.index'),
        values: params,
        only: ['study-plans', 'can_create_study_plan', 'student'],
    });

    return (
        <div className="flex w-full flex-col pb-32">
            <div className="mb-8 flex flex-col items-start justify-between gap-y-4 lg:flex-row lg:items-center">
                <HeaderTitle title={page_settings.title} subtitle={page_settings.subtitle} icon={IconBuilding} />
                <div className="flex w-full flex-col space-y-2 sm:flex-row sm:space-x-2 sm:space-y-0 lg:w-auto">
                    {/* Existing KRS Button Logic */}
                    {can_create_study_plan ? (
                        <Button variant="orange" size="xl" className="w-full lg:w-auto" asChild>
                            <Link href={route('students.study-plans.create')}>
                                <IconPlus className="mr-1 size-4" />
                                Ajukan KRS
                            </Link>
                        </Button>
                    ) : block_reason === 'KRS Sudah Diajukan' ? (
                        <Button variant="outline" size="xl" className="w-full lg:w-auto" asChild disabled>
                            <span>
                                <IconPlus className="mr-1 size-4" />
                                KRS Sudah Diajukan
                            </span>
                        </Button>
                    ) : block_reason === 'Semester Tidak Sesuai' && can_create_backdated_study_plan ? (
                        <Dialog open={backdatedDialogOpen} onOpenChange={setBackdatedDialogOpen}>
                            <DialogTrigger asChild>
                                <Button variant="amber" size="xl" className="w-full lg:w-auto">
                                    <IconHistory className="mr-1 size-4" />
                                    Ajukan KRS Tertinggal
                                </Button>
                            </DialogTrigger>
                            <DialogContent className="sm:max-w-md">
                                <DialogHeader>
                                    <DialogTitle>Ajukan KRS Semester Tertinggal</DialogTitle>
                                    <DialogDescription>
                                        Pilih semester dan tahun ajaran untuk mengajukan KRS tertinggal
                                    </DialogDescription>
                                </DialogHeader>
                                <form onSubmit={submitBackdatedForm} className="space-y-4">
                                    <div className="space-y-2">
                                        <Label htmlFor="semester">Semester</Label>
                                        <RadioGroup
                                            id="semester"
                                            value={backdatedData.semester.toString()}
                                            onValueChange={(value) => setBackdatedData('semester', value)}
                                            className="flex flex-col space-y-2"
                                        >
                                            {missed_semesters.map((semester) => (
                                                <div key={semester} className="flex items-center space-x-2">
                                                    <RadioGroupItem
                                                        id={`semester-${semester}`}
                                                        value={semester.toString()}
                                                    />
                                                    <Label htmlFor={`semester-${semester}`} className="cursor-pointer">
                                                        Semester {semester}{' '}
                                                        {semester % 2 === 1 ? '(Ganjil)' : '(Genap)'}
                                                    </Label>
                                                </div>
                                            ))}
                                        </RadioGroup>
                                    </div>

                                    <div className="space-y-2">
                                        <Label htmlFor="academic_year">Tahun Ajaran</Label>
                                        <Select
                                            value={backdatedData.academic_year_id}
                                            onValueChange={(value) => setBackdatedData('academic_year_id', value)}
                                        >
                                            <SelectTrigger className="w-full">
                                                <SelectValue placeholder="Pilih Tahun Ajaran" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {academic_years.map((year) => (
                                                    <SelectItem key={year.id} value={year.id.toString()}>
                                                        {year.name} - {year.semester === 'odd' ? 'Ganjil' : 'Genap'}
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                    </div>

                                    <div className="mt-4 flex justify-end">
                                        <Button
                                            type="submit"
                                            variant="blue"
                                            disabled={
                                                backdatedProcessing ||
                                                !backdatedData.semester ||
                                                !backdatedData.academic_year_id
                                            }
                                        >
                                            {backdatedProcessing ? 'Memproses...' : 'Lanjutkan'}
                                        </Button>
                                    </div>
                                </form>
                            </DialogContent>
                        </Dialog>
                    ) : block_reason === 'Semester Tidak Sesuai' ? (
                        <Button variant="red" size="xl" className="w-full lg:w-auto" asChild disabled>
                            <span>
                                <IconAlertCircle className="mr-1 size-4" />
                                Hubungi Admin
                            </span>
                        </Button>
                    ) : student?.classroom_id ? (
                        <Button variant="outline" size="xl" className="w-full lg:w-auto" asChild disabled>
                            <span>
                                <IconPlus className="mr-1 size-4" />
                                KRS Sudah Diajukan
                            </span>
                        </Button>
                    ) : (
                        <Button variant="blue" size="xl" className="w-full lg:w-auto" asChild>
                            <Link href={route('students.study-plans.select-classroom')}>
                                <IconSchool className="mr-1 size-4" />
                                Pilih Kelas
                            </Link>
                        </Button>
                    )}

                    {/* Always show backdated KRS option if available and not already showing */}
                    {can_create_backdated_study_plan && block_reason !== 'Semester Tidak Sesuai' && (
                        <Dialog>
                            <DialogTrigger asChild>
                                <Button variant="amber" size="xl" className="w-full lg:w-auto">
                                    <IconClock className="mr-1 size-4" />
                                    KRS Tertinggal
                                </Button>
                            </DialogTrigger>
                            <DialogContent className="sm:max-w-md">
                                <DialogHeader>
                                    <DialogTitle>Ajukan KRS Semester Tertinggal</DialogTitle>
                                    <DialogDescription>
                                        Pilih semester dan tahun ajaran untuk mengajukan KRS tertinggal
                                    </DialogDescription>
                                </DialogHeader>
                                <form onSubmit={submitBackdatedForm} className="space-y-4">
                                    <div className="space-y-2">
                                        <Label htmlFor="semester">Semester</Label>
                                        <RadioGroup
                                            id="semester"
                                            value={backdatedData.semester.toString()}
                                            onValueChange={(value) => setBackdatedData('semester', value)}
                                            className="flex flex-col space-y-2"
                                        >
                                            {missed_semesters.map((semester) => (
                                                <div key={semester} className="flex items-center space-x-2">
                                                    <RadioGroupItem
                                                        id={`semester-${semester}`}
                                                        value={semester.toString()}
                                                    />
                                                    <Label htmlFor={`semester-${semester}`} className="cursor-pointer">
                                                        Semester {semester}{' '}
                                                        {semester % 2 === 1 ? '(Ganjil)' : '(Genap)'}
                                                    </Label>
                                                </div>
                                            ))}
                                        </RadioGroup>
                                    </div>

                                    <div className="space-y-2">
                                        <Label htmlFor="academic_year">Tahun Ajaran</Label>
                                        <Select
                                            value={backdatedData.academic_year_id}
                                            onValueChange={(value) => setBackdatedData('academic_year_id', value)}
                                        >
                                            <SelectTrigger className="w-full">
                                                <SelectValue placeholder="Pilih Tahun Ajaran" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {academic_years.map((year) => (
                                                    <SelectItem key={year.id} value={year.id.toString()}>
                                                        {year.name} - {year.semester === 'odd' ? 'Ganjil' : 'Genap'}
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                    </div>

                                    <div className="mt-4 flex justify-end">
                                        <Button
                                            type="submit"
                                            variant="blue"
                                            disabled={
                                                backdatedProcessing ||
                                                !backdatedData.semester ||
                                                !backdatedData.academic_year_id
                                            }
                                        >
                                            {backdatedProcessing ? 'Memproses...' : 'Lanjutkan'}
                                        </Button>
                                    </div>
                                </form>
                            </DialogContent>
                        </Dialog>
                    )}
                </div>
            </div>

            {student && (
                <Card className="mb-6">
                    <CardHeader>
                        <CardTitle>Informasi Mahasiswa</CardTitle>
                        <CardDescription>Detail informasi akademik Anda</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div className="grid grid-cols-1 gap-6 md:grid-cols-3">
                            <div>
                                <p className="text-sm text-muted-foreground">Nama / NIM</p>
                                <p className="font-medium">{student.name}</p>
                                <p className="text-sm text-muted-foreground">{student.nim}</p>
                            </div>

                            <div>
                                <p className="text-sm text-muted-foreground">Program Studi / Fakultas</p>
                                <p className="font-medium">{student.department_name}</p>
                                <p className="text-sm text-muted-foreground">{student.faculty_name}</p>
                            </div>

                            <div>
                                <p className="text-sm text-muted-foreground">Semester / Kelas</p>
                                <p className="font-medium">Semester {student.semester}</p>
                                <p className="text-sm text-muted-foreground">
                                    {student.classroom_name ? (
                                        student.classroom_name
                                    ) : (
                                        <span className="text-amber-500">Belum memilih kelas</span>
                                    )}
                                </p>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            )}

            {/* Peringatan semester tidak sesuai dengan opsi KRS tertinggal */}
            {semester_mismatch && (
                <Alert variant="destructive" className="mb-6">
                    <IconAlertCircle className="h-4 w-4" />
                    <AlertTitle>Semester Tidak Sesuai</AlertTitle>
                    <AlertDescription>
                        Anda belum mengajukan KRS untuk semester sebelumnya.
                        {can_create_backdated_study_plan ? (
                            <span>
                                {' '}
                                Anda dapat menggunakan tombol <strong>Ajukan KRS Tertinggal</strong> untuk memulai
                                pengisian KRS semester tertinggal.
                            </span>
                        ) : (
                            <span> Harap hubungi admin untuk bantuan pengajuan KRS.</span>
                        )}
                    </AlertDescription>
                </Alert>
            )}

            {/* Alert untuk menampilkan KRS tertinggal jika ada */}
            {missed_semesters.length > 0 && !semester_mismatch && (
                <Alert variant="warning" className="mb-6">
                    <IconHistory className="h-4 w-4" />
                    <AlertTitle>KRS Semester Tertinggal</AlertTitle>
                    <AlertDescription>
                        Anda memiliki {missed_semesters.length} semester yang belum diajukan KRS:{' '}
                        {missed_semesters.map((sem, i) => (
                            <span key={sem}>
                                {i > 0 && ', '}
                                Semester {sem}
                            </span>
                        ))}
                        . Gunakan tombol <strong>KRS Tertinggal</strong> untuk mengisi KRS tersebut.
                    </AlertDescription>
                </Alert>
            )}

            <div className="flex flex-col gap-y-8">
                {/* Filters */}
                <div className="flex w-full flex-col gap-4 lg:flex-row lg:items-center">
                    <Input
                        className="w-full sm:w-1/4"
                        placeholder="Search..."
                        value={params?.search}
                        onChange={(e) => setParams((prev) => ({ ...prev, search: e.target.value }))}
                    />
                    <Select value={params?.load} onValueChange={(e) => setParams({ ...params, load: e })}>
                        <SelectTrigger className="w-full sm:w-24">
                            <SelectValue placeholder="Load" />
                        </SelectTrigger>
                        <SelectContent>
                            {[10, 25, 50, 75, 100].map((number, index) => (
                                <SelectItem key={index} value={number}>
                                    {number}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    <Button variant="red" onClick={() => setParams(state)} size="xl">
                        <IconRefresh className="mr-1 size-4" />
                        Bersihkan
                    </Button>
                </div>
                {/* Show Filter */}
                <ShowFilter params={params} />
                {data.length === 0 ? (
                    <EmptyState
                        icon={IconBuilding}
                        title="Tidak ada kartu rencana studi"
                        subtitle="Mulailah dengan membuat kartu rencana studi baru."
                    />
                ) : (
                    <Table className="w-full border">
                        <TableHeader>
                            <TableRow>
                                <TableHead className="border text-center">#</TableHead>
                                <TableHead className="border text-center">Semester</TableHead>
                                <TableHead className="border text-center">
                                    <Button
                                        variant="ghost"
                                        className="group inline-flex"
                                        onClick={() => onSortable('academic_year_id')}
                                    >
                                        Tahun Ajaran
                                        <span className="ml-2 flex-none rounded text-muted-foreground">
                                            <IconArrowsDownUp className="size-4" />
                                        </span>
                                    </Button>
                                </TableHead>
                                <TableHead className="border text-center">
                                    <Button
                                        variant="ghost"
                                        className="group inline-flex"
                                        onClick={() => onSortable('status')}
                                    >
                                        Status
                                        <span className="ml-2 flex-none rounded text-muted-foreground">
                                            <IconArrowsDownUp className="size-4" />
                                        </span>
                                    </Button>
                                </TableHead>
                                <TableHead className="border text-center">
                                    <Button
                                        variant="ghost"
                                        className="group inline-flex"
                                        onClick={() => onSortable('created_at')}
                                    >
                                        Dibuat Pada
                                        <span className="ml-2 flex-none rounded text-muted-foreground">
                                            <IconArrowsDownUp className="size-4" />
                                        </span>
                                    </Button>
                                </TableHead>
                                <TableHead className="border text-center">Aksi</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {data.map((studyPlan, index) => (
                                <TableRow key={index}>
                                    <TableCell className="border text-center">
                                        {index + 1 + (meta.current_page - 1) * meta.per_page}
                                    </TableCell>
                                    <TableCell className="border text-center">Semester {studyPlan.semester}</TableCell>
                                    <TableCell className="border text-center">{studyPlan.academicYear.name}</TableCell>
                                    <TableCell className="border text-center">
                                        <Badge variant={STUDYPLANSTATUSVARIANT[studyPlan.status]}>
                                            {studyPlan.status}
                                        </Badge>
                                    </TableCell>
                                    <TableCell className="border text-center">
                                        {formatDateIndo(studyPlan.created_at)}
                                    </TableCell>
                                    <TableCell className="border text-center align-middle">
                                        <div className="flex items-center justify-center gap-x-1">
                                            <Button variant="blue" size="sm" asChild>
                                                <Link href={route('students.study-plans.show', [studyPlan.id])}>
                                                    <IconEye className="size-4" />
                                                </Link>
                                            </Button>
                                            {studyPlan.status === 'Approved' && (
                                                <Button variant="green" size="sm" asChild>
                                                    <a
                                                        href={route('students.study-plans.download', [studyPlan.id])}
                                                        target="_blank"
                                                    >
                                                        <IconDownload className="size-4" />
                                                    </a>
                                                </Button>
                                            )}
                                        </div>
                                    </TableCell>
                                </TableRow>
                            ))}
                        </TableBody>
                    </Table>
                )}
                <div className="flex w-full flex-col items-center justify-between gap-y-2 lg:flex-row">
                    <p className="text-sm text-muted-foreground">
                        Menampilkan <span className="font-medium">{meta.from ?? 0}</span> -{' '}
                        <span className="font-medium">{meta.to ?? 0}</span> dari {meta.total} kartu rencana studi
                    </p>
                    <div className="overflow-x-auto">
                        {meta.has_pages && <PaginationTable meta={meta} links={links} />}
                    </div>
                </div>
            </div>
        </div>
    );
}

Index.layout = (page) => <StudentLayout title={page.props.page_settings.title} children={page} />;
