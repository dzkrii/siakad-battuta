import HeaderTitle from '@/Components/HeaderTitle';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/ui/card';
import { Checkbox } from '@/Components/ui/checkbox';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/Components/ui/table';
import StudentLayout from '@/Layouts/StudentLayout';
import { cn, flashMessage } from '@/lib/utils';
import { Link, useForm, usePage } from '@inertiajs/react';
import {
    IconArrowBack,
    IconBuilding,
    IconCalendarTime,
    IconCheck,
    IconClock,
    IconSchool,
    IconUser,
} from '@tabler/icons-react';
import { useState } from 'react';
import { toast } from 'sonner';

export default function Create() {
    const { page_settings, schedules, current_classroom } = usePage().props;
    const [selectedCourses, setSelectedCourses] = useState(0);
    const [totalCredits, setTotalCredits] = useState(0);

    const { data, setData, post, errors, processing, reset } = useForm({
        schedule_id: [],
        _method: page_settings.method,
    });

    const handleCheckboxChange = (checked, scheduleId, credits) => {
        let newScheduleIds;

        if (checked) {
            newScheduleIds = [...data.schedule_id, scheduleId];
            setSelectedCourses(selectedCourses + 1);
            setTotalCredits(totalCredits + (credits || 0));
        } else {
            newScheduleIds = data.schedule_id.filter((id) => id !== scheduleId);
            setSelectedCourses(selectedCourses - 1);
            setTotalCredits(totalCredits - (credits || 0));
        }

        setData('schedule_id', newScheduleIds);
    };

    const onHandleSubmit = (e) => {
        e.preventDefault();
        post(page_settings.action, {
            preserveScroll: true,
            preserveState: true,
            onSuccess: (success) => {
                const flash = flashMessage(success);
                if (flash) toast[flash.type](flash.message);
            },
        });
    };

    const onHandleReset = () => {
        reset();
        setSelectedCourses(0);
        setTotalCredits(0);
    };

    // Group schedules by day of week
    const dayOrder = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'];
    const schedulesByDay = schedules.reduce((acc, schedule) => {
        const day = schedule.day_of_week;
        if (!acc[day]) {
            acc[day] = [];
        }
        acc[day].push(schedule);
        return acc;
    }, {});

    // Sort days according to the day order
    const sortedDays = Object.keys(schedulesByDay).sort((a, b) => dayOrder.indexOf(a) - dayOrder.indexOf(b));

    return (
        <div className="flex w-full flex-col pb-32">
            <div className="mb-8 flex flex-col items-start justify-between gap-y-4 lg:flex-row lg:items-center">
                <HeaderTitle title={page_settings.title} subtitle={page_settings.subtitle} icon={IconBuilding} />
                <Button variant="orange" size="xl" className="w-full lg:w-auto" asChild>
                    <Link href={route('students.study-plans.index')}>
                        <IconArrowBack className="size-4" />
                        Kembali
                    </Link>
                </Button>
            </div>

            {/* Current Class Information */}
            <Card className="mb-6">
                <CardHeader>
                    <CardTitle>Informasi Kelas</CardTitle>
                    <CardDescription>Anda sedang memilih mata kuliah untuk kelas berikut</CardDescription>
                </CardHeader>
                <CardContent>
                    <div className="grid grid-cols-1 gap-6 md:grid-cols-2">
                        <div className="flex items-center gap-3">
                            <div className="flex size-10 items-center justify-center rounded-full bg-primary/10">
                                <IconSchool className="size-5 text-primary" />
                            </div>
                            <div>
                                <p className="text-sm text-muted-foreground">Kelas</p>
                                <p className="font-medium">{current_classroom?.name || '-'}</p>
                            </div>
                        </div>

                        <div className="flex items-center gap-3">
                            <div className="flex size-10 items-center justify-center rounded-full bg-primary/10">
                                <IconUser className="size-5 text-primary" />
                            </div>
                            <div>
                                <p className="text-sm text-muted-foreground">Kuota Terpilih</p>
                                <p className="font-medium">
                                    <span className={cn(selectedCourses === 0 ? 'text-red-500' : 'text-green-500')}>
                                        {selectedCourses}
                                    </span>{' '}
                                    Mata Kuliah | {totalCredits} SKS
                                </p>
                            </div>
                        </div>
                    </div>

                    <div className="mt-4">
                        <Link
                            href={route('students.study-plans.select-classroom')}
                            className="text-sm text-primary hover:underline"
                        >
                            Ingin mengubah kelas? Klik disini
                        </Link>
                    </div>
                </CardContent>
            </Card>

            <form onSubmit={onHandleSubmit}>
                {errors.schedule_id && (
                    <div className="mb-4 rounded-md border border-red-200 bg-red-50 p-3 text-red-600">
                        {errors.schedule_id}
                    </div>
                )}

                {sortedDays.map((day, dayIndex) => (
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
                                        <TableHead className="w-[60px]">Pilih</TableHead>
                                        <TableHead>Kode</TableHead>
                                        <TableHead>Mata Kuliah</TableHead>
                                        <TableHead>Dosen</TableHead>
                                        <TableHead>Kelas</TableHead>
                                        <TableHead>Waktu</TableHead>
                                        <TableHead>SKS</TableHead>
                                        <TableHead>Kuota</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {schedulesByDay[day].map((schedule, index) => (
                                        <TableRow
                                            key={index}
                                            className={cn(
                                                schedule.taken_quota === schedule.quota && 'bg-red-50',
                                                data.schedule_id.includes(schedule.id) && 'bg-blue-50',
                                            )}
                                        >
                                            <TableCell className="py-3">
                                                <Checkbox
                                                    id={`schedule_id_${schedule.id}`}
                                                    checked={data.schedule_id.includes(schedule.id)}
                                                    disabled={schedule.taken_quota === schedule.quota}
                                                    onCheckedChange={(checked) => {
                                                        handleCheckboxChange(
                                                            checked,
                                                            schedule.id,
                                                            schedule.course.credits,
                                                        );
                                                    }}
                                                />
                                            </TableCell>
                                            <TableCell className="font-mono">{schedule.course.kode_matkul}</TableCell>
                                            <TableCell>
                                                <div className="font-medium">{schedule.course.name}</div>
                                            </TableCell>
                                            <TableCell>{schedule.course.teacher}</TableCell>
                                            <TableCell>{schedule.classroom.name}</TableCell>
                                            <TableCell>
                                                <div className="flex items-center gap-1.5">
                                                    <IconClock className="size-3.5 text-muted-foreground" />
                                                    <span>
                                                        {schedule.start_time} - {schedule.end_time}
                                                    </span>
                                                </div>
                                            </TableCell>
                                            <TableCell>{schedule.course.credits || '-'}</TableCell>
                                            <TableCell>
                                                <Badge
                                                    variant={
                                                        schedule.taken_quota === schedule.quota
                                                            ? 'destructive'
                                                            : 'outline'
                                                    }
                                                    className={cn(
                                                        schedule.taken_quota === schedule.quota
                                                            ? 'bg-red-100 text-red-800 hover:bg-red-100'
                                                            : 'bg-green-100 text-green-800 hover:bg-green-100',
                                                    )}
                                                >
                                                    {schedule.taken_quota} / {schedule.quota}
                                                </Badge>
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        </CardContent>
                    </Card>
                ))}

                <div className="mt-8 flex flex-col gap-2 lg:flex-row lg:justify-end">
                    <Button type="button" variant="ghost" size="xl" onClick={onHandleReset}>
                        Reset
                    </Button>
                    <Button
                        type="submit"
                        variant="blue"
                        size="xl"
                        disabled={processing || data.schedule_id.length === 0}
                    >
                        <IconCheck className="mr-1 size-4" />
                        {processing ? 'Memproses...' : 'Simpan KRS'}
                    </Button>
                </div>
            </form>
        </div>
    );
}

Create.layout = (page) => <StudentLayout children={page} title={page.props.page_settings.title} />;
