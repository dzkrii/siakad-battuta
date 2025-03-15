import EmptyState from '@/Components/EmptyState';
import HeaderTitle from '@/Components/HeaderTitle';
import PaginationTable from '@/Components/PaginationTable';
import ShowFilter from '@/Components/ShowFilter';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/ui/card';
import { Input } from '@/Components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/Components/ui/table';
import UseFilter from '@/hooks/UseFilter.';
import StudentLayout from '@/Layouts/StudentLayout';
import { formatDateIndo, STUDYPLANSTATUSVARIANT } from '@/lib/utils';
import { Link, usePage } from '@inertiajs/react';
import {
    IconArrowsDownUp,
    IconBuilding,
    IconDownload,
    IconEye,
    IconPlus,
    IconRefresh,
    IconSchool,
} from '@tabler/icons-react';
import { useState } from 'react';

export default function Index() {
    const { page_settings, studyPlans, state, can_create_study_plan, student } = usePage().props;
    const { data, meta, links } = studyPlans;
    const [params, setParams] = useState(state);

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
                    {can_create_study_plan ? (
                        <Button variant="orange" size="xl" className="w-full lg:w-auto" asChild>
                            <Link href={route('students.study-plans.create')}>
                                <IconPlus className="mr-1 size-4" />
                                Ajukan KRS
                            </Link>
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
                    <Table className="w-full">
                        <TableHeader>
                            <TableRow>
                                <TableHead>#</TableHead>
                                <TableHead>Semester</TableHead>
                                <TableHead>
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
                                <TableHead>
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
                                <TableHead>
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
                                <TableHead>Aksi</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {data.map((studyPlan, index) => (
                                <TableRow key={index}>
                                    <TableCell>{index + 1 + (meta.current_page - 1) * meta.per_page}</TableCell>
                                    <TableCell>Semester {studyPlan.semester}</TableCell>
                                    <TableCell>{studyPlan.academicYear.name}</TableCell>
                                    <TableCell>
                                        <Badge variant={STUDYPLANSTATUSVARIANT[studyPlan.status]}>
                                            {studyPlan.status}
                                        </Badge>
                                    </TableCell>
                                    <TableCell>{formatDateIndo(studyPlan.created_at)}</TableCell>
                                    <TableCell>
                                        <div className="flex items-center gap-x-1">
                                            <Button variant="blue" size="sm" asChild>
                                                <Link href={route('students.study-plans.show', [studyPlan.id])}>
                                                    <IconEye className="size-4" />
                                                </Link>
                                            </Button>
                                            {studyPlan.status === 'APPROVED' && (
                                                <Button variant="green" size="sm" asChild>
                                                    <a
                                                        href={route('students.study-plans.download-pdf', [
                                                            studyPlan.id,
                                                        ])}
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
