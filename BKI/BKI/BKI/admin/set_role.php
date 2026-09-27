<?php
    session_start();
    include("koneksi.php");

    date_default_timezone_set('Asia/Jakarta');

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $role = isset($_POST['role']) ? $_POST['role'] : '';
        $latitude  = isset($_POST['latitude']) ? $_POST['latitude'] : null;
        $longitude = isset($_POST['longitude']) ? $_POST['longitude'] : null;
        $_SESSION['role'] = $role;

        if ($role === 'User') {
            $user_id = (int) $_SESSION['user_id'];
            $tanggal = date('Y-m-d');
            $time_login = date('H:i:s');
            $is_valid_geo = is_numeric($latitude) && is_numeric($longitude)
                && $latitude >= -90 && $latitude <= 90
                && $longitude >= -180 && $longitude <= 180;
            $geotagging = $is_valid_geo ? ($latitude . ',' . $longitude) : '';

            $check_time_off_query = "
                SELECT id, absence_type
                FROM time_off
                WHERE user_id = $user_id
                AND '$tanggal' BETWEEN start_date AND end_date
                LIMIT 1
            ";
            $time_off_result = mysqli_query($koneksi, $check_time_off_query);

            if ($time_off_result && mysqli_num_rows($time_off_result) > 0) {
                $_SESSION['login_latitude'] = '';
                $_SESSION['login_longitude'] = '';
                header("Location: dashboard.php");
                exit;
            }
        
            $check_query  = "SELECT * FROM time WHERE user_id = $user_id AND tanggal = '$tanggal' LIMIT 1";
            $check_result = mysqli_query($koneksi, $check_query);
        
            if (mysqli_num_rows($check_result) > 0) {
                $row = mysqli_fetch_assoc($check_result);
                if ($row['is_break'] == true && empty($row['after_break'])) {
                    $update_query = "UPDATE time SET after_break = '$time_login', geotagging_after_break = '$geotagging', is_break = false WHERE user_id = $user_id AND tanggal = '$tanggal'";
                    if (mysqli_query($koneksi, $update_query)) {
                        echo "<!DOCTYPE html>
                                <html lang='en'>
                                <head>
                                    <meta charset='UTF-8'>
                                    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
                                    <title>Welcome Back</title>
                                    <link href='img/logo.png' rel='icon'>
                                    <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
                                </head>
                                <body>
                                    <script>
                                        Swal.fire({
                                            icon: 'success',
                                            title: 'Happy return to activity!',
                                            text: 'You are back from the break.',
                                            backdrop: true,
                                            confirmButtonColor: '#3085d6',
                                            confirmButtonText: 'OK'
                                        }).then((result) => {
                                            if (result.isConfirmed) {
                                                window.location.href = 'dashboard.php';
                                            }
                                        });
                                    </script>
                                </body>
                                </html>";
                        exit;
                    }
                }

                header("Location: dashboard.php");
                exit;
            } else {
                $check_time_off_query = "
                    SELECT id
                    FROM time_off
                    WHERE user_id = $user_id
                    AND '$tanggal' BETWEEN start_date AND end_date
                    LIMIT 1
                ";
                $time_off_result = mysqli_query($koneksi, $check_time_off_query);

                if ($time_off_result && mysqli_num_rows($time_off_result) > 0) {
                    header("Location: dashboard.php");
                    exit;
                }

                $_SESSION['login_latitude'] = $is_valid_geo ? $latitude : '';
                $_SESSION['login_longitude'] = $is_valid_geo ? $longitude : '';
                header("Location: pilih_kehadiran.php");
                exit;
            }
        }
        
        header("Location: dashboard.php");
        exit;
        
    } else {
        header("Location: Halaman_login.php");
        exit;
    }
?>