<?php
    session_start();

    if (!isset($_SESSION['username'])) {
        header("Location: Halaman_login.php");
        exit;
    }

    require __DIR__ . '/koneksi.php';
    require __DIR__ . '/../../../vendor/autoload.php';

    use PhpOffice\PhpSpreadsheet\Spreadsheet;
    use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
    use PhpOffice\PhpSpreadsheet\Style\Alignment;
    use PhpOffice\PhpSpreadsheet\Style\Border;
    use PhpOffice\PhpSpreadsheet\Style\Fill;
    use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;


    if ($_SERVER["REQUEST_METHOD"] !== "POST") {
        die("Invalid Request.");
    }


    if (
        !isset($_POST['export_date']) ||
        empty($_POST['export_date'])
    ) {
        die("No date has been selected.");
    }

    $export_date = $_POST['export_date'];


    $date = DateTime::createFromFormat(
        'Y-m-d',
        $export_date
    );

    if (
        !$date ||
        $date->format('Y-m-d') !== $export_date
    ) {
        die("Invalid date format.");
    }


    $spreadsheet = new Spreadsheet();

    $sheet = $spreadsheet->getActiveSheet();

    $sheet->setTitle(
        'Planning Export'
    );


    $sheet->mergeCells('A1:J1');

    $sheet->setCellValue(
        'A1',
        'Planning Export (' .
        date(
            'd-m-Y',
            strtotime($export_date)
        ) .
        ')'
    );

    $sheet->getStyle('A1')
        ->getFont()
        ->setBold(true)
        ->setSize(16);

    $sheet->getStyle('A1')
        ->getAlignment()
        ->setHorizontal(
            Alignment::HORIZONTAL_CENTER
        )
        ->setVertical(
            Alignment::VERTICAL_CENTER
        );

    $sheet->getRowDimension(1)
        ->setRowHeight(30);


    $headers = [
        'No.',
        'Date',
        'NUP',
        'Name',
        'Division',
        'Description',
        'Upload Time',
        'Image',
        'Status',
        'History'
    ];

    $column = 'A';

    foreach ($headers as $header) {

        $sheet->setCellValue(
            $column . '3',
            $header
        );

        $column++;
    }


    $headerStyle =
        $sheet->getStyle('A3:J3');

    $headerStyle
        ->getFont()
        ->setBold(true);

    $headerStyle
        ->getAlignment()
        ->setHorizontal(
            Alignment::HORIZONTAL_CENTER
        )
        ->setVertical(
            Alignment::VERTICAL_CENTER
        );

    $headerStyle
        ->getFill()
        ->setFillType(
            Fill::FILL_SOLID
        )
        ->getStartColor()
        ->setARGB('D9EAF7');

    $headerStyle
        ->getBorders()
        ->getAllBorders()
        ->setBorderStyle(
            Border::BORDER_THIN
        );

    $sheet->getRowDimension(3)
        ->setRowHeight(25);


    $username = $_SESSION['username'];
    $role     = $_SESSION['role'];

    $username_safe = mysqli_real_escape_string(
        $koneksi,
        $username
    );

    $query = "
        SELECT 
            p.id,
            p.tanggal,
            p.deskripsi,
            p.time_upload_activity_planning,
            p.status,
            p.gambar,
            p.history_update,
            u.nup,
            u.nama,
            u.divisi

        FROM planning p

        JOIN users u
            ON p.user_id = u.id

        WHERE p.tanggal = '$export_date'
        AND u.status = 'Active'
    ";

    // Selain Super-Admin hanya boleh export data sendiri
    if ($role !== 'Super-Admin') {
        $query .= "
            AND u.username = '$username_safe'
        ";
    }

    $query .= "
        ORDER BY
            p.status DESC,
            p.time_upload_activity_planning ASC
    ";

    $result = mysqli_query(
        $koneksi,
        $query
    );

    if (!$result) {
        die(
            "Query error: " .
            mysqli_error($koneksi)
        );
    }


    $result = mysqli_query(
        $koneksi,
        $query
    );


    if (!$result) {

        die(
            "Query error: " .
            mysqli_error($koneksi)
        );
    }


    $rowNumber = 4;

    $no = 1;


    while (
        $row = mysqli_fetch_assoc($result)
    ) {


        $status = !empty($row['gambar'])
            ? 'Completed'
            : 'On-progress';


        $sheet->setCellValue(
            'A' . $rowNumber,
            $no
        );


        $sheet->setCellValue(
            'B' . $rowNumber,
            !empty($row['tanggal'])
                ? date(
                    'd-m-Y',
                    strtotime($row['tanggal'])
                )
                : ''
        );


        $sheet->setCellValue(
            'C' . $rowNumber,
            $row['nup']
        );


        $sheet->setCellValue(
            'D' . $rowNumber,
            $row['nama']
        );


        $sheet->setCellValue(
            'E' . $rowNumber,
            $row['divisi']
        );


        $sheet->setCellValue(
            'F' . $rowNumber,
            $row['deskripsi']
        );


        $sheet->setCellValue(
            'G' . $rowNumber,
            $row['time_upload_activity_planning']
        );


        $sheet->setCellValue(
            'I' . $rowNumber,
            $status
        );


        $sheet->setCellValue(
            'J' . $rowNumber,
            $row['history_update']
        );


        if (!empty($row['gambar'])) {


            $gambarPaths = explode(
                ',',
                $row['gambar']
            );


            $imageIndex = 0;


            foreach (
                $gambarPaths as $path
            ) {


                $path = trim($path);


                if ($path === '') {
                    continue;
                }


                $imagePath =
                    __DIR__ .
                    '/img/' .
                    $path;


                if (!file_exists($imagePath)) {
                    continue;
                }


                $imageInfo =
                    getimagesize(
                        $imagePath
                    );


                if (
                    $imageInfo === false ||
                    empty($imageInfo[0]) ||
                    empty($imageInfo[1])
                ) {
                    continue;
                }


                $originalWidth =
                    $imageInfo[0];

                $originalHeight =
                    $imageInfo[1];


                $imageHeight = 100;


                $imageWidth =
                    (
                        $originalWidth /
                        $originalHeight
                    ) *
                    $imageHeight;


                $drawing =
                    new Drawing();


                $drawing->setName(
                    'Evidence_' .
                    $no .
                    '_' .
                    $imageIndex
                );


                $drawing->setDescription(
                    'Planning Evidence'
                );


                $drawing->setPath(
                    $imagePath
                );


                $drawing->setCoordinates(
                    'H' . $rowNumber
                );


                $drawing->setHeight(
                    $imageHeight
                );


                $columnWidthPixels = 210;


                $offsetX =
                    (
                        $columnWidthPixels -
                        $imageWidth
                    ) / 2;


                if ($offsetX < 8) {

                    $offsetX = 8;
                }


                $drawing->setOffsetX(
                    $offsetX
                );


                $imageGap = 10;


                $drawing->setOffsetY(
                    8 +
                    (
                        $imageIndex *
                        (
                            $imageHeight +
                            $imageGap
                        )
                    )
                );


                $drawing->setWorksheet(
                    $sheet
                );


                $imageIndex++;
            }


            if ($imageIndex > 0) {


                $totalHeightPixels =
                    8 +
                    (
                        $imageIndex *
                        $imageHeight
                    ) +
                    (
                        (
                            $imageIndex - 1
                        ) *
                        $imageGap
                    ) +
                    5;


                $rowHeight =
                    $totalHeightPixels *
                    0.75;


                if ($rowHeight < 86.5) {

                    $rowHeight = 86.5;
                }


                $sheet
                    ->getRowDimension(
                        $rowNumber
                    )
                    ->setRowHeight(
                        $rowHeight
                    );
            }


        } else {


            $sheet->setCellValue(
                'H' . $rowNumber,
                'No Image'
            );


            $sheet
                ->getStyle(
                    'H' . $rowNumber
                )
                ->getAlignment()
                ->setHorizontal(
                    Alignment::HORIZONTAL_CENTER
                )
                ->setVertical(
                    Alignment::VERTICAL_CENTER
                );


            $sheet
                ->getRowDimension(
                    $rowNumber
                )
                ->setRowHeight(25);
        }


        $rowNumber++;

        $no++;
    }


    $lastRow =
        $rowNumber - 1;


    if ($lastRow >= 3) {


        $dataStyle =
            $sheet->getStyle(
                'A3:J' . $lastRow
            );


        $dataStyle
            ->getBorders()
            ->getAllBorders()
            ->setBorderStyle(
                Border::BORDER_THIN
            );


        $dataStyle
            ->getAlignment()
            ->setVertical(
                Alignment::VERTICAL_TOP
            );
    }


    foreach (
        [
            'A',
            'B',
            'C',
            'G',
            'H',
            'I'
        ]
        as $col
    ) {


        if ($lastRow >= 4) {


            $sheet
                ->getStyle(
                    $col .
                    '4:' .
                    $col .
                    $lastRow
                )
                ->getAlignment()
                ->setHorizontal(
                    Alignment::HORIZONTAL_CENTER
                );
        }
    }


    if ($lastRow >= 4) {


        $sheet
            ->getStyle(
                'H4:H' .
                $lastRow
            )
            ->getAlignment()
            ->setHorizontal(
                Alignment::HORIZONTAL_CENTER
            )
            ->setVertical(
                Alignment::VERTICAL_CENTER
            );
    }


    foreach (
        [
            'F',
            'J'
        ]
        as $col
    ) {


        if ($lastRow >= 4) {


            $sheet
                ->getStyle(
                    $col .
                    '4:' .
                    $col .
                    $lastRow
                )
                ->getAlignment()
                ->setWrapText(true);
        }
    }


    $sheet
        ->getColumnDimension('A')
        ->setWidth(5);


    $sheet
        ->getColumnDimension('B')
        ->setWidth(12);


    $sheet
        ->getColumnDimension('C')
        ->setWidth(12);


    $sheet
        ->getColumnDimension('D')
        ->setWidth(25);


    $sheet
        ->getColumnDimension('E')
        ->setWidth(15);


    $sheet
        ->getColumnDimension('F')
        ->setWidth(40);


    $sheet
        ->getColumnDimension('G')
        ->setWidth(15);


    $sheet
        ->getColumnDimension('H')
        ->setWidth(30);


    $sheet
        ->getColumnDimension('I')
        ->setWidth(15);


    $sheet
        ->getColumnDimension('J')
        ->setWidth(12);


    $sheet->freezePane(
        'A4'
    );


    if ($lastRow >= 3) {

        $sheet->setAutoFilter(
            'A3:J' . $lastRow
        );
    }


    // =====================================================
    // DOWNLOAD XLSX
    // =====================================================

    $filename =
        'planning_' .
        $export_date .
        '.xlsx';


    header(
        'Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
    );


    header(
        'Content-Disposition: attachment; filename="' .
        $filename .
        '"'
    );


    header(
        'Cache-Control: max-age=0'
    );


    $writer =
        new Xlsx(
            $spreadsheet
        );


    $writer->save(
        'php://output'
    );


    exit;
?>