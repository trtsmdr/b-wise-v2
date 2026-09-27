<?php
session_start();

include("koneksi.php");

date_default_timezone_set('Asia/Jakarta');

$document_root = realpath($_SERVER['DOCUMENT_ROOT']);
$project_root = realpath(__DIR__ . '/../../../..');
$relative_path = str_replace('\\', '/', str_replace($document_root, '', $project_root));

$base_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'];

$index_url = $base_url . rtrim($relative_path, '/') . '/index.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['logout_after_attendance'])) {
    session_unset();
    session_destroy();

    header("Location: " . $index_url);
    exit;
}

    date_default_timezone_set('Asia/Jakarta');

    if (!isset($_SESSION['username']) || !isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
        header("Location: Halaman_login.php");
        exit;
    }

    if ($_SESSION['role'] !== 'User') {
        header("Location: dashboard.php");
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header("Location: pilih_kehadiran.php");
        exit;
    }

    $user_id = (int) $_SESSION['user_id'];
    $tanggal_hari_ini = date('Y-m-d');
    $time_login = date('H:i:s');
    $attendance_type = isset($_POST['attendance_type']) ? $_POST['attendance_type'] : 'masuk';
    $latitude   = isset($_POST['latitude']) ? $_POST['latitude'] : '';
    $longitude  = isset($_POST['longitude']) ? $_POST['longitude'] : '';

    $is_valid_geo = is_numeric($latitude) && is_numeric($longitude)
        && $latitude >= -90 && $latitude <= 90
        && $longitude >= -180 && $longitude <= 180;
    $geotagging = $is_valid_geo ? ($latitude . ',' . $longitude) : '';

    $check_today_query = "SELECT id FROM time WHERE user_id = $user_id AND tanggal = '$tanggal_hari_ini' LIMIT 1";
    $check_today_result = mysqli_query($koneksi, $check_today_query);
    if ($check_today_result && mysqli_num_rows($check_today_result) > 0) {
        header("Location: dashboard.php");
        exit;
    }

    if ($attendance_type === 'masuk') {
        $batas_waktu = strtotime('08:00:00');
        $waktu_login = strtotime($time_login);

        if ($waktu_login > $batas_waktu) {
            $_SESSION['telat'] = true;
            $selisih = $waktu_login - $batas_waktu;
            $menit_telat = floor($selisih / 60);
            $jam_telat = floor($menit_telat / 60);
            $sisa_menit_telat = $menit_telat % 60;
            $detik_telat = $selisih % 60;

            if ($jam_telat > 0) {
                $_SESSION['telat_waktu'] = "$jam_telat hours $sisa_menit_telat minutes $detik_telat seconds late!";
            } elseif ($sisa_menit_telat > 0) {
                $_SESSION['telat_waktu'] = "$sisa_menit_telat minutes $detik_telat seconds late!";
            } else {
                $_SESSION['telat_waktu'] = "$detik_telat seconds late!";
            }
            $status = 'Late';
        } else {
            $_SESSION['telat'] = false;
            $status = 'On-time';
        }

        $insert_time_query = "
            INSERT INTO time (tanggal, user_id, time_login, geotagging, status)
            VALUES ('$tanggal_hari_ini', $user_id, '$time_login', '$geotagging', '$status')
        ";

        if (!mysqli_query($koneksi, $insert_time_query)) {
            $_SESSION['attendance_alert'] = [
                'icon'  => 'error',
                'title' => 'Check In failed!',
                'text'  => 'Please try again.'
            ];

            header("Location: pilih_kehadiran.php");
            exit;
        }

        unset($_SESSION['login_latitude'], $_SESSION['login_longitude']);

        $_SESSION['attendance_alert'] = [
            'icon'  => 'success',
            'title' => 'Check In successful!',
            'text'  => 'Your attendance has been recorded successfully.',
            'redirect' => 'dashboard.php'
        ];

        header("Location: pilih_kehadiran.php");
        exit;
        }

        if ($attendance_type !== 'Sick' && $attendance_type !== 'Permission/Leave') {
            header("Location: pilih_kehadiran.php");
            exit;
        }

        $absence_type = $attendance_type;
        $start_date = isset($_POST['start_date']) ? $_POST['start_date'] : '';
        $end_date = isset($_POST['end_date']) ? $_POST['end_date'] : '';
        $description = isset($_POST['description']) ? trim($_POST['description']) : '';

        if (empty($start_date) || empty($end_date) || strtotime($end_date) < strtotime($start_date)) {
            header("Location: pilih_kehadiran.php?error=invalid_date");
            exit;
        }

        $overlap_query = "
            SELECT id
            FROM time_off
            WHERE user_id = $user_id
            AND start_date <= '$end_date'
            AND end_date >= '$start_date'
            LIMIT 1
        ";
        $overlap_result = mysqli_query($koneksi, $overlap_query);
        if ($overlap_result && mysqli_num_rows($overlap_result) > 0) {
            header("Location: pilih_kehadiran.php?error=overlap");
            exit;
        }

        if (!isset($_FILES['evidence']) || $_FILES['evidence']['error'] !== UPLOAD_ERR_OK) {
            header("Location: pilih_kehadiran.php?error=upload_failed");
            exit;
        }

        $file_tmp = $_FILES['evidence']['tmp_name'];
        $file_name = $_FILES['evidence']['name'];
        $file_size = isset($_FILES['evidence']['size']) ? (int) $_FILES['evidence']['size'] : 0;

        if ($file_size > 3 * 1024 * 1024) {
            header("Location: pilih_kehadiran.php?error=file_too_large");
            exit;
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_file($finfo, $file_tmp);
        finfo_close($finfo);

        $allowed_mimes = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'application/pdf' => 'pdf'
        ];

        if (!array_key_exists($mime_type, $allowed_mimes)) {
            header("Location: pilih_kehadiran.php?error=invalid_file");
            exit;
        }

        $upload_dir = __DIR__ . '/img/time_off/';
        if (!is_dir($upload_dir)) {
            if (!mkdir($upload_dir, 0755, true)) {
                header("Location: pilih_kehadiran.php?error=upload_failed");
                exit;
            }
        }

        $username = isset($_SESSION['username']) ? $_SESSION['username'] : 'user';
        $safe_username = preg_replace('/[^A-Za-z0-9_-]/', '_', $username);
        $safe_absence_type = preg_replace('/[^A-Za-z0-9_-]/', '_', strtolower($absence_type));
        $file_extension = $allowed_mimes[$mime_type];

        $stored_filename = $safe_username . '_' . $safe_absence_type . '_' . date('dmYHis') . '.' . $file_extension;
        $target_path = $upload_dir . $stored_filename;

        if (!move_uploaded_file($file_tmp, $target_path)) {
            header("Location: pilih_kehadiran.php?error=upload_failed");
            exit;
        }

        $evidence_for_db = $stored_filename;
        $description_sql = "NULL";
        if ($description !== '') {
            $description_escaped = mysqli_real_escape_string($koneksi, $description);
            $description_sql = "'$description_escaped'";
        }

        $insert_time_off_query = "
            INSERT INTO time_off (user_id, absence_type, start_date, end_date, evidence, description)
            VALUES ($user_id, '$absence_type', '$start_date', '$end_date', '$evidence_for_db', $description_sql)
        ";

    if (!mysqli_query($koneksi, $insert_time_off_query)) {
        $_SESSION['attendance_alert'] = [
            'icon' => 'error',
            'title' => 'Submission failed!',
            'text' => 'Unable to submit your attendance. Please try again.'
        ];

        header("Location: pilih_kehadiran.php");
        exit;
    }

    $time_off_id = mysqli_insert_id($koneksi);

    $period_start = new DateTime($start_date);
    $period_end = new DateTime($end_date);
    $period_end->modify('+1 day');

    $period = new DatePeriod($period_start, new DateInterval('P1D'), $period_end);
    foreach ($period as $date_item) {
        $date_value = $date_item->format('Y-m-d');

        $check_time_query = "SELECT id FROM time WHERE user_id = $user_id AND tanggal = '$date_value' LIMIT 1";
        $check_time_result = mysqli_query($koneksi, $check_time_query);

        if ($check_time_result && mysqli_num_rows($check_time_result) > 0) {
    $absence_status = $absence_type === 'Sick' ? 'Sick' : 'Permission/Leave';

    $update_time_query = "
        UPDATE time
        SET
            time_off_id = $time_off_id,
            time_login = NULL,
            geotagging = NULL,
            status = '$absence_status',
            before_break = NULL,
            geotagging_before_break = NULL,
            after_break = NULL,
            geotagging_after_break = NULL,
            time_logout = NULL,
            geotagging_logout = NULL,
            is_break = 0
        WHERE user_id = $user_id
        AND tanggal = '$date_value'
    ";
            mysqli_query($koneksi, $update_time_query);
        } else {
    $absence_status = $absence_type === 'Sick' ? 'Sick' : 'Permission/Leave';

    $insert_time_query = "
        INSERT INTO time (
            tanggal,
            user_id,
            time_login,
            geotagging,
            status,
            before_break,
            geotagging_before_break,
            after_break,
            geotagging_after_break,
            time_logout,
            geotagging_logout,
            is_break,
            time_off_id
        )
        VALUES (
            '$date_value',
            $user_id,
            NULL,
            NULL,
            '$absence_status',
            NULL,
            NULL,
            NULL,
            NULL,
            NULL,
            NULL,
            0,
            $time_off_id
        )
    ";
            mysqli_query($koneksi, $insert_time_query);
        }
    }

    unset($_SESSION['login_latitude'], $_SESSION['login_longitude']);

    $_SESSION['attendance_alert'] = [
        'icon' => 'success',
        'title' => $absence_type === 'Sick'
            ? 'Sick submission successful!'
            : 'Permission/Leave submission successful!',
        'text' => 'Your submission has been recorded successfully.',
        'redirect' => $index_url,
        'logout_after_alert' => true
    ];

    header("Location: pilih_kehadiran.php");
    exit;
?>
