import EmptyState from '@/Components/EmptyState';
import HeaderTitle from '@/Components/HeaderTitle';
import { Alert, AlertDescription } from '@/Components/ui/alert';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardHeader } from '@/Components/ui/card';
import { Input } from '@/Components/ui/input';
import { Table, TableBody, TableCell, TableFooter, TableHead, TableHeader, TableRow } from '@/Components/ui/table';
import AppLayout from '@/Layouts/AppLayout';
import { flashMessage } from '@/lib/utils';
import { Link, useForm } from '@inertiajs/react';
import { IconArrowLeft, IconBook, IconCheck, IconReportAnalytics } from '@tabler/icons-react';
import { useEffect } from 'react';
import { toast } from 'sonner';

export default function Edit(props) {
    const { student, semester, studyResult, courseGrades } = props;

    const { data, setData, post, errors, processing, reset } = useForm({
        grades: [],
        _method: props.page_settings.method,
    });

    // Initialize form data
    useEffect(() => {
        if (courseGrades.length > 0) {
            const initialGrades = courseGrades.map((item) => {
                return {
                    course_id: item.course.id,
                    course_name: item.course.name,
                    course_code: item.course.kode_matkul,
                    classroom_id: item.classroom_id,
                    credit: item.course.credit,
                    tugas: item.grades.tugas,
                    uts: item.grades.uts,
                    uas: item.grades.uas,
                    attendance_count: item.attendance_count,
                    final_score: item.final_score || 0,
                    letter: item.letter || 'E',
                };
            });

            setData('grades', initialGrades);
        }
    }, [courseGrades]);

    // Update grades values
    const handleGradeChange = (courseId, field, value) => {
        const newGrades = data.grades.map((grade) => {
            if (grade.course_id === courseId) {
                const updatedGrade = { ...grade, [field]: value };

                // Recalculate the final score and letter grade
                const attendancePercent = (updatedGrade.attendance_count / 16) * 10;
                const taskPercent = updatedGrade.tugas * 0.5;
                const utsPercent = updatedGrade.uts * 0.15;
                const uasPercent = updatedGrade.uas * 0.25;

                const newFinalScore = Number((attendancePercent + taskPercent + utsPercent + uasPercent).toFixed(2));
                updatedGrade.final_score = newFinalScore;
                updatedGrade.letter = getLetterGrade(newFinalScore);

                return updatedGrade;
            }
            return grade;
        });

        setData('grades', newGrades);
    };

    // Determine letter grade from score
    const getLetterGrade = (score) => {
        if (score >= 80) return 'A';
        if (score >= 75) return 'B+';
        if (score >= 70) return 'B';
        if (score >= 65) return 'C+';
        if (score >= 55) return 'C';
        if (score >= 40) return 'D';
        return 'E';
    };

    const onHandleSubmit = (e) => {
        e.preventDefault();

        const confirmMessage = 'Apakah Anda yakin ingin menyimpan perubahan nilai mahasiswa?';
        if (confirm(confirmMessage)) {
            post(props.page_settings.action, {
                preserveScroll: true,
                preserveState: true,
                onSuccess: (success) => {
                    const flash = flashMessage(success);
                    if (flash) toast[flash.type](flash.message);
                },
            });
        }
    };

    return (
        <div className="flex w-full flex-col pb-32">
            <div className="mb-8 flex flex-col items-start justify-between gap-y-4 lg:flex-row lg:items-center">
                <HeaderTitle
                    title={props.page_settings.title}
                    subtitle={props.page_settings.subtitle}
                    icon={IconReportAnalytics}
                />
                <Button variant="ghost" asChild>
                    <Link href={route('admin.students.grades.select-semester', [student.student_number])}>
                        <IconArrowLeft className="mr-2 size-4" />
                        Kembali ke Pilihan Semester
                    </Link>
                </Button>
            </div>

            <Card>
                <CardHeader className="mb-4 p-0">
                    <div className="flex flex-wrap gap-4 px-6 py-4">
                        <div className="flex min-w-[200px] flex-col gap-1 border-r pr-4">
                            <span className="text-sm text-gray-500">Nama Mahasiswa</span>
                            <span className="font-semibold">{student.user.name}</span>
                        </div>
                        <div className="flex min-w-[120px] flex-col gap-1 border-r pr-4">
                            <span className="text-sm text-gray-500">NIM</span>
                            <span className="font-semibold">{student.student_number}</span>
                        </div>
                        <div className="flex min-w-[100px] flex-col gap-1 border-r pr-4">
                            <span className="text-sm text-gray-500">Semester</span>
                            <span className="font-semibold">{semester}</span>
                        </div>
                        <div className="flex min-w-[120px] flex-col gap-1 border-r pr-4">
                            <span className="text-sm text-gray-500">Program Studi</span>
                            <span className="font-semibold">{student.department.name}</span>
                        </div>
                        {studyResult && (
                            <div className="flex min-w-[80px] flex-col gap-1">
                                <span className="text-sm text-gray-500">IPK</span>
                                <span className="font-semibold">{studyResult.gpa || '0.00'}</span>
                            </div>
                        )}
                    </div>
                    <div className="space-y-4 px-6">
                        <Alert>
                            <AlertDescription>
                                Anda dapat mengubah komponen nilai (Tugas, UTS, UAS, dan Kehadiran) untuk setiap mata
                                kuliah. Nilai akhir akan dihitung otomatis berdasarkan bobot: Tugas (50%), UTS (15%),
                                UAS (25%), Kehadiran (10%).
                            </AlertDescription>
                        </Alert>
                        {errors && Object.keys(errors).length > 0 && (
                            <Alert variant="destructive">
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
                </CardHeader>
                <CardContent className="p-0 [&-td]:whitespace-nowrap [&-td]:px-6 [&-th]:px-6">
                    {data.grades.length === 0 ? (
                        <EmptyState
                            icon={IconBook}
                            title="Tidak ada data mata kuliah"
                            subtitle="Mahasiswa belum mengambil mata kuliah di semester ini atau tidak ada data KRS"
                        />
                    ) : (
                        <form onSubmit={onHandleSubmit}>
                            <div className="overflow-x-auto">
                                <Table className="w-full border">
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead rowSpan="2" className="w-10 max-w-10 border text-center">
                                                #
                                            </TableHead>
                                            <TableHead rowSpan="2" className="min-w-[240px] border text-center">
                                                Mata Kuliah
                                            </TableHead>
                                            <TableHead rowSpan="2" className="w-20 border text-center">
                                                Kode
                                            </TableHead>
                                            <TableHead rowSpan="2" className="w-16 border text-center">
                                                SKS
                                            </TableHead>
                                            <TableHead colSpan="4" className="border text-center">
                                                Komponen Nilai
                                            </TableHead>
                                            <TableHead rowSpan="2" className="border text-center">
                                                Nilai Akhir
                                            </TableHead>
                                            <TableHead rowSpan="2" className="border text-center">
                                                Huruf Mutu
                                            </TableHead>
                                        </TableRow>
                                        <TableRow>
                                            <TableHead className="border text-center">Tugas (50%)</TableHead>
                                            <TableHead className="border text-center">UTS (15%)</TableHead>
                                            <TableHead className="border text-center">UAS (25%)</TableHead>
                                            <TableHead className="border text-center">Kehadiran (10%)</TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {data.grades.map((grade, index) => (
                                            <TableRow key={index}>
                                                <TableCell className="border text-center">{index + 1}</TableCell>
                                                <TableCell className="border">{grade.course_name}</TableCell>
                                                <TableCell className="border text-center">
                                                    {grade.course_code}
                                                </TableCell>
                                                <TableCell className="border text-center">{grade.credit}</TableCell>
                                                {/* Tugas Field */}
                                                <TableCell className="border text-center">
                                                    <Input
                                                        className="w-[60px] text-center"
                                                        value={grade.tugas}
                                                        type="number"
                                                        min="0"
                                                        max="100"
                                                        onChange={(e) =>
                                                            handleGradeChange(
                                                                grade.course_id,
                                                                'tugas',
                                                                parseInt(e.target.value) || 0,
                                                            )
                                                        }
                                                    />
                                                </TableCell>
                                                {/* UTS Field */}
                                                <TableCell className="border text-center">
                                                    <Input
                                                        className="w-[60px] text-center"
                                                        value={grade.uts}
                                                        type="number"
                                                        min="0"
                                                        max="100"
                                                        onChange={(e) =>
                                                            handleGradeChange(
                                                                grade.course_id,
                                                                'uts',
                                                                parseInt(e.target.value) || 0,
                                                            )
                                                        }
                                                    />
                                                </TableCell>
                                                {/* UAS Field */}
                                                <TableCell className="border text-center">
                                                    <Input
                                                        className="w-[60px] text-center"
                                                        value={grade.uas}
                                                        type="number"
                                                        min="0"
                                                        max="100"
                                                        onChange={(e) =>
                                                            handleGradeChange(
                                                                grade.course_id,
                                                                'uas',
                                                                parseInt(e.target.value) || 0,
                                                            )
                                                        }
                                                    />
                                                </TableCell>
                                                {/* Attendance Field */}
                                                <TableCell className="border text-center">
                                                    <Input
                                                        className="w-[60px] text-center"
                                                        value={grade.attendance_count}
                                                        type="number"
                                                        min="0"
                                                        max="16"
                                                        onChange={(e) =>
                                                            handleGradeChange(
                                                                grade.course_id,
                                                                'attendance_count',
                                                                parseInt(e.target.value) || 0,
                                                            )
                                                        }
                                                    />
                                                </TableCell>
                                                <TableCell className="border text-center font-semibold">
                                                    {grade.final_score.toFixed(2)}
                                                </TableCell>
                                                <TableCell className="border text-center font-semibold">
                                                    {grade.letter}
                                                </TableCell>
                                            </TableRow>
                                        ))}
                                    </TableBody>
                                    <TableFooter>
                                        <TableRow>
                                            <TableCell colSpan="10" className="border-t p-0"></TableCell>
                                        </TableRow>
                                    </TableFooter>
                                </Table>
                            </div>

                            <div className="mb-4 mt-6 flex justify-center">
                                <Button variant="orange" type="submit" size="lg" disabled={processing} className="px-8">
                                    <IconCheck className="mr-2 size-4" />
                                    Simpan Perubahan
                                </Button>
                            </div>
                        </form>
                    )}
                </CardContent>
            </Card>
        </div>
    );
}

Edit.layout = (page) => <AppLayout title={page.props.page_settings.title} children={page} />;
