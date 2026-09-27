<?php
    session_start();

    date_default_timezone_set('Asia/Jakarta');

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

    $result = mysqli_query($koneksi, "SELECT id, nup, nama, divisi FROM users WHERE status = 'Active'");

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

    if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $tanggal = date('Y-m-d');

    if (is_user()) {
        $user_id = (int) $_SESSION['user_id'];
    } else {
        $user_id = isset($_POST['user_id']) ? (int) $_POST['user_id'] : 0;
    }

    $deskripsi = htmlspecialchars($_POST['deskripsi']);

        $time_upload_activity_planning = date('Y-m-d H:i:s');

        $history_update = htmlspecialchars($_POST['history_update']);

        $status = 'On-progress';

        $gambar = '';

    if (isset($_FILES['gambar']) && $_FILES['gambar']['error'] == UPLOAD_ERR_OK) {
        $target_dir  = "img/";
        $target_file = $target_dir . basename($_FILES["gambar"]["name"]);
        
        if (move_uploaded_file($_FILES["gambar"]["tmp_name"], $target_file)) {
            $gambar  = basename($_FILES["gambar"]["name"]);
            $status  = 'Completed';
        } else {
            error_log('Failed to move uploaded file');
        }
    }
    
    $data = [
        'tanggal'   => $tanggal,
        'user_id'   => $user_id,
        'deskripsi' => $deskripsi,
        'gambar'    => $gambar,
        'time_upload_activity_planning' => $time_upload_activity_planning,
        'status'    => $status,
        'history_update' => $history_update
    ];

    if (isset($_POST['tambah_planning'])) {
        if (tambah_planning($data) > 0) {
            echo "<script>
                window.onload = function() {
                    Swal.fire({
                        icon: 'success',
                        title: 'Planning successfully added!',
                        showConfirmButton: false,
                        timer: 1500
                    }).then(() => {
                        document.location.href = 'planning.php';
                    });
                }
            </script>";
        } else {
            echo "<script>
                window.onload = function() {
                    Swal.fire({
                        icon: 'error',
                        title: 'Planning failed to add!',
                        showConfirmButton: false,
                        timer: 1500
                    });
                }
            </script>";
            }
        }
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
    <title>BKI - Planning</title>
    <link href="img/logo.png" rel="icon">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,300;0,400;0,500;0,600;1,400;1,500;1,600" rel="stylesheet">

    <link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css" rel="stylesheet" />

    <!-- BEGIN: Vendor CSS -->
    <link rel="stylesheet" type="text/css" href="../../../app-assets/vendors/css/vendors.min.css">
    <!-- END: Vendor CSS -->

    <!-- BEGIN: Theme CSS -->
    <link rel="stylesheet" type="text/css" href="../../../app-assets/css/bootstrap.css">
    <link rel="stylesheet" type="text/css" href="../../../app-assets/css/bootstrap-extended.css">
    <link rel="stylesheet" type="text/css" href="../../../app-assets/css/colors.css">
    <link rel="stylesheet" type="text/css" href="../../../app-assets/css/components.css">
    <link rel="stylesheet" type="text/css" href="../../../app-assets/css/themes/semi-dark-layout.css">
    <!-- END: Theme CSS -->

    <!-- BEGIN: Page CSS -->
    <link rel="stylesheet" type="text/css" href="../../../app-assets/css/core/menu/menu-types/vertical-menu.css">
    <!-- END: Page CSS -->

    <style>
        .btn-primary {
            background: linear-gradient(135deg, #AFC8AD, #88AB8E);
            color: #FFF;
            border: none;
            padding: 12px 24px;
            text-decoration: none;
        }
        .select2-container--default.select2-container--open .select2-selection--single {
            border-color: #003285 !important;
        }
        .select2-container--default .select2-selection--single {
            border-color: #CED4DA;
            transition: border-color 0.1s ease;
        }
        .select2-container--default .select2-selection--single.item-selected {
            border-color: #003285 !important;
        }
        .select2-container--default .select2-results__option--highlighted {
            background-color: #FF7F3E !important;
            color: #ffffff !important;
        }
        .select2-container--default .select2-selection--single .select2-selection__rendered {
            color: #333333;
        }
        .select2-container--default .select2-results__option[aria-selected="true"] {
            background: linear-gradient(135deg, #FFDA78, #FF7F3E);
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
                        <h2 class="brand-text" sytle="font-size: 20px;">B - WISE</h2>
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
                            <li class="active"><a class="d-flex align-items-center" href="planning.php"><i data-feather="circle"></i><span class="menu-item text-truncate" data-i18n="Planning">Planning</span></a>
                            </li>
                            <li><a class="d-flex align-items-center" href="avident.php"><i data-feather="circle"></i><span class="menu-item text-truncate" data-i18n="Avident">Evidence</span></a>
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
                        <h2 class="float-start mb-0">Form Planning</h2>
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
                                    <form id="planningForm" action="" method="POST" class="form form-vertical" enctype="multipart/form-data">
                                        <div class="row">
                                            <div class="col-6">
                                                <div class="mb-1">
                                                    <label for="tanggal" class="form-label">Date</label>
                                                    <input type="text" id="tanggal" name="tanggal" value="<?php echo date('Y-m-d'); ?>" class="form-control" readonly>
                                                </div>
                                                <div class="mb-1">
                                                <label for="user_id" class="form-label">User</label>
                                                <?php if (is_user()): ?>
                                                    <input type="text" id="user_name" value="<?php echo $nama; ?>" class="form-control" readonly>
                                                    <input type="hidden" id="user_id" name="user_id" value="<?php echo $_SESSION['user_id']; ?>">
                                                <?php else: ?>
                                                    <select class="form-control select2" id="user_id" name="user_id" required>
                                                        <option value="">Select User</option>
                                                        <?php while ($row = mysqli_fetch_assoc($result)): ?>
                                                            <option value="<?php echo $row['id']; ?>"><?php echo $row['nup']; ?> - <?php echo $row['nama']; ?> - <?php echo $row['divisi']; ?></option>
                                                        <?php endwhile; ?>
                                                    </select>
                                                <?php endif; ?>
                                                </div>
                                                </div>
                                                <div class="col-6">
                                                    <div class="mb-1">
                                                        <label for="deskripsi" class="form-label">Description</label>
                                                        <textarea id ="deskripsi" style="height:115px" class="form-control" name="deskripsi" required></textarea>
                                                    </div>
                                                </div>
                                                <!-- <div class="col-6">
                                                    <input type="hidden" id="time_upload_activity_planning" name="time_upload_activity_planning" />
                                                </div> -->
                                                <div class="col-6">
                                                    <input type="hidden" id="status" name="status" value="On-progress" />
                                                </div>
                                                <div class="col-6">
                                                    <input type="hidden" id="history_update" name="history_update" />
                                                </div>
                                                <div class="col-12">
                                                    <a href="planning.php" class="btn btn-outline-secondary">Back</a>
                                                    <button type="submit" name="tambah_planning" class="btn btn-primary_2 me-1">Save</button>
                                                </div>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </section>
                    </div>
                </div>
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
    <script src="https://code.jquery.com/jquery-3.3.1.slim.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.10/js/select2.min.js"></script>

    <script>
        $(window).on('load', function() {
            if (feather) {
                feather.replace({
                    width: 14,
                    height: 14
                });
            }
        })

        // document.addEventListener('DOMContentLoaded', function() {
        //     var now = new Date();
        //     var hours   = now.getHours().toString().padStart(2, '0');
        //     var minutes = now.getMinutes().toString().padStart(2, '0');
        //     var seconds = now.getSeconds().toString().padStart(2, '0');
        //     var timeStamp = now.toISOString().split('T')[0] + ' ' + hours + ':' + minutes + ':' + seconds;
            
        //     document.getElementById('time_upload_activity_planning').value = timeStamp;
        // });

        $(document).ready(function() {
            $('#user_id').select2({
                placeholder: 'Select User',
                dropdownParent: $('body'),
                minimumInputLength: 1,
                width: '100%'
            });

            function sortSelectOptions() {
                var $select  = $('#user_id');
                var $options = $select.find('option');

                $options.sort(function(a, b) {
                    return $(a).text().localeCompare($(b).text());
                });

                $select.empty().append($options);
            }

            sortSelectOptions();

        $('#user_id').on('select2:select', function() {
            $('.select2-selection--single').addClass('item-selected');
        });

        $(document).on('click', function(e) {
            if (!$(e.target).closest('.select2-container').length) {
                $('.select2-selection--single').removeClass('item-selected');
            }
        });

        $('#user_id').on('select2:open', function() {
            $('.select2-selection--single').removeClass('item-selected');
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