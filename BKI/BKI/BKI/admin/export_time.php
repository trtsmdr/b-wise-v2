<?php
session_start();

if (!isset($_SESSION['username'])) {
    header('Location: Halaman_login.php');
    exit;
}

date_default_timezone_set('Asia/Jakarta');

require __DIR__ . '/koneksi.php';
require __DIR__ . '/../../../vendor/autoload.php';
require __DIR__ . '/api_holidays.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

$role = $_SESSION['role'];
$nama = $_SESSION['nama'];
$type = $_GET['type'] ?? '';
$divisions = ['Inspector', 'Admin', 'HSE', 'Finance', 'Marketing', 'Information Technology'];

if (!in_array($type, ['monthly', 'period', 'all'], true)) {
    die('Invalid export type.');
}

function sql_escape(mysqli $connection, string $value): string
{
    return mysqli_real_escape_string($connection, $value);
}

function add_user_filter(string $query, string $role, string $nama, mysqli $connection): string
{
    if ($role === 'User') {
        $query .= " AND u.nama = '" . sql_escape($connection, $nama) . "'";
    }

    return $query;
}

function style_title(Worksheet $sheet, string $title, string $period, string $lastColumn): void
{
    $sheet->mergeCells('A1:' . $lastColumn . '1');
    $sheet->setCellValue('A1', $title);
    $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
    $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $sheet->getStyle('A1')->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
    $sheet->getRowDimension(1)->setRowHeight(30);

    $sheet->mergeCells('A2:' . $lastColumn . '2');
    $sheet->setCellValue('A2', $period);
    $sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $sheet->getStyle('A2')->getFont()->setItalic(true);
}

function style_headers(Worksheet $sheet, array $headers, string $lastColumn): void
{
    foreach ($headers as $index => $header) {
        $column = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($index + 1);
        $sheet->setCellValue($column . '3', $header);
    }

    $style = $sheet->getStyle('A3:' . $lastColumn . '3');
    $style->getFont()->setBold(true);
    $style->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $style->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
    $style->getAlignment()->setWrapText(true);
    $style->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('D9EAF7');
    $style->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
}

function add_division_header(Worksheet $sheet, int &$row, string $division, string $lastColumn): void
{
    $sheet->mergeCells('A' . $row . ':' . $lastColumn . $row);
    $sheet->setCellValue('A' . $row, $division);

    $colors = [
        'Inspector' => 'D9EAF7',
        'Admin' => 'E2F0D9',
        'HSE' => 'FFF2CC',
        'Finance' => 'E4DFEC',
        'Marketing' => 'FCE4D6',
        'Information Technology' => 'DDEBF7',
    ];

    $color = $colors[$division] ?? 'D9EAF7';

    $style = $sheet->getStyle('A' . $row . ':' . $lastColumn . $row);
    $style->getFont()->setBold(true);
    $style->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB($color);
    $style->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
    $style->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

    $row++;
}

function finish_table(Worksheet $sheet, int $lastRow, string $lastColumn): void
{
    if ($lastRow < 4) {
        return;
    }

    $style = $sheet->getStyle('A4:' . $lastColumn . $lastRow);
    $style->getAlignment()->setVertical(Alignment::VERTICAL_TOP)->setWrapText(true);
    $style->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
    $sheet->setAutoFilter('A3:' . $lastColumn . $lastRow);
}

function set_widths(Worksheet $sheet, array $widths): void
{
    foreach ($widths as $column => $width) {
        $sheet->getColumnDimension($column)->setWidth($width);
    }
}

function get_period_dates(int $year, int $month): array
{
    $start = new DateTime(
        sprintf('%04d-%02d-15', $year, $month),
        new DateTimeZone('Asia/Jakarta')
    );

    $end = clone $start;
    $end->modify('+1 month');

    return [
        'start' => $start->format('Y-m-d'),
        'end' => $end->format('Y-m-d'),
    ];
}

function get_summary_time_condition(string $startDate, string $endDate): string
{
    $condition = "p.tanggal >= '$startDate' AND p.tanggal <= '$endDate'";
    $nonWorkingDates = get_non_working_dates($startDate, $endDate);

    if ($nonWorkingDates) {
        $dates = array_map(static fn(string $date): string => "'" . $date . "'", $nonWorkingDates);
        $condition .= " AND DATE(p.tanggal) NOT IN (" . implode(',', $dates) . ")";
    }

    return $condition;
}

