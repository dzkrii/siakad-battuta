import EmptyState from '@/Components/EmptyState';
import HeaderTitle from '@/Components/HeaderTitle';
import ShowFilter from '@/Components/ShowFilter';
import { Alert, AlertDescription } from '@/Components/ui/alert';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardHeader } from '@/Components/ui/card';
import { Checkbox } from '@/Components/ui/checkbox';
import { Input } from '@/Components/ui/input';
import { Table, TableBody, TableCell, TableFooter, TableHead, TableHeader, TableRow } from '@/Components/ui/table';
import UseFilter from '@/hooks/UseFilter.';
import AppLayout from '@/Layouts/AppLayout';
import { flashMessage } from '@/lib/utils';
import { useForm } from '@inertiajs/react';
import {
    IconCheck,
    IconDoor,
    IconDownload,
    IconEdit,
    IconFileUpload,
    IconRefresh,
    IconUsers,
} from '@tabler/icons-react';
import { useEffect, useState } from 'react';
import { toast } from 'sonner';

export default function Index(props) {
    const students = props.students;

    const [params, setParams] = useState(props.state);
    const [editMode, setEditMode] = useState(false);

    UseFilter({
        route: route('teachers.classrooms.index', [props.course, props.classroom]),
        values: params,
        only: ['students'],
    });

    const { data, setData, post, errors, processing, reset } = useForm({
        attendances: [],
        grades: [],
        _method: props.page_settings.method,
    });

    // Pre-populate form data when edit mode is activated
    useEffect(() => {
        if (editMode) {
            const initialGrades = [];

            // Initialize grades for all students in edit mode
            students.forEach((student) => {
                // For each category, check if there's existing data and add it to the initial form data
                ['tugas', 'uts', 'uas'].forEach((category) => {
                    const existingGrade = getGradeStudent(student.id, student.grades, category, null);
                    if (existingGrade) {
                        initialGrades.push({
                            student_id: student.id,
                            course_id: props.course.id,
                            classroom_id: props.classroom.id,
                            category: category,
                            section: null,
                            grade: existingGrade.grade,
                        });
                    }
                });
            });

            setData('grades', initialGrades);
        } else {
            // Clear form data when exiting edit mode
            reset();
        }
    }, [editMode]);

    const onHandleSubmit = (e) => {
        e.preventDefault();

        const confirmMessage = 'Apakah Anda yakin ingin menyimpan dan memperbarui nilai akhir?';
        if (confirm(confirmMessage)) {
            post(props.page_settings.action, {
                preserveScroll: true,
                preserveState: true,
                onSuccess: (success) => {
                    const flash = flashMessage(success);
                    if (flash) toast[flash.type](flash.message);
                    reset();
                    setEditMode(false);
                },
            });
        }
    };

    const isAttendanceChecked = (attendances, studentId, section) => {
        return attendances.some(
            (attendance) => attendance.student_id === studentId && attendance.section === section && attendance.status,
        );
    };

    const updateAttendance = (attendances, setData, studentId, section, checked) => {
        const updatedAttendance = attendances.filter(
            (attendance) => !(attendance.student_id === studentId && attendance.section === section),
        );

        if (checked) {
            updatedAttendance.push({
                student_id: studentId,
                course_id: props.course.id,
                classroom_id: props.classroom.id,
                section: section,
                status: true,
            });
        }

        setData('attendances', updatedAttendance);
    };

    const getGradeValue = (grades, studentId, category, section) => {
        return (
            grades.find(
                (grade) => grade.student_id === studentId && grade.category === category && grade.section === section,
            )?.grade || ''
        );
    };

    const updateGrade = (grades, setData, studentId, category, section, gradeValue) => {
        const updatedGrades = grades.filter(
            (grade) => !(grade.student_id === studentId && grade.category === category && grade.section === section),
        );

        updatedGrades.push({
            student_id: studentId,
            course_id: props.course.id,
            classroom_id: props.classroom.id,
            category: category,
            section: section,
            grade: parseInt(gradeValue, 10) || 0,
        });

        setData('grades', updatedGrades);
    };

    const getAttendanceStudent = (student_id, attendances, section) => {
        return attendances.find((grade) => grade.student_id === student_id && grade.section === section);
    };

    const getGradeStudent = (student_id, grades, category, section) => {
        return grades.find(
            (grade) => grade.student_id === student_id && grade.category === category && grade.section === section,
        );
    };

    const toggleEditMode = () => {
        setEditMode(!editMode);
    };

    return (
        <div className="flex w-full flex-col pb-32">
            <div className="mb-8 flex flex-col items-start justify-between gap-y-4 lg:flex-row lg:items-center">
                <HeaderTitle
                    title={props.page_settings.title}
                    subtitle={props.page_settings.subtitle}
                    icon={IconDoor}
                />

                <div className="flex flex-wrap gap-2">
                    <Button
                        variant={editMode ? 'green' : 'blue'}
                        onClick={toggleEditMode}
                        className="flex items-center gap-2"
                    >
                        <IconEdit className="size-4" />
                        {editMode ? 'Mode Edit Aktif' : 'Edit Nilai'}
                    </Button>

                    <Button
                        variant="blue"
                        disabled={true}
                        onClick={() =>
                            (window.location.href = route('teachers.classrooms.import.index', [
                                props.course.id,
                                props.classroom.id,
                            ]))
                        }
                        className="flex items-center gap-2"
                    >
                        <IconFileUpload className="size-4" />
                        Import Excel
                    </Button>

                    <Button
                        variant="green"
                        disabled={true}
                        onClick={() =>
                            (window.location.href = route('teachers.classrooms.template.attendance', [
                                props.course.id,
                                props.classroom.id,
                            ]))
                        }
                        className="flex items-center gap-2"
                    >
                        <IconDownload className="size-4" />
                        Template Absensi
                    </Button>

                    <Button
                        variant="green"
                        disabled={true}
                        onClick={() =>
                            (window.location.href = route('teachers.classrooms.template.grade', [
                                props.course.id,
                                props.classroom.id,
                            ]))
                        }
                        className="flex items-center gap-2"
                    >
                        <IconDownload className="size-4" />
                        Template Nilai
                    </Button>
                </div>
            </div>
            <Card>
                <CardHeader className="mb-4 p-0">
                    <div className="flex w-full flex-col gap-4 px-6 py-4 lg:flex-row lg:items-center">
                        <Input
                            className="w-full sm:w-1/4"
                            placeholder="Cari nama mahasiswa"
                            values={params?.search}
                            onChange={(e) => setParams((prev) => ({ ...prev, search: e.target.value }))}
                        />
                        <Button variant="red" size="xl" onClick={(e) => setParams(props.state)}>
                            <IconRefresh className="size-4" />
                            Bersihkan
                        </Button>
                    </div>
                    <div className="space-y-4 px-6">
                        {editMode ? (
                            <Alert>
                                <AlertDescription>
                                    Mode edit aktif. Anda dapat mengubah nilai yang sudah disimpan. Jangan lupa klik
                                    "Simpan Perubahan" setelah selesai mengedit.
                                </AlertDescription>
                            </Alert>
                        ) : (
                            <Alert variant="destructive">
                                <AlertDescription>
                                    Harap isi dengan teliti, pastikan Anda memeriksa nilai sebelum menyimpan
                                </AlertDescription>
                            </Alert>
                        )}
                        {errors && Object.keys(errors).length > 0 && (
                            <Alert variant="red">
                                <AlertDescription>
                                    {typeof errors === 'string' ? (
                                        errors
                                    ) : (
                                        <ul>
                                            {Object.entries(errors).map(([key, message]) => (
                                                <li key={key}>{message}</li>
                                            ))}
                                        </ul>
                                    )}
                                </AlertDescription>
                            </Alert>
                        )}
                    </div>
                    <ShowFilter params={params} />
                </CardHeader>
                <CardContent className="p-0 [&-td]:whitespace-nowrap [&-td]:px-6 [&-th]:px-6">
                    {students.length === 0 ? (
                        <EmptyState
                            icon={IconUsers}
                            title="Tidak ada mahasiswa"
                            subtitle="Tidak ada mahasiswa yang bergabung di kelas ini"
                        />
                    ) : (
                        <div>
                            <form onSubmit={onHandleSubmit}>
                                <div className="overflow-x-auto">
                                    <Table className="w-full border">
                                        <TableHeader>
                                            <TableRow>
                                                <TableHead rowSpan="2" className="w-10 max-w-10 border text-center">
                                                    #
                                                </TableHead>
                                                <TableHead rowSpan="2" className="min-w-[180px] border text-center">
                                                    Nama
                                                </TableHead>
                                                <TableHead rowSpan="2" className="w-28 border text-center">
                                                    NIM
                                                </TableHead>
                                                <TableHead colSpan="16" className="border text-center">
                                                    Absensi
                                                </TableHead>
                                                <TableHead rowSpan="2" className="border text-center">
                                                    Tugas
                                                </TableHead>
                                                <TableHead rowSpan="2" className="border text-center">
                                                    UTS
                                                </TableHead>
                                                <TableHead rowSpan="2" className="border text-center">
                                                    UAS
                                                </TableHead>
                                                <TableHead colSpan="4" className="border text-center">
                                                    Total
                                                </TableHead>
                                                <TableHead colSpan="4" className="border text-center">
                                                    Persentase Nilai
                                                </TableHead>
                                                <TableHead rowSpan="2" className="border text-center">
                                                    Nilai Akhir
                                                </TableHead>
                                                <TableHead rowSpan="2" className="border text-center">
                                                    Huruf Mutu
                                                </TableHead>
                                            </TableRow>
                                            <TableRow className="border text-center">
                                                {Array.from({ length: 16 }).map((_, i) => (
                                                    <TableHead key={i} className="border text-center">
                                                        {i + 1}
                                                    </TableHead>
                                                ))}
                                                <TableHead className="border text-center">Absen</TableHead>
                                                <TableHead className="border text-center">Tugas</TableHead>
                                                <TableHead className="border text-center">UTS</TableHead>
                                                <TableHead className="border text-center">UAS</TableHead>
                                                <TableHead className="border text-center">Absen (10%)</TableHead>
                                                <TableHead className="border text-center">Tugas (50%)</TableHead>
                                                <TableHead className="border text-center">UTS (15%)</TableHead>
                                                <TableHead className="border text-center">UAS (25%)</TableHead>
                                            </TableRow>
                                        </TableHeader>
                                        <TableBody>
                                            {students.map((student, index) => (
                                                <TableRow key={index}>
                                                    <TableCell className="w-10 max-w-10 border text-center">
                                                        {index + 1}
                                                    </TableCell>
                                                    <TableCell className="min-w-[200px] border">
                                                        <div className="flex items-center gap-2">
                                                            <span>{student.user.name}</span>
                                                        </div>
                                                    </TableCell>
                                                    <TableCell className="border text-center">
                                                        {student.student_number}
                                                    </TableCell>
                                                    {/* Array Absensi */}
                                                    {Array.from({ length: 16 }).map((_, section) => {
                                                        const attendance = getAttendanceStudent(
                                                            student.id,
                                                            student.attendances,
                                                            section + 1,
                                                        );
                                                        return (
                                                            <TableCell key={section} className="border">
                                                                {attendance ? (
                                                                    <IconCheck className="size-3 text-green-500" />
                                                                ) : (
                                                                    <Checkbox
                                                                        id={`attendances_${student.id}_section_${section + 1}`}
                                                                        name="attendances"
                                                                        checked={isAttendanceChecked(
                                                                            data.attendances,
                                                                            student.id,
                                                                            section + 1,
                                                                        )}
                                                                        onCheckedChange={(checked) =>
                                                                            updateAttendance(
                                                                                data.attendances,
                                                                                setData,
                                                                                student.id,
                                                                                section + 1,
                                                                                checked,
                                                                            )
                                                                        }
                                                                    />
                                                                )}
                                                            </TableCell>
                                                        );
                                                    })}

                                                    {/* Array Tugas */}
                                                    <TableCell className="border text-center">
                                                        {getGradeStudent(student.id, student.grades, 'tugas', null) &&
                                                        !editMode ? (
                                                            getGradeStudent(student.id, student.grades, 'tugas', null)
                                                                .grade
                                                        ) : (
                                                            <Input
                                                                className="w-[60px]"
                                                                value={getGradeValue(
                                                                    data.grades,
                                                                    student.id,
                                                                    'tugas',
                                                                    null,
                                                                )}
                                                                onChange={(e) => {
                                                                    updateGrade(
                                                                        data.grades,
                                                                        setData,
                                                                        student.id,
                                                                        'tugas',
                                                                        null,
                                                                        e.target.value,
                                                                    );
                                                                }}
                                                            />
                                                        )}
                                                    </TableCell>

                                                    {/* UTS */}
                                                    <TableCell className="border text-center">
                                                        {getGradeStudent(student.id, student.grades, 'uts', null) &&
                                                        !editMode ? (
                                                            getGradeStudent(student.id, student.grades, 'uts', null)
                                                                .grade
                                                        ) : (
                                                            <Input
                                                                className="w-[60px]"
                                                                value={getGradeValue(
                                                                    data.grades,
                                                                    student.id,
                                                                    'uts',
                                                                    null,
                                                                )}
                                                                onChange={(e) => {
                                                                    updateGrade(
                                                                        data.grades,
                                                                        setData,
                                                                        student.id,
                                                                        'uts',
                                                                        null,
                                                                        e.target.value,
                                                                    );
                                                                }}
                                                            />
                                                        )}
                                                    </TableCell>

                                                    {/* UAS */}
                                                    <TableCell className="border text-center">
                                                        {getGradeStudent(student.id, student.grades, 'uas', null) &&
                                                        !editMode ? (
                                                            getGradeStudent(student.id, student.grades, 'uas', null)
                                                                .grade
                                                        ) : (
                                                            <Input
                                                                className="w-[60px]"
                                                                value={getGradeValue(
                                                                    data.grades,
                                                                    student.id,
                                                                    'uas',
                                                                    null,
                                                                )}
                                                                onChange={(e) => {
                                                                    updateGrade(
                                                                        data.grades,
                                                                        setData,
                                                                        student.id,
                                                                        'uas',
                                                                        null,
                                                                        e.target.value,
                                                                    );
                                                                }}
                                                            />
                                                        )}
                                                    </TableCell>
                                                    <TableCell className="border text-center">
                                                        {student.total.attendances_count}
                                                    </TableCell>
                                                    <TableCell className="border text-center">
                                                        {student.total.tasks_count}
                                                    </TableCell>
                                                    <TableCell className="border text-center">
                                                        {student.total.uts_count}
                                                    </TableCell>
                                                    <TableCell className="border text-center">
                                                        {student.total.uas_count}
                                                    </TableCell>
                                                    <TableCell className="border text-center">
                                                        {student.percentage.attendance_percentage}
                                                    </TableCell>
                                                    <TableCell className="border text-center">
                                                        {student.percentage.task_percentage}
                                                    </TableCell>
                                                    <TableCell className="border text-center">
                                                        {student.percentage.uts_percentage}
                                                    </TableCell>
                                                    <TableCell className="border text-center">
                                                        {student.percentage.uas_percentage}
                                                    </TableCell>
                                                    <TableCell className="border text-center">
                                                        {student.final_score}
                                                    </TableCell>
                                                    <TableCell className="border text-center">
                                                        {student.letter}
                                                    </TableCell>
                                                </TableRow>
                                            ))}
                                        </TableBody>
                                        <TableFooter>
                                            <TableRow>
                                                <TableCell colSpan="31" className="border-none p-0"></TableCell>
                                            </TableRow>
                                        </TableFooter>
                                    </Table>
                                </div>

                                <div className="mb-4 mt-6 flex justify-center">
                                    <Button
                                        variant="orange"
                                        type="submit"
                                        size="lg"
                                        disabled={processing}
                                        className="px-8"
                                    >
                                        <IconCheck className="mr-2" />
                                        Simpan Perubahan
                                    </Button>
                                </div>
                            </form>
                        </div>
                    )}
                </CardContent>
            </Card>
        </div>
    );
}

Index.layout = (page) => <AppLayout children={page} title={page.props.page_settings.title} />;
