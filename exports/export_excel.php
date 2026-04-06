<?php
error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);
require __DIR__ . '/../config/database.php';
require __DIR__ . '/../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    die("Akses ditolak!");
}

$filter_bulan = $_GET['bulan'] ?? date('Y-m');
$filter_kelas = $_GET['kelas_id'] ?? '';

$where  = "WHERE u.role = 'siswa'";
$params = [$filter_bulan];

if ($filter_kelas) {
    $where .= " AND EXISTS (
        SELECT 1 FROM absensi ab2
        JOIN sesi_absensi s2 ON ab2.sesi_id = s2.id
        WHERE ab2.siswa_id = u.id AND s2.kelas_id = ?
    )";
    $params[] = $filter_kelas;
}

$stmt = $pdo->prepare("
    SELECT u.nama,
        COUNT(CASE WHEN a.status = 'hadir'     THEN 1 END) AS hadir,
        COUNT(CASE WHEN a.status = 'terlambat' THEN 1 END) AS terlambat,
        COUNT(CASE WHEN a.status = 'alpha'     THEN 1 END) AS alpha,
        COUNT(a.id) AS total,
        ROUND(
            COUNT(CASE WHEN a.status IN ('hadir','terlambat') THEN 1 END) * 100.0
            / NULLIF(COUNT(a.id), 0), 1
        ) AS persen_hadir
    FROM users u
    LEFT JOIN absensi a ON u.id = a.siswa_id
        AND DATE_FORMAT(a.waktu_absen, '%Y-%m') = ?
    $where
    GROUP BY u.id, u.nama
    ORDER BY u.nama
");
$stmt->execute($params);
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Buat Spreadsheet
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Rekap Absensi');

// Judul
$sheet->mergeCells('A1:G1');
$sheet->setCellValue('A1', 'LAPORAN REKAP ABSENSI - ' . strtoupper($filter_bulan));
$sheet->getStyle('A1')->applyFromArray([
    'font'      => ['bold' => true, 'size' => 14, 'color' => ['rgb' => 'FFFFFF']],
    'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '2C3E50']],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
]);
$sheet->getRowDimension(1)->setRowHeight(35);

// Header kolom
$headers = ['No', 'Nama Siswa', 'Hadir', 'Terlambat', 'Alpha', 'Total Sesi', '% Hadir'];
foreach ($headers as $col => $header) {
    $sheet->setCellValue(chr(65 + $col) . '2', $header);
}
$sheet->getStyle('A2:G2')->applyFromArray([
    'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
    'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '2980B9']],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
    'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'FFFFFF']]],
]);
$sheet->getRowDimension(2)->setRowHeight(22);

// Isi data
foreach ($data as $i => $row) {
    $rowNum = $i + 3;
    $persen = $row['persen_hadir'] ?? 0;
    $bgColor = ($i % 2 == 0) ? 'FFFFFF' : 'EBF5FB';

    $sheet->setCellValue("A$rowNum", $i + 1);
    $sheet->setCellValue("B$rowNum", $row['nama']);
    $sheet->setCellValue("C$rowNum", $row['hadir']);
    $sheet->setCellValue("D$rowNum", $row['terlambat']);
    $sheet->setCellValue("E$rowNum", $row['alpha']);
    $sheet->setCellValue("F$rowNum", $row['total']);
    $sheet->setCellValue("G$rowNum", $persen . '%');

    $sheet->getStyle("A$rowNum:G$rowNum")->applyFromArray([
        'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $bgColor]],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
    ]);
    $sheet->getStyle("B$rowNum")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);

    // Warna % kehadiran
    $persenColor = $persen >= 80 ? '27AE60' : ($persen >= 60 ? 'F39C12' : 'E74C3C');
    $sheet->getStyle("G$rowNum")->getFont()->setBold(true)->getColor()->setRGB($persenColor);
}

// Border semua data
$lastRow = count($data) + 2;
$sheet->getStyle("A2:G$lastRow")->applyFromArray([
    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'BDC3C7']]],
]);

// Lebar kolom
$sheet->getColumnDimension('A')->setWidth(5);
$sheet->getColumnDimension('B')->setWidth(30);
$sheet->getColumnDimension('C')->setWidth(10);
$sheet->getColumnDimension('D')->setWidth(12);
$sheet->getColumnDimension('E')->setWidth(10);
$sheet->getColumnDimension('F')->setWidth(12);
$sheet->getColumnDimension('G')->setWidth(12);

// Download
$filename = 'rekap_absensi_' . $filter_bulan . '.xlsx';
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
?>