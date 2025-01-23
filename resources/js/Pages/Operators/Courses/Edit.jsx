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
import { IconArrowLeft, IconBooks, IconCheck } from '@tabler/icons-react';
import { toast } from 'sonner';

export default function Edit(props) {
    const { data, setData, post, processing, errors, reset } = useForm({
        teacher_id: props.course.teacher_id ?? null,
        code: props.course.code ?? '',
        name: props.course.name ?? '',
        credit: props.course.credit ?? '',
        semester: props.course.semester ?? 1,
        _method: props.page_settings.method,
    });

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
                    icon={IconBooks}
                />
                <Button variant="orange" size="xl" className="w-full lg:w-auto" asChild>
                    <Link href={route('operators.courses.index')}>
                        <IconArrowLeft className="size-4" />
                        Kembali
                    </Link>
                </Button>
            </div>

            <Card>
                <CardContent className="p-6">
                    <form onSubmit={onHandleSubmit}>
                        <div className="grid grid-cols-1 gap-4 lg:grid-cols-4">
                            {/* Kode */}
                            <div className="col-span-full">
                                <Label htmlFor="code">Kode</Label>
                                <Input
                                    type="text"
                                    name="code"
                                    id="code"
                                    value={data.code}
                                    onChange={onHandleChange}
                                    placeholder="Masukkan kode mata kuliah"
                                />
                                {errors.code && <InputError message={errors.code} />}
                            </div>
                            {/* Nama */}
                            <div className="col-span-full">
                                <Label htmlFor="name">Nama</Label>
                                <Input
                                    type="text"
                                    name="name"
                                    id="name"
                                    value={data.name}
                                    onChange={onHandleChange}
                                    placeholder="Masukkan nama mata kuliah"
                                />
                                {errors.name && <InputError message={errors.name} />}
                            </div>
                            {/* Dosen */}
                            <div className="col-span-2">
                                <Label htmlFor="teacher_id">Dosen Pengampu</Label>
                                <Select
                                    defaultValue={data.teacher_id}
                                    onValueChange={(value) => setData('teacher_id', value)}
                                >
                                    <SelectTrigger>
                                        <SelectValue>
                                            {props.teachers.find((teacher) => teacher.value == data.teacher_id)
                                                ?.label ?? 'Pilih dosen pengampu'}
                                        </SelectValue>
                                    </SelectTrigger>
                                    <SelectContent>
                                        {props.teachers.map((teacher, index) => (
                                            <SelectItem key={index} value={teacher.value}>
                                                {teacher.label}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                {errors.teacher_id && <InputError message={errors.teacher_id} />}
                            </div>
                            {/* SKS */}
                            <div className="col-span-full">
                                <Label htmlFor="credit">SKS</Label>
                                <Input
                                    type="number"
                                    name="credit"
                                    id="credit"
                                    value={data.credit}
                                    onChange={onHandleChange}
                                    placeholder="Masukkan jumlah sks"
                                />
                                {errors.credit && <InputError message={errors.credit} />}
                            </div>
                            {/* Semester */}
                            <div className="col-span-full">
                                <Label htmlFor="semester">Semester</Label>
                                <Input
                                    type="number"
                                    name="semester"
                                    id="semester"
                                    value={data.semester}
                                    onChange={onHandleChange}
                                    placeholder="Masukkan semester"
                                />
                                {errors.semester && <InputError message={errors.semester} />}
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