function fetch_time_rows(mysqli $connection, string $where, string $role, string $nama): array
{
    $query = "
        SELECT p.tanggal, p.time_login, p.geotagging, p.before_break,
            p.geotagging_before_break, p.after_break, p.geotagging_after_break,
            p.time_logout, p.geotagging_logout, p.time_off_id,
            t.absence_type, t.start_date, t.end_date, t.evidence, t.description,
            u.nup, u.nama, u.divisi
        FROM time p
        JOIN users u ON p.user_id = u.id
        LEFT JOIN time_off t ON p.time_off_id = t.id
        WHERE u.status = 'Active' $where";

    $query = add_user_filter($query, $role, $nama, $connection);

    $query .= "
        ORDER BY
            FIELD(
                u.divisi,
                'Inspector',
                'Admin',
                'HSE',
                'Finance',
                'Marketing',
                'Information Technology'
            ),
            p.tanggal DESC,
            u.nama ASC
    ";

    $result = mysqli_query($connection, $query);

    if (!$result) {
        die('Query error: ' . mysqli_error($connection));
    }

    return mysqli_fetch_all($result, MYSQLI_ASSOC);
}

function fetch_summary_rows(mysqli $connection, string $timeCondition, string $role, string $nama, int $fixedMonths): array
{
    $query = "
        SELECT u.id, u.nama, u.divisi, u.nup,
            COALESCE(u.basic_salary, 0) AS basic_salary,
            COALESCE(u.meal_allowance, 0) AS meal_allowance,
            COALESCE(u.transport_allowance, 0) AS transport_allowance,
            COALESCE(u.welfare_allowance, 0) AS welfare_allowance,
            COALESCE(u.other_allowance, 0) AS other_allowance,
            COALESCE(u.extra_fooding, 0) AS extra_fooding,
            COUNT(DISTINCT CASE WHEN p.tanggal IS NOT NULL THEN DATE_FORMAT(p.tanggal, '%Y-%m') END) AS month_count,
            SUM(CASE WHEN p.status IN ('Late', 'On-time') THEN 1 ELSE 0 END) AS total_present,
            SUM(CASE WHEN p.status = 'Sick' THEN 1 ELSE 0 END) AS total_sick,
            SUM(CASE WHEN p.status = 'Permission' THEN 1 ELSE 0 END) AS total_permission,
            SUM(CASE WHEN p.status = 'Leave' THEN 1 ELSE 0 END) AS total_leave,
            SUM(CASE WHEN DAYOFWEEK(p.tanggal) = 6 AND p.status IN ('Late', 'On-time') THEN 1 ELSE 0 END) AS friday_present
        FROM users u
        LEFT JOIN time p ON p.user_id = u.id AND $timeCondition
        WHERE u.status = 'Active'";
    $query = add_user_filter($query, $role, $nama, $connection);
    $query .= " GROUP BY u.id, u.nama, u.divisi, u.nup, u.basic_salary, u.meal_allowance,
        u.transport_allowance, u.welfare_allowance, u.other_allowance, u.extra_fooding
        ORDER BY FIELD(u.divisi, 'Inspector', 'Admin', 'HSE', 'Finance', 'Marketing', 'Information Technology'), u.nama ASC";

    $result = mysqli_query($connection, $query);
    if (!$result) {
        die('Query error: ' . mysqli_error($connection));
    }

    $rows = mysqli_fetch_all($result, MYSQLI_ASSOC);
    foreach ($rows as &$row) {
        $row['month_count'] = $fixedMonths > 0 ? $fixedMonths : (int)$row['month_count'];
    }

    return $rows;
}

function summary_values(array $row, bool $includeSalary): array
{
    $present = (int)$row['total_present'];
    $sick = (int)$row['total_sick'];
    $permission = (int)$row['total_permission'];
    $leave = (int)$row['total_leave'];
    if (!$includeSalary) {
        return [$present, $sick, $permission, $leave];
    }

    $months = (int)$row['month_count'];
    $basic = (float)$row['basic_salary'] * $months;
    $welfare = (float)$row['welfare_allowance'] * $months;
    $other = (float)$row['other_allowance'] * $months;
    $mealTransport = ((float)$row['meal_allowance'] + (float)$row['transport_allowance']) * ($present + $sick + $leave);
    $extraFooding = (float)$row['extra_fooding'] * (int)$row['friday_present'];
    $deduction = $permission * ((float)$row['meal_allowance'] + (float)$row['transport_allowance']);
    $total = $basic + $welfare + $other + $mealTransport + $extraFooding;

    return [$present, $sick, $permission, $leave, $basic, $welfare, $other, $mealTransport, $extraFooding, $deduction, $total];
}

