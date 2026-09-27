<?php
    session_start();
    include("koneksi.php");

    date_default_timezone_set('Asia/Jakarta');

    if (isset($_SESSION['user_id']) && isset($_SESSION['role']) && $_SESSION['role'] === 'User') {
        $user_id = (int) $_SESSION['user_id'];
        $today = date('Y-m-d');

        $check_time_off_query = "
            SELECT id
            FROM time_off
            WHERE user_id = $user_id
            AND '$today' BETWEEN start_date AND end_date
            LIMIT 1
        ";

        $time_off_result = mysqli_query($koneksi, $check_time_off_query);

        if ($time_off_result && mysqli_num_rows($time_off_result) > 0) {
            echo json_encode([
                'status' => 'success',
                'message' => 'Geotagging is not required because you are on leave or sick.'
            ]);
            exit;
        }

        $data = json_decode(file_get_contents("php://input"), true);

        if (isset($data['latitude']) && isset($data['longitude']) && isset($data['type'])) {
            $latitude  = $data['latitude'];
            $longitude = $data['longitude'];
            $type = $data['type'];

            if (is_numeric($latitude) && is_numeric($longitude) && $latitude >= -90 && $latitude <= 90 && $longitude >= -180 && $longitude <= 180) {
                $geotagging = $latitude . ',' . $longitude;

                $column = '';

                switch ($type) {
                    case 'login':
                        $column = 'geotagging';
                        break;
                    case 'before_break':
                        $column = 'geotagging_before_break';
                        break;
                    case 'after_break':
                        $column = 'geotagging_after_break';
                        break;
                    case 'logout':
                        $column = 'geotagging_logout';
                        break;
                    default:
                        echo json_encode(['status' => 'error', 'message' => 'Invalid type']);
                        exit;
                }

                $check_query = "SELECT $column FROM time WHERE user_id = $user_id AND tanggal = '$today' LIMIT 1";
                $check_result = mysqli_query($koneksi, $check_query);

                if ($check_result && mysqli_num_rows($check_result) > 0) {
                    $row = mysqli_fetch_assoc($check_result);

                    if (empty($row[$column])) {
                        $update_query = "UPDATE time SET $column = '$geotagging' WHERE user_id = $user_id AND tanggal = '$today'";
                        $result = mysqli_query($koneksi, $update_query);

                        if ($result) {
                            echo json_encode(['status' => 'success']);
                        } else {
                            echo json_encode(['status' => 'error', 'message' => 'Gagal menyimpan geotagging']);
                        }
                    } else {
                        echo json_encode(['status' => 'success', 'message' => 'Geotagging sudah ada']);
                    }
                } else {
                    echo json_encode(['status' => 'success', 'message' => 'Data attendance belum tersedia']);
                }
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Data lokasi tidak valid']);
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Data lokasi tidak lengkap']);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'User tidak terautentikasi atau role tidak sesuai']);
    }
?>