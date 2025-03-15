import HeaderTitle from '@/Components/HeaderTitle';
import { Alert, AlertDescription } from '@/Components/ui/alert';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/Components/ui/table';
import StudentLayout from '@/Layouts/StudentLayout';
import { STUDYPLANSTATUS, STUDYPLANSTATUSVARIANT, cn, formatDateIndo } from '@/lib/utils';
import { Link } from '@inertiajs/react';
import {
    IconArrowBack,
    IconCalendarTime,
    IconClock,
    IconDownload,
    IconFileText,
    IconSchool,
    IconUser,
} from '@tabler/icons-react';

export default function Show(props) {
    const { studyPlan } = props;

    // Group schedules by day of week
    const dayOrder = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'];
    const schedulesByDay = studyPlan.schedules.reduce((acc, schedule) => {
        const day = schedule.day_of_week;
        if (!acc[day]) {
            acc[day] = [];
        }
        acc[day].push(schedule);
        return acc;
    }, {});

    // Sort days according to the day order
    const sortedDays = Object.keys(schedulesByDay).sort((a, b) => dayOrder.indexOf(a) - dayOrder.indexOf(b));

    // Calculate total credits
    const totalCredits = studyPlan.schedules.reduce((total, schedule) => {
        return total + (parseInt(schedule.course.credit) || 0);
    }, 0);

    return (
        <div className="flex w-full flex-col pb-32">
            <div className="mb-8 flex flex-col items-start justify-between gap-y-4 lg:flex-row lg:items-center">
                <HeaderTitle
                    title={props.page_settings.title}
                    subtitle={props.page_settings.subtitle}
                    icon={IconFileText}
                />
                <div className="flex w-full flex-col gap-y-2 lg:w-auto lg:flex-row lg:gap-x-2">
                    {studyPlan.status === STUDYPLANSTATUS.APPROVED && (
                        <Button variant="green" size="xl" className="w-full lg:w-auto" asChild>
                            <a
                                href={route('students.study-plans.download', studyPlan.id)}
                                target="_blank"
                                rel="noopener noreferrer"
                            >
                                <IconDownload className="mr-1 size-4" />
                                Download PDF
                            </a>
                        </Button>
                    )}
                    <Button variant="orange" size="xl" className="w-full lg:w-auto" asChild>
                        <Link href={route('students.study-plans.index')}>
                            <IconArrowBack className="mr-1 size-4" />
                            Kembali
                        </Link>
                    </Button>
                </div>
            </div>

            {studyPlan.status === STUDYPLANSTATUS.REJECT && (
                <Alert variant="destructive" className="mb-6">
                    <AlertDescription className="font-medium">
                        Alasan Penolakan: {studyPlan.notes || 'Tidak ada catatan yang diberikan.'}
                    </AlertDescription>
                </Alert>
            )}

            {/* Study Plan Information Card */}
            <Card className="mb-6">
                <CardHeader>
                    <CardTitle>Informasi Kartu Rencana Studi</CardTitle>
                    <CardDescription>Detail informasi KRS yang sudah diajukan</CardDescription>
                </CardHeader>
                <CardContent>
                    <div className="grid grid-cols-1 gap-6 md:grid-cols-3">
                        <div className="flex items-center gap-3">
                            <div className="flex size-10 items-center justify-center rounded-full bg-primary/10">
                                <IconUser className="size-5 text-primary" />
                            </div>
                            <div>
                                <p className="text-sm text-muted-foreground">Mahasiswa</p>
                                <p className="font-medium">{studyPlan.student.name || 'N/A'}</p>
                                <p className="text-xs text-muted-foreground">
                                    NIM: {studyPlan.student?.student_number || 'N/A'}
                                </p>
                            </div>
                        </div>

                        <div className="flex items-center gap-3">
                            <div className="flex size-10 items-center justify-center rounded-full bg-primary/10">
                                <IconSchool className="size-5 text-primary" />
                            </div>
                            <div>
                                <p className="text-sm text-muted-foreground">Tahun Ajaran</p>
                                <p className="font-medium">
                                    {studyPlan.academicYear.semester} - {studyPlan.academicYear.name}
                                </p>
                                <p className="text-xs text-muted-foreground">
                                    Dibuat pada: {formatDateIndo(studyPlan.created_at)}
                                </p>
                            </div>
                        </div>

                        <div className="flex items-center gap-3">
                            <div className="flex size-10 items-center justify-center rounded-full bg-primary/10">
                                <IconFileText className="size-5 text-primary" />
                            </div>
                            <div>
                                <p className="text-sm text-muted-foreground">Status</p>
                                <div>
                                    <Badge
                                        variant={STUDYPLANSTATUSVARIANT[studyPlan.status]}
                                        className={cn(
                                            studyPlan.status === STUDYPLANSTATUS.APPROVED &&
                                                'bg-green-100 text-green-800 hover:bg-green-100',
                                            studyPlan.status === STUDYPLANSTATUS.PENDING &&
                                                'bg-amber-100 text-amber-800 hover:bg-amber-100',
                                            studyPlan.status === STUDYPLANSTATUS.REJECT &&
                                                'bg-red-100 text-red-800 hover:bg-red-100',
                                        )}
                                    >
                                        {studyPlan.status}
                                    </Badge>
                                </div>
                                <p className="mt-1 text-xs text-muted-foreground">
                                    Total: {studyPlan.schedules.length} Mata Kuliah ({totalCredits} SKS)
                                </p>
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>

            {/* Schedules by Day */}
            {sortedDays.length > 0 ? (
                sortedDays.map((day, dayIndex) => (
                    <Card key={dayIndex} className="mb-6">
                        <CardHeader className="bg-muted/50">
                            <div className="flex items-center gap-3">
                                <IconCalendarTime className="size-5" />
                                <CardTitle>{day}</CardTitle>
                            </div>
                        </CardHeader>
                        <CardContent className="p-0">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead className="w-[60px]">#</TableHead>
                                        <TableHead>Kode</TableHead>
                                        <TableHead>Mata Kuliah</TableHead>
                                        <TableHead>Dosen</TableHead>
                                        <TableHead>SKS</TableHead>
                                        <TableHead>Kelas</TableHead>
                                        <TableHead>Waktu</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {schedulesByDay[day].map((schedule, index) => (
                                        <TableRow key={index}>
                                            <TableCell>{index + 1}</TableCell>
                                            <TableCell className="font-mono">{schedule.course.kode_matkul}</TableCell>
                                            <TableCell>
                                                <div className="font-medium">{schedule.course.name}</div>
                                            </TableCell>
                                            <TableCell>{schedule.course.teacher}</TableCell>
                                            <TableCell>{schedule.course.credit}</TableCell>
                                            <TableCell>{schedule.classroom.name}</TableCell>
                                            <TableCell>
                                                <div className="flex items-center gap-1.5">
                                                    <IconClock className="size-3.5 text-muted-foreground" />
                                                    <span>
                                                        {schedule.start_time} - {schedule.end_time}
                                                    </span>
                                                </div>
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        </CardContent>
                    </Card>
                ))
            ) : (
                <Table className="w-full">
                    <TableHeader>
                        <TableRow>
                            <TableHead>#</TableHead>
                            <TableHead>Kode Mata Kuliah</TableHead>
                            <TableHead>Nama Mata Kuliah</TableHead>
                            <TableHead>Dosen Pengampu</TableHead>
                            <TableHead>SKS</TableHead>
                            <TableHead>Kelas</TableHead>
                            <TableHead>Tahun Ajaran</TableHead>
                            <TableHead>Waktu</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {studyPlan.schedules.map((schedule, index) => (
                            <TableRow key={index}>
                                <TableCell>{index + 1}</TableCell>
                                <TableCell>{schedule.course.kode_matkul}</TableCell>
                                <TableCell>{schedule.course.name}</TableCell>
                                <TableCell>{schedule.course.teacher}</TableCell>
                                <TableCell>{schedule.course.credit}</TableCell>
                                <TableCell>{schedule.classroom.name}</TableCell>
                                <TableCell>{schedule.academicYear.name}</TableCell>
                                <TableCell>
                                    {schedule.day_of_week}, {schedule.start_time} - {schedule.end_time}
                                </TableCell>
                            </TableRow>
                        ))}
                    </TableBody>
                </Table>
            )}

            {/* Summary Footer - Keeping the original format as a fallback if needed */}
            {!sortedDays.length && (
                <div className="flex w-full flex-col items-center justify-between py-2 lg:flex-row">
                    <p className="text-sm text-muted-foreground">
                        Tahun ajaran :{' '}
                        <span className="font-bold text-blue-600">
                            {studyPlan.academicYear.semester} - {studyPlan.academicYear.name}
                        </span>
                    </p>
                    <p className="text-sm text-muted-foreground">
                        Status : <Badge variant={STUDYPLANSTATUSVARIANT[studyPlan.status]}>{studyPlan.status}</Badge>
                    </p>
                </div>
            )}
        </div>
    );
}

Show.layout = (page) => <StudentLayout children={page} title={page.props.page_settings.title} />;
