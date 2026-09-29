<?php
$host = "localhost";
$username = "root";
$password = "";
$database = "sbue_bki";

$koneksi = new mysqli($host, $username, $password, $database);
if ($koneksi->connect_error) {
    die("Koneksi Gagal: " . $koneksi->connect_error);
}

function feedback($data) {
    global $koneksi;

    $name    = htmlspecialchars(trim($data['name']));
    $email   = htmlspecialchars(trim($data['email']));
    $subject = htmlspecialchars(trim($data['subject']));
    $message = htmlspecialchars(trim($data['message']));

    $stmt = $koneksi->prepare("INSERT INTO feedback (name, email, subject, message) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $name, $email, $subject, $message);

    $stmt->execute();
    return $stmt->affected_rows;
}

function parse_amount($value) {
    $value = str_replace('.', '', trim((string)$value));
    $value = str_replace(',', '.', $value);
    return (float)$value;
}

function tambah_user($data) {
    global $koneksi;

    $nup = htmlspecialchars($data['nup']);
    $nama = htmlspecialchars($data['nama']);
    $divisi = htmlspecialchars($data['divisi']);
    $username = htmlspecialchars($data['username']);
    $password = md5(htmlspecialchars($data['password']));
    $status = htmlspecialchars($data['status']);
    $basic_salary = parse_amount($data['basic_salary']);
    $meal_allowance = parse_amount($data['meal_allowance']);
    $transport_allowance = parse_amount($data['transport_allowance']);
    $welfare_allowance = parse_amount($data['welfare_allowance'] ?? 0);
    $other_allowance = parse_amount($data['other_allowance'] ?? 0);
    $extra_fooding = parse_amount($data['extra_fooding'] ?? 0);
    $image = upload_file();
    $role = htmlspecialchars($data['role'] ?? 'User');

    $stmt = $koneksi->prepare("INSERT INTO users (nup, nama, divisi, username, password, role, status, image, basic_salary, meal_allowance, transport_allowance, welfare_allowance, other_allowance, extra_fooding) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssssssdddddd", $nup, $nama, $divisi, $username, $password, $role, $status, $image, $basic_salary, $meal_allowance, $transport_allowance, $welfare_allowance, $other_allowance, $extra_fooding);

    $stmt->execute();
    return $stmt->affected_rows;
}

function tambah_time($data) {
    global $koneksi;

    $tanggal = htmlspecialchars($data['tanggal']);
    $user_id = htmlspecialchars($data['user_id']);
    $time_login = htmlspecialchars($data['time_login']);
    $before_break = htmlspecialchars($data['before_break']);
    $after_break = htmlspecialchars($data['after_break']);
    $time_logout = htmlspecialchars($data['time_logout']);
    $geotagging = htmlspecialchars($data['geotagging']);

    $stmt = $koneksi->prepare("INSERT INTO time (tanggal, user_id, time_login, before_break, after_break, time_logout, geotagging) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sisssss", $tanggal, $user_id, $time_login, $before_break, $after_break, $time_logout, $geotagging);

    $stmt->execute();
    return $stmt->affected_rows;
}

function tambah_planning($data) {
    global $koneksi;

    $tanggal = $data['tanggal'];
    $user_id = $data['user_id'];
    $deskripsi = $data['deskripsi'];
    $gambar = $data['gambar'];
    $time_upload_activity_planning = $data['time_upload_activity_planning'];
    $status = $data['status'];
    $history_update = 0;

    if (!empty($gambar)) {
        $status = 'Completed';
        $history_update = 1;
    } else {
        $status = 'On-progress';
    }
    
    $query = "INSERT INTO planning (tanggal, user_id, deskripsi, gambar, time_upload_activity_planning, status, history_update)
                VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt = $koneksi->prepare($query);
    
    if (!$stmt) {
        die('Prepare failed: ' . $koneksi->error);
    }

    $stmt->bind_param("sissssi", $tanggal, $user_id, $deskripsi, $gambar, $time_upload_activity_planning, $status, $history_update);
    
    if ($stmt->execute()) {
        return $stmt->affected_rows;
    } else {
        die('Execute failed: ' . $stmt->error);
    }
}


function hapus_feedback($id) {
    global $koneksi;

    $stmt = $koneksi->prepare("DELETE FROM feedback WHERE id = ?");
    $stmt->bind_param("i", $id);
    
    $stmt->execute();
    return $stmt->affected_rows;
}

function hapus_user($id) {
    global $koneksi;

    $stmt = $koneksi->prepare("SELECT image FROM users WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    if ($row['image']) {
        unlink('img/' . $row['image']);
    }

    $stmt = $koneksi->prepare("DELETE FROM time WHERE user_id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();

    $stmt = $koneksi->prepare("DELETE FROM planning WHERE user_id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();

    $stmt = $koneksi->prepare("DELETE FROM users WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    return $stmt->affected_rows;
}

function hapus_time($id) {
    global $koneksi;

    $stmt = $koneksi->prepare("DELETE FROM time WHERE id = ?");
    $stmt->bind_param("i", $id);
    
    $stmt->execute();
    return $stmt->affected_rows;
}

function hapus_planning($id) {
    global $koneksi;

    $stmt = $koneksi->prepare("SELECT gambar FROM planning WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();

    if (!empty($row['gambar'])) {
        $gambar_arr = explode(',', $row['gambar']);
        foreach ($gambar_arr as $gambar) {
            $file_path = 'img/' . trim($gambar);
            if (file_exists($file_path)) {
                unlink($file_path);
            }
        }
    }

    $stmt = $koneksi->prepare("DELETE FROM planning WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    return $stmt->affected_rows;
}

function update_user($data) {
    global $koneksi;

    $id = $data['id'];
    $nup = htmlspecialchars($data['nup']);
    $nama = htmlspecialchars($data['nama']);
    $divisi = htmlspecialchars($data['divisi']);
    $username = htmlspecialchars($data['username']);
    $status = htmlspecialchars($data['status']);
    $basic_salary = parse_amount($data['basic_salary']);
    $meal_allowance = parse_amount($data['meal_allowance']);
    $transport_allowance = parse_amount($data['transport_allowance']);
    $welfare_allowance = parse_amount($data['welfare_allowance'] ?? 0);
    $other_allowance = parse_amount($data['other_allowance'] ?? 0);
    $extra_fooding = parse_amount($data['extra_fooding'] ?? 0);

    if ($_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $stmt = $koneksi->prepare("SELECT image FROM users WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();

        if ($row['image']) {
            unlink('img/' . $row['image']);
        }

        $image = upload_file();
        $image_query = ", image = ?";
        $image_param = $image;
    } else {
        $image_query = "";
        $image_param = null;
    }

    $password = !empty($data['password']) ? md5(htmlspecialchars($data['password'])) : null;

    if ($password) {
        $password_query = ", password = ?";
        $password_param = $password;
    } else {
        $password_query = "";
        $password_param = null;
    }

    $query = "UPDATE users SET nup = ?, nama = ?, divisi = ?, username = ?, status = ?, basic_salary = ?, meal_allowance = ?, transport_allowance = ?, welfare_allowance = ?, other_allowance = ?, extra_fooding = ?" . $image_query . $password_query . " WHERE id = ?";
    $stmt = $koneksi->prepare($query);

    if ($image_param && $password_param) {
        $stmt->bind_param("sssssddddddssi", $nup, $nama, $divisi, $username, $status, $basic_salary, $meal_allowance, $transport_allowance, $welfare_allowance, $other_allowance, $extra_fooding, $image_param, $password_param, $id);
    } elseif ($image_param) {
        $stmt->bind_param("sssssddddddsi", $nup, $nama, $divisi, $username, $status, $basic_salary, $meal_allowance, $transport_allowance, $welfare_allowance, $other_allowance, $extra_fooding, $image_param, $id);
    } elseif ($password_param) {
        $stmt->bind_param("sssssddddddsi", $nup, $nama, $divisi, $username, $status, $basic_salary, $meal_allowance, $transport_allowance, $welfare_allowance, $other_allowance, $extra_fooding, $password_param, $id);
    } else {
        $stmt->bind_param("sssssddddddi", $nup, $nama, $divisi, $username, $status, $basic_salary, $meal_allowance, $transport_allowance, $welfare_allowance, $other_allowance, $extra_fooding, $id);
    }

    $stmt->execute();
    return $stmt->affected_rows;
}


function update_time($data) {
    global $koneksi;

    $id = $data['id'];
    $tanggal = htmlspecialchars($data['tanggal']);
    $user_id = htmlspecialchars($data['user_id']);
    $time_login = htmlspecialchars($data['time_login']);
    $time_logout = htmlspecialchars($data['time_logout']);
    $geotagging = htmlspecialchars($data['geotagging']);

    $stmt = $koneksi->prepare("UPDATE time SET tanggal=?, user_id=?, time_login=?, time_logout=?, geotagging=? WHERE id=?");
    $stmt->bind_param("sisssi", $tanggal, $user_id, $time_login, $time_logout, $geotagging, $id);
    
    $stmt->execute();
    return $stmt->affected_rows;
}

function update_planning($data) {
    global $koneksi;

    $id = $data['id'];
    $tanggal = htmlspecialchars($data['tanggal']);
    $user_id = htmlspecialchars($data['user_id']);
    $gambar = isset($data['gambar']) ? $data['gambar'] : '';
    $time_upload_activity_planning = isset($data['time_upload_activity_planning']) ? htmlspecialchars($data['time_upload_activity_planning']) : '';
    $deskripsi = htmlspecialchars($data['deskripsi']);
    $status = isset($data['status']) ? htmlspecialchars($data['status']) : '';

    $valid_statuses = ['Incomplete', 'On-progress', 'Completed'];
    if (!in_array($status, $valid_statuses)) {
        $status = 'On-progress';
    }

    $role = $_SESSION['role'];

    $stmt = $koneksi->prepare("SELECT gambar, deskripsi, history_update, time_upload_activity_planning, time_upload_avident, status FROM planning WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $existing_data = $result->fetch_assoc();

    $existing_time_upload_activity_planning = $existing_data['time_upload_activity_planning'];
    $existing_time_upload_avident = $existing_data['time_upload_avident'];
    $existing_gambar = $existing_data['gambar'];
    $existing_deskripsi = $existing_data['deskripsi'];
    $existing_history_update = (int)$existing_data['history_update'];
    $existing_status = $existing_data['status'];

    if ($deskripsi != $existing_deskripsi) {
        $history_update = ($role === 'User') ? $existing_history_update + 1 : $existing_history_update;
    } else {
        $history_update = $existing_history_update;
    }

    $time_upload_activity_planning = !empty($time_upload_activity_planning) ? $time_upload_activity_planning : $existing_time_upload_activity_planning;
    $time_upload_avident = isset($data['time_upload_avident']) ? htmlspecialchars($data['time_upload_avident']) : $existing_time_upload_avident;

    if (!empty($time_upload_activity_planning) && !empty($time_upload_avident)) {
        $start_time = new DateTime($time_upload_activity_planning);
        $end_time = new DateTime($time_upload_avident);
        $duration = $start_time->diff($end_time);
        $collection_duration = $duration->format('%H:%I:%S');
    } else {
        $collection_duration = NULL;
    }

    if ($existing_status === 'Completed') {
        $status = 'Completed';
    }

    $stmt = $koneksi->prepare("UPDATE planning SET tanggal=?, user_id=?, gambar=?, time_upload_avident=?, time_upload_activity_planning=?, deskripsi=?, status=?, history_update=?, collection_duration=? WHERE id=?");
    $stmt->bind_param("sisssssssi", $tanggal, $user_id, $gambar, $time_upload_avident, $time_upload_activity_planning, $deskripsi, $status, $history_update, $collection_duration, $id);

    $stmt->execute();
    return $stmt->affected_rows;
}

function upload_file() {
    $namaFile = $_FILES['image']['name'];
    $ukuranFile = $_FILES['image']['size'];
    $error = $_FILES['image']['error'];
    $tmpName = $_FILES['image']['tmp_name'];

    $extensifileValid = ['jpg', 'jpeg', 'png'];
    $extensifile = explode('.', $namaFile);
    $extensifile = strtolower(end($extensifile));

    if (!in_array($extensifile, $extensifileValid)) {
        echo "<script>alert('Format Tidak Valid');</script>";
        die();
    }

    if ($ukuranFile > 2048000) {
        echo "<script>alert('Ukuran File Max 2 MB');</script>";
        die();
    }

    $namaFileBaru = uniqid();
    $namaFileBaru .= '.' . $extensifile;

    move_uploaded_file($tmpName, 'img/' . $namaFileBaru);
    return $namaFileBaru;
}
?>