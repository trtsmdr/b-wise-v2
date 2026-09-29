<?php
    session_start();
    $upload_error = $_SESSION['upload_error'] ?? null;
    unset($_SESSION['upload_error']);
    
    if (!isset($_SESSION['username'])) {
        header("location: Halaman_login.php");
        exit;
    }

    $nama  = $_SESSION['nama'];
    $role  = $_SESSION['role'];
    $image = $_SESSION['image'];

    require 'koneksi.php';

    function is_superadmin() {
        return $_SESSION['role'] === 'Super-Admin';
    }
    
    function is_admin() {
        return $_SESSION['role'] === 'Admin';
    }
    
    function is_user() {
        return $_SESSION['role'] === 'User';
    }
    
    $id    = $_GET['id'];
    $query = "
        SELECT p.id, p.tanggal, p.time_upload_avident, p.deskripsi, p.gambar,
            u.id as user_id, u.nup, u.nama, u.divisi
        FROM planning p
        JOIN users u ON p.user_id = u.id
        WHERE p.id = '$id'
    ";

    $result = mysqli_query($koneksi, $query);
    $row    = mysqli_fetch_assoc($result);

    function upload_error($title, $message) {
        $_SESSION['upload_error'] = [
            'title' => $title,
            'message' => $message
        ];

        header('Location: ' . $_SERVER['PHP_SELF'] . '?id=' . urlencode($_GET['id'] ?? ''));
        exit;
    }

    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $tanggal = htmlspecialchars($_POST['tanggal']);
        $user_id = htmlspecialchars($_POST['user_id']);
        $deskripsi = htmlspecialchars($_POST['deskripsi']);
        $time_upload_avident = htmlspecialchars($_POST['time_upload_avident']);

        $uploaded_files = [];
        $upload_dir = 'img/';
        $max_file_size = 5 * 1024 * 1024;
        $allowed_mime_types = ['image/jpeg', 'image/png', 'image/heic', 'image/heif'];

        if (isset($_FILES['gambar']) && isset($_FILES['gambar']['name']) && is_array($_FILES['gambar']['name'])) {
            $file_count = count($_FILES['gambar']['name']);

            for ($key = 0; $key < $file_count; $key++) {
                if ($_FILES['gambar']['error'][$key] === UPLOAD_ERR_NO_FILE) {
                    continue;
                }

                $original_name = $_FILES['gambar']['name'][$key];

                if ($_FILES['gambar']['error'][$key] !== UPLOAD_ERR_OK) {
                    upload_error('Upload failed!', 'Failed to upload file ' . htmlspecialchars($original_name) . '.');
                }

                $tmp_name = $_FILES['gambar']['tmp_name'][$key];
                $file_size = (int) $_FILES['gambar']['size'][$key];
                $original_extension = strtolower(pathinfo($original_name, PATHINFO_EXTENSION));

                if ($file_size > $max_file_size) {
                    upload_error('File too large!', 'File ' . htmlspecialchars($original_name) . ' exceeds the maximum file size of 5 MB.');
                }

                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mime_type = finfo_file($finfo, $tmp_name);
                finfo_close($finfo);

                $is_heic = in_array($original_extension, ['heic', 'heif'], true) || in_array($mime_type, ['image/heic', 'image/heif'], true);

                if ($is_heic) {
                    if (!extension_loaded('imagick')) {
                        upload_error('Upload failed!', 'HEIC and HEIF support is not available.');
                    }

                    try {
                        $imagick_class = 'Imagick';
                        $validator = new $imagick_class();
                        $validator->readImage($tmp_name);
                        $validator->setIteratorIndex(0);
                        $validator->clear();
                        $validator->destroy();
                    } catch (Throwable $e) {
                        upload_error('Invalid image!', 'File ' . htmlspecialchars($original_name) . ' is not a valid image.');
                    }

                    $final_extension = 'jpg';
                } else {
                    if (!in_array($mime_type, $allowed_mime_types, true)) {
                        upload_error('Invalid file!', 'File ' . htmlspecialchars($original_name) . ' is not supported. Use PNG, JPG, JPEG, HEIC, or HEIF.');
                    }

                    if (@getimagesize($tmp_name) === false) {
                        upload_error('Invalid image!', 'File ' . htmlspecialchars($original_name) . ' is not a valid image.');
                    }

                    if ($mime_type === 'image/png') {
                        $final_extension = 'png';
                    } elseif ($original_extension === 'jpeg') {
                        $final_extension = 'jpeg';
                    } else {
                        $final_extension = 'jpg';
                    }
                }

                $file_name = date('YmdHis') . '_' . uniqid() . '.' . $final_extension;
                $target_file = $upload_dir . $file_name;

                if ($is_heic) {
                    try {
                        $image = new Imagick();
                        $image->readImage($tmp_name);
                        $image->setIteratorIndex(0);
                        $image->setImageFormat('jpg');
                        $image->setImageCompressionQuality(90);
                        $image->stripImage();

                        if (!$image->writeImage($target_file)) {
                            $image->clear();
                            $image->destroy();
                            upload_error('Upload failed!', 'File ' . htmlspecialchars($original_name) . ' could not be saved. Please try again.');
                        }

                        $image->clear();
                        $image->destroy();
                    } catch (Throwable $e) {
                        if (file_exists($target_file)) {
                            unlink($target_file);
                        }

                        upload_error('Upload failed!', 'File ' . htmlspecialchars($original_name) . ' could not be saved. Please try again.');
                    }
                } else {
                    if (!move_uploaded_file($tmp_name, $target_file)) {
                        foreach ($uploaded_files as $uploaded_file) {
                            $uploaded_path = $upload_dir . $uploaded_file;

                            if (file_exists($uploaded_path)) {
                                unlink($uploaded_path);
                            }
                        }

                        upload_error('Upload failed!', 'File ' . htmlspecialchars($original_name) . ' could not be saved. Please try again.');
                    }
                }

                if (!file_exists($target_file) || filesize($target_file) > $max_file_size) {
                    if (file_exists($target_file)) {
                        unlink($target_file);
                    }

                    upload_error('Upload failed!', 'File ' . htmlspecialchars($original_name) . ' exceeds the maximum file size of 5 MB.');
                }

                $uploaded_files[] = $file_name;
            }
        }

        $existing_files = array_filter(explode(',', $row['gambar']));
        $existing_files = array_map('trim', $existing_files);

        $images_to_delete = !empty($_POST['images_to_delete']) ? array_map('trim', explode(',', $_POST['images_to_delete'])) : [];
        $images_to_delete = array_filter($images_to_delete);

        $remaining_images = array_diff($existing_files, $images_to_delete);

        $gambar = implode(',', array_merge($remaining_images, $uploaded_files));

        if (empty($gambar)) {
            echo "<script>
                document.addEventListener('DOMContentLoaded', function() {
                    Swal.fire({
                        icon: 'error',
                        title: 'Proof of Activity Required!',
                        text: 'Please upload at least one image before saving.',
                        confirmButtonText: 'OK',
                        allowOutsideClick: false,
                        allowEscapeKey: false
                    });
                });
            </script>";
            exit;
        }

        $status = 'Completed';

        $data = [
            'id' => $id,
            'gambar' => $gambar,
            'tanggal' => $tanggal,
            'user_id' => $user_id,
            'deskripsi' => $deskripsi,
            'time_upload_avident' => $time_upload_avident,
            'status' => $status
        ];
        
        if (update_planning($data) > 0) {
            foreach ($images_to_delete as $image) {
                $file_path = 'img/' . $image;
                if (file_exists($file_path) && !is_dir($file_path)) {
                    unlink($file_path);
                }
            }
            echo "<script>
                document.addEventListener('DOMContentLoaded', function() {
                    Swal.fire({
                        icon: 'success',
                        title: 'Planning successfully updated!',
                        showConfirmButton: false,
                        timer: 1500
                    }).then(() => {
                        window.location.href = 'avident.php';
                    });
                });
            </script>";
        } else {
            echo "<script>
                document.addEventListener('DOMContentLoaded', function() {
                    Swal.fire({
                        icon: 'error',
                        title: 'Planning failed to update!',
                        showConfirmButton: false,
                        timer: 1500
                    });
                });
            </script>";
        }
    }

    $imageName = trim($image);
    $imagePath = '';

    $extensions = ['jpg', 'jpeg', 'png', 'webp', 'heic'];

    foreach ($extensions as $ext) {
        $filePath = __DIR__ . '/img/' . $imageName . '.' . $ext;

        if (file_exists($filePath)) {
            $imagePath = 'img/' . $imageName . '.' . $ext;
            break;
        }
    }

    if ($imagePath === '') {
        $imagePath = 'img/default.png';
    }
