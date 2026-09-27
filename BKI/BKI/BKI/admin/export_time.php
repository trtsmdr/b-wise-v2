<?php
    session_start();

    if (!isset($_SESSION['username'])) {
        header("Location: Halaman_login.php");
        exit;
    }

    date_default_timezone_set('Asia/Jakarta');

    require __DIR__ . '/koneksi.php';
    require __DIR__ . '/../../../vendor/autoload.php';

    use PhpOffice\PhpSpreadsheet\Spreadsheet;
    use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
    use PhpOffice\PhpSpreadsheet\Style\Alignment;
    use PhpOffice\PhpSpreadsheet\Style\Border;
    use PhpOffice\PhpSpreadsheet\Style\Fill;
    use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;

    $role = $_SESSION['role'];
    $nama = $_SESSION['nama'];

    $type = $_GET['type'] ?? '';

    if (!in_array($type, ['monthly', 'period', 'all'], true)) {
        die('Invalid export type.');
    }

    $spreadsheet = new Spreadsheet();

    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('Time Export');

    $summarySheet = null;

    $where = '';
    $periodText = '';
    $title = '';

    if ($type === 'monthly') {

        $month = isset($_GET['month']) ? intval($_GET['month']) : intval(date('m'));
        $year = isset($_GET['year']) ? intval($_GET['year']) : intval(date('Y'));

        if ($month < 1 || $month > 12) {
            $month = intval(date('m'));
        }

        if ($year < 2000 || $year > 2100) {
            $year = intval(date('Y'));
        }

        $monthName = date('F', mktime(0, 0, 0, $month, 1));
        $lastDay = date('t', mktime(0, 0, 0, $month, 1));

        $periodText = '01-' . str_pad($month, 2, '0', STR_PAD_LEFT) . '-' . $year . ' to ' . $lastDay . '-' . str_pad($month, 2, '0', STR_PAD_LEFT) . '-' . $year;

        $where = "AND MONTH(p.tanggal) = '$month' AND YEAR(p.tanggal) = '$year'";

        $title = 'Employee Time Export - ' . $monthName . ' ' . $year;

        $filename = 'time_' . str_pad($month, 2, '0', STR_PAD_LEFT) . '_' . $year . '_' . date('Y-m-d_H-i-s') . '.xlsx';

    } elseif ($type === 'period') {

        $startDate = $_GET['start_date'] ?? '';
        $endDate = $_GET['end_date'] ?? '';

        if (empty($startDate) || empty($endDate)) {
            die('Start date and end date are required.');
        }

        if ($startDate > $endDate) {
            die('Start date cannot be greater than end date.');
        }

        $startDateEscaped = mysqli_real_escape_string($koneksi, $startDate);
        $endDateEscaped = mysqli_real_escape_string($koneksi, $endDate);

        $where = "AND p.tanggal BETWEEN '$startDateEscaped' AND '$endDateEscaped'";

        $periodText = date('d-m-Y', strtotime($startDate)) . ' to ' . date('d-m-Y', strtotime($endDate));

        $title = 'Employee Time Export - Period';

        $filename = 'time_' . $startDate . '_to_' . $endDate . '_' . date('Y-m-d_H-i-s') . '.xlsx';

    } elseif ($type === 'all') {

        $where = '';

        $periodText = 'All Data';

        $title = 'Employee Time Export - All Data';

        $filename = 'time_all_' . date('Y-m-d_H-i-s') . '.xlsx';
    }

    // Super Admin
    if ($role === 'Super-Admin') {

        $summarySheet = $spreadsheet->createSheet();
        $summarySheet->setTitle('Summary');

        $headers = [
            'Name',
            'Division',
            'NUP',
            'Total Present',
            'Sick',
            'Permission/Leave'
        ];

        $lastColumn = 'F';

        $summarySheet->mergeCells('A1:' . $lastColumn . '1');
        $summarySheet->setCellValue('A1', $title);

        $summarySheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
        $summarySheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $summarySheet->getStyle('A1')->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);

        $summarySheet->getRowDimension(1)->setRowHeight(30);

        $summarySheet->mergeCells('A2:' . $lastColumn . '2');
        $summarySheet->setCellValue('A2', $periodText);

        $summarySheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $summarySheet->getStyle('A2')->getFont()->setItalic(true);

        $column = 'A';

        foreach ($headers as $header) {
            $summarySheet->setCellValue($column . '3', $header);
            $column++;
        }

        $headerStyle = $summarySheet->getStyle('A3:' . $lastColumn . '3');

        $headerStyle->getFont()->setBold(true);
        $headerStyle->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $headerStyle->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
        $headerStyle->getAlignment()->setWrapText(true);

        $headerStyle->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()
            ->setARGB('D9EAF7');

        $headerStyle->getBorders()->getAllBorders()
            ->setBorderStyle(Border::BORDER_THIN);

        if ($type === 'monthly') {

            $timeCondition = "MONTH(p.tanggal) = '$month' AND YEAR(p.tanggal) = '$year'";

        } elseif ($type === 'period') {

            $timeCondition = "p.tanggal BETWEEN '$startDateEscaped' AND '$endDateEscaped'";

        } else {

            $timeCondition = "1=1";
        }

        $query = "
            SELECT
                u.nama,
                u.divisi,
                u.nup,
                SUM(
                    CASE
                        WHEN p.status IN ('Late', 'On-time') THEN 1
                        ELSE 0
                    END
                ) AS total_present,
                SUM(
                    CASE
                        WHEN p.status = 'Sick' THEN 1
                        ELSE 0
                    END
                ) AS total_sick,
                SUM(
                    CASE
                        WHEN p.status = 'Permission/Leave' THEN 1
                        ELSE 0
                    END
                ) AS total_permission_leave
            FROM users u
            LEFT JOIN time p
                ON p.user_id = u.id
                AND $timeCondition
            WHERE u.status = 'Active'
            GROUP BY
                u.id,
                u.nama,
                u.divisi,
                u.nup
            ORDER BY
                u.nama ASC
        ";

        $result = mysqli_query($koneksi, $query);

        if (!$result) {
            die("Query error: " . mysqli_error($koneksi));
        }

        $rowNumber = 4;

        while ($row = mysqli_fetch_assoc($result)) {

            $summarySheet->setCellValue('A' . $rowNumber, $row['nama'] ?? '');
            $summarySheet->setCellValue('B' . $rowNumber, $row['divisi'] ?? '');
            $summarySheet->setCellValue('C' . $rowNumber, $row['nup'] ?? '');
            $summarySheet->setCellValue('D' . $rowNumber, (int)($row['total_present'] ?? 0));
            $summarySheet->setCellValue('E' . $rowNumber, (int)($row['total_sick'] ?? 0));
            $summarySheet->setCellValue('F' . $rowNumber, (int)($row['total_permission_leave'] ?? 0));

            $rowNumber++;
        }

        $lastRow = $rowNumber - 1;

        if ($lastRow >= 3) {

            $dataStyle = $summarySheet->getStyle('A3:F' . $lastRow);

            $dataStyle->getBorders()->getAllBorders()
                ->setBorderStyle(Border::BORDER_THIN);

            $dataStyle->getAlignment()
                ->setVertical(Alignment::VERTICAL_TOP);

            $summarySheet->getStyle('A4:C' . $lastRow)
                ->getAlignment()
                ->setHorizontal(Alignment::HORIZONTAL_LEFT);

            $summarySheet->getStyle('D4:F' . $lastRow)
                ->getAlignment()
                ->setHorizontal(Alignment::HORIZONTAL_CENTER);

            $summarySheet->setAutoFilter('A3:F' . $lastRow);
        }

        $summarySheet->getColumnDimension('A')->setWidth(30);
        $summarySheet->getColumnDimension('B')->setWidth(20);
        $summarySheet->getColumnDimension('C')->setWidth(15);
        $summarySheet->getColumnDimension('D')->setWidth(20);
        $summarySheet->getColumnDimension('E')->setWidth(15);
        $summarySheet->getColumnDimension('F')->setWidth(22);

        $summarySheet->freezePane('A4');
    }

    //Export Tabs
    $headers = [
        'No.',
        'Date',
        'NUP',
        'Name',
        'Division',
        'Absence Type',
        'Date Range',
        'Evidence',
        'Description',
        'Login Time',
        'Login Geotagging',
        'Before Break',
        'Before Break Geotagging',
        'After Break',
        'After Break Geotagging',
        'Logout Time',
        'Logout Geotagging'
    ];

    $lastColumn = 'Q';

    $sheet->mergeCells('A1:' . $lastColumn . '1');
    $sheet->setCellValue('A1', $title);

    $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
    $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $sheet->getStyle('A1')->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);

    $sheet->getRowDimension(1)->setRowHeight(30);

    $sheet->mergeCells('A2:' . $lastColumn . '2');
    $sheet->setCellValue('A2', $periodText);

    $sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $sheet->getStyle('A2')->getFont()->setItalic(true);

    $column = 'A';

    foreach ($headers as $header) {
        $sheet->setCellValue($column . '3', $header);
        $column++;
    }

    $headerStyle = $sheet->getStyle('A3:' . $lastColumn . '3');

    $headerStyle->getFont()->setBold(true);
    $headerStyle->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $headerStyle->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
    $headerStyle->getAlignment()->setWrapText(true);

    $headerStyle->getFill()
        ->setFillType(Fill::FILL_SOLID)
        ->getStartColor()
        ->setARGB('D9EAF7');

    $headerStyle->getBorders()->getAllBorders()
        ->setBorderStyle(Border::BORDER_THIN);

    $query = "
        SELECT
            p.id,
            p.tanggal,
            p.time_login,
            p.geotagging,
            p.before_break,
            p.geotagging_before_break,
            p.after_break,
            p.geotagging_after_break,
            p.time_logout,
            p.geotagging_logout,
            p.time_off_id,
            t.absence_type,
            t.start_date,
            t.end_date,
            t.evidence,
            t.description,
            u.nup,
            u.nama,
            u.divisi
        FROM time p
        JOIN users u ON p.user_id = u.id
        LEFT JOIN time_off t ON p.time_off_id = t.id
        WHERE u.status = 'Active'
        $where
    ";

    if ($role === 'User') {
        $namaEscaped = mysqli_real_escape_string($koneksi, $nama);
        $query .= " AND u.nama = '$namaEscaped'";
    }

    $query .= " ORDER BY p.tanggal DESC, u.nama ASC";

    $result = mysqli_query($koneksi, $query);

    if (!$result) {
        die("Query error: " . mysqli_error($koneksi));
    }

    $rowNumber = 4;
    $no = 1;

    while ($row = mysqli_fetch_assoc($result)) {

        $isTimeOff = !empty($row['time_off_id']);

        $absenceType = '-';
        $dateRange = '-';
        $evidence = '-';
        $description = '-';

        if ($isTimeOff) {

            $absenceType = !empty($row['absence_type'])
                ? $row['absence_type']
                : '-';

            $startDate = !empty($row['start_date'])
                ? date('d-m-Y', strtotime($row['start_date']))
                : '-';

            $endDate = !empty($row['end_date'])
                ? date('d-m-Y', strtotime($row['end_date']))
                : '-';

            $dateRange = $startDate === $endDate
                ? $startDate
                : $startDate . ' to ' . $endDate;

            $description = !empty($row['description'])
                ? $row['description']
                : '-';

            $evidence = !empty($row['evidence'])
                ? $row['evidence']
                : '-';
        }

        $loginTime = $isTimeOff ? '-' : ($row['time_login'] ?? '-');
        $beforeBreak = $isTimeOff ? '-' : ($row['before_break'] ?? '-');
        $afterBreak = $isTimeOff ? '-' : ($row['after_break'] ?? '-');
        $logoutTime = $isTimeOff ? '-' : ($row['time_logout'] ?? '-');

        $loginGeotagging = $isTimeOff ? '-' : ($row['geotagging'] ?? '-');
        $beforeBreakGeotagging = $isTimeOff ? '-' : ($row['geotagging_before_break'] ?? '-');
        $afterBreakGeotagging = $isTimeOff ? '-' : ($row['geotagging_after_break'] ?? '-');
        $logoutGeotagging = $isTimeOff ? '-' : ($row['geotagging_logout'] ?? '-');

        $sheet->setCellValue('A' . $rowNumber, $no);
        $sheet->setCellValue('B' . $rowNumber, !empty($row['tanggal']) ? date('d-m-Y', strtotime($row['tanggal'])) : '');
        $sheet->setCellValue('C' . $rowNumber, $row['nup'] ?? '');
        $sheet->setCellValue('D' . $rowNumber, $row['nama'] ?? '');
        $sheet->setCellValue('E' . $rowNumber, $row['divisi'] ?? '');
        $sheet->setCellValue('F' . $rowNumber, $absenceType);
        $sheet->setCellValue('G' . $rowNumber, $dateRange);
        $sheet->setCellValue('H' . $rowNumber, $evidence);
        $sheet->setCellValue('I' . $rowNumber, $description);
        $sheet->setCellValue('J' . $rowNumber, $loginTime);
        $sheet->setCellValue('K' . $rowNumber, $loginGeotagging);
        $sheet->setCellValue('L' . $rowNumber, $beforeBreak);
        $sheet->setCellValue('M' . $rowNumber, $beforeBreakGeotagging);
        $sheet->setCellValue('N' . $rowNumber, $afterBreak);
        $sheet->setCellValue('O' . $rowNumber, $afterBreakGeotagging);
        $sheet->setCellValue('P' . $rowNumber, $logoutTime);
        $sheet->setCellValue('Q' . $rowNumber, $logoutGeotagging);

        if ($isTimeOff && !empty($row['evidence'])) {

            $evidenceFile = basename($row['evidence']);
            $evidencePath = __DIR__ . '/img/time_off/' . $evidenceFile;

            if (file_exists($evidencePath)) {

                $extension = strtolower(pathinfo($evidenceFile, PATHINFO_EXTENSION));

                if (in_array($extension, ['jpg', 'jpeg', 'png'])) {

                    try {

                        $drawing = new Drawing();
                        $drawing->setName('Evidence');
                        $drawing->setDescription('Absence Evidence');
                        $drawing->setPath($evidencePath);
                        $drawing->setHeight(80);
                        $drawing->setCoordinates('H' . $rowNumber);
                        $drawing->setOffsetX(5);
                        $drawing->setOffsetY(5);
                        $drawing->setWorksheet($sheet);

                        $sheet->setCellValue('H' . $rowNumber, '');
                        $sheet->getRowDimension($rowNumber)->setRowHeight(70);

                    } catch (Exception $e) {

                        $sheet->setCellValue('H' . $rowNumber, $evidenceFile);
                    }

                } elseif ($extension === 'pdf') {

                    $sheet->setCellValue('H' . $rowNumber, $evidenceFile);

                } else {

                    $sheet->setCellValue('H' . $rowNumber, $evidenceFile);
                }
            }
        }

        $rowNumber++;
        $no++;
    }

    $lastRow = $rowNumber - 1;

    if ($lastRow >= 3) {

        $dataStyle = $sheet->getStyle('A3:' . $lastColumn . $lastRow);

        $dataStyle->getBorders()->getAllBorders()
            ->setBorderStyle(Border::BORDER_THIN);

        $dataStyle->getAlignment()
            ->setVertical(Alignment::VERTICAL_TOP);

        $dataStyle->getAlignment()->setWrapText(true);

        $sheet->getStyle('A4:A' . $lastRow)
            ->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->getStyle('B4:B' . $lastRow)
            ->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->getStyle('C4:C' . $lastRow)
            ->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->getStyle('F4:G' . $lastRow)
            ->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->getStyle('J4:Q' . $lastRow)
            ->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->setAutoFilter('A3:' . $lastColumn . $lastRow);
    }

    $sheet->getColumnDimension('A')->setWidth(7);
    $sheet->getColumnDimension('B')->setWidth(15);
    $sheet->getColumnDimension('C')->setWidth(15);
    $sheet->getColumnDimension('D')->setWidth(25);
    $sheet->getColumnDimension('E')->setWidth(20);
    $sheet->getColumnDimension('F')->setWidth(20);
    $sheet->getColumnDimension('G')->setWidth(25);
    $sheet->getColumnDimension('H')->setWidth(25);
    $sheet->getColumnDimension('I')->setWidth(40);
    $sheet->getColumnDimension('J')->setWidth(18);
    $sheet->getColumnDimension('K')->setWidth(25);
    $sheet->getColumnDimension('L')->setWidth(18);
    $sheet->getColumnDimension('M')->setWidth(25);
    $sheet->getColumnDimension('N')->setWidth(18);
    $sheet->getColumnDimension('O')->setWidth(25);
    $sheet->getColumnDimension('P')->setWidth(18);
    $sheet->getColumnDimension('Q')->setWidth(25);

    $sheet->freezePane('A4');
    $spreadsheet->setActiveSheetIndex(0);

    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: max-age=0');

    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');

    exit;
?>