function write_summary(Worksheet $sheet, string $title, string $period, array $rows, array $divisions, bool $includeSalary): void
{
    $headers = $includeSalary
        ? ['Name', 'Division', 'NUP', 'Total Present', 'Sick', 'Permission', 'Leave', 'Basic Salary', 'Welfare Allowance', 'Other Allowance', 'Meal & Transport Allowance', 'Extra Fooding', 'Permission Deduction', 'Total Salary']
        : ['Name', 'Division', 'NUP', 'Total Present', 'Sick', 'Permission', 'Leave'];

    $lastColumn = $includeSalary ? 'N' : 'G';

    style_title($sheet, $title, $period, $lastColumn);
    style_headers($sheet, $headers, $lastColumn);

    $rowNumber = 4;

    foreach ($divisions as $division) {
        $divisionRows = array_filter($rows, static fn(array $row): bool => $row['divisi'] === $division);

        if (!$divisionRows) {
            continue;
        }

        add_division_header($sheet, $rowNumber, $division, $lastColumn);

        foreach ($divisionRows as $row) {
            $values = summary_values($row, $includeSalary);
            $sheet->fromArray([$row['nama'], $row['divisi'], $row['nup'], ...$values], null, 'A' . $rowNumber);
            $rowNumber++;
        }
    }

    $lastRow = $rowNumber - 1;

    finish_table($sheet, $lastRow, $lastColumn);

    $sheet->getStyle('D4:G' . max(4, $lastRow))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

    if ($includeSalary) {
        $sheet->getStyle('H4:N' . max(4, $lastRow))->getNumberFormat()->setFormatCode('"Rp" #,##0');
        $sheet->getStyle('H4:N' . max(4, $lastRow))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
    }

    $sheet->freezePane('A4');

    set_widths($sheet, $includeSalary
        ? ['A' => 30, 'B' => 24, 'C' => 15, 'D' => 16, 'E' => 12, 'F' => 15, 'G' => 12, 'H' => 18, 'I' => 20, 'J' => 18, 'K' => 25, 'L' => 18, 'M' => 22, 'N' => 18]
        : ['A' => 30, 'B' => 24, 'C' => 15, 'D' => 16, 'E' => 12, 'F' => 15, 'G' => 12]);
}

