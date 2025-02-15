<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Kartu Rencana Studi</title>
    <style>
        body {
            font-family: "Times New Roman", serif;
            font-size: 12pt;
            /* padding: 20px; */
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
        }

        .info {
            margin-bottom: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        th,
        td {
            border: 1px solid #ddd;
            padding: 4px;
            text-align: center;
        }

        th {
            background-color: #f5f5f5;
        }

        .footer {
            margin-top: 30px;
            display: flex;
            justify-content: space-between;
        }

        .total-sks {
            margin-top: 20px;
        }

        .tanda-tangan {
            width: 50%;
            border: none;
            text-align: left;
        }
    </style>
</head>

<body>
    <div style="display: flex; justify-content: center; align-items: center; text-align: center; margin-bottom: 10px;">
        <img src="{{ public_path('battuta.png') }}" alt="Logo Universitas Battuta"
            style="width: 100px; vertical-align: middle">
        <h1 style="display: inline-block; vertical-align: middle; font-size: 40px; margin-left: 10px">UNIVERSITAS BATTUTA
        </h1>
        <hr>
    </div>
    <div class="header">
        <h2>KARTU RENCANA STUDI (KRS)</h2>
    </div>

    <div class="info">
        <table style="width: 100%; border: none;">
            <tr>
                <td style="border: none; text-align: left">NIM</td>
                <td style="border: none; text-align: left">: {{ $studyPlan->student->student_number }}</td>
            </tr>
            <tr>
                <td style="border: none; width: 150px; text-align: left">Nama Mahasiswa</td>
                <td style="border: none; text-align: left">: {{ auth()->user()->name }}</td>
            </tr>
            <tr>
                <td style="border: none; width: 150px; text-align: left">Program Studi</td>
                <td style="border: none; text-align: left">: {{ $studyPlan->student->department->name }}</td>
            </tr>
            <tr>
                <td style="border: none; text-align: left">Semester</td>
                <td style="border: none; text-align: left">: {{ $studyPlan->semester }}</td>
            </tr>
            <tr>
                <td style="border: none; text-align: left">Tahun Akademik</td>
                <td style="border: none; text-align: left">: {{ $studyPlan->academicYear->name }}</td>
            </tr>
        </table>
    </div>

    <table>
        <thead>
            <tr>
                <th rowspan="2">NO</th>
                <th colspan="2">MATA KULIAH</th>
                <th rowspan="2">SKS</th>
            </tr>
            <tr>
                <th>KODE</th>
                <th>NAMA</th>
            </tr>
        </thead>
        <tbody>
            @php $totalSks = 0; @endphp
            @foreach ($studyPlan->schedules as $index => $schedule)
                @php $totalSks += $schedule->course->credit; @endphp
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $schedule->course->kode_matkul }}</td>
                    <td style="text-align: left">{{ $schedule->course->name }}</td>
                    {{-- <td>{{ $schedule->course->teacher->user->name }}</td> --}}
                    <td>{{ $schedule->course->credit }}</td>
                    {{-- <td>{{ $schedule->classroom->name }}</td> --}}
                    {{-- <td>{{ $schedule->day_of_week }}, {{ $schedule->start_time }} - {{ $schedule->end_time }}</td> --}}
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="3" style="text-align: center; font-weight: bold">TOTAL</td>
                <td style="text-align: center; font-weight: bold">{{ $totalSks }}</td>
            </tr>
        </tfoot>
    </table>

    {{-- <div class="footer">
        <div>
            Mahasiswa,
            <br><br><br><br>
            {{ auth()->user()->name }}<br>
            NIM. {{ auth()->user()->student->nim }}
        </div>
        <div>
            {{ auth()->user()->student->department->city }}, {{ date('d F Y') }}<br>
            Mengetahui,<br>
            Ketua Program Studi
            <br><br><br><br>
            Fahmi Ruziq, S.T., M.Kom.
        </div>
    </div> --}}

    <table style="margin-top: 50px">
        <tr>
            <td class="tanda-tangan"></td>
            <td class="tanda-tangan" style="padding-left: 60px">Medan,
                {{ \Carbon\Carbon::now()->translatedFormat('d F Y') }}</td>
        </tr>
        <tr>
            <td class="tanda-tangan">Mahasiswa</td>
            <td class="tanda-tangan" style="padding-left: 60px">Ketua Program Studi</td>
        </tr>
        <tr>
            <td class="tanda-tangan"><br /><br /><br /></td>
            <td class="tanda-tangan"><br /><br /><br /></td>
        </tr>
        <tr>
            <td class="tanda-tangan">{{ auth()->user()->name }}</td>
            <td class="tanda-tangan" style="padding-left: 60px">
                @if ($studyPlan->student->department_id == 1)
                    {{-- Informatika --}}
                    Baginda Harahap, S.Pd., M.Kom.
                @elseif ($studyPlan->student->department_id == 2)
                    {{--  Teknologi Informasi --}}
                    Aripin Rambe, S.Kom., M.Kom.
                @elseif ($studyPlan->student->department_id == 3)
                    {{--  Sistem Informasi --}}
                    Fahmi Ruziq, S.T., M.Kom.
                @elseif ($studyPlan->student->department_id == 4)
                    {{--  Akuntansi --}}
                    Fhikry Ahmad H. Srg., S.E., M.Ak.
                @elseif ($studyPlan->student->department_id == 5)
                    {{--  Kewirausahaan --}}
                    Atika Aini Nasution, S.E., M.M.
                @elseif ($studyPlan->student->department_id == 6)
                    {{--  Manajemen --}}
                    Amril, S.Kom., M.M.
                @elseif ($studyPlan->student->department_id == 7)
                    {{--  Hukum --}}
                    Junaidi Lubis, S.H., M.H.
                @elseif ($studyPlan->student->department_id == 8)
                    {{-- PGSD --}}
                    Nur Wahyuni, S.Pd., M.Pd.
                @elseif ($studyPlan->student->department_id == 9)
                    {{-- PGPAUD --}}
                    Putri Sari Ulfa, S.Pd., M.Pd.
                @endif
            </td>
        </tr>
    </table>
</body>

</html>
