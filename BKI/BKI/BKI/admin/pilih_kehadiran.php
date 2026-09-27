<?php
    session_start();
    include("koneksi.php");

    date_default_timezone_set('Asia/Jakarta');

    header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
    header("Cache-Control: post-check=0, pre-check=0", false);
    header("Pragma: no-cache");
    header("Expires: 0");

    if (!isset($_SESSION['user_id']) || !isset($_SESSION['username']) || !isset($_SESSION['role'])) {
        header("Location: Halaman_login.php");
        exit;
    }

    if ($_SESSION['role'] !== 'User') {
        header("Location: dashboard.php");
        exit;
    }

    $user_id = (int) $_SESSION['user_id'];
    $today = date('Y-m-d');

    $check_attendance_query = "
        SELECT id
        FROM time
        WHERE user_id = $user_id
        AND tanggal = '$today'
        LIMIT 1
    ";

    $check_attendance_result = mysqli_query($koneksi, $check_attendance_query);

    if ($check_attendance_result && mysqli_num_rows($check_attendance_result) > 0 && !isset($_SESSION['attendance_alert'])) {
        header("Location: dashboard.php");
        exit;
    }

    $error = isset($_GET['error']) ? $_GET['error'] : '';
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
    <title>BKI - Select Attendance</title>
    <link href="img/logo.png" rel="icon">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,300;0,400;0,500;0,600;1,400;1,500;1,600" rel="stylesheet">

    <link rel="stylesheet" type="text/css" href="../../../app-assets/vendors/css/vendors.min.css">
    <link rel="stylesheet" type="text/css" href="../../../app-assets/css/bootstrap.css">
    <link rel="stylesheet" type="text/css" href="../../../app-assets/css/bootstrap-extended.css">
    <link rel="stylesheet" type="text/css" href="../../../app-assets/css/colors.css">
    <link rel="stylesheet" type="text/css" href="../../../app-assets/css/components.css">
    <link rel="stylesheet" type="text/css" href="../../../app-assets/css/themes/semi-dark-layout.css">
    <link rel="stylesheet" type="text/css" href="../../../app-assets/css/core/menu/menu-types/vertical-menu.css">
    <link rel="stylesheet" type="text/css" href="../../../assets/css/style.css">

    <style>
        .btn-primary {
            background: linear-gradient(135deg, #2A629A, #003285);
            color: #FFF;
            border: none;
            padding: 12px 24px;
            text-decoration: none;
        }
        .option-card {
            border: 1px solid #d9d9d9;
            border-radius: 10px;
            padding: 12px;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        .option-card:hover {
            border-color: #003285;
            box-shadow: 0 4px 14px rgba(0, 50, 133, 0.15);
        }
        .option-card.active {
            border-color: #003285;
            background: #f5f8ff;
        }
        .hidden-section {
            display: none;
        }
        .note-danger {
            color: #dc3545;
            font-size: 12px;
            margin-top: 6px;
            display: block;
        }
    </style>
</head>

<body class="vertical-layout vertical-menu-modern blank-page navbar-floating footer-static" data-open="click" data-menu="vertical-menu-modern" data-col="blank-page">
    <div class="app-content content">
        <div class="content-overlay"></div>
        <div class="header-navbar-shadow"></div>
        <div class="content-wrapper">
            <div class="content-body"></div>
        </div>
    </div>

    <div class="modal fade" id="attendanceModal" tabindex="-1" aria-labelledby="attendanceModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="attendanceModalLabel">Select Attendance Status</h5>
                </div>
                <div class="modal-body">
                    <?php if ($error === 'invalid_date'): ?>
                        <div class="alert alert-danger">Invalid date range. End Date must be the same as or after Start Date.</div>
                    <?php elseif ($error === 'overlap'): ?>
                        <div class="alert alert-danger">The selected absence date range overlaps with an existing record.</div>
                    <?php elseif ($error === 'invalid_file'): ?>
                        <div class="alert alert-danger">Evidence must be JPG, JPEG, PNG, or PDF.</div>
                    <?php elseif ($error === 'file_too_large'): ?>
                        <div class="alert alert-danger">Evidence file is too large. Maximum allowed size is 3MB.</div>
                    <?php elseif ($error === 'upload_failed'): ?>
                        <div class="alert alert-danger">Evidence upload failed. Please try again.</div>
                    <?php endif; ?>

                    <form action="proses_kehadiran.php" method="POST" enctype="multipart/form-data" id="attendanceForm">
                        <input type="hidden" name="latitude" id="latitude" value="<?php echo htmlspecialchars($_SESSION['login_latitude'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                        <input type="hidden" name="longitude" id="longitude" value="<?php echo htmlspecialchars($_SESSION['login_longitude'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">

                        <div class="row g-1 mb-2">
                            <div class="col-md-4">
                                <label class="option-card active w-100" id="card-masuk">
                                    <input type="radio" name="attendance_type" value="masuk" checked class="d-none">
                                    <div class="fw-bolder">Check In</div>
                                </label>
                            </div>
                            <div class="col-md-4">
                                <label class="option-card w-100" id="card-sakit">
                                    <input type="radio" name="attendance_type" value="Sick" class="d-none">
                                    <div class="fw-bolder">Sick</div>
                                </label>
                            </div>
                            <div class="col-md-4">
                                <label class="option-card w-100" id="card-cuti">
                                    <input type="radio" name="attendance_type" value="Permission/Leave" class="d-none">
                                    <div class="fw-bolder">Permission/Leave</div>
                                </label>
                            </div>
                        </div>

                        <div id="timeOffFields" class="hidden-section">
                            <div class="row">
                                <div class="col-md-6 mb-1">
                                    <label for="start_date" class="form-label">Start Date<span class="text-danger">*</span></label>
                                    <input type="date" name="start_date" id="start_date" class="form-control">
                                </div>
                                <div class="col-md-6 mb-1">
                                    <label for="end_date" class="form-label">End Date<span class="text-danger">*</span></label>
                                    <input type="date" name="end_date" id="end_date" class="form-control">
                                </div>
                            </div>

                            <div class="mb-1">
                                <label for="evidence" class="form-label">Evidence (JPG/JPEG/PNG/PDF)<span class="text-danger">*</span></label>
                                <input type="file" name="evidence" id="evidence" class="form-control" accept=".jpg,.jpeg,.png,.pdf,application/pdf">
                                <span class="note-danger">Maximum file size: 3MB.</span>
                            </div>

                            <div class="mb-1">
                                <label for="description" class="form-label">Description</label>
                                <textarea name="description" id="description" rows="4" class="form-control" placeholder="Write additional notes (optional)..."></textarea>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end mt-2">
                            <button type="submit" class="btn btn-primary">Submit</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="../../../app-assets/vendors/js/vendors.min.js"></script>
    
    <script src="../../../app-assets/js/core/app-menu.js"></script>
    <script src="../../../app-assets/js/core/app.js"></script>
    
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        function setActiveCard(value) {
            const cards = {
                masuk: document.getElementById('card-masuk'),
                sakit: document.getElementById('card-sakit'),
                permission_leave: document.getElementById('card-cuti')
            };

            Object.keys(cards).forEach(function(key) {
                cards[key].classList.remove('active');
            });

            if (value === 'Sick') {
                cards.sakit.classList.add('active');
            } else if (value === 'Permission/Leave') {
                cards.permission_leave.classList.add('active');
            } else {
                cards.masuk.classList.add('active');
            }
        }

        function toggleTimeOffFields(value) {
            const section = document.getElementById('timeOffFields');
            const startDate = document.getElementById('start_date');
            const endDate = document.getElementById('end_date');
            const evidence = document.getElementById('evidence');
            const description = document.getElementById('description');
            const isTimeOff = value === 'Sick' || value === 'Permission/Leave';

            section.style.display = isTimeOff ? 'block' : 'none';

            startDate.required = isTimeOff;
            endDate.required = isTimeOff;
            evidence.required = isTimeOff;
            description.required = false;

            if (!isTimeOff) {
                startDate.value = '';
                endDate.value = '';
                evidence.value = '';
                description.value = '';
                endDate.min = today;
            }
        }

        const startDate = document.getElementById('start_date');
        const endDate = document.getElementById('end_date');
        const today = '<?php echo date('Y-m-d'); ?>';

        startDate.min = today;
        endDate.min = today;

        startDate.addEventListener('change', function() {
            endDate.min = this.value || today;

            if (endDate.value && endDate.value < this.value) {
                endDate.value = '';
            }
        });

        document.querySelectorAll('input[name="attendance_type"]').forEach(function(input) {
            input.addEventListener('change', function() {
                setActiveCard(this.value);
                toggleTimeOffFields(this.value);
            });
        });

        document.getElementById('attendanceForm').addEventListener('submit', function(e) {
            const selectedInput = document.querySelector('input[name="attendance_type"]:checked');
            const selected = selectedInput ? selectedInput.value : 'masuk';

            if (selected === 'masuk') {
                return;
            }

            if (selected === 'Sick' || selected === 'Permission/Leave') {
                const startValue = document.getElementById('start_date').value;
                const endValue = document.getElementById('end_date').value;
                const fileInput = document.getElementById('evidence');

                if (!startValue || !endValue || !fileInput.files.length) {
                    return;
                }

                const startDateValue = new Date(startValue);
                const endDateValue = new Date(endValue);

                if (endDateValue < startDateValue) {
                    e.preventDefault();
                    alert('End Date must be the same as or after Start Date.');
                    return;
                }

                const file = fileInput.files[0];
                const maxSize = 3 * 1024 * 1024;
                const allowedMimeTypes = ['image/jpeg', 'image/png', 'application/pdf'];

                if (file.size > maxSize) {
                    e.preventDefault();
                    alert('Evidence file is too large. Maximum allowed size is 3MB.');
                    return;
                }

                if (!allowedMimeTypes.includes(file.type)) {
                    e.preventDefault();
                    alert('Evidence must be JPG, JPEG, PNG, or PDF.');
                    return;
                }
            }
        });

        toggleTimeOffFields('masuk');

        $(document).ready(function() {
            $('#attendanceModal').modal('show');
        });

        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(function(position) {
                document.getElementById('latitude').value = position.coords.latitude;
                document.getElementById('longitude').value = position.coords.longitude;
            });
        }

        if (window.history && window.history.pushState) {
            window.history.pushState(null, '', window.location.href);
            window.addEventListener('popstate', function() {
                window.history.pushState(null, '', window.location.href);
            });
        }
    </script>

    <?php if (isset($_SESSION['attendance_alert'])): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const attendanceAlert = <?php echo json_encode($_SESSION['attendance_alert'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>;

            Swal.fire({
                icon: attendanceAlert.icon || 'info',
                title: attendanceAlert.title || '',
                text: attendanceAlert.text || '',
                confirmButtonText: 'OK',
                allowOutsideClick: false,
                allowEscapeKey: false
            }).then(function(result) {
                if (result.isConfirmed && attendanceAlert.logout_after_alert) {
                    const logoutForm = document.createElement('form');
                    logoutForm.method = 'POST';
                    logoutForm.action = 'proses_kehadiran.php';

                    const logoutInput = document.createElement('input');
                    logoutInput.type = 'hidden';
                    logoutInput.name = 'logout_after_attendance';
                    logoutInput.value = '1';

                    logoutForm.appendChild(logoutInput);
                    document.body.appendChild(logoutForm);
                    logoutForm.submit();
                    return;
                }

                if (result.isConfirmed && attendanceAlert.redirect) {
                    window.location.href = attendanceAlert.redirect;
                }
            });
        });
    </script>
    <?php unset($_SESSION['attendance_alert']); ?>
    <?php endif; ?>
    
</body>
</html>
