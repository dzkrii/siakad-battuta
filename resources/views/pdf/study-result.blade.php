<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <meta name="google" content="notranslate">
    <meta http-equiv="Content-Language" content="id">
    <title>Kartu Hasil Studi</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            line-height: 1.5;
            color: #000;
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
        }

        .logo {
            margin: 0 auto;
            width: 80px;
            height: 80px;
        }

        .university-name {
            font-size: 35px;
            font-weight: bold;
            margin: 10px 0 5px;
        }

        .university-address {
            font-size: 11px;
            margin: 5px 0;
        }

        .divider {
            border-top: 2px solid #000;
            margin: 10px 0;
        }

        .title {
            font-size: 18px;
            font-weight: bold;
            text-align: center;
            margin: 20px 0;
        }

        .student-info {
            margin-bottom: 20px;
        }

        .student-info table {
            width: 100%;
            border-collapse: collapse;
        }

        .student-info td {
            padding: 5px;
            vertical-align: top;
        }

        .grades-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        .grades-table th,
        .grades-table td {
            border: 1px solid #000;
            padding: 5px;
            text-align: center;
        }

        .grades-table th {
            background-color: #f2f2f2;
        }

        /* Set equal width for H, A, SKS, and T columns */
        .grades-table th.equal-width,
        .grades-table td.equal-width {
            width: 8%;
        }

        /* Adjust other column widths */
        .grades-table th.no-column,
        .grades-table td.no-column {
            width: 5%;
        }

        .grades-table th.code-column,
        .grades-table td.code-column {
            width: 12%;
        }

        .grades-table th.name-column,
        .grades-table td.name-column {
            width: 43%;
        }

        .summary {
            margin-top: 20px;
        }

        .signature {
            margin-top: 40px;
            text-align: right;
        }

        .legend {
            margin-top: 30px;
            font-size: 10px;
        }
    </style>
</head>

<body>
    <div class="header">
        <table style="width: 100%;">
            <tr>
                <td style="width: 100px; vertical-align: top;">
                    <img src="{{ public_path('battuta.png') }}" alt="Logo Universitas" style="width: 100px;">
                </td>
                <td style="text-align: center; vertical-align: top;">
                    <div class="university-name">UNIVERSITAS BATTUTA</div>
                    <div class="university-address">
                        Jl. Sekip Simpang Jl. Sikambing No. 1 Medan – 20111 | Telp. (061) 80514277<br>
                        Website : www.battuta.ac.id | Email : official@battuta.ac.id
                    </div>
                </td>
                <td style="width: 80px;"></td>
            </tr>
        </table>
    </div>

    <div class="divider"></div>

    <div class="title">KARTU HASIL STUDI</div>

    <div class="student-info">
        <table>
            <tr>
                <td style="width: 200px; font-size: 14px;">Nama</td>
                <td style="width: 10px; font-size: 14px;">:</td>
                <td style="font-size: 14px;">{{ auth()->user()->name }}</td>
            </tr>
            <tr>
                <td style="font-size: 14px;">Nomor Induk Mahasiswa</td>
                <td style="font-size: 14px;">:</td>
                <td style="font-size: 14px;">{{ $student->student_number }}</td>
            </tr>
            <tr>
                <td style="font-size: 14px;">Fakultas / Program Studi</td>
                <td style="font-size: 14px;">:</td>
                <td style="font-size: 14px;">
                    {{ $student->faculty->name }} / {{ $student->department->name }}</td>
            </tr>
            <tr>
                <td style="font-size: 14px;">Tahun Akademik</td>
                <td style="font-size: 14px;">:</td>
                <td style="font-size: 14px;">{{ $studyResult->academicYear->name }}</td>
            </tr>
            <tr>
                <td style="font-size: 14px;">Semester</td>
                <td style="font-size: 14px;">:</td>
                <td style="font-size: 14px;">
                    {{ $studyResult->semester }}</td>
            </tr>
        </table>
    </div>

    <table class="grades-table">
        <thead>
            <tr>
                <th class="no-column" rowspan="2">NO</th>
                <th colspan="2">MATA KULIAH</th>
                <th class="equal-width" rowspan="2">H</th>
                <th class="equal-width" rowspan="2">A</th>
                <th class="equal-width" rowspan="2">SKS</th>
                <th class="equal-width" rowspan="2">T</th>
            </tr>
            <tr>
                <th class="code-column">KODE</th>
                <th class="name-column">NAMA</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($grades as $index => $grade)
                <tr>
                    <td class="no-column">{{ $index + 1 }}</td>
                    <td class="code-column">{{ $grade->course->kode_matkul }}</td>
                    <td class="name-column" style="text-align: left;">{{ $grade->course->name }}</td>
                    <td class="equal-width">{{ $grade->letter }}</td>
                    <td class="equal-width">{{ $grade->weight_of_value }}</td>
                    <td class="equal-width">{{ $grade->course->credit }}</td>
                    <td class="equal-width">{{ $grade->weight_of_value * $grade->course->credit }}</td>
                </tr>
            @endforeach
            <tr>
                <td colspan="5" style="text-align: center;"><strong>Jumlah</strong></td>
                <td class="equal-width"><strong>{{ $totalSks }}</strong></td>
                <td class="equal-width"><strong>{{ $totalT }}</strong></td>
            </tr>
            <tr>
                <td colspan="5" style="text-align: center;"><strong>Indeks Prestasi Semester (IPS)</strong></td>
                <td colspan="2" style="text-align: center;">
                    <strong>{{ number_format($studyResult->gpa, 2, '.', '') }}</strong>
                </td>
            </tr>
        </tbody>
    </table>

    <div class="legend">
        <table>
            <tr>
                {{-- <td style="vertical-align: top; width: 120px; text-align: right;">Keterangan :</td> --}}
                <td style="vertical-align: top; width: 450px;">
                    H : Nilai Huruf<br>
                    A : Nilai Angka<br>
                    SKS : Satuan Kredit Semester<br>
                    T : Nilai Angka x SKS
                </td>
                <td style="vertical-align: top; text-align: left; font-size: 12px;">
                    Medan, {{ $date }}<br>
                    Ketua Program Studi<br><br><br><br>
                    {{ $kaprodi }}
                </td>
            </tr>
        </table>
    </div>
</body>

</html>
