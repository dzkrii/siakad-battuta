import HeaderTitle from '@/Components/HeaderTitle';
import { Alert, AlertDescription } from '@/Components/ui/alert';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardHeader } from '@/Components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/Components/ui/table';
import AppLayout from '@/Layouts/AppLayout';
import { Link } from '@inertiajs/react';
import { IconChevronRight, IconReportAnalytics, IconSchool } from '@tabler/icons-react';

export default function SelectSemester(props) {
    const { student, studyResults, semesters } = props;

    // Function to calculate overall GPA (IPK)
    const calculateOverallGPA = (studyResults) => {
        if (!studyResults || studyResults.length === 0) return '0.00';

        let totalWeightedGPA = 0;
        let totalCredits = 0;

        // Calculate weighted GPA (GPA * credits for each semester)
        studyResults.forEach((result) => {
            const credits = parseInt(result.total_credit) || 0;
            const gpa = parseFloat(result.gpa) || 0;

            totalWeightedGPA += gpa * credits;
            totalCredits += credits;
        });

        // Prevent division by zero
        if (totalCredits === 0) return '0.00';

        // Calculate overall GPA and format to 2 decimal places
        const overallGPA = (totalWeightedGPA / totalCredits).toFixed(2);

        return overallGPA;
    };

    return (
        <div className="flex w-full flex-col pb-32">
            <div className="mb-8 flex flex-col items-start justify-between gap-y-4 lg:flex-row lg:items-center">
                <HeaderTitle
                    title={props.page_settings.title}
                    subtitle={props.page_settings.subtitle}
                    icon={IconReportAnalytics}
                />
            </div>

            <div className="grid grid-cols-1 gap-6 md:grid-cols-2">
                {/* Student Information */}
                <Card>
                    <CardHeader className="flex flex-row items-center justify-between border-b">
                        <h3 className="text-lg font-medium">Informasi Mahasiswa</h3>
                    </CardHeader>
                    <CardContent className="p-4">
                        <div className="space-y-3">
                            <div className="flex justify-between border-b pb-2">
                                <span className="font-semibold">Nama:</span>
                                <span>{student.user.name}</span>
                            </div>
                            <div className="flex justify-between border-b pb-2">
                                <span className="font-semibold">NIM:</span>
                                <span>{student.student_number}</span>
                            </div>
                            <div className="flex justify-between border-b pb-2">
                                <span className="font-semibold">Fakultas:</span>
                                <span>{student.faculty.name}</span>
                            </div>
                            <div className="flex justify-between border-b pb-2">
                                <span className="font-semibold">Program Studi:</span>
                                <span>{student.department.name}</span>
                            </div>
                            <div className="flex justify-between border-b pb-2">
                                <span className="font-semibold">Kelas:</span>
                                <span>{student.classroom?.name || '-'}</span>
                            </div>
                            <div className="flex justify-between pb-2">
                                <span className="font-semibold">Semester Saat Ini:</span>
                                <span>{student.semester}</span>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                {/* Select Semester Card */}
                <Card>
                    <CardHeader className="flex flex-row items-center justify-between border-b">
                        <h3 className="text-lg font-medium">Pilih Semester untuk Edit Nilai</h3>
                    </CardHeader>
                    <CardContent className="p-4">
                        <Alert className="mb-4">
                            <AlertDescription>
                                Pilih semester untuk mengedit nilai mahasiswa. Perubahan nilai akan mempengaruhi IPK dan
                                status akademik mahasiswa.
                            </AlertDescription>
                        </Alert>

                        <div className="space-y-2">
                            {semesters.map((semester) => {
                                const studyResult = studyResults.find((sr) => sr.semester === semester);
                                const academicYear = studyResult?.academic_year?.name || 'Belum ada data';

                                return (
                                    <div
                                        key={semester}
                                        className="flex items-center justify-between rounded-md border p-3 transition-colors hover:bg-slate-50"
                                    >
                                        <div className="flex items-center gap-3">
                                            <div className="flex h-10 w-10 items-center justify-center rounded-full bg-blue-100 text-blue-600">
                                                <IconSchool size={20} />
                                            </div>
                                            <div>
                                                <h4 className="text-base font-medium">Semester {semester}</h4>
                                                <p className="text-sm text-gray-500">
                                                    {studyResult
                                                        ? `Tahun Akademik ${academicYear}`
                                                        : 'Belum ada data nilai'}
                                                </p>
                                            </div>
                                        </div>
                                        <Button asChild variant="blue">
                                            <Link
                                                href={route('admin.students.grades.edit', [
                                                    student.student_number,
                                                    semester,
                                                ])}
                                            >
                                                Edit <IconChevronRight className="ml-1 size-4" />
                                            </Link>
                                        </Button>
                                    </div>
                                );
                            })}
                        </div>
                    </CardContent>
                </Card>

                {/* Study Results Summary */}
                <Card className="md:col-span-2">
                    <CardHeader className="flex flex-row items-center justify-between border-b">
                        <h3 className="text-lg font-medium">Ringkasan IPK per Semester</h3>
                    </CardHeader>
                    <CardContent className="p-4">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Semester</TableHead>
                                    <TableHead>Tahun Akademik</TableHead>
                                    <TableHead>IPS</TableHead>
                                    <TableHead>SKS Diambil</TableHead>
                                    {/* <TableHead>Status</TableHead> */}
                                    <TableHead>Aksi</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {studyResults.length > 0 ? (
                                    studyResults.map((result) => (
                                        <TableRow key={result.id}>
                                            <TableCell>{result.semester}</TableCell>
                                            <TableCell>{result.academic_year?.name || '-'}</TableCell>
                                            <TableCell>{result.gpa || '0.00'}</TableCell>
                                            <TableCell>{result.total_credit || '0'}</TableCell>
                                            {/* <TableCell>{result.status || 'Aktif'}</TableCell> */}
                                            <TableCell>
                                                <Button asChild variant="blue" size="sm">
                                                    <Link
                                                        href={route('admin.students.grades.edit', [
                                                            student.student_number,
                                                            result.semester,
                                                        ])}
                                                    >
                                                        Edit Nilai <IconChevronRight className="ml-1 size-3" />
                                                    </Link>
                                                </Button>
                                            </TableCell>
                                        </TableRow>
                                    ))
                                ) : (
                                    <TableRow>
                                        <TableCell colSpan={6} className="text-center">
                                            Belum ada data hasil studi
                                        </TableCell>
                                    </TableRow>
                                )}
                            </TableBody>
                        </Table>

                        {/* Overall Summary Section */}
                        {studyResults.length > 0 && (
                            <div className="mt-4 border-t pt-3">
                                <div className="flex flex-col space-y-2 md:flex-row md:justify-between md:space-y-0">
                                    <div className="flex items-center">
                                        <span className="font-medium">Total SKS Keseluruhan:</span>
                                        <span className="ml-2">
                                            {studyResults.reduce(
                                                (total, result) => total + (parseInt(result.total_credit) || 0),
                                                0,
                                            )}
                                        </span>
                                    </div>
                                    <div className="flex items-center">
                                        <span className="font-medium">IPK Keseluruhan:</span>
                                        <span className="ml-2">{calculateOverallGPA(studyResults)}</span>
                                    </div>
                                </div>
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>
        </div>
    );
}

SelectSemester.layout = (page) => <AppLayout title={page.props.page_settings.title} children={page} />;