function write_time_sheet(Worksheet $sheet, string $title, string $period, array $rows, array $divisions): void 
{
    $headers = ['No.', 'Date', 'NUP', 'Name', 'Division', 'Absence Type', 'Date Range', 'Evidence', 'Description', 'Login Time', 'Login Geotagging', 'Before Break', 'Before Break Geotagging', 'After Break', 'After Break Geotagging', 'Logout Time', 'Logout Geotagging'];

    style_title($sheet, $title, $period, 'Q');
    style_headers($sheet, $headers, 'Q');

    $rowNumber = 4;
    $number = 1;

    foreach ($divisions as $division) {
        $divisionRows = array_filter(
            $rows,
            static fn(array $row): bool => $row['divisi'] === $division
        );

        if (!$divisionRows) {
            continue;
        }

        add_division_header($sheet, $rowNumber, $division, 'Q');

        foreach ($divisionRows as $row) {
            $timeOff = !empty($row['time_off_id']);

            $date = date('Y-m-d', strtotime($row['tanggal']));
            $formattedDate = format_indonesian_date($date);
            $holidayInfo = get_holiday_info($date);

            if ($holidayInfo) {
                $holidayLabel = $holidayInfo['is_cuti_bersama'] ? 'CUTI BERSAMA' : 'LIBUR';
                $formattedDate .= ' - ' . $holidayLabel . ': ' . $holidayInfo['name'];
            }

            $start = $row['start_date'] ? date('d-m-Y', strtotime($row['start_date'])) : '-';
            $end = $row['end_date'] ? date('d-m-Y', strtotime($row['end_date'])) : '-';
            $dateRange = $start === $end ? $start : $start . ' to ' . $end;

            $values = [$number++, $formattedDate, $row['nup'], $row['nama'], $row['divisi'],
                $timeOff ? ($row['absence_type'] ?: '-') : '-',
                $timeOff ? $dateRange : '-',
                $timeOff ? ($row['evidence'] ?: '-') : '-',
                $timeOff ? ($row['description'] ?: '-') : '-',
                $timeOff ? '-' : ($row['time_login'] ?: '-'),
                $timeOff ? '-' : ($row['geotagging'] ?: '-'),
                $timeOff ? '-' : ($row['before_break'] ?: '-'),
                $timeOff ? '-' : ($row['geotagging_before_break'] ?: '-'),
                $timeOff ? '-' : ($row['after_break'] ?: '-'),
                $timeOff ? '-' : ($row['geotagging_after_break'] ?: '-'),
                $timeOff ? '-' : ($row['time_logout'] ?: '-'),
                $timeOff ? '-' : ($row['geotagging_logout'] ?: '-')
            ];

            $sheet->fromArray($values, null, 'A' . $rowNumber);
            if ($holidayInfo) {
                $sheet->getStyle('B' . $rowNumber)->getFont()->setBold(true);
            }

            if ($timeOff && !empty($row['evidence'])) {
                $evidenceFile = basename($row['evidence']);
                $evidencePath = __DIR__ . '/img/time_off/' . $evidenceFile;
                $extension = strtolower(pathinfo($evidenceFile, PATHINFO_EXTENSION));
                if (
                    is_file($evidencePath) &&
                    in_array($extension, ['jpg', 'jpeg', 'png'], true)
                ) {
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
                }
            }

            $rowNumber++;
        }
    }

    finish_table($sheet, $rowNumber - 1, 'Q');
    $sheet->getStyle('A4:A' . max(4, $rowNumber - 1))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $sheet->getStyle('B4:C' . max(4, $rowNumber - 1))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $sheet->getStyle('F4:G' . max(4, $rowNumber - 1))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $sheet->getStyle('J4:Q' . max(4, $rowNumber - 1))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $sheet->freezePane('A4');
    set_widths($sheet, ['A' => 7, 'B' => 38, 'C' => 15, 'D' => 25, 'E' => 24, 'F' => 20, 'G' => 25, 'H' => 25, 'I' => 40, 'J' => 18, 'K' => 25, 'L' => 18, 'M' => 25, 'N' => 18, 'O' => 25, 'P' => 18, 'Q' => 25]);
}

$year = max(2000, min(2100, (int)($_GET['year'] ?? date('Y'))));
$periodText = '';
$title = '';
$fixedMonths = 0;
$monthSheets = [];

if ($type === 'monthly') {
    $month = max(1, min(12, (int)($_GET['month'] ?? date('m'))));
    $period = get_period_dates($year, $month);
    $monthName = date('F', mktime(0, 0, 0, $month, 1));
    $where = "AND p.tanggal >= '{$period['start']}' AND p.tanggal <= '{$period['end']}'";
    $periodText = date('d-m-Y', strtotime($period['start'])) . ' to ' . date('d-m-Y', strtotime($period['end']));
    $title = 'Employee Time Export - ' . $monthName . ' ' . $year;
    $monthSheets = [[$month, $monthName, $where, $periodText, $period['start'], $period['end']]];
    $fixedMonths = 1;
    $filename = sprintf('time_%02d_%d_%s.xlsx', $month, $year, date('Y-m-d_H-i-s'));
} elseif ($type === 'period') {
    $startDate = $_GET['start_date'] ?? '';
    $endDate = $_GET['end_date'] ?? '';

    if (!$startDate || !$endDate || $startDate > $endDate) {
        die('A valid start date and end date are required.');
    }

    $startDate = sql_escape($koneksi, $startDate);
    $endDate = sql_escape($koneksi, $endDate);
    $where = "AND p.tanggal >= '$startDate' AND p.tanggal <= '$endDate'";
    $periodText = date('d-m-Y', strtotime($startDate)) . ' to ' . date('d-m-Y', strtotime($endDate));
    $title = 'Employee Time Export - Period';
    $monthSheets = [[0, 'Time Export', $where, $periodText, $startDate, $endDate]];
    $fixedMonths = 1;
    $filename = 'time_' . $startDate . '_to_' . $endDate . '_' . date('Y-m-d_H-i-s') . '.xlsx';
} else {
    $title = 'Employee Time Export - ' . $year;
    $periodText = 'Payroll Period 15-' . $year . ' to 15-' . ($year + 1);
    $monthSheets = [];

    for ($month = 1; $month <= 12; $month++) {
        $period = get_period_dates($year, $month);
        $monthName = date('F', mktime(0, 0, 0, $month, 1));
        $where = "AND p.tanggal >= '{$period['start']}' AND p.tanggal <= '{$period['end']}'";
        $monthSheets[] = [$month, $monthName, $where, $monthName . ' ' . $year . ' (' . date('d-m-Y', strtotime($period['start'])) . ' to ' . date('d-m-Y', strtotime($period['end'])) . ')', $period['start'], $period['end']];
    }

    $fixedMonths = 1;
    $filename = 'time_all_' . $year . '_' . date('Y-m-d_H-i-s') . '.xlsx';
}

