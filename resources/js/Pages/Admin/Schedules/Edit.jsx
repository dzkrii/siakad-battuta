import ClassroomSearchSelect from '@/Components/ClassroomSearchSelect'; // Import komponen baru
import CourseSearchSelect from '@/Components/CourseSearchSelect'; // Import komponen baru
import HeaderTitle from '@/Components/HeaderTitle';
import InputError from '@/Components/InputError';
import { Button } from '@/Components/ui/button';
import { Card, CardContent } from '@/Components/ui/card';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
import AppLayout from '@/Layouts/AppLayout';
import { flashMessage } from '@/lib/utils';
import { Link, useForm } from '@inertiajs/react';
import { IconArrowLeft, IconCalendar, IconCheck } from '@tabler/icons-react';
import { toast } from 'sonner';

export default function Edit(props) {
    const { data, setData, post, processing, errors, reset } = useForm({
        faculty_id: props.schedule.faculty_id ?? null,
        department_id: props.schedule.department_id ?? null,
        course_id: props.schedule.course_id ?? null,
        classroom_id: props.schedule.classroom_id ?? null,
        start_time: props.schedule.start_time ?? '',
        end_time: props.schedule.end_time ?? '',
        day_of_week: props.schedule.day_of_week ?? '',
        quota: props.schedule.quota ?? 0,
        _method: props.page_settings.method,
    });

    const options = props.courses.map((course) => ({
        value: course.value,
        label: course.label,
        teacher: course.teacher,
        department: course.department,
    }));

    const onHandleChange = (e) => setData(e.target.name, e.target.value);

    const onHandleSubmit = (e) => {
        e.preventDefault();
        post(props.page_settings.action, {
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
    };

    return (
        <div className="flex w-full flex-col pb-32">
            <div className="mb-8 flex flex-col items-start justify-between gap-y-4 lg:flex-row lg:items-center">
                <HeaderTitle
                    title={props.page_settings.title}
                    subtitle={props.page_settings.subtitle}
                    icon={IconCalendar}
                />
                <Button variant="orange" size="xl" className="w-full lg:w-auto" asChild>
                    <Link href={route('admin.schedules.index')}>
                        <IconArrowLeft className="size-4" />
                        Kembali
                    </Link>
                </Button>
            </div>

            <Card>
                <CardContent className="p-6">
                    <form onSubmit={onHandleSubmit}>
                        <div className="grid grid-cols-1 gap-4 lg:grid-cols-4">
                            <div className="col-span-2">
                                <Label htmlFor="faculty_id">Fakultas</Label>
                                <Select
                                    defaultValue={data.faculty_id}
                                    onValueChange={(value) => setData('faculty_id', value)}
                                >
                                    <SelectTrigger>
                                        <SelectValue>
                                            {props.faculties.find((faculty) => faculty.value == data.faculty_id)
                                                ?.label ?? 'Pilih fakultas'}
                                        </SelectValue>
                                    </SelectTrigger>
                                    <SelectContent>
                                        {props.faculties.map((faculty, index) => (
                                            <SelectItem key={index} value={faculty.value}>
                                                {faculty.label}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                {errors.faculty_id && <InputError message={errors.faculty_id} />}
                            </div>
                            <div className="col-span-2">
                                <Label htmlFor="department_id">Program Studi</Label>
                                <Select
                                    defaultValue={data.department_id}
                                    onValueChange={(value) => setData('department_id', value)}
                                >
                                    <SelectTrigger>
                                        <SelectValue>
                                            {props.departments.find(
                                                (department) => department.value == data.department_id,
                                            )?.label ?? 'Pilih program studi'}
                                        </SelectValue>
                                    </SelectTrigger>
                                    <SelectContent>
                                        {props.departments.map((department, index) => (
                                            <SelectItem key={index} value={department.value}>
                                                {department.label}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                {errors.department_id && <InputError message={errors.department_id} />}
                            </div>
                            <div className="col-span-2">
                                <Label htmlFor="course_id">Mata Kuliah</Label>
                                {/* Ganti Select dengan CourseSearchSelect */}
                                <CourseSearchSelect
                                    options={options}
                                    value={data.course_id}
                                    onChange={(value) => setData('course_id', value)}
                                    placeholder="Pilih mata kuliah"
                                    error={errors.course_id}
                                />
                            </div>
                            <div className="col-span-2">
                                <Label htmlFor="classroom_id">Kelas</Label>
                                {/* Ganti Select dengan ClassroomSearchSelect */}
                                <ClassroomSearchSelect
                                    options={props.classrooms}
                                    value={data.classroom_id}
                                    onChange={(value) => setData('classroom_id', value)}
                                    placeholder="Pilih kelas"
                                    error={errors.classroom_id}
                                />
                            </div>
                            <div className="col-span-2">
                                <Label htmlFor="start_time">Waktu Mulai</Label>
                                <Input
                                    type="time"
                                    name="start_time"
                                    id="start_time"
                                    value={data.start_time}
                                    onChange={onHandleChange}
                                    placeholder="Masukkan waktu mulai"
                                />
                                {errors.start_time && <InputError message={errors.start_time} />}
                            </div>
                            <div className="col-span-2">
                                <Label htmlFor="end_time">Waktu Berakhir</Label>
                                <Input
                                    type="time"
                                    name="end_time"
                                    id="end_time"
                                    value={data.end_time}
                                    onChange={onHandleChange}
                                    placeholder="Masukkan waktu berakhir"
                                />
                                {errors.end_time && <InputError message={errors.end_time} />}
                            </div>
                            <div className="col-span-2">
                                <Label htmlFor="quota">Kuota</Label>
                                <Input
                                    type="number"
                                    name="quota"
                                    id="quota"
                                    value={data.quota}
                                    onChange={onHandleChange}
                                    placeholder="Masukkan kuota"
                                />
                                {errors.quota && <InputError message={errors.quota} />}
                            </div>
                            <div className="col-span-2">
                                <Label htmlFor="day_of_week">Hari</Label>
                                <Select
                                    defaultValue={data.day_of_week}
                                    onValueChange={(value) => setData('day_of_week', value)}
                                >
                                    <SelectTrigger>
                                        <SelectValue>
                                            {props.days.find((day) => day.value == data.day_of_week)?.label ??
                                                'Pilih hari'}
                                        </SelectValue>
                                    </SelectTrigger>
                                    <SelectContent>
                                        {props.days.map((day, index) => (
                                            <SelectItem key={index} value={day.value}>
                                                {day.label}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                {errors.day_of_week && <InputError message={errors.day_of_week} />}
                            </div>
                        </div>

                        <div className="mt-8 flex flex-col gap-2 lg:flex-row lg:justify-end">
                            <Button type="button" variant="ghost" size="xl" onClick={onHandleReset}>
                                Reset
                            </Button>
                            <Button type="submit" variant="blue" size="xl" disabled={processing}>
                                <IconCheck />
                                Save
                            </Button>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </div>
    );
}

Edit.layout = (page) => <AppLayout children={page} title={page.props.page_settings.title} />;