?>

<!DOCTYPE html>

<html class="loading semi-dark-layout" lang="en" data-layout="semi-dark-layout" data-textdirection="ltr">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width,initial-scale=1.0,user-scalable=0,minimal-ui">
    <meta name="description" content="Vuexy admin is super flexible, powerful, clean &amp; modern responsive bootstrap 4 admin template with unlimited possibilities.">
    <meta name="keywords" content="admin template, Vuexy admin template, dashboard template, flat admin template, responsive admin template, web app">
    <meta name="author" content="PIXINVENT">
    <title>BKI - Evidence</title>
    <link href="img/logo.png" rel="icon">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,300;0,400;0,500;0,600;1,400;1,500;1,600" rel="stylesheet">

    <!-- BEGIN: Vendor CSS -->
    <link rel="stylesheet" type="text/css" href="../../../app-assets/vendors/css/vendors.min.css">
    <!-- END: Vendor CSS -->

    <!-- BEGIN: Theme CSS -->
    <link rel="stylesheet" type="text/css" href="../../../app-assets/css/bootstrap.css">
    <link rel="stylesheet" type="text/css" href="../../../app-assets/css/bootstrap-extended.css">
    <link rel="stylesheet" type="text/css" href="../../../app-assets/css/colors.css">
    <link rel="stylesheet" type="text/css" href="../../../app-assets/css/components.css">
    <link rel="stylesheet" type="text/css" href="../../../app-assets/css/themes/dark-layout.css">
    <link rel="stylesheet" type="text/css" href="../../../app-assets/css/themes/bordered-layout.css">
    <link rel="stylesheet" type="text/css" href="../../../app-assets/css/themes/semi-dark-layout.css">
    <!-- END: Theme CSS -->

    <!-- BEGIN: Page CSS -->
    <link rel="stylesheet" type="text/css" href="../../../app-assets/css/core/menu/menu-types/vertical-menu.css">
    <!-- END: Page CSS -->

    <!-- BEGIN: Custom CSS -->
    <link rel="stylesheet" type="text/css" href="../../../assets/css/style.css">
    <!-- END: Custom CSS -->

    <style>
        .btn-primary {
            background: linear-gradient(135deg, #FFDA78, #FF7F3E);
            color: #FFF;
            border: none;
            padding: 12px 24px;
            text-decoration: none;
        }
        .image-container {
            display: inline-block;
            position: relative;
            margin-right: 10px;
        }
        .image-container img {
            width: 100px;
            border: 1px solid #ddd;
            display: block;
        }
        .delete-button {
            display: none;
            position: absolute;
            top: 0;
            right: 0;
            background: red;
            color: white;
            border: none;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            text-align: center;
            line-height: 20px;
            cursor: pointer;
        }
        .image-container:hover .delete-button {
            display: block;
        }
        .preview-image {
            display: inline-block;
            position: relative;
            margin-right: 10px;
        }
        .preview-image img {
            width: 100px;
            border: 1px solid #ddd;
        }
        .profile-navbar-img {
            width: 40px !important;
            height: 40px !important;
            object-fit: cover !important;
            object-position: center !important;
            border-radius: 50% !important;
        }
        .note-danger {
            color: #dc3545;
            font-size: 12px;
            margin-top: 6px;
            display: block;
        }
    </style>
</head>

<body class="vertical-layout vertical-menu-modern  navbar-floating footer-static" data-open="click" data-menu="vertical-menu-modern" data-col="">

    <!-- BEGIN: Header -->
    <nav class="header-navbar navbar navbar-expand-lg align-items-center floating-nav navbar-light navbar-shadow container-xxl">
        <div class="navbar-container d-flex content">
            <div class="d-flex align-items-center">
                <ul class="nav navbar-nav d-xl-none">
                    <li class="nav-item"><a class="nav-link menu-toggle" href="#"><i class="ficon" data-feather="menu"></i></a></li>
                </ul>
            </div>
            <ul class="nav navbar-nav align-items-center ms-auto">
                <li class="nav-item dropdown dropdown-user"><a class="nav-link dropdown-toggle dropdown-user-link" id="dropdown-user" href="#" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        <div class="user-nav d-sm-flex d-none"><span class="user-name fw-bolder"><?php echo $nama; ?></span><span class="user-status"><?php echo $role; ?></span></div><span class="avatar"><img class="round profile-navbar-img" src="<?php echo htmlspecialchars($imagePath); ?>" alt="Profile"><span class="avatar-status-online"></span></span>
                    </a>
                    <div class="dropdown-menu dropdown-menu-end" aria-labelledby="dropdown-user"><a class="dropdown-item" href="profile.php"><i class="me-50" data-feather="user"></i> Profile</a>
                        <?php if (is_user()): ?>
                            <a class="dropdown-item" href="#" onclick="confirmBreak(); return false;"><i class="me-50" data-feather="battery-charging"></i> Break</a>
                        <?php endif; ?>
                        <a class="dropdown-item" href="#" onclick="confirmLogout(); return false;"><i class="me-50" data-feather="power"></i> Logout</a>
                    </div>
                </li>
            </ul>
        </div>
    </nav>
    <!-- END: Header -->

    <!-- BEGIN: Main Menu -->
    <div class="main-menu menu-fixed menu-dark menu-accordion menu-shadow" data-scroll-to-active="true">
        <div class="navbar-header">
            <ul class="nav navbar-nav flex-row">
                <li class="nav-item me-auto">
                    <a class="navbar-brand" href="#">
                        <h2 class="brand-text" style="font-size: 20px;">B - WISE</h2>
                        <hr>
                    </a>
                </li>
            </ul>
        </div>
        <div class="shadow-bottom"></div>
            <div class="main-menu-content">
                <ul class="navigation navigation-main" id="main-menu-navigation" data-menu="menu-navigation">
                    <li class="nav-item"><a class="d-flex align-items-center" href="dashboard.php"><i data-feather="home"></i><span class="menu-title text-truncate" data-i18n="Dashboard">Dashboard</span></a>
                    </li><br>
                    <li class="nav-item"><a class="d-flex align-items-center" href="#"><i data-feather="users"></i><span class="menu-title text-truncate" data-i18n="Employee Activity">Employee Activity</span></a>
                        <ul class="menu-content">
                            <li><a class="d-flex align-items-center" href="time.php"><i data-feather="circle"></i><span class="menu-item text-truncate" data-i18n="Employee Time">Time</span></a>
                            </li>
                            <li><a class="d-flex align-items-center" href="planning.php"><i data-feather="circle"></i><span class="menu-item text-truncate" data-i18n="Planning">Planning</span></a>
                            </li>
                            <li class="active"><a class="d-flex align-items-center" href="avident.php"><i data-feather="circle"></i><span class="menu-item text-truncate" data-i18n="Evidence">Evidence</span></a>
                            </li>
                        </ul>
                    </li><br>
                    <?php if (is_superadmin() || is_admin()): ?>
                        <li class="nav-item"><a class="d-flex align-items-center" href="role.php"><i data-feather="user-plus"></i><span class="menu-title text-truncate" data-i18n="Role ">Role </span></a>
                        </li><br>
                        <li class="nav-item"><a class="d-flex align-items-center" href="feedback.php"><i data-feather="mail"></i><span class="menu-title text-truncate" data-i18n="Feedback ">Feedback </span></a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    <!-- END: Main Menu -->

    <!-- BEGIN: Content -->
    <div class="app-content content ">
        <div class="content-overlay"></div>
        <div class="header-navbar-shadow"></div>
        <div class="content-wrapper container-xxl p-0">
            <div class="content-header row">
                <div class="content-header-left col-md-9 col-12 mb-2">
                    <div class="row breadcrumbs-top">
                        <h2 class="float-start mb-0">Form Evidence</h2>
                    </div>
                </div>
            </div>
            <div class="content-body">
                <!-- Basic Vertical form layout section start -->
                <section id="basic-vertical-layouts">
                    <div class="row">
                        <div class="col-md-12 col-12">
                            <div class="card">
                                <div class="card-body">
                                    <form id="avidentForm" action="" method="POST" class="form form-vertical" enctype="multipart/form-data">
                                        <input type="hidden" id="images_to_delete" name="images_to_delete" value="" />         
                                        <div class="row">
                                            <div class="col-6">
                                                <div class="mb-1">
                                                    <label for="tanggal" class="form-label">Date</label>
                                                    <input type="date" class="form-control" id="tanggal" name="tanggal" value="<?= htmlspecialchars($row['tanggal']) ?>" readonly />
                                                </div>
                                                <div class="mb-1">
                                                    <label for="user_id" class="form-label">User</label>
                                                    <select class="form-control" id="user_id" name="user_id" disabled>
                                                        <option value="">Select User</option>
                                                        <?php
                                                        $user_query = mysqli_query($koneksi, "SELECT id, nup, nama, divisi FROM users WHERE status = 'Active'");
                                                        while ($user = mysqli_fetch_assoc($user_query)) {
                                                            $selected = $user['id'] == $row['user_id'] ? 'selected' : '';
                                                            echo "<option value=\"{$user['id']}\" $selected>{$user['nup']} - {$user['nama']} ({$user['divisi']})</option>";
                                                        }
                                                        ?>
                                                    </select>
                                                </div>
                                                <div>
                                                    <input type="hidden" name="user_id" value="<?php echo $row['user_id']; ?>">
                                                </div>
                                                </div>
                                                <div class="col-6">
                                                    <div class="mb-1">
                                                        <label for="deskripsi" class="form-label">Description</label>
                                                        <textarea style="height: 115px;" class="form-control" id="deskripsi" name="deskripsi" readonly><?= $row['deskripsi'] ?></textarea>
                                                    </div>
                                                </div>
                                                <div class="col-6">
                                                    <div class="mb-1">
                                                        <label for="gambar" class="form-label">Proof of Activity</label>
                                                        <input type="file" class="form-control" id="gambar" name="gambar[]" accept=".jpg,.jpeg,.png,.heic,.heif,image/jpeg,image/png,image/heic,image/heif" multiple />
                                                        <small class="text-muted">Maximum file size of 5 MB per file. Supported formats: PNG, JPG, JPEG, HEIC, and HEIF.</small>
                                                        <div id="image-preview" class="mt-2">
                                                            <?php
                                                            $existing_images = explode(',', $row['gambar']);
                                                            foreach ($existing_images as $image) {
                                                                if ($image) {
                                                                    echo "
                                                                    <div class=\"image-container\">
                                                                        <img src=\"img/$image\" />
                                                                        <button type=\"button\" class=\"btn btn-danger btn-sm delete-image\" data-image=\"$image\">X</button>
                                                                    </div>
                                                                    ";
                                                                }
                                                            }
                                                            ?>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-6">
                                                    <input type="hidden" id="time_upload_avident" name="time_upload_avident" value="<?= htmlspecialchars($row['time_upload_avident']) ?>" />
                                                </div>
                                            
                                                <br>
                                            
                                                <div class="col-12">
                                                    <a href="avident.php" class="btn btn-outline-secondary">Back</a>
                                                    <button type="submit" name="update_planning" class="btn btn-primary_2 me-1">Save</button>
                                                </div>
                      
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </div>
    <!-- END: Content -->

    <div class="sidenav-overlay"></div>
    <div class="drag-target"></div>

    <!-- BEGIN: Footer -->
    <footer class="footer footer-static footer-light">
        <p class="clearfix mb-0"><span class="float-md-start d-block d-md-inline-block mt-25">&copy; Biro Klasifikasi Indonesia <span class="d-none d-sm-inline-block">2026</span></span></p>
    </footer>
    <button class="btn btn-primary btn-icon scroll-top" type="button"><i data-feather="arrow-up"></i></button>
    <!-- END: Footer -->

    <!-- BEGIN: Vendor JS -->
    <script src="../../../app-assets/vendors/js/vendors.min.js"></script>
    <!-- END: Vendor JS -->

    <!-- BEGIN: Theme JS -->
    <script src="../../../app-assets/js/core/app-menu.js"></script>
    <script src="../../../app-assets/js/core/app.js"></script>
    <!-- END: Theme JS -->

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <?php if ($upload_error): ?>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        Swal.fire({
            icon: 'error',
            title: <?= json_encode($upload_error['title']) ?>,
            text: <?= json_encode($upload_error['message']) ?>,
            confirmButtonText: 'OK',
            allowOutsideClick: false,
            allowEscapeKey: false
        });
    });
    </script>
    <?php endif; ?>

    <script>
        $(window).on('load', function() {
            if (feather) {
                feather.replace({
                    width: 14,
                    height: 14
                });
            }
        })

        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('avidentForm');
            const fileInput = document.getElementById('gambar');
            const previewContainer = document.getElementById('image-preview');
            const imageToDeleteField = document.getElementById('images_to_delete');
            const timeUploadField = document.getElementById('time_upload_avident');

            let selectedFiles = [];
            const allowedTypes = ['image/jpeg', 'image/png', 'image/heic', 'image/heif'];

            function updateFileInput() {
                const dataTransfer = new DataTransfer();

                selectedFiles.forEach(function(file) {
                    dataTransfer.items.add(file);
                });

                fileInput.files = dataTransfer.files;
            }

            function renderNewImages() {
                previewContainer.querySelectorAll('.preview-image').forEach(function(element) {
                    element.remove();
                });

                selectedFiles.forEach(function(file, index) {
                    const reader = new FileReader();

                    reader.onload = function(e) {
                        const previewImage = document.createElement('div');
                        previewImage.className = 'preview-image';
                        previewImage.dataset.index = index;
                        previewImage.innerHTML = `
                            <img src="${e.target.result}" />
                            <button type="button" class="btn btn-danger btn-sm delete-preview-image">X</button>
                        `;

                        previewContainer.appendChild(previewImage);

                        previewImage.querySelector('.delete-preview-image').addEventListener('click', function() {
                            selectedFiles.splice(index, 1);
                            updateFileInput();
                            renderNewImages();
                        });
                    };

                    reader.readAsDataURL(file);
                });
            }

            fileInput.addEventListener('change', function() {
                const newFiles = Array.from(this.files);
                for (const file of newFiles) {
                    const extension = file.name.split('.').pop().toLowerCase();
                    const allowedExtensions = ['jpg', 'jpeg', 'png', 'heic', 'heif'];
                    const maxFileSize = 5 * 1024 * 1024;

                    if (!allowedExtensions.includes(extension)) {
                        this.value = '';

                        Swal.fire({
                            icon: 'error',
                            title: 'Invalid file!',
                            text: 'Only JPG, JPEG, PNG, HEIC, and HEIF images are allowed.',
                            confirmButtonText: 'OK',
                            allowOutsideClick: false,
                            allowEscapeKey: false
                        });

                        return;
                    }

                    if (file.size > maxFileSize) {
                        this.value = '';

                        Swal.fire({
                            icon: 'error',
                            title: 'File too large!',
                            text: 'File ' + file.name + ' exceeds the maximum file size of 5 MB.',
                            confirmButtonText: 'OK',
                            allowOutsideClick: false,
                            allowEscapeKey: false
                        });

                        return;
                    }
                }

                newFiles.forEach(function(file) {
                    const isDuplicate = selectedFiles.some(function(existingFile) {
                        return existingFile.name === file.name &&
                            existingFile.size === file.size &&
                            existingFile.lastModified === file.lastModified;
                    });

                    if (!isDuplicate) {
                        selectedFiles.push(file);
                    }
                });

                updateFileInput();
                renderNewImages();
            });

            document.querySelectorAll('.delete-image').forEach(function(button) {
                button.addEventListener('click', function() {
                    const image = this.dataset.image;
                    const imagesToDelete = imageToDeleteField.value ? imageToDeleteField.value.split(',').filter(Boolean) : [];

                    if (!imagesToDelete.includes(image)) {
                        imagesToDelete.push(image);
                    }

                    imageToDeleteField.value = imagesToDelete.join(',');

                    const container = this.closest('.image-container');

                    if (container) {
                        container.remove();
                    }
                });
            });

            form.addEventListener('submit', function(e) {
                const existingImages = Array.from(previewContainer.querySelectorAll('.image-container')).length;
                const newImages = selectedFiles.length;

                if (existingImages === 0 && newImages === 0) {
                    e.preventDefault();

                    Swal.fire({
                        icon: 'error',
                        title: 'Proof of Activity Required!',
                        text: 'Please upload at least one image before saving.',
                        confirmButtonText: 'OK',
                        allowOutsideClick: false,
                        allowEscapeKey: false
                    });

                    return;
                }

                updateFileInput();

                const now = new Date();
                const hours = now.getHours().toString().padStart(2, '0');
                const minutes = now.getMinutes().toString().padStart(2, '0');
                const seconds = now.getSeconds().toString().padStart(2, '0');
                const timeStamp = now.toISOString().split('T')[0] + ' ' + hours + ':' + minutes + ':' + seconds;

                timeUploadField.value = timeStamp;
            });
        });
    </script>

    <script>
        function getGeolocation(callback) {
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(function(position) {
                    callback(position.coords.latitude, position.coords.longitude);
                }, function(error) {
                    console.error("Error getting geolocation: ", error);
                    callback(null, null);
                });
            } else {
                console.error("Geolocation is not supported by this browser.");
                callback(null, null);
            }
        }

        function confirmLogout() {
            Swal.fire({
                title: 'Are you sure?',
                text: "You will be logged out!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, logout!',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({
                        title: 'Final Check',
                        text: "Have you finished all your work for today?",
                        icon: 'question',
                        showCancelButton: true,
                        confirmButtonColor: '#3085d6',
                        cancelButtonColor: '#d33',
                        confirmButtonText: 'Yes, I am done!',
                        cancelButtonText: 'No, let me finish'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            getGeolocation(function(latitude, longitude) {
                                if (latitude && longitude) {
                                    window.location.href = 'logout.php?latitude=' + latitude + '&longitude=' + longitude;
                                } else {
                                    window.location.href = 'logout.php';
                                }
                            });
                        }
                    });
                }
            });
        }

        function confirmBreak() {
            Swal.fire({
                title: 'Are you sure?',
                text: "You will take a break!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, break!',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    getGeolocation(function(latitude, longitude) {
                        if (latitude && longitude) {
                            window.location.href = 'break.php?latitude=' + latitude + '&longitude=' + longitude;
                        } else {
                            window.location.href = 'break.php';
                        }
                    });
                }
            });
        }

        function checkTime() {
            var now = new Date();
            var hours = now.getHours();
            var minutes = now.getMinutes();
            var seconds = now.getSeconds();

            if (hours === 23 && minutes === 59 && seconds === 59) {
                var xhr = new XMLHttpRequest();
                xhr.open("GET", "logout.php", true);
                xhr.onreadystatechange = function () {
                    if (xhr.readyState === 4 && xhr.status === 200) {
                        window.location.href = "Halaman_login.php?error=session_expired";
                    }
                };
                xhr.send();
            }
        }

        setInterval(checkTime, 1000);
    </script>

</body>
</html>