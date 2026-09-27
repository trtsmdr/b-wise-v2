<?php
    session_start();
    include("koneksi.php");

    date_default_timezone_set('Asia/Jakarta');

    header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
    header("Cache-Control: post-check=0, pre-check=0", false);
    header("Pragma: no-cache");
    header("Expires: 0");

    if (!isset($_SESSION['username']) || !isset($_SESSION['user_id'])) {
        header("Location: Halaman_login.php");
        exit;
    }

    if (!isset($_SESSION['roles']) || !is_array($_SESSION['roles']) || count($_SESSION['roles']) <= 1) {
        if (isset($_SESSION['role']) && $_SESSION['role'] === 'User') {
            header("Location: pilih_kehadiran.php");
        } else {
            header("Location: dashboard.php");
        }
        exit;
    }

    if (isset($_SESSION['role']) && $_SESSION['role'] !== '') {
        if ($_SESSION['role'] === 'User') {
            header("Location: pilih_kehadiran.php");
        } else {
            header("Location: dashboard.php");
        }
        exit;
    }

    $roles = $_SESSION['roles'];
?>

<!DOCTYPE html>

<html class="loading semi-dark-layout" lang="en" data-layout="semi-dark-layout" data-textdirection="ltr">

<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width,initial-scale=1.0,user-scalable=0,minimal-ui">
    <meta name="description" content="Vuexy admin is super flexible, powerful, clean &amp; modern responsive bootstrap 4 admin template with unlimited possibilities.">
    <meta name="keywords" content="admin template, Vuexy admin template, dashboard template, flat admin template, responsive admin template, web app">
    <meta name="author" content="PIXINVENT">
    <title>Select Role</title>
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

<body>

    <!-- BEGIN: Content -->
    <div class="modal fade" id="roleModal" tabindex="-1" aria-labelledby="roleModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="roleModalLabel">Select Role</h5>
                </div>
                <div class="modal-body">
                    <form action="set_role.php" method="post">
                        <div class="form-group">
                            <label for="role">Select a role to login:</label>
                            <select class="form-control" name="role" id="role">
                                <?php foreach ($roles as $role): ?>
                                    <option value="<?php echo $role; ?>"><?php echo $role; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="d-flex justify-content-end mt-2">
                            <button type="submit" class="btn btn-primary">Continue</button>
                        </div>
                    </form>
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
    <!-- END: Page JS -->

    <script>
        $(document).ready(function() {
            $('#roleModal').modal('show');
        });
    </script>

    <script>
        if (window.history && window.history.pushState) {
            window.history.pushState(null, '', window.location.href);
            window.addEventListener('popstate', function() {
                window.history.pushState(null, '', window.location.href);
            });
        }
    </script>

</body>
</html>