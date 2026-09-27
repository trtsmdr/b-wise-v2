<?php
    session_start();
    include("koneksi.php");

    date_default_timezone_set('Asia/Jakarta');

    header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
    header("Cache-Control: post-check=0, pre-check=0", false);
    header("Pragma: no-cache");
    header("Expires: 0");

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

        if (isset($_SESSION['roles']) && count($_SESSION['roles']) > 1) {
            header("Location: pilih_role.php");
            exit;
        }
    }

    $error = isset($_GET['error']) ? $_GET['error'] : '';
    $success = isset($_GET['success']) ? $_GET['success'] : '';
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
    <title>B - WISE</title>
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
    <link rel="stylesheet" type="text/css" href="../../../app-assets/css/themes/semi-dark-layout.css">
    <!-- END: Theme CSS -->

    <!-- BEGIN: Page CSS -->
    <link rel="stylesheet" type="text/css" href="../../../app-assets/css/core/menu/menu-types/vertical-menu.css">
    <link rel="stylesheet" type="text/css" href="../../../app-assets/css/plugins/forms/form-validation.css">
    <link rel="stylesheet" type="text/css" href="../../../app-assets/css/pages/page-auth.css">
    <!-- END: Page CSS -->

    <!-- BEGIN: Custom CSS -->
    <link rel="stylesheet" type="text/css" href="../../../assets/css/style.css">
    <!-- END: Custom CSS -->

    <style>
        .btn-primary {
            background: linear-gradient(135deg, #2A629A, #003285);
            color: #FFF;
            border: none;
            padding: 12px 24px;
            text-decoration: none;
        }
    </style>
</head>

<body class="vertical-layout vertical-menu-modern blank-page navbar-floating footer-static" data-open="click" data-menu="vertical-menu-modern" data-col="blank-page">
    
    <!-- BEGIN: Content -->
    <div class="app-content content ">
        <div class="content-overlay"></div>
        <div class="header-navbar-shadow"></div>
        <div class="content-wrapper">
            <div class="content-header row"></div>
            <div class="content-body">
                <div class="auth-wrapper auth-v1 px-2">
                    <div class="auth-inner py-2">
                        <div class="card mb-0">
                            <div class="card-body">
                                <a href="#" class="brand-logo">
                                    <img src="img/logo.png" width="250px" alt="" style="margin-top: 5px; margin-bottom: 20px;">
                                </a>
                                <?php if ($error === 'inactive'): ?>
                                    <div class="alert alert-danger">
                                        Your account is inactive. Please contact the IT team.
                                    </div>
                                <?php elseif ($error === 'invalid'): ?>
                                    <div class="alert alert-danger">
                                        Invalid username or password.
                                    </div>
                                <?php elseif ($error === 'session_expired'): ?>
                                    <div class="alert alert-warning">
                                        Session expired. Please login again.
                                    </div>
                                <?php endif; ?>

                                <?php if ($success === 'time_off_submitted'): ?>
                                    <div class="alert alert-success">
                                        Your application has been submitted successfully.
                                    </div>
                                <?php endif; ?>

                                <form class="auth-login-form mt-2" action="proses_login.php" method="POST">
                                    <div class="mb-1">
                                        <label for="username" class="form-label">Username</label>
                                        <input type="text" class="form-control" id="username" name="username" placeholder="Username" aria-describedby="username" tabindex="1" autofocus />
                                    </div>
                                    <div class="mb-1">
                                        <label for="password" class="form-label">Password</label>
                                        <div class="input-group input-group-merge form-password-toggle">
                                            <input type="password" class="form-control form-control-merge" id="password" name="password" tabindex="2" placeholder="Password" aria-describedby="username" />
                                            <span class="input-group-text cursor-pointer"><i data-feather="eye"></i></span>
                                        </div>
                                    </div>
                                    <input type="hidden" name="latitude" id="latitude">
                                    <input type="hidden" name="longitude" id="longitude">

                                    <center>
                                    <button class="btn btn-primary_1 w-75" tabindex="4" style="margin-top: 20px;">Login</button>
                                    </center>
                                
                                </form>
                            </div>
                        </div>
                    </div>
                </div>                    
            </div>
        </div>
    </div>
    <!-- END: Content -->

    <!-- BEGIN: Vendor JS -->
    <script src="../../../app-assets/vendors/js/vendors.min.js"></script>
    <!-- END: Vendor JS -->

    <!-- BEGIN: Page Vendor JS -->
    <script src="../../../app-assets/vendors/js/forms/validation/jquery.validate.min.js"></script>
    <!-- END: Page Vendor JS -->

    <!-- BEGIN: Theme JS -->
    <script src="../../../app-assets/js/core/app-menu.js"></script>
    <script src="../../../app-assets/js/core/app.js"></script>
    <!-- END: Theme JS -->

    <!-- BEGIN: Page JS -->
    <script src="../../../app-assets/js/scripts/pages/page-auth-login.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- END: Page JS -->

    <script>
        $(window).on('load', function() {
            if (feather) {
                feather.replace({
                    width: 14,
                    height: 14
                });
            }
        })

        const loginForm = document.querySelector('.auth-login-form');
        const latitudeInput = document.getElementById('latitude');
        const longitudeInput = document.getElementById('longitude');

        let locationAllowed = false;

        function getLocation() {
            if (!navigator.geolocation) {
                locationAllowed = false;
                return;
            }

            navigator.geolocation.getCurrentPosition(
                function(position) {
                    latitudeInput.value = position.coords.latitude;
                    longitudeInput.value = position.coords.longitude;
                    locationAllowed = true;
                },
                function(error) {
                    locationAllowed = false;
                    latitudeInput.value = '';
                    longitudeInput.value = '';
                },
                {
                    enableHighAccuracy: true,
                    timeout: 10000,
                    maximumAge: 0
                }
            );
        }

        getLocation();

        loginForm.addEventListener('submit', function(e) {
            if (!latitudeInput.value || !longitudeInput.value || !locationAllowed) {
                e.preventDefault();

                Swal.fire({
                    icon: 'warning',
                    title: 'Location Required',
                    text: 'Please enable your location permission before logging in.',
                    confirmButtonText: 'OK',
                    allowOutsideClick: false
                }).then(function() {
                    getLocation();
                });

                return;
            }
        });
    </script>
    
</body>
</html>