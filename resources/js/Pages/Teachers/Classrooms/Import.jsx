import HeaderTitle from '@/Components/HeaderTitle';
import { Alert, AlertDescription } from '@/Components/ui/alert';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { Input } from '@/Components/ui/input';
import AppLayout from '@/Layouts/AppLayout';
import { useForm } from '@inertiajs/react';
import { IconArrowLeft, IconDoor, IconDownload, IconFileUpload } from '@tabler/icons-react';
import { useState } from 'react';
import { toast } from 'sonner';

export default function Import(props) {
    const { course, classroom } = props;
    const [isLoading, setIsLoading] = useState(false);

    // Form for attendance import
    const attendanceForm = useForm({
        file: null,
    });

    // Form for grades import (will implement later)
    const gradesForm = useForm({
        file: null,
    });

    const handleAttendanceFileChange = (e) => {
        const file = e.target.files[0];
        if (file) {
            attendanceForm.setData('file', file);
        }
    };

    const handleGradesFileChange = (e) => {
        const file = e.target.files[0];
        if (file) {
            gradesForm.setData('file', file);
        }
    };

    const handleAttendanceSubmit = (e) => {
        e.preventDefault();

        if (!attendanceForm.data.file) {
            toast.error('Harap pilih file excel untuk diimport');
            return;
        }

        setIsLoading(true);
        attendanceForm.post(route('teachers.classrooms.import.attendances', [course.id, classroom.id]), {
            preserveScroll: true,
            onSuccess: () => {
                setIsLoading(false);
                toast.success('Data absensi berhasil diimport');
            },
            onError: (errors) => {
                setIsLoading(false);
                if (typeof errors === 'string') {
                    toast.error(errors);
                } else {
                    const errorMessage = Object.values(errors).flat()[0];
                    toast.error(errorMessage);
                }
            },
        });
    };

    const handleGradesSubmit = (e) => {
        e.preventDefault();

        if (!gradesForm.data.file) {
            toast.error('Harap pilih file excel untuk diimport');
            return;
        }

        setIsLoading(true);
        gradesForm.post(route('teachers.classrooms.import.grades', [course.id, classroom.id]), {
            preserveScroll: true,
            onSuccess: () => {
                setIsLoading(false);
                toast.success('Data nilai berhasil diimport');
            },
            onError: (errors) => {
                setIsLoading(false);
                if (typeof errors === 'string') {
                    toast.error(errors);
                } else {
                    const errorMessage = Object.values(errors).flat()[0];
                    toast.error(errorMessage);
                }
            },
        });
    };

    return (
        <div className="flex w-full flex-col pb-32">
            <div className="mb-8 flex flex-col items-start justify-between gap-y-4 lg:flex-row lg:items-center">
                <HeaderTitle
                    title={props.page_settings.title}
                    subtitle={props.page_settings.subtitle}
                    icon={IconDoor}
                />

                <Button
                    variant="blue"
                    onClick={() =>
                        (window.location.href = route('teachers.classrooms.index', [course.id, classroom.id]))
                    }
                    className="flex items-center gap-2"
                >
                    <IconArrowLeft className="size-4" />
                    Kembali
                </Button>
            </div>

            <div className="grid grid-cols-1 gap-6 lg:grid-cols-2">
                {/* Attendance Import */}
                <Card>
                    <CardHeader>
                        <CardTitle>Import Data Absensi</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <Alert className="mb-4">
                            <AlertDescription>
                                Download template excel terlebih dahulu, isi dengan data absensi mahasiswa, kemudian
                                upload kembali file excel yang sudah diisi.
                            </AlertDescription>
                        </Alert>

                        <div className="mb-4">
                            <Button
                                variant="green"
                                onClick={() =>
                                    (window.location.href = route('teachers.classrooms.template.attendance', [
                                        course.id,
                                        classroom.id,
                                    ]))
                                }
                                className="flex w-full items-center gap-2"
                            >
                                <IconDownload className="size-4" />
                                Download Template Absensi
                            </Button>
                        </div>

                        <form onSubmit={handleAttendanceSubmit}>
                            <div className="mb-4">
                                <label htmlFor="attendance-file" className="mb-2 block text-sm font-medium">
                                    Upload Excel Absensi
                                </label>
                                <Input
                                    id="attendance-file"
                                    type="file"
                                    accept=".xlsx,.xls"
                                    onChange={handleAttendanceFileChange}
                                    className="cursor-pointer"
                                />
                                {attendanceForm.errors.file && (
                                    <p className="mt-1 text-sm text-red-500">{attendanceForm.errors.file}</p>
                                )}
                            </div>

                            <Button
                                type="submit"
                                variant="orange"
                                className="flex w-full items-center gap-2"
                                disabled={isLoading || !attendanceForm.data.file}
                            >
                                <IconFileUpload className="size-4" />
                                {isLoading ? 'Memproses...' : 'Import Data Absensi'}
                            </Button>
                        </form>
                    </CardContent>
                </Card>

                {/* Grades Import (we'll implement this later) */}
                <Card>
                    <CardHeader>
                        <CardTitle>Import Data Nilai</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <Alert className="mb-4">
                            <AlertDescription>
                                Download template excel terlebih dahulu, isi dengan data nilai mahasiswa, kemudian
                                upload kembali file excel yang sudah diisi.
                            </AlertDescription>
                        </Alert>

                        <div className="mb-4">
                            <Button
                                variant="green"
                                onClick={() =>
                                    (window.location.href = route('teachers.classrooms.template.grade', [
                                        course.id,
                                        classroom.id,
                                    ]))
                                }
                                className="flex w-full items-center gap-2"
                            >
                                <IconDownload className="size-4" />
                                Download Template Nilai
                            </Button>
                        </div>

                        <form onSubmit={handleGradesSubmit}>
                            <div className="mb-4">
                                <label htmlFor="grades-file" className="mb-2 block text-sm font-medium">
                                    Upload Excel Nilai
                                </label>
                                <Input
                                    id="grades-file"
                                    type="file"
                                    accept=".xlsx,.xls"
                                    onChange={handleGradesFileChange}
                                    className="cursor-pointer"
                                />
                                {gradesForm.errors.file && (
                                    <p className="mt-1 text-sm text-red-500">{gradesForm.errors.file}</p>
                                )}
                            </div>

                            <Button
                                type="submit"
                                variant="orange"
                                className="flex w-full items-center gap-2"
                                disabled={isLoading || !gradesForm.data.file}
                            >
                                <IconFileUpload className="size-4" />
                                {isLoading ? 'Memproses...' : 'Import Data Nilai'}
                            </Button>
                        </form>
                    </CardContent>
                </Card>
            </div>
        </div>
    );
}

Import.layout = (page) => <AppLayout children={page} title={page.props.page_settings.title} />;
