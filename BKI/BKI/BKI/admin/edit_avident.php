<?php
    session_start();
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

    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $tanggal = htmlspecialchars($_POST['tanggal']);
        $user_id = htmlspecialchars($_POST['user_id']);
        $deskripsi = htmlspecialchars($_POST['deskripsi']);
        $time_upload_avident = htmlspecialchars($_POST['time_upload_avident']);

        $uploaded_files = [];
        $upload_dir = 'img/';

        // foreach ($_FILES['gambar']['name'] as $key => $name) {
        //     $tmp_name = $_FILES['gambar']['tmp_name'][$key];
            
        //     if (!empty($tmp_name)) {
        //         $file_name = time() . '_' . basename($name);
        //         $target_file = $upload_dir . $file_name;
            
        //         $check = getimagesize($tmp_name);
        //         if ($check !== false) {
        //             if (move_uploaded_file($tmp_name, $target_file)) {
        //                 $uploaded_files[] = $file_name;
        //             } else {
        //                 echo "<script>alert('File upload failed for " . htmlspecialchars($name) . "');</script>";
        //             }
        //         } else {
        //             echo "<script>alert('File " . htmlspecialchars($name) . " is not an image');</script>";
        //         }
        //     }
        // }
        
        foreach ($_FILES['gambar']['name'] as $key => $name) {
            $tmp_name = $_FILES['gambar']['tmp_name'][$key];
            $file_size = $_FILES['gambar']['size'][$key];
            $file_error = $_FILES['gambar']['error'][$key];
        
            if (!empty($tmp_name) && $file_error === UPLOAD_ERR_OK) {
        
                // Maksimal 2 MB per file
                if ($file_size > 2 * 1024 * 1024) {
                    echo "<script>
                        alert('File " . htmlspecialchars($name) . " melebihi ukuran maksimal 2 MB.');
                    </script>";
                    continue;
                }
        
                // Cek MIME type file
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mime = finfo_file($finfo, $tmp_name);
                finfo_close($finfo);
        
                // Format yang diperbolehkan
                $allowed_types = [
                    'image/jpeg',
                    'image/png'
                ];
        
                if (!in_array($mime, $allowed_types)) {
                    echo "<script>
                        alert('File " . htmlspecialchars($name) . " tidak didukung. Gunakan PNG, JPG, atau JPEG.');
                    </script>";
                    continue;
                }
        
                // Pastikan file benar-benar gambar
                $check = getimagesize($tmp_name);
        
                if ($check === false) {
                    echo "<script>
                        alert('File " . htmlspecialchars($name) . " bukan gambar yang valid.');
                    </script>";
                    continue;
                }
        
                // Ambil ekstensi file
                $extension = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        
                // Buat nama file baru
                $file_name = time() . '_' . $key . '.' . $extension;
                $target_file = $upload_dir . $file_name;
        
                if (move_uploaded_file($tmp_name, $target_file)) {
                    $uploaded_files[] = $file_name;
                } else {
                    echo "<script>
                        alert('Gagal mengupload file " . htmlspecialchars($name) . "');
                    </script>";
                }
            }
        }

        $existing_files = array_filter(explode(',', $row['gambar']));
        $existing_files = array_map('trim', $existing_files);
        
        $images_to_delete = array_map('trim', explode(',', $_POST['images_to_delete']));
        $remaining_images = array_diff($existing_files, $images_to_delete);
        
        $gambar = implode(',', array_merge($remaining_images, $uploaded_files));
        
        $status = empty($gambar) ? 'On-progress' : 'Completed';
        
        $data = [
            'id'        => $id,
            'gambar'    => $gambar,
            'tanggal'   => $tanggal,
            'user_id'   => $user_id,
            'deskripsi' => $deskripsi,
            'time_upload_avident' => $time_upload_avident,
            'status'    => $status
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
                                                        <!--<input type="file" class="form-control" id="gambar" name="gambar[]" multiple />-->
                                                        <input type="file"
                                                           class="form-control"
                                                           id="gambar"
                                                           name="gambar[]"
                                                           multiple
                                                           accept=".png,.jpg,.jpeg,image/png,image/jpeg">
                                                    
                                                        <small class="text-muted">
                                                            Maximum file size of 2 MB per file. Supported formats: PNG, JPG, and JPEG.
                                                        </small>
                                                        
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

    <script>
        $(window).on('load', function() {
            if (feather) {
                feather.replace({
                    width: 14,
                    height: 14
                });
            }
        })

        document.getElementById('avidentForm').addEventListener('submit', function() {
            var now   = new Date();
            var hours = now.getHours().toString().padStart(2, '0');
            var minutes = now.getMinutes().toString().padStart(2, '0');
            var seconds = now.getSeconds().toString().padStart(2, '0');
            var timeStamp = now.toISOString().split('T')[0] + ' ' + hours + ':' + minutes + ':' + seconds;
            document.getElementById('time_upload_avident').value = timeStamp;
        });

        document.addEventListener('DOMContentLoaded', function() {
            const imageToDeleteField = document.getElementById('images_to_delete');

        document.getElementById('gambar').addEventListener('change', function(event) {
            const input = event.target;
            const previewContainer = document.getElementById('image-preview');
        
            const maxSize = 2 * 1024 * 1024; // 2 MB
            const allowedTypes = ['image/jpeg', 'image/png'];
        
            let validFiles = [];
            let rejectedFiles = [];
        
            Array.from(input.files).forEach(file => {
        
                // Cek ukuran file
                if (file.size > maxSize) {
                    rejectedFiles.push(
                        file.name + ' (lebih dari 2 MB)'
                    );
                    return;
                }
        
                // Cek format file
                if (!allowedTypes.includes(file.type)) {
                    rejectedFiles.push(
                        file.name + ' (format tidak didukung)'
                    );
                    return;
                }
        
                validFiles.push(file);
            });
        
            // Jika ada file yang ditolak
            if (rejectedFiles.length > 0) {
                Swal.fire({
                    icon: 'warning',
                    title: 'File tidak dapat diupload',
                    html: rejectedFiles.join('<br>'),
                    confirmButtonText: 'OK'
                });
            }
        
            // Set hanya file yang valid
            input.files = new FileListItems(validFiles);
        
            // Preview hanya file valid
            validFiles.forEach(file => {
                const reader = new FileReader();
        
                reader.onload = function(e) {
                    const previewImage = document.createElement('div');
                    previewImage.className = 'preview-image';
        
                    previewImage.innerHTML = `
                        <img src="${e.target.result}" />
                        <button type="button"
                                class="btn btn-danger btn-sm delete-preview-image">
                            X
                        </button>
                    `;
        
                    previewContainer.appendChild(previewImage);
        
                    previewImage
                        .querySelector('.delete-preview-image')
                        .addEventListener('click', function() {
        
                            const currentFiles = Array.from(input.files);
        
                            const index = Array.from(
                                previewContainer.querySelectorAll('.preview-image')
                            ).indexOf(previewImage);
        
                            currentFiles.splice(index, 1);
        
                            input.files = new FileListItems(currentFiles);
        
                            previewImage.remove();
                        });
                };
        
                reader.readAsDataURL(file);
            });
        });

        function FileListItems(files) {
            const b = new ClipboardEvent("").clipboardData || new DataTransfer()
            for (let i = 0, len = files.length; i < len; i++) b.items.add(files[i])
            return b.files
        }

        document.querySelectorAll('.delete-image').forEach(button => {
            button.addEventListener('click', function() {
                const image = this.dataset.image;
                const imagesToDelete = imageToDeleteField.value ? imageToDeleteField.value.split(',') : [];
                if (!imagesToDelete.includes(image)) {
                    imagesToDelete.push(image);
                }
                imageToDeleteField.value = imagesToDelete.join(',');

                const container = this.closest('.image-container');
                container.parentNode.removeChild(container);
            });
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