<?php
    session_start();
    include("koneksi.php");

    date_default_timezone_set('Asia/Jakarta');

    if (isset($_SESSION['user_id'])) {
        $current_time = date('H:i:s');
        
        if ($current_time === '23:59:59') {
            session_destroy();
            header("location: Halaman_login.php?error=session_expired");
            exit;
        }
    }

    if (isset($_SESSION['user_id']) && isset($_SESSION['username'])) {
        $user_id = (int) $_SESSION['user_id'];
        $tanggal = date('Y-m-d');

        if (isset($_SESSION['roles']) && count($_SESSION['roles']) > 1 && empty($_SESSION['role'])) {
            header("Location: pilih_role.php");
            exit;
        }

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
                header("Location: dashboard.php");
                exit;
            }

            $check_time_query = "
                SELECT id
                FROM time
                WHERE user_id = $user_id
                AND tanggal = '$tanggal'
                LIMIT 1
            ";
            $check_time_result = mysqli_query($koneksi, $check_time_query);

            if ($check_time_result && mysqli_num_rows($check_time_result) > 0) {
                header("Location: dashboard.php");
                exit;
            }

            header("Location: pilih_kehadiran.php");
            exit;
        }

        if (isset($_SESSION['role']) && !empty($_SESSION['role'])) {
            header("Location: dashboard.php");
            exit;
        }
    }

    $username  = isset($_POST['username']) ? trim($_POST['username']) : '';
    $password  = isset($_POST['password']) ? $_POST['password'] : '';
    $latitude  = isset($_POST['latitude']) ? $_POST['latitude'] : null;
    $longitude = isset($_POST['longitude']) ? $_POST['longitude'] : null;

    $query  = "SELECT * FROM users WHERE username = '$username' AND password = md5('$password')";
    $result = mysqli_query($koneksi, $query);

    if (mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);

        if ($row['status'] === 'Active') {
            $_SESSION['username'] = $username;
            $_SESSION['user_id'] = $row['id'];
            $_SESSION['nama']  = $row['nama'];
            $_SESSION['roles'] = explode(', ', $row['role']);
            $_SESSION['image'] = $row['image'];
            
            if (count($_SESSION['roles']) > 1) {
                header("location: pilih_role.php");
                exit;
            } else {
                $_SESSION['role'] = $_SESSION['roles'][0];
            }

            if ($_SESSION['role'] === 'User') {
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
                    header("location: dashboard.php");
                    exit;
                }

                $check_query = "SELECT * FROM time WHERE user_id = $user_id AND tanggal = '$tanggal' LIMIT 1";
                $check_result = mysqli_query($koneksi, $check_query);

                if (mysqli_num_rows($check_result) > 0) {
                    $row = mysqli_fetch_assoc($check_result);

                    if ((int) $row['is_break'] === 1 && empty($row['after_break'])) {
                        $update_query = "
                            UPDATE time
                            SET
                                after_break = '$time_login',
                                geotagging_after_break = '$geotagging',
                                is_break = 0
                            WHERE user_id = $user_id
                            AND tanggal = '$tanggal'
                            AND time_off_id IS NULL
                        ";

                        if (!mysqli_query($koneksi, $update_query)) {
                            die("Error updating record: " . mysqli_error($koneksi));
                        }

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

                    header("location: dashboard.php");
                    exit;
                }

                $_SESSION['login_latitude'] = $is_valid_geo ? $latitude : '';
                $_SESSION['login_longitude'] = $is_valid_geo ? $longitude : '';

                header("location: pilih_kehadiran.php");
                exit;
            }

            header("location: dashboard.php");
            exit;
        } else {
            header("location: Halaman_login.php?error=inactive");
            exit;
        }
    } else {
        header("location: Halaman_login.php?error=invalid");
        exit;
    }
?>