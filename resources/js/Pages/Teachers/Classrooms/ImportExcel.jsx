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
    const [importMode, setImportMode] = useState('single'); // 'single' atau 'all_schedules'

    // Form untuk import nilai (single classroom)
    const {
        data: gradeData,
        setData: setGradeData,
        post: postGrade,
        processing: gradeProcessing,
        errors: gradeErrors,
        reset: gradeReset,
    } = useForm({
        file: null,
    });

    // Form untuk import nilai semua jadwal
    const {
        data: schedulesData,
        setData: setSchedulesData,
        post: postSchedules,
        processing: schedulesProcessing,
        errors: schedulesErrors,
        reset: schedulesReset,
    } = useForm({
        file: null,
    });

    // Form untuk import absensi (single classroom)
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

    // Form untuk import absensi semua jadwal
    const {
        data: attendanceSchedulesData,
        setData: setAttendanceSchedulesData,
        post: postAttendanceSchedules,
        processing: attendanceSchedulesProcessing,
        errors: attendanceSchedulesErrors,
        reset: attendanceSchedulesReset,
    } = useForm({
        file: null,
    });

    // Form untuk import excel dosen
    const {
        data: dosenExcelData,
        setData: setDosenExcelData,
        post: postDosenExcel,
        processing: dosenExcelProcessing,
        errors: dosenExcelErrors,
        reset: dosenExcelReset,
    } = useForm({
        file: null,
    });

    // Download template nilai (single classroom)
    const handleDownloadGradeTemplate = () => {
        window.location.href = route('teachers.classrooms.template.grade', [course.id, classroom.id]);
    };

    // Download template nilai semua jadwal
    const handleDownloadSchedulesTemplate = () => {
        window.location.href = route('teachers.courses.template.schedules', [course.id]);
    };

    // Download template absensi (single classroom)
    const handleDownloadAttendanceTemplate = () => {
        window.location.href = route('teachers.classrooms.template.attendance', [course.id, classroom.id]);
    };

    // Download template absensi semua jadwal
    const handleDownloadAttendanceSchedulesTemplate = () => {
        window.location.href = route('teachers.courses.template.attendance-schedules', [course.id]);
    };

    // Handle submit form excel dosen
    const handleDosenExcelSubmit = (e) => {
        e.preventDefault();

        postDosenExcel(route('teachers.classrooms.import.dosen-excel', [course.id, classroom.id]), {
            preserveScroll: true,
            onSuccess: () => {
                toast.success('File Excel dosen berhasil diimpor');
                dosenExcelReset();
            },
            onError: (errors) => {
                toast.error('Terjadi kesalahan saat import Excel dosen');
                console.error(errors);
            },
        });
    };

    // Handle submit form nilai (single classroom)
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

    // Handle submit form nilai semua jadwal
    const handleSchedulesSubmit = (e) => {
        e.preventDefault();

        postSchedules(route('teachers.courses.import.schedules', [course.id]), {
            preserveScroll: true,
            onSuccess: () => {
                toast.success('Nilai untuk semua jadwal berhasil diimport');
                schedulesReset();
            },
            onError: (errors) => {
                toast.error('Terjadi kesalahan saat import nilai untuk semua jadwal');
                console.error(errors);
            },
        });
    };

    // Handle submit form absensi (single classroom)
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

    // Handle submit form absensi semua jadwal
    const handleAttendanceSchedulesSubmit = (e) => {
        e.preventDefault();

        postAttendanceSchedules(route('teachers.courses.import.attendance-schedules', [course.id]), {
            preserveScroll: true,
            onSuccess: () => {
                toast.success('Absensi untuk semua jadwal berhasil diimport');
                attendanceSchedulesReset();
            },
            onError: (errors) => {
                toast.error('Terjadi kesalahan saat import absensi untuk semua jadwal');
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

                    {/* Mode Selector (Shared between both tabs) */}
                    <div className="mb-4 flex items-center space-x-6 border-b pb-4">
                        <span className="font-medium">Mode Import:</span>
                        <div className="flex space-x-4">
                            <label className="flex items-center space-x-2">
                                <input
                                    type="radio"
                                    name="importMode"
                                    value="single"
                                    checked={importMode === 'single'}
                                    onChange={() => setImportMode('single')}
                                    className="h-4 w-4 border-gray-300 text-orange-600 focus:ring-orange-500"
                                />
                                <span>Per Kelas</span>
                            </label>
                            <label className="flex items-center space-x-2">
                                <input
                                    type="radio"
                                    name="importMode"
                                    value="all_schedules"
                                    checked={importMode === 'all_schedules'}
                                    onChange={() => setImportMode('all_schedules')}
                                    className="h-4 w-4 border-gray-300 text-orange-600 focus:ring-orange-500"
                                />
                                <span>Semua Jadwal Mata Kuliah</span>
                            </label>
                            <label className="flex items-center space-x-2">
                                <input
                                    type="radio"
                                    name="importMode"
                                    value="dosen_excel"
                                    checked={importMode === 'dosen_excel'}
                                    onChange={() => setImportMode('dosen_excel')}
                                    className="h-4 w-4 border-gray-300 text-orange-600 focus:ring-orange-500"
                                />
                                <span>Excel Dosen (Langsung)</span>
                            </label>
                        </div>
                    </div>

                    {/* Tab Content for Nilai */}
                    {activeTab === 'nilai' && (
                        <div className="flex flex-col space-y-4">
                            {/* Single Classroom Import */}
                            {importMode === 'single' && (
                                <div className="space-y-4">
                                    <div className="flex flex-col space-y-2">
                                        <label htmlFor="category" className="text-sm font-medium text-gray-700">
                                            Import Nilai Per Kelas
                                        </label>
                                        <div className="flex items-center space-x-4">
                                            <Alert className="flex-1">
                                                <AlertDescription>
                                                    <p>Format Excel berisi kolom:</p>
                                                    <ul className="mt-2 list-disc pl-6">
                                                        <li>
                                                            <strong>student_id</strong>: ID mahasiswa (terisi otomatis)
                                                        </li>
                                                        <li>
                                                            <strong>nim</strong>: Nomor Induk Mahasiswa
                                                        </li>
                                                        <li>
                                                            <strong>name</strong>: Nama mahasiswa
                                                        </li>
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
                                            <div>
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
                                            {gradeErrors.file && (
                                                <p className="text-sm text-red-500">{gradeErrors.file}</p>
                                            )}
                                        </div>

                                        <Alert variant="warning">
                                            <AlertDescription>
                                                Pastikan format file Excel sesuai dengan template yang disediakan. Data
                                                yang sudah diimport tidak dapat diubah lagi.
                                            </AlertDescription>
                                        </Alert>

                                        <Button
                                            type="submit"
                                            disabled={gradeProcessing}
                                            className="flex items-center gap-2"
                                        >
                                            <IconCloudUpload className="size-4" />
                                            {gradeProcessing ? 'Sedang Memproses...' : 'Import Nilai'}
                                        </Button>
                                    </form>

                                    <Alert className="mt-4">
                                        <AlertDescription>
                                            <strong className="font-medium">Catatan:</strong> Mode ini mengimpor nilai
                                            hanya untuk kelas {classroom.name} saja.
                                        </AlertDescription>
                                    </Alert>
                                </div>
                            )}

                            {/* All Schedules Import */}
                            {importMode === 'all_schedules' && (
                                <div className="flex flex-col space-y-4">
                                    <div className="flex flex-col space-y-2">
                                        <label className="text-sm font-medium text-gray-700">
                                            Import Nilai Semua Jadwal Mata Kuliah
                                        </label>
                                        <div className="flex items-center space-x-4">
                                            <Alert>
                                                <AlertDescription>
                                                    <p>Format Excel untuk semua jadwal berisi kolom:</p>
                                                    <ul className="mt-2 list-disc pl-6">
                                                        <li>
                                                            <strong>nim</strong>: Nomor Induk Mahasiswa
                                                        </li>
                                                        <li>
                                                            <strong>name</strong>: Nama Mahasiswa
                                                        </li>
                                                        <li>
                                                            <strong>classroom_id</strong>: ID Kelas
                                                        </li>
                                                        <li>
                                                            <strong>classroom_name</strong>: Nama Kelas
                                                        </li>
                                                        <li className="text-yellow-600">
                                                            <strong>nama_mata_kuliah</strong>: Nama Mata Kuliah
                                                        </li>
                                                        <li>
                                                            <strong>jadwal</strong>: Informasi jadwal
                                                        </li>
                                                        <li className="font-semibold text-green-600">
                                                            <strong>nilai_absensi</strong>: Jumlah pertemuan yang
                                                            dihadiri (1-16)
                                                        </li>
                                                        <li className="text-blue-600">
                                                            <strong>nilai_tugas</strong>: Nilai tugas (0-100)
                                                        </li>
                                                        <li className="text-blue-600">
                                                            <strong>nilai_uts</strong>: Nilai UTS (0-100)
                                                        </li>
                                                        <li className="text-blue-600">
                                                            <strong>nilai_uas</strong>: Nilai UAS (0-100)
                                                        </li>
                                                    </ul>
                                                    <p className="mt-2">
                                                        Template berisi semua mahasiswa dari semua kelas yang terkait
                                                        dengan mata kuliah ini.
                                                    </p>
                                                </AlertDescription>
                                            </Alert>
                                            <div>
                                                <Button
                                                    variant="blue"
                                                    className="flex items-center gap-2"
                                                    onClick={handleDownloadSchedulesTemplate}
                                                >
                                                    <IconDownload className="size-4" />
                                                    Download Template
                                                </Button>
                                            </div>
                                        </div>
                                    </div>

                                    <form onSubmit={handleSchedulesSubmit} className="space-y-4">
                                        <div className="flex flex-col space-y-2">
                                            <label
                                                htmlFor="schedulesFile"
                                                className="text-sm font-medium text-gray-700"
                                            >
                                                Upload File Excel
                                            </label>
                                            <Input
                                                id="schedulesFile"
                                                type="file"
                                                accept=".xlsx,.xls"
                                                onChange={(e) => setSchedulesData('file', e.target.files[0])}
                                            />
                                            {schedulesErrors.file && (
                                                <p className="text-sm text-red-500">{schedulesErrors.file}</p>
                                            )}
                                        </div>

                                        <Alert variant="warning">
                                            <AlertDescription>
                                                <p>Petunjuk penggunaan:</p>
                                                <ul className="mt-2 list-disc pl-6">
                                                    <li>Anda dapat mengisi nilai untuk semua kelas sekaligus</li>
                                                    <li className="font-semibold text-green-600">
                                                        Nilai Absensi diisi dengan angka 1-16 (jumlah pertemuan yang
                                                        dihadiri)
                                                    </li>
                                                    <li>Kolom yang kosong akan diabaikan</li>
                                                    <li>Nilai yang sudah ada akan diperbarui</li>
                                                    <li>
                                                        <strong>Jangan mengubah</strong> kolom nim, name, classroom_id,
                                                        classroom_name, nama_mata_kuliah, dan jadwal
                                                    </li>
                                                </ul>
                                            </AlertDescription>
                                        </Alert>

                                        <Button
                                            type="submit"
                                            disabled={schedulesProcessing}
                                            className="flex items-center gap-2"
                                        >
                                            <IconCloudUpload className="size-4" />
                                            {schedulesProcessing ? 'Sedang Memproses...' : 'Import Nilai dan Absensi'}
                                        </Button>
                                    </form>

                                    <Alert className="mt-4 bg-green-50 text-green-800">
                                        <AlertDescription>
                                            <strong className="font-medium">🔥 Fitur Baru:</strong> Mode ini
                                            memungkinkan Anda mengimpor nilai untuk{' '}
                                            <strong>semua jadwal mata kuliah {course.name} sekaligus</strong>, meskipun
                                            diampu oleh beberapa dosen!
                                        </AlertDescription>
                                    </Alert>
                                </div>
                            )}

                            {/* Tampilkan form import untuk Excel Dosen jika mode yang dipilih adalah dosen_excel */}
                            {importMode === 'dosen_excel' && (
                                <div className="flex flex-col space-y-4">
                                    <div className="flex flex-col space-y-2">
                                        <label className="text-sm font-medium text-gray-700">
                                            Import Langsung dari Excel Dosen
                                        </label>
                                        <div className="flex items-center space-x-4">
                                            <Alert className="flex-1">
                                                <AlertDescription>
                                                    <p>File Excel dosen akan diproses dengan ketentuan:</p>
                                                    <ul className="mt-2 list-disc pl-6">
                                                        <li>
                                                            <strong>NIM/Nomor Mahasiswa</strong>: Untuk identifikasi
                                                            mahasiswa
                                                        </li>
                                                        <li>
                                                            <strong>Partisipatif/Kehadiran</strong>: Dikonversi ke data
                                                            absensi
                                                        </li>
                                                        <li>
                                                            <strong>Proyek/Tugas</strong>: Diisi ke nilai tugas
                                                        </li>
                                                        <li>
                                                            <strong>UTS</strong>: Nilai ujian tengah semester
                                                        </li>
                                                        <li>
                                                            <strong>UAS</strong>: Nilai ujian akhir semester
                                                        </li>
                                                    </ul>
                                                    <p className="mt-2">
                                                        Sistem akan otomatis mendeteksi kolom berdasarkan nama yang
                                                        mirip dengan kategori di atas.
                                                    </p>
                                                </AlertDescription>
                                            </Alert>
                                        </div>
                                    </div>

                                    <form onSubmit={handleDosenExcelSubmit} className="space-y-4">
                                        <div className="flex flex-col space-y-2">
                                            <label
                                                htmlFor="dosenExcelFile"
                                                className="text-sm font-medium text-gray-700"
                                            >
                                                Upload File Excel Dosen
                                            </label>
                                            <Input
                                                id="dosenExcelFile"
                                                type="file"
                                                accept=".xlsx,.xls,.csv"
                                                onChange={(e) => setDosenExcelData('file', e.target.files[0])}
                                            />
                                            {dosenExcelErrors.file && (
                                                <p className="text-sm text-red-500">{dosenExcelErrors.file}</p>
                                            )}
                                        </div>

                                        <Alert variant="warning">
                                            <AlertDescription>
                                                <p>Petunjuk penggunaan:</p>
                                                <ul className="mt-2 list-disc pl-6">
                                                    <li>
                                                        Upload <strong>tanpa modifikasi</strong> file Excel yang
                                                        diberikan oleh dosen
                                                    </li>
                                                    <li>Pastikan file berisi kolom untuk NIM/Nomor Mahasiswa</li>
                                                    <li>Sistem akan mencoba mendeteksi kolom nilai secara otomatis</li>
                                                    <li>
                                                        Jika mahasiswa tidak ditemukan, baris tersebut akan dilewati
                                                    </li>
                                                </ul>
                                            </AlertDescription>
                                        </Alert>

                                        <Button
                                            type="submit"
                                            disabled={dosenExcelProcessing}
                                            className="flex items-center gap-2"
                                        >
                                            <IconCloudUpload className="size-4" />
                                            {dosenExcelProcessing ? 'Sedang Memproses...' : 'Import Excel Dosen'}
                                        </Button>
                                    </form>

                                    <Alert className="mt-4 bg-green-50 text-green-800">
                                        <AlertDescription>
                                            <strong className="font-medium">🔥 Fitur Baru:</strong> Mode ini
                                            memungkinkan Anda mengimpor langsung dari file Excel dosen tanpa perlu
                                            menyesuaikan format terlebih dahulu!
                                        </AlertDescription>
                                    </Alert>
                                </div>
                            )}
                        </div>
                    )}

                    {/* Tab Content for Absensi */}
                    {activeTab === 'absensi' && (
                        <div className="flex flex-col space-y-4">
                            {/* Single Classroom Attendance Import */}
                            {importMode === 'single' && (
                                <div className="flex flex-col space-y-4">
                                    <div className="flex items-center justify-between">
                                        <h3 className="text-lg font-semibold">Import Absensi Per Kelas</h3>
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
                                            <label
                                                htmlFor="attendanceFile"
                                                className="text-sm font-medium text-gray-700"
                                            >
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

                                    <Alert className="mt-4">
                                        <AlertDescription>
                                            <strong className="font-medium">Catatan:</strong> Mode ini mengimpor absensi
                                            hanya untuk kelas {classroom.name} saja.
                                        </AlertDescription>
                                    </Alert>
                                </div>
                            )}

                            {/* All Schedules Attendance Import */}
                            {importMode === 'all_schedules' && (
                                <div className="flex flex-col space-y-4">
                                    <div className="flex flex-col space-y-2">
                                        <label className="text-sm font-medium text-gray-700">
                                            Import Absensi Semua Jadwal Mata Kuliah
                                        </label>
                                        <div className="flex items-center space-x-4">
                                            <Alert>
                                                <AlertDescription>
                                                    <p>Format Excel untuk absensi semua jadwal berisi kolom:</p>
                                                    <ul className="mt-2 list-disc pl-6">
                                                        <li>
                                                            <strong>nim</strong>: Nomor Induk Mahasiswa
                                                        </li>
                                                        <li>
                                                            <strong>name</strong>: Nama Mahasiswa
                                                        </li>
                                                        <li>
                                                            <strong>classroom_id</strong>: ID Kelas
                                                        </li>
                                                        <li>
                                                            <strong>classroom_name</strong>: Nama Kelas
                                                        </li>
                                                        <li>
                                                            <strong>jadwal</strong>: Informasi jadwal
                                                        </li>
                                                        <li>
                                                            <strong>pertemuan_1</strong> sampai{' '}
                                                            <strong>pertemuan_16</strong>: Kehadiran per pertemuan
                                                        </li>
                                                    </ul>
                                                    <p className="mt-2">
                                                        Template berisi semua mahasiswa dari semua kelas yang terkait
                                                        dengan mata kuliah ini.
                                                    </p>
                                                </AlertDescription>
                                            </Alert>
                                            <div>
                                                <Button
                                                    variant="blue"
                                                    className="flex items-center gap-2"
                                                    onClick={handleDownloadAttendanceSchedulesTemplate}
                                                >
                                                    <IconDownload className="size-4" />
                                                    Download Template
                                                </Button>
                                            </div>
                                        </div>
                                    </div>

                                    <form onSubmit={handleAttendanceSchedulesSubmit} className="space-y-4">
                                        <div className="flex flex-col space-y-2">
                                            <label
                                                htmlFor="attendanceSchedulesFile"
                                                className="text-sm font-medium text-gray-700"
                                            >
                                                Upload File Excel
                                            </label>
                                            <Input
                                                id="attendanceSchedulesFile"
                                                type="file"
                                                accept=".xlsx,.xls"
                                                onChange={(e) => setAttendanceSchedulesData('file', e.target.files[0])}
                                            />
                                            {attendanceSchedulesErrors.file && (
                                                <p className="text-sm text-red-500">{attendanceSchedulesErrors.file}</p>
                                            )}
                                        </div>

                                        <Alert variant="warning">
                                            <AlertDescription>
                                                <p>Petunjuk penggunaan:</p>
                                                <ul className="mt-2 list-disc pl-6">
                                                    <li>Isi dengan angka 1 untuk kehadiran (Hadir)</li>
                                                    <li>Biarkan kosong atau isi 0 untuk ketidakhadiran</li>
                                                    <li>
                                                        <strong>Jangan mengubah</strong> kolom nim, name, classroom_id,
                                                        classroom_name, dan jadwal
                                                    </li>
                                                </ul>
                                            </AlertDescription>
                                        </Alert>

                                        <Button
                                            type="submit"
                                            disabled={attendanceSchedulesProcessing}
                                            className="flex items-center gap-2"
                                        >
                                            <IconCloudUpload className="size-4" />
                                            {attendanceSchedulesProcessing
                                                ? 'Sedang Memproses...'
                                                : 'Import Absensi Semua Jadwal'}
                                        </Button>
                                    </form>

                                    <Alert className="mt-4 bg-green-50 text-green-800">
                                        <AlertDescription>
                                            <strong className="font-medium">🔥 Fitur Baru:</strong> Mode ini
                                            memungkinkan Anda mengimpor absensi untuk{' '}
                                            <strong>semua jadwal mata kuliah {course.name} sekaligus</strong>, meskipun
                                            diampu oleh beberapa dosen!
                                        </AlertDescription>
                                    </Alert>
                                </div>
                            )}
                        </div>
                    )}

                    <div className="mt-8 border-t pt-6">
                        <h3 className="mb-4 text-lg font-semibold">Petunjuk Penggunaan:</h3>
                        <ol className="list-decimal space-y-2 pl-6">
                            <li>Download template Excel sesuai jenis data yang akan diimport</li>
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