$spreadsheet = new Spreadsheet();
$firstSheet = $spreadsheet->getActiveSheet();

if ($type === 'all') {
    $firstSheet->setTitle('Export Time');

    $firstPeriod = $monthSheets[0][4];
    $lastPeriod = $monthSheets[count($monthSheets) - 1][5];
    $allTimeWhere = "AND p.tanggal >= '$firstPeriod' AND p.tanggal <= '$lastPeriod'";

    $allTimeRows = fetch_time_rows($koneksi, $allTimeWhere, $role, $nama);

    write_time_sheet($firstSheet, 'Employee Time Export - ' . $year, date('d-m-Y', strtotime($firstPeriod)) . ' to ' . date('d-m-Y', strtotime($lastPeriod)), $allTimeRows, $divisions);

    $allSummaryRows = [];

    foreach ($monthSheets as $monthSheet) {
        $timeRows = fetch_time_rows($koneksi, $monthSheet[2], $role, $nama);

        if (!$timeRows) {
            continue;
        }

        $timeCondition = get_summary_time_condition($monthSheet[4], $monthSheet[5]);
        $summaryRows = fetch_summary_rows($koneksi, $timeCondition, $role, $nama, 1);

        if (!$summaryRows) {
            continue;
        }

        $monthSummarySheet = $spreadsheet->createSheet();
        $monthSummarySheet->setTitle(substr($monthSheet[1], 0, 25));

        write_summary($monthSummarySheet, 'Attendance & Salary - ' . $monthSheet[1], $monthSheet[3], $summaryRows, $divisions, $role === 'Super-Admin');

        foreach ($summaryRows as $row) {
            $key = $row['id'];

            if (!isset($allSummaryRows[$key])) {
                $allSummaryRows[$key] = $row;
                $allSummaryRows[$key]['total_present'] = 0;
                $allSummaryRows[$key]['total_sick'] = 0;
                $allSummaryRows[$key]['total_permission'] = 0;
                $allSummaryRows[$key]['total_leave'] = 0;
                $allSummaryRows[$key]['friday_present'] = 0;
                $allSummaryRows[$key]['month_count'] = 0;
            }

            $allSummaryRows[$key]['total_present'] += (int)$row['total_present'];
            $allSummaryRows[$key]['total_sick'] += (int)$row['total_sick'];
            $allSummaryRows[$key]['total_permission'] += (int)$row['total_permission'];
            $allSummaryRows[$key]['total_leave'] += (int)$row['total_leave'];
            $allSummaryRows[$key]['friday_present'] += (int)$row['friday_present'];
            $allSummaryRows[$key]['month_count']++;
        }
    }

    $summarySheet = $spreadsheet->createSheet();
    $summarySheet->setTitle('Summary');

    write_summary($summarySheet, 'Employee Attendance & Salary Summary - ' . $year, $periodText, array_values($allSummaryRows), $divisions, $role === 'Super-Admin');
} else {
    $firstSheet->setTitle('Time Export');

    if ($role === 'Super-Admin') {
        $summarySheet = $spreadsheet->createSheet();
        $summarySheet->setTitle('Summary');

        $timeCondition = get_summary_time_condition($monthSheets[0][4], $monthSheets[0][5]);
        $summaryRows = fetch_summary_rows($koneksi, $timeCondition, $role, $nama, 1);

        write_summary($summarySheet, $title, $periodText, $summaryRows, $divisions, true);
    }

    write_time_sheet($firstSheet, $title, $periodText, fetch_time_rows($koneksi, $monthSheets[0][2], $role, $nama), $divisions);
}

$spreadsheet->setActiveSheetIndex(0);
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: max-age=0');
(new Xlsx($spreadsheet))->save('php://output');
exit;