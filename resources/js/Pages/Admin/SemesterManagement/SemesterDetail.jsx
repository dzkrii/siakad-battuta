import AlertAction from '@/Components/AlertAction';
import EmptyState from '@/Components/EmptyState';
import HeaderTitle from '@/Components/HeaderTitle';
import PaginationTable from '@/Components/PaginationTable';
import ShowFilter from '@/Components/ShowFilter';
import { Avatar, AvatarFallback, AvatarImage } from '@/Components/ui/avatar';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardFooter, CardHeader } from '@/Components/ui/card';
import { Checkbox } from '@/Components/ui/checkbox';
import { Input } from '@/Components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/Components/ui/table';
import UseFilter from '@/hooks/UseFilter.';
import AppLayout from '@/Layouts/AppLayout';
import { router } from '@inertiajs/react';
import {
    IconArrowsDownUp,
    IconArrowsUp,
    IconCalendarUp,
    IconChevronLeft,
    IconRefresh,
    IconUsers,
} from '@tabler/icons-react';
import { useState } from 'react';

export default function SemesterDetail(props) {
    const { data: students, meta, links } = props.students;
    const [params, setParams] = useState(props.state);
    const [selectedStudents, setSelectedStudents] = useState([]);
    const [selectAll, setSelectAll] = useState(false);

    const onSortable = (field) => {
        setParams({
            ...params,
            field: field,
            direction: params.direction === 'asc' ? 'desc' : 'asc',
        });
    };

    UseFilter({
        route: route('admin.semester-management.show', props.semester),
        values: params,
        only: ['students'],
    });

    const handleSelectAll = () => {
        if (selectAll) {
            setSelectedStudents([]);
        } else {
            setSelectedStudents(students.map((student) => student.id));
        }
        setSelectAll(!selectAll);
    };

    const handleSelectStudent = (studentId) => {
        if (selectedStudents.includes(studentId)) {
            setSelectedStudents(selectedStudents.filter((id) => id !== studentId));
            setSelectAll(false);
        } else {
            setSelectedStudents([...selectedStudents, studentId]);
            if (selectedStudents.length + 1 === students.length) {
                setSelectAll(true);
            }
        }
    };

    const handleIncreaseSemester = () => {
        if (selectedStudents.length === 0) {
            return;
        }

        router.post(route('admin.semester-management.increase'), {
            student_ids: selectedStudents,
        });
    };

    const handleIncreaseAllSemester = () => {
        router.post(route('admin.semester-management.increase-all', props.semester));
    };

    return (
        <div className="flex w-full flex-col pb-32">
            <div className="mb-8 flex flex-col items-start justify-between gap-y-4 lg:flex-row lg:items-center">
                <HeaderTitle
                    title={props.page_settings.title}
                    subtitle={props.page_settings.subtitle}
                    icon={IconCalendarUp}
                />
                <div className="flex w-full flex-col gap-2 lg:w-auto lg:flex-row">
                    <Button variant="outline" size="xl" asChild>
                        <a href={route('admin.semester-management.index')}>
                            <IconChevronLeft className="mr-1 size-4" />
                            Kembali
                        </a>
                    </Button>
                    <AlertAction
                        trigger={
                            <Button variant="primary" size="xl" disabled={students.length === 0}>
                                <IconArrowsUp className="mr-1 size-4" />
                                Naikkan Semua ke Semester {parseInt(props.semester) + 1}
                            </Button>
                        }
                        title={`Naikkan Semua Mahasiswa Semester ${props.semester}?`}
                        description={`Semua mahasiswa semester ${props.semester} dengan KRS status approved akan dinaikkan ke semester ${
                            parseInt(props.semester) + 1
                        }. Kelas mahasiswa akan direset. Apakah Anda yakin?`}
                        action={handleIncreaseAllSemester}
                    />

                    <AlertAction
                        trigger={
                            <Button variant="purple" size="xl" disabled={selectedStudents.length === 0}>
                                <IconArrowsUp className="mr-1 size-4" />
                                Naikkan {selectedStudents.length} Mahasiswa Terpilih
                            </Button>
                        }
                        title={`Naikkan ${selectedStudents.length} Mahasiswa Terpilih?`}
                        description={`${selectedStudents.length} mahasiswa terpilih akan dinaikkan ke semester ${
                            parseInt(props.semester) + 1
                        }. Kelas mahasiswa akan direset. Apakah Anda yakin?`}
                        action={handleIncreaseSemester}
                    />
                </div>
            </div>
            <Card>
                <CardHeader className="mb-4 p-0">
                    {/* Filters */}
                    <div className="flex w-full flex-col gap-4 px-6 py-4 lg:flex-row lg:items-center">
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
                        <Button variant="red" onClick={() => setParams(props.state)} size="xl">
                            <IconRefresh className="size-4" />
                            Bersihkan
                        </Button>
                    </div>
                    {/* Show Filter */}
                    <ShowFilter params={params} />
                </CardHeader>
                <CardContent className="p-0 [&-td]:whitespace-nowrap [&-td]:px-6 [&-th]:px-6">
                    {students.length === 0 ? (
                        <EmptyState
                            icon={IconUsers}
                            title={`Tidak ada mahasiswa semester ${props.semester} dengan KRS approved`}
                            subtitle="Silakan periksa status KRS mahasiswa pada semester ini."
                        />
                    ) : (
                        <Table className="w-full">
                            <TableHeader>
                                <TableRow>
                                    <TableHead>
                                        <Checkbox checked={selectAll} onCheckedChange={handleSelectAll} />
                                    </TableHead>
                                    <TableHead>#</TableHead>
                                    <TableHead>
                                        <Button
                                            variant="ghost"
                                            className="group inline-flex"
                                            onClick={() => onSortable('name')}
                                        >
                                            Nama
                                            <span className="ml-2 flex-none rounded text-muted-foreground">
                                                <IconArrowsDownUp className="size-4" />
                                            </span>
                                        </Button>
                                    </TableHead>
                                    <TableHead>
                                        <Button
                                            variant="ghost"
                                            className="group inline-flex"
                                            onClick={() => onSortable('student_number')}
                                        >
                                            NIM
                                            <span className="ml-2 flex-none rounded text-muted-foreground">
                                                <IconArrowsDownUp className="size-4" />
                                            </span>
                                        </Button>
                                    </TableHead>
                                    <TableHead>
                                        <Button
                                            variant="ghost"
                                            className="group inline-flex"
                                            onClick={() => onSortable('faculty_id')}
                                        >
                                            Fakultas
                                            <span className="ml-2 flex-none rounded text-muted-foreground">
                                                <IconArrowsDownUp className="size-4" />
                                            </span>
                                        </Button>
                                    </TableHead>
                                    <TableHead>
                                        <Button
                                            variant="ghost"
                                            className="group inline-flex"
                                            onClick={() => onSortable('department_id')}
                                        >
                                            Program Studi
                                            <span className="ml-2 flex-none rounded text-muted-foreground">
                                                <IconArrowsDownUp className="size-4" />
                                            </span>
                                        </Button>
                                    </TableHead>
                                    <TableHead>
                                        <Button
                                            variant="ghost"
                                            className="group inline-flex"
                                            onClick={() => onSortable('classroom_id')}
                                        >
                                            Kelas
                                            <span className="ml-2 flex-none rounded text-muted-foreground">
                                                <IconArrowsDownUp className="size-4" />
                                            </span>
                                        </Button>
                                    </TableHead>
                                    <TableHead>
                                        <Button
                                            variant="ghost"
                                            className="group inline-flex"
                                            onClick={() => onSortable('batch')}
                                        >
                                            Angkatan
                                            <span className="ml-2 flex-none rounded text-muted-foreground">
                                                <IconArrowsDownUp className="size-4" />
                                            </span>
                                        </Button>
                                    </TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {students.map((student, index) => (
                                    <TableRow key={index}>
                                        <TableCell>
                                            <Checkbox
                                                checked={selectedStudents.includes(student.id)}
                                                onCheckedChange={() => handleSelectStudent(student.id)}
                                            />
                                        </TableCell>
                                        <TableCell>{index + 1 + (meta.current_page - 1) * meta.per_page}</TableCell>
                                        <TableCell className="flex items-center gap-2">
                                            <Avatar>
                                                <AvatarImage src={student.user.avatar} />
                                                <AvatarFallback>{student.user.name.substring(0, 1)}</AvatarFallback>
                                            </Avatar>
                                            <span>{student.user.name}</span>
                                        </TableCell>
                                        <TableCell>{student.student_number}</TableCell>
                                        <TableCell>{student.faculty.name}</TableCell>
                                        <TableCell>{student.department.name}</TableCell>
                                        <TableCell>{student.classroom?.name || '-'}</TableCell>
                                        <TableCell>{student.batch}</TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    )}
                </CardContent>
                <CardFooter className="flex w-full flex-col items-center justify-between gap-y-2 border-t py-3 lg:flex-row">
                    <p className="text-sm text-muted-foreground">
                        Menampilkan <span className="font-medium">{meta.from ?? 0}</span> -{' '}
                        <span className="font-medium">{meta.to ?? 0}</span> dari {meta.total} mahasiswa
                    </p>
                    <div className="overflow-x-auto">
                        {meta.has_pages && <PaginationTable meta={meta} links={links} />}
                    </div>
                </CardFooter>
            </Card>
        </div>
    );
}

SemesterDetail.layout = (page) => <AppLayout title={page.props.page_settings.title} children={page} />;
