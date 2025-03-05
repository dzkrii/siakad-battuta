import HeaderTitle from '@/Components/HeaderTitle';
import { Alert, AlertDescription } from '@/Components/ui/alert';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardHeader } from '@/Components/ui/card';
import { Input } from '@/Components/ui/input';
import AppLayout from '@/Layouts/AppLayout';
import { Head, useForm } from '@inertiajs/react';
import { IconCloudUpload, IconDoor, IconDownload } from '@tabler/icons-react';
import { useState } from 'react';
import { toast } from 'sonner';

export default function ImportExcel({ page_settings, course, classroom }) {
    const [activeTab, setActiveTab] = useState('nilai');
    const [selectedCategory, setSelectedCategory] = useState('tugas');

    // Form untuk import nilai
    const {
        data: gradeData,
        setData: setGradeData,
        post: postGrade,
        processing: gradeProcessing,
        errors: gradeErrors,
        reset: gradeReset,
    } = useForm({
        category: 'tugas',
        file: null,
    });

    // Form untuk import absensi
    const {
        data: attendanceData,
        setData: setAttendanceData,
        post: postAttendance,
        processing: attendanceProcessing,
        errors: attendanceErrors,
        reset: attendanceReset,
    } = useForm({
        file: null,
    });

    // Download template nilai
    const handleDownloadGradeTemplate = () => {
        window.location.href = route('teachers.classrooms.template.grade', [
            course.id,
            classroom.id,
            { category: selectedCategory },
        ]);
    };

    // Download template absensi
    const handleDownloadAttendanceTemplate = () => {
        window.location.href = route('teachers.classrooms.template.attendance', [course.id, classroom.id]);
    };

    // Handle submit form nilai
    const handleGradeSubmit = (e) => {
        e.preventDefault();

        postGrade(route('teachers.classrooms.import.grades', [course.id, classroom.id]), {
            preserveScroll: true,
            onSuccess: () => {
                toast.success('Nilai berhasil diimport');
                gradeReset();
            },
            onError: (errors) => {
                toast.error('Terjadi kesalahan saat import nilai');
                console.error(errors);
            },
        });
    };

    // Handle submit form absensi
    const handleAttendanceSubmit = (e) => {
        e.preventDefault();

        postAttendance(route('teachers.classrooms.import.attendances', [course.id, classroom.id]), {
            preserveScroll: true,
            onSuccess: () => {
                toast.success('Absensi berhasil diimport');
                attendanceReset();
            },
            onError: (errors) => {
                toast.error('Terjadi kesalahan saat import absensi');
                console.error(errors);
            },
        });
    };

    return (
        <div className="flex w-full flex-col pb-32">
            <Head title={page_settings.title} />

            <div className="mb-8 flex flex-col items-start justify-between gap-y-4 lg:flex-row lg:items-center">
                <HeaderTitle title={page_settings.title} subtitle={page_settings.subtitle} icon={IconDoor} />

                <div className="flex gap-2">
                    <Button variant="outline" onClick={() => window.history.back()}>
                        Kembali
                    </Button>
                    <Button
                        variant="orange"
                        onClick={() =>
                            (window.location.href = route('teachers.classrooms.index', [course.id, classroom.id]))
                        }
                    >
                        Lihat Data Nilai
                    </Button>
                </div>
            </div>

            <Card>
                <CardHeader>
                    <h2 className="text-2xl font-bold">Import Data Excel</h2>
                    <p className="text-gray-500">
                        Gunakan fitur ini untuk mengimport data nilai dan absensi dari file Excel
                    </p>
                </CardHeader>
                <CardContent>
                    {/* Tab Navigation */}
                    <div className="mb-6 border-b">
                        <div className="flex space-x-4">
                            <button
                                className={`px-4 py-2 font-medium ${
                                    activeTab === 'nilai'
                                        ? 'border-b-2 border-orange-500 text-orange-600'
                                        : 'text-gray-500 hover:text-gray-700'
                                }`}
                                onClick={() => setActiveTab('nilai')}
                            >
                                Import Nilai
                            </button>
                            <button
                                className={`px-4 py-2 font-medium ${
                                    activeTab === 'absensi'
                                        ? 'border-b-2 border-orange-500 text-orange-600'
                                        : 'text-gray-500 hover:text-gray-700'
                                }`}
                                onClick={() => setActiveTab('absensi')}
                            >
                                Import Absensi
                            </button>
                        </div>
                    </div>

                    {/* Tab Content for Nilai */}
                    {activeTab === 'nilai' && (
                        <div className="flex flex-col space-y-4">
                            <div className="flex flex-col space-y-2">
                                <label htmlFor="category" className="text-sm font-medium text-gray-700">
                                    Jenis Nilai
                                </label>
                                <div className="flex items-center space-x-4">
                                    <Alert variant="info">
                                        <AlertDescription>
                                            <p>Format Excel untuk nilai berisi kolom:</p>
                                            <ul className="mt-2 list-disc pl-6">
                                                <li>
                                                    <strong>nilai_tugas</strong>: Nilai tugas (0-100)
                                                </li>
                                                <li>
                                                    <strong>nilai_uts</strong>: Nilai UTS (0-100)
                                                </li>
                                                <li>
                                                    <strong>nilai_uas</strong>: Nilai UAS (0-100)
                                                </li>
                                            </ul>
                                            <p className="mt-2">
                                                Anda bisa mengisi satu, dua, atau ketiga nilai sekaligus.
                                            </p>
                                        </AlertDescription>
                                    </Alert>
                                    <Button
                                        variant="blue"
                                        className="flex items-center gap-2"
                                        onClick={handleDownloadGradeTemplate}
                                    >
                                        <IconDownload className="size-4" />
                                        Download Template
                                    </Button>
                                </div>
                            </div>

                            <form onSubmit={handleGradeSubmit} className="space-y-4">
                                <div className="flex flex-col space-y-2">
                                    <label htmlFor="gradeFile" className="text-sm font-medium text-gray-700">
                                        Upload File Excel
                                    </label>
                                    <Input
                                        id="gradeFile"
                                        type="file"
                                        accept=".xlsx,.xls"
                                        onChange={(e) => setGradeData('file', e.target.files[0])}
                                    />
                                    {gradeErrors.file && <p className="text-sm text-red-500">{gradeErrors.file}</p>}
                                </div>

                                <Alert variant="warning">
                                    <AlertDescription>
                                        Pastikan format file Excel sesuai dengan template yang disediakan. Data yang
                                        sudah diimport tidak dapat diubah lagi.
                                    </AlertDescription>
                                </Alert>

                                <Button type="submit" disabled={gradeProcessing} className="flex items-center gap-2">
                                    <IconCloudUpload className="size-4" />
                                    {gradeProcessing ? 'Sedang Memproses...' : 'Import Nilai'}
                                </Button>
                            </form>
                        </div>
                    )}

                    {/* Tab Content for Absensi */}
                    {activeTab === 'absensi' && (
                        <div className="flex flex-col space-y-4">
                            <div className="flex items-center justify-between">
                                <h3 className="text-lg font-semibold">Import Data Absensi</h3>
                                <Button
                                    variant="blue"
                                    className="flex items-center gap-2"
                                    onClick={handleDownloadAttendanceTemplate}
                                >
                                    <IconDownload className="size-4" />
                                    Download Template
                                </Button>
                            </div>

                            <form onSubmit={handleAttendanceSubmit} className="space-y-4">
                                <div className="flex flex-col space-y-2">
                                    <label htmlFor="attendanceFile" className="text-sm font-medium text-gray-700">
                                        Upload File Excel
                                    </label>
                                    <Input
                                        id="attendanceFile"
                                        type="file"
                                        accept=".xlsx,.xls"
                                        onChange={(e) => setAttendanceData('file', e.target.files[0])}
                                    />
                                    {attendanceErrors.file && (
                                        <p className="text-sm text-red-500">{attendanceErrors.file}</p>
                                    )}
                                </div>

                                <Alert variant="warning">
                                    <AlertDescription>
                                        <p>Petunjuk pengisian absensi:</p>
                                        <ul className="mt-2 list-disc pl-6">
                                            <li>Isi dengan angka 1 untuk kehadiran (Hadir)</li>
                                            <li>Biarkan kosong atau isi 0 untuk ketidakhadiran</li>
                                            <li>Data yang sudah diimport tidak dapat diubah</li>
                                        </ul>
                                    </AlertDescription>
                                </Alert>

                                <Button
                                    type="submit"
                                    disabled={attendanceProcessing}
                                    className="flex items-center gap-2"
                                >
                                    <IconCloudUpload className="size-4" />
                                    {attendanceProcessing ? 'Sedang Memproses...' : 'Import Absensi'}
                                </Button>
                            </form>
                        </div>
                    )}

                    <div className="mt-8 border-t pt-6">
                        <h3 className="mb-4 text-lg font-semibold">Petunjuk Penggunaan:</h3>
                        <ol className="list-decimal space-y-2 pl-6">
                            <li>Download template Excel sesuai jenis data yang akan diimport (Nilai atau Absensi)</li>
                            <li>Isi data pada template tanpa mengubah struktur kolom yang ada</li>
                            <li>Untuk nilai, pastikan nilainya dalam rentang 0-100</li>
                            <li>Untuk absensi, isi dengan 1 (hadir) atau biarkan kosong/0 (tidak hadir)</li>
                            <li>Upload file Excel yang sudah diisi</li>
                            <li>Klik tombol Import untuk memproses data</li>
                        </ol>
                    </div>
                </CardContent>
            </Card>
        </div>
    );
}

ImportExcel.layout = (page) => <AppLayout children={page} title={page.props.page_settings.title} />;
