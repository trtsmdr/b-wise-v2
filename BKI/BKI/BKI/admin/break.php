<?php
session_start();
include("koneksi.php");

date_default_timezone_set('Asia/Jakarta');

if (isset($_SESSION['user_id']) && $_SESSION['role'] === 'User') {
    $user_id = (int) $_SESSION['user_id'];
    $tanggal = date('Y-m-d');
    $current_time = date('H:i:s');

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
                            text: 'Please enable your location permission before starting your break.',
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

    $check_time_off_query = "
        SELECT id, absence_type
        FROM time_off
        WHERE user_id = $user_id
        AND '$tanggal' BETWEEN start_date AND end_date
        LIMIT 1
    ";
    $time_off_result = mysqli_query($koneksi, $check_time_off_query);

    if ($time_off_result && mysqli_num_rows($time_off_result) > 0) {
        session_destroy();
        header("Location: ../../../../index.php");
        exit;
    }

    $check_query = "
        SELECT *
        FROM time
        WHERE user_id = $user_id
        AND tanggal = '$tanggal'
        AND time_off_id IS NULL
        LIMIT 1
    ";
    $check_result = mysqli_query($koneksi, $check_query);

    if (mysqli_num_rows($check_result) > 0) {
        $row = mysqli_fetch_assoc($check_result);

        if ($row['is_break'] == false && empty($row['before_break'])) {
            $update_query = "
                UPDATE time
                SET
                    before_break = '$current_time',
                    geotagging_before_break = '$geotagging',
                    is_break = true
                WHERE user_id = $user_id
                AND tanggal = '$tanggal'
                AND time_off_id IS NULL
            ";

            if (mysqli_query($koneksi, $update_query)) {
                echo "<!DOCTYPE html>
                        <html lang='en'>
                            <head>
                                <meta charset='UTF-8'>
                                <meta name='viewport' content='width=device-width, initial-scale=1.0'>
                                <title>Break Alert</title>
                                <link href='img/logo.png' rel='icon'>
                                <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
                            </head>
                            <body>
                                <script>
                                    Swal.fire({
                                        icon: 'success',
                                        title: 'Have a good rest!',
                                        text: 'You have started your break time.',
                                        backdrop: true,
                                        confirmButtonColor: '#3085d6',
                                        confirmButtonText: 'OK'
                                    }).then((result) => {
                                        if (result.isConfirmed) {
                                            window.location.href = '../../../../index.php';
                                        }
                                    });
                                </script>
                            </body>
                        </html>";

                session_destroy();
                exit;
            }
        } elseif (!empty($row['before_break']) && !empty($row['after_break'])) {
            echo "<!DOCTYPE html>
                    <html lang='en'>
                    <head>
                        <meta charset='UTF-8'>
                        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
                        <title>Break Alert</title>
                        <link href='img/logo.png' rel='icon'>
                        <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
                    </head>
                    <body>
                        <script>
                            Swal.fire({
                                icon: 'warning',
                                title: 'You have taken a break!',
                                text: 'You have taken a break today.',
                                backdrop: true,
                                confirmButtonColor: '#3085d6',
                                confirmButtonText: 'OK'
                            }).then((result) => {
                                if (result.isConfirmed) {
                                    window.history.back();
                                }
                            });
                        </script>
                    </body>
                    </html>";

            exit;
        }
    } else {
        $insert_query = "
            INSERT INTO time (
                tanggal,
                user_id,
                time_login,
                before_break,
                after_break,
                time_logout,
                geotagging_before_break,
                geotagging,
                is_break,
                status
            )
            VALUES (
                '$tanggal',
                $user_id,
                '00:00:00',
                '$current_time',
                '00:00:00',
                '00:00:00',
                '$geotagging',
                '',
                true,
                'On-time'
            )
        ";

        if (!mysqli_query($koneksi, $insert_query)) {
            die("Error inserting record: " . mysqli_error($koneksi));
        }
    }
}

session_destroy();
header("Location: ../../../../index.php");
exit;
?>