import AlertAction from '@/Components/AlertAction';
import ClassroomSearchSelect from '@/Components/ClassroomSearchSelect';
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
import { IconArrowLeft, IconCheck, IconTrash, IconUsers } from '@tabler/icons-react';
import { useRef } from 'react';
import { toast } from 'sonner';

export default function Edit(props) {
    const fileInputAvatar = useRef(null);
    const { data, setData, post, processing, errors, reset } = useForm({
        faculty_id: props.student.faculty_id ?? null,
        department_id: props.student.department_id ?? null,
        classroom_id: props.student.classroom_id ?? null,
        fee_group_id: props.student.fee_group_id ?? null,
        name: props.student.user.name ?? '',
        email: props.student.user.email ?? '',
        password: '',
        avatar: null,
        student_number: props.student.student_number ?? '',
        semester: props.student.semester ?? 1,
        batch: props.student.batch ?? '',
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
        fileInputAvatar.current.value = null;
    };

    return (
        <div className="flex w-full flex-col pb-32">
            <div className="mb-8 flex flex-col items-start justify-between gap-y-4 lg:flex-row lg:items-center">
                <HeaderTitle
                    title={props.page_settings.title}
                    subtitle={props.page_settings.subtitle}
                    icon={IconUsers}
                />
                <Button variant="orange" size="xl" className="w-full lg:w-auto" asChild>
                    <Link href={route('admin.students.index')}>
                        <IconArrowLeft className="size-4" />
                        Kembali
                    </Link>
                </Button>
            </div>

            <Card>
                <CardContent className="p-6">
                    <form onSubmit={onHandleSubmit}>
                        <div className="grid grid-cols-1 gap-4 lg:grid-cols-4">
                            <div className="col-span-full">
                                <Label htmlFor="name">Nama</Label>
                                <Input
                                    type="text"
                                    name="name"
                                    id="name"
                                    value={data.name}
                                    onChange={onHandleChange}
                                    placeholder="Masukkan nama mahasiswa"
                                />
                                {errors.name && <InputError message={errors.name} />}
                            </div>
                            {/* <div className="col-span-2">
                                <Label htmlFor="email">Email</Label>
                                <Input
                                    type="email"
                                    name="email"
                                    id="email"
                                    value={data.email}
                                    onChange={onHandleChange}
                                    placeholder="Masukkan alamat email"
                                />
                                {errors.email && <InputError message={errors.email} />}
                            </div> */}
                            <div className="col-span-2">
                                <Label htmlFor="password">Password</Label>
                                <Input
                                    type="password"
                                    name="password"
                                    id="password"
                                    value={data.password}
                                    onChange={onHandleChange}
                                    placeholder="**********"
                                />
                                {errors.password && <InputError message={errors.password} />}
                            </div>
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
                                <Label htmlFor="classroom_id">Kelas</Label>
                                {/* Ganti Select dengan ClassroomSearchSelect */}
                                <div className="flex items-center space-x-2">
                                    <ClassroomSearchSelect
                                        options={props.classrooms}
                                        value={data.classroom_id}
                                        onChange={(value) => setData('classroom_id', value)}
                                        placeholder="Pilih kelas"
                                        error={errors.classroom_id}
                                    />

                                    {data.classroom_id && (
                                        <AlertAction
                                            trigger={
                                                <Button variant="red" size="sm">
                                                    <IconTrash className="size-4" />
                                                </Button>
                                            }
                                            action={() => {
                                                // Menghapus kelas dari data
                                                setData('classroom_id', null);

                                                // Jika perlu, Anda bisa mengirim request ke server
                                                // Contoh: axios.delete(`/api/students/${studentId}/classroom`);
                                            }}
                                        />
                                    )}
                                </div>
                            </div>
                            <div className="col-span-2">
                                <Label htmlFor="fee_group_id">Golongan UKT</Label>
                                <Select
                                    defaultValue={data.fee_group_id}
                                    onValueChange={(value) => setData('fee_group_id', value)}
                                >
                                    <SelectTrigger>
                                        <SelectValue>
                                            {props.feeGroups.find((feeGroup) => feeGroup.value == data.fee_group_id)
                                                ?.label ?? 'Pilih golongan ukt'}
                                        </SelectValue>
                                    </SelectTrigger>
                                    <SelectContent>
                                        {props.feeGroups.map((feeGroup, index) => (
                                            <SelectItem key={index} value={feeGroup.value}>
                                                {feeGroup.label}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                {errors.fee_group_id && <InputError message={errors.fee_group_id} />}
                            </div>
                            <div className="col-span-2">
                                <Label htmlFor="student_number">Nomor Induk Mahasiswa (NIM)</Label>
                                <Input
                                    type="text"
                                    name="student_number"
                                    id="student_number"
                                    value={data.student_number}
                                    onChange={onHandleChange}
                                    placeholder="Masukkan nomor induk mahasiswa"
                                />
                                {errors.student_number && <InputError message={errors.student_number} />}
                            </div>
                            <div className="col-span-2">
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
                            <div className="col-span-2">
                                <Label htmlFor="batch">Angkatan</Label>
                                <Input
                                    type="text"
                                    name="batch"
                                    id="batch"
                                    value={data.batch}
                                    onChange={onHandleChange}
                                    placeholder="Masukkan angkatan"
                                />
                                {errors.batch && <InputError message={errors.batch} />}
                            </div>
                            <div className="col-span-2">
                                <Label htmlFor="avatar">Avatar</Label>
                                <Input
                                    type="file"
                                    name="avatar"
                                    id="avatar"
                                    onChange={(e) => setData(e.target.name, e.target.files[0])}
                                    ref={fileInputAvatar}
                                />
                                {errors.avatar && <InputError message={errors.avatar} />}
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
