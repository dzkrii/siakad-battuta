import HeaderTitle from '@/Components/HeaderTitle';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/ui/card';
import { Label } from '@/Components/ui/label';
import { RadioGroup, RadioGroupItem } from '@/Components/ui/radio-group';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/Components/ui/table';
import StudentLayout from '@/Layouts/StudentLayout';
import { cn, flashMessage } from '@/lib/utils';
import { Link, useForm, usePage } from '@inertiajs/react';
import { IconArrowBack, IconSchool } from '@tabler/icons-react';
import { toast } from 'sonner';

export default function SelectClassroom() {
    // Get data from controller
    const { page_settings, classrooms, current_classroom, can_change_classroom } = usePage().props;

    // Inertia form handling
    const { data, setData, post, processing, errors } = useForm({
        classroom_id: current_classroom ? current_classroom.id : '',
    });

    // Handle form submission
    const handleSubmit = (e) => {
        e.preventDefault();
        post(page_settings.action, {
            preserveScroll: true,
            onSuccess: (success) => {
                const flash = flashMessage(success);
                if (flash) toast[flash.type](flash.message);
            },
        });
    };

    return (
        <div className="flex w-full flex-col pb-32">
            <div className="mb-8 flex flex-col items-start justify-between gap-y-4 lg:flex-row lg:items-center">
                <HeaderTitle title={page_settings.title} subtitle={page_settings.subtitle} icon={IconSchool} />
                <Button variant="orange" size="xl" className="w-full lg:w-auto" asChild>
                    <Link href={route('students.study-plans.index')}>
                        <IconArrowBack className="size-4" />
                        Kembali
                    </Link>
                </Button>
            </div>

            <Card className="mb-6">
                <CardHeader>
                    <CardTitle>Informasi Pemilihan Kelas</CardTitle>
                    <CardDescription>
                        Kelas yang ditampilkan hanya kelas yang sesuai dengan semester dan program studi Anda.
                        {!can_change_classroom && current_classroom && (
                            <div className="mt-2 font-medium text-amber-600">
                                Anda tidak dapat mengubah kelas karena sudah memiliki KRS yang diajukan.
                            </div>
                        )}
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <div className="mb-4 grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div>
                            <span className="text-sm font-medium">Format Kelas:</span>
                            <p className="text-sm text-muted-foreground">[Kode Prodi]-[Semester]-[Waktu/Kelas]</p>
                        </div>
                        <div>
                            <span className="text-sm font-medium">Contoh:</span>
                            <p className="text-sm text-muted-foreground">
                                IF-4-PAGI = Informatika Semester 4 Kelas Pagi
                            </p>
                        </div>
                    </div>

                    {current_classroom && (
                        <div className="mb-6 rounded-md bg-blue-50 p-4">
                            <h3 className="font-medium text-blue-700">Kelas Saat Ini</h3>
                            <p className="text-blue-600">{current_classroom.name}</p>
                        </div>
                    )}
                </CardContent>
            </Card>

            {can_change_classroom ? (
                <form onSubmit={handleSubmit}>
                    <Card>
                        <CardHeader>
                            <CardTitle>Daftar Kelas Tersedia</CardTitle>
                            <CardDescription>Pilih kelas yang sesuai dengan jadwal Anda</CardDescription>
                        </CardHeader>
                        <CardContent>
                            {classrooms.length === 0 ? (
                                <div className="p-8 text-center">
                                    <p className="text-muted-foreground">
                                        Tidak ada kelas yang tersedia untuk semester Anda.
                                    </p>
                                </div>
                            ) : (
                                <div className="space-y-4">
                                    <RadioGroup
                                        value={data.classroom_id}
                                        onValueChange={(value) => setData('classroom_id', value)}
                                        className="flex flex-col space-y-2"
                                    >
                                        <Table>
                                            <TableHeader>
                                                <TableRow>
                                                    <TableHead className="w-[100px]">Pilih</TableHead>
                                                    <TableHead>Nama Kelas</TableHead>
                                                    <TableHead>Fakultas</TableHead>
                                                    <TableHead>Program Studi</TableHead>
                                                </TableRow>
                                            </TableHeader>
                                            <TableBody>
                                                {classrooms.map((classroom) => (
                                                    <TableRow
                                                        key={classroom.id}
                                                        className={cn(
                                                            current_classroom &&
                                                                current_classroom.id === classroom.id &&
                                                                'bg-blue-50',
                                                        )}
                                                    >
                                                        <TableCell>
                                                            <RadioGroupItem
                                                                value={classroom.id}
                                                                id={`classroom-${classroom.id}`}
                                                            />
                                                        </TableCell>
                                                        <TableCell>
                                                            <Label
                                                                htmlFor={`classroom-${classroom.id}`}
                                                                className="cursor-pointer font-medium"
                                                            >
                                                                {classroom.name}
                                                            </Label>
                                                        </TableCell>
                                                        <TableCell>{classroom.faculty_name}</TableCell>
                                                        <TableCell>{classroom.department_name}</TableCell>
                                                    </TableRow>
                                                ))}
                                            </TableBody>
                                        </Table>
                                    </RadioGroup>

                                    {errors.classroom_id && (
                                        <div className="mt-2 text-sm text-red-500">{errors.classroom_id}</div>
                                    )}
                                </div>
                            )}
                        </CardContent>
                    </Card>

                    <div className="mt-8 flex justify-end">
                        <Button type="submit" disabled={processing || classrooms.length === 0} variant="blue" size="xl">
                            {processing ? 'Memproses...' : current_classroom ? 'Ubah Kelas' : 'Pilih Kelas'}
                        </Button>
                    </div>
                </form>
            ) : (
                <div className="mt-6 flex justify-end">
                    <Button variant="blue" size="xl" asChild>
                        <Link href={route('students.study-plans.create')}>Lanjut ke Pengajuan KRS</Link>
                    </Button>
                </div>
            )}
        </div>
    );
}

SelectClassroom.layout = (page) => <StudentLayout children={page} title={page.props.page_settings.title} />;
