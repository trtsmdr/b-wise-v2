<?php
session_start();
include("koneksi.php");

date_default_timezone_set('Asia/Jakarta');

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: 0");

$latitude = isset($_GET['latitude']) ? $_GET['latitude'] : null;
$longitude = isset($_GET['longitude']) ? $_GET['longitude'] : null;

$is_valid_location = is_numeric($latitude)
    && is_numeric($longitude)
    && $latitude >= -90
    && $latitude <= 90
    && $longitude >= -180
    && $longitude <= 180;

if (!$is_valid_location) {
    echo "<!DOCTYPE html>
            <html lang='en'>
            <head>
                <meta charset='UTF-8'>
                <meta name='viewport' content='width=device-width, initial-scale=1.0'>
                <title>Location Required</title>
                <link href='img/logo.png' rel='icon'>
                <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
            </head>
            <body>
                <script>
                    Swal.fire({
                        icon: 'warning',
                        title: 'Location Required',
                        text: 'Please enable your location permission before logging out.',
                        confirmButtonText: 'OK',
                        allowOutsideClick: false,
                        allowEscapeKey: false
                    }).then(function() {
                        window.history.back();
                    });
                </script>
            </body>
            </html>";
    exit;
}

$geotagging = $latitude . ',' . $longitude;

if (isset($_SESSION['user_id'])) {
    $user_id = (int) $_SESSION['user_id'];
    $tanggal = date('Y-m-d');

    if (isset($_SESSION['role']) && $_SESSION['role'] === 'User') {
        $check_time_off_query = "
            SELECT id
            FROM time_off
            WHERE user_id = $user_id
            AND '$tanggal' BETWEEN start_date AND end_date
            LIMIT 1
        ";
        $time_off_result = mysqli_query($koneksi, $check_time_off_query);

        if ($time_off_result && mysqli_num_rows($time_off_result) > 0) {
            session_unset();
            session_destroy();

            if (ini_get('session.use_cookies')) {
                $params = session_get_cookie_params();
                setcookie(
                    session_name(),
                    '',
                    time() - 42000,
                    $params['path'],
                    $params['domain'],
                    $params['secure'],
                    $params['httponly']
                );
            }

            header("Location: ../../../../index.php");
            exit;
        }

        $time_logout = date('H:i:s');

        $check_query = "
            SELECT *
            FROM time
            WHERE user_id = $user_id
            AND tanggal = '$tanggal'
            AND time_off_id IS NULL
        ";
        $check_result = mysqli_query($koneksi, $check_query);

        if ($check_result && mysqli_num_rows($check_result) > 0) {
            $update_query = "
                UPDATE time
                SET
                    time_logout = '$time_logout',
                    geotagging_logout = '$geotagging'
                WHERE user_id = $user_id
                AND tanggal = '$tanggal'
                AND time_off_id IS NULL
            ";

            mysqli_query($koneksi, $update_query);
        } else {
            $insert_query = "
                INSERT INTO time (
                    tanggal,
                    user_id,
                    time_login,
                    time_logout,
                    geotagging_logout,
                    geotagging
                )
                VALUES (
                    '$tanggal',
                    $user_id,
                    '00:00:00',
                    '$time_logout',
                    '$geotagging',
                    ''
                )
            ";

            mysqli_query($koneksi, $insert_query);
        }
    }
}

$_SESSION = [];
session_unset();
session_destroy();

if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();

    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params['path'],
        $params['domain'],
        $params['secure'],
        $params['httponly']
    );
}

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: 0");

header("Location: ../../../../index.php");
exit;
?>