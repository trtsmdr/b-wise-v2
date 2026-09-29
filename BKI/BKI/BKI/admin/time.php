<?php
    session_start();
    if (!isset($_SESSION['username'])) {
        header("location: Halaman_login.php");
        exit;
    }

    $nama  = $_SESSION['nama'];
    $role  = $_SESSION['role'];
    $image = $_SESSION['image'];

    function is_user() {
        return $_SESSION['role'] === 'User';
    }

    function is_superadmin() {
        return $_SESSION['role'] === 'Super-Admin';
    }

    function is_admin() {
        return $_SESSION['role'] === 'Admin';
    }

    include("koneksi.php");

    $selected_month = (int)date('m');
    $selected_year = (int)date('Y');

    if ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET['search'])) {
        $selected_month = (int)($_GET['month'] ?? date('m'));
        $selected_year = (int)($_GET['year'] ?? date('Y'));
    }

    $period_start = sprintf('%04d-%02d-15', $selected_year, $selected_month);
    $period_end = date('Y-m-d', strtotime($period_start . ' +1 month'));
    $period_start_display = date('d M Y', strtotime($period_start));
    $period_end_display = date('d M Y', strtotime($period_end));

    $query = "
        SELECT p.id, p.tanggal, p.time_login, p.geotagging, p.before_break, p.geotagging_before_break, p.after_break, p.geotagging_after_break, p.time_logout, p.geotagging_logout,
        p.time_off_id, t.absence_type, t.start_date, t.end_date, t.evidence, t.description,
        u.nup, u.nama, u.divisi
        FROM time p
        JOIN users u ON p.user_id = u.id
        LEFT JOIN time_off t ON p.time_off_id = t.id
        WHERE u.status = 'active'
        AND p.tanggal >= '$period_start'
        AND p.tanggal <= '$period_end'
    ";

    if (is_user()) {
        $query .= " AND u.nama = '$nama'";
    }

    $query .= " ORDER BY p.tanggal DESC";

    $result = mysqli_query($koneksi, $query);

    date_default_timezone_set('Asia/Jakarta');
    $current_time = date('H:i:s');

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
    <title>BKI - Time</title>
    <link href="../../assets/img/logo.png" rel="icon">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,300;0,400;0,500;0,600;1,400;1,500;1,600" rel="stylesheet">

    <!-- BEGIN: Vendor CSS -->
    <link rel="stylesheet" type="text/css" href="../../../app-assets/vendors/css/vendors.min.css">
    <!-- END: Vendor CSS -->

    <!-- Favicons -->
    <link href="img/logo.png" rel="icon">

    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.7.1/dist/leaflet.css" />

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
        .active {
            color: green;
        }
        .inactive {
            color: red;
        }
        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        #searchInput:hover {
            border: 1px solid #003285 !important;
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
    <!-- END: Header -->

    <!-- BEGIN: Main Menu -->
    <div class="main-menu menu-fixed menu-dark menu-accordion menu-shadow" data-scroll-to-active="true">
        <div class="navbar-header">
            <ul class="nav navbar-nav flex-row">
                <li class="nav-item me-auto">
                    <a class="navbar-brand" href="#">
                        <h2 class="brand-text" style="font-size: 20px;">BKI</h2>
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
                            <li class="active"><a class="d-flex align-items-center" href="time.php"><i data-feather="circle"></i><span class="menu-item text-truncate" data-i18n="Employee Time">Time</span></a>
                            </li>
                            <li><a class="d-flex align-items-center" href="planning.php"><i data-feather="circle"></i><span class="menu-item text-truncate" data-i18n="Planning">Planning</span></a>
                            </li>
                            <li><a class="d-flex align-items-center" href="avident.php"><i data-feather="circle"></i><span class="menu-item text-truncate" data-i18n="Evidence">Evidence</span></a>
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
                            <h2 class="float-start mb-0">Employee Time</h2>
                    </div>
                </div>
            </div>
            <div class="content-body">
                <div class="row" id="table-hover-animation">
                    <div class="col-12">
                        <div class="d-flex justify-content-end align-items-center mb-1">
                            <form method="get" action="time.php" class="d-flex">
                                <div class="me-2">
                                    <label for="month" class="form-label d-none">Month</label>
                                    <select id="month" name="month" class="form-select">
                                        <?php
                                        $monthNames = [
                                            1 => 'January',
                                            2 => 'February',
                                            3 => 'March',
                                            4 => 'April',
                                            5 => 'May',
                                            6 => 'June',
                                            7 => 'July',
                                            8 => 'August',
                                            9 => 'September',
                                            10 => 'October',
                                            11 => 'November',
                                            12 => 'December'
                                        ];
                                        for ($m = 1; $m <= 12; $m++):
                                            $start = sprintf('%04d-%02d-15', $selected_year, $m);
                                            $end = date('Y-m-d', strtotime($start . ' +1 month'));
                                            $startLabel = date('d M', strtotime($start));
                                            $endLabel = date('d M', strtotime($end));
                                        ?>
                                            <option value="<?php echo $m; ?>" <?php echo $m == $selected_month ? 'selected' : ''; ?>><?php echo $monthNames[$m] . ' (' . $startLabel . ' - ' . $endLabel . ')'; ?></option>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                                <div class="me-2">
                                    <label for="year" class="form-label d-none">Year</label>
                                    <select id="year" name="year" class="form-select">
                                        <?php for ($y = date('Y'); $y >= 2000; $y--): ?>
                                            <option value="<?php echo $y; ?>" <?php echo $y == $selected_year ? 'selected' : ''; ?>>
                                                <?php echo $y; ?>
                                            </option>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                                <button type="submit" name="search" class="btn btn-primary">Search</button>
                            </form>
                        </div>
                        <div class="card">
                            <div class="card-header d-flex justify-content-between">
                                <div>
                                    <label for="entriesSelect">Show</label>
                                    <select id="entriesSelect" class="form-select form-select-sm" style="width: auto; display: inline-block;">
                                        <option value="10">10</option>
                                        <option value="25">25</option>
                                        <option value="50">50</option>
                                        <option value="100">100</option>
                                    </select>
                                    <label for="entriesSelect" class="ms-2">entries</label>
                                </div>
                                <div class="d-flex flex-column align-items-end">
                                    <input type="text" id="searchInput" placeholder="Search..." class="form-control search-input mb-2" style="width: 220px;" onkeyup="searchTable()">
                                    <div class="d-flex gap-2">
                                        <a href="export_time.php?type=monthly&month=<?php echo urlencode($selected_month); ?>&year=<?php echo urlencode($selected_year); ?>" class="btn btn-outline-secondary"><i data-feather="download"></i> Monthly Export</a>
                                        <button type="button" class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#periodExportModal"><i data-feather="download"></i> Period Export</button>
                                        <a href="export_time.php?type=all&year=<?php echo urlencode($selected_year); ?>" class="btn btn-outline-secondary"><i data-feather="download"></i> Export All</a>
                                    </div>
                                </div>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-hover-animation" style="min-width: 2200px;">
                                    <thead>
                                        <tr style="text-align: center;">
                                            <th style="min-width: 50px;">No.</th>
                                            <th style="min-width: 50px;">Date</th>
                                            <th style="min-width: 200px;">NUP</th>
                                            <th style="min-width: 350px;">Name</th>
                                            <th style="min-width: 150px;">Division</th>
                                            <th style="min-width: 180px;">Absence Type</th>
                                            <th style="min-width: 120px;">Absence Detail</th>
                                            <th style="min-width: 100px;">Login Time</th>
                                            <th style="min-width: 100px;">Before Break</th>
                                            <th style="min-width: 100px;">After Break</th>
                                            <th style="min-width: 100px;">Logout Time</th>
                                        </tr>
                                    </thead>
                                    <tbody id="tableBody" style="text-align: center;">
                                    <?php
                                        $i = 1;
                                        if ($result->num_rows > 0) {
                                            while ($row = $result->fetch_assoc()) {
                                                $is_time_off = !empty($row['time_off_id']);
                                                $time_logout_display = $row['time_logout'] ? htmlspecialchars($row['time_logout']) : date('H:i:s');

                                                $geotagging = htmlspecialchars($row['geotagging'] ?? '');
                                                $geotagging_array = explode(',', $geotagging);
                                                $latitude = $geotagging_array[0] ?? '';
                                                $longitude = $geotagging_array[1] ?? '';

                                                $geotagging_logout = htmlspecialchars($row['geotagging_logout'] ?? '');
                                                $geotagging_logout_array = explode(',', $geotagging_logout);
                                                $logout_latitude = $geotagging_logout_array[0] ?? '';
                                                $logout_longitude = $geotagging_logout_array[1] ?? '';

                                                $geotagging_before_break = htmlspecialchars($row['geotagging_before_break'] ?? '');
                                                $geotagging_before_break_array = explode(',', $geotagging_before_break);
                                                $before_break_latitude = $geotagging_before_break_array[0] ?? '';
                                                $before_break_longitude = $geotagging_before_break_array[1] ?? '';

                                                $geotagging_after_break = htmlspecialchars($row['geotagging_after_break'] ?? '');
                                                $geotagging_after_break_array = explode(',', $geotagging_after_break);
                                                $after_break_latitude = $geotagging_after_break_array[0] ?? '';
                                                $after_break_longitude = $geotagging_after_break_array[1] ?? '';
                                                
                                                $absence_label = '-';
                                                if (!empty($row['absence_type'])) {
                                                    $absence_label = htmlspecialchars($row['absence_type']);
                                                }

                                                $detail_html = '-';
                                                if ($is_time_off) {
                                                    $absence_type_attr = htmlspecialchars($row['absence_type'] ?? '-', ENT_QUOTES, 'UTF-8');
                                                    $start_date_attr = !empty($row['start_date']) ? date('d-m-Y', strtotime($row['start_date'])) : '-';
                                                    $end_date_attr = !empty($row['end_date']) ? date('d-m-Y', strtotime($row['end_date'])) : '-';
                                                    $description_attr = htmlspecialchars($row['description'] ?? '-', ENT_QUOTES, 'UTF-8');
                                                    if ($description_attr === '') {
                                                        $description_attr = '-';
                                                    }

                                                    $evidence_link_attr = '-';
                                                    $evidence_name_attr = '-';
                                                    if (!empty($row['evidence'])) {
                                                        $safe_evidence = htmlspecialchars($row['evidence'], ENT_QUOTES, 'UTF-8');
                                                        $evidence_link_attr = 'img/time_off/' . $safe_evidence;
                                                        $evidence_name_attr = $safe_evidence;
                                                    }

                                                    $detail_html = "
                                                        <button
                                                            type='button'
                                                            class='btn btn-sm btn-outline-primary view-detail-btn'
                                                            data-bs-toggle='modal'
                                                            data-bs-target='#timeOffDetailModal'
                                                            data-absence-type='{$absence_type_attr}'
                                                            data-start-date='{$start_date_attr}'
                                                            data-end-date='{$end_date_attr}'
                                                            data-evidence-link='{$evidence_link_attr}'
                                                            data-evidence-name='{$evidence_name_attr}'
                                                            data-description='{$description_attr}'
                                                            title='View detail'
                                                        >
                                                            <i class='fa fa-eye'></i>
                                                        </button>
                                                    ";
                                                }

                                                $logout_class = 'inactive';
                                                if (!$is_time_off && empty($row['time_logout'])) {
                                                    $logout_class = 'active';
                                                }

                                                $login_display = $is_time_off ? '-' : htmlspecialchars($row['time_login'] ?? '');
                                                $before_break_display = $is_time_off ? '-' : htmlspecialchars($row['before_break'] ?? '');
                                                $after_break_display = $is_time_off ? '-' : htmlspecialchars($row['after_break'] ?? '');
                                                echo "
                                                <tr>
                                                    <td>{$i}</td>
                                                    <td>" . date('d-m-Y', strtotime($row['tanggal'])) . "</td>
                                                    <td>" . htmlspecialchars($row['nup'] ?? '') . "</td>
                                                    <td>" . htmlspecialchars($row['nama'] ?? '') . "</td>
                                                    <td>" . htmlspecialchars($row['divisi'] ?? '') . "</td>
                                                    <td>{$absence_label}</td>
                                                    <td>{$detail_html}</td>
                                                    <td>{$login_display}";
                                                if (!$is_time_off && !empty($row['geotagging'])) {
                                                    echo "<a href='#' class='geotagging-link' data-bs-toggle='modal' data-bs-target='#mapModal' data-lat='{$latitude}' data-lng='{$longitude}' data-time='" . htmlspecialchars($row['time_login'] ?? '') . "' style='margin-left: 10px;'>
                                                            <i class='fa fa-map-location'></i>
                                                        </a>";
                                                }
                                                echo "</td>
                                                    <td>{$before_break_display}";
                                                if (!$is_time_off && !empty($row['geotagging_before_break'])) {
                                                    echo "<a href='#' class='geotagging-link' data-bs-toggle='modal' data-bs-target='#mapModal' data-lat='{$before_break_latitude}' data-lng='{$before_break_longitude}' data-time='" . htmlspecialchars($row['before_break'] ?? '') . "' style='margin-left: 10px;'>
                                                            <i class='fa fa-map-location'></i>
                                                        </a>";
                                                }
                                                echo "</td>
                                                    <td>{$after_break_display}";
                                                if (!$is_time_off && !empty($row['geotagging_after_break'])) {
                                                    echo "<a href='#' class='geotagging-link' data-bs-toggle='modal' data-bs-target='#mapModal' data-lat='{$after_break_latitude}' data-lng='{$after_break_longitude}' data-time='" . htmlspecialchars($row['after_break'] ?? '') . "' style='margin-left: 10px;'>
                                                            <i class='fa fa-map-location'></i>
                                                        </a>";
                                                }
                                                echo "</td>
                                                    <td id='logout-time-{$row['id']}' class='{$logout_class}'>";
                                                if ($is_time_off) {
                                                    echo "-";
                                                } elseif (!empty($row['time_logout'])) {
                                                    echo htmlspecialchars($row['time_logout']);
                                                }
                                                if (!$is_time_off && !empty($row['geotagging_logout'])) {
                                                    echo "<a href='#' class='geotagging-link' data-bs-toggle='modal' data-bs-target='#mapModal' data-lat='{$logout_latitude}' data-lng='{$logout_longitude}' data-time='" . htmlspecialchars($time_logout_display) . "' style='margin-left: 10px;'>
                                                            <i class='fa fa-map-location'></i>
                                                        </a>";
                                                }
                                                echo "</td>
                                                </tr>";

                                                $i++;
                                            }
                                        }
                                        ?>
                                    </tbody>
                                </table>
                            </div>
                                <div class="d-flex justify-content-between align-items-center mt-2 px-2">
                                    <div id="table-info" class="text-left"></div>
                                    <div class="pagination-container">
                                        <ul class="pagination">
                                        </ul>
                                    </div>
                                </div>
                        </div>
                    </div>
                </div>
                    <div class="modal fade" id="mapModal" tabindex="-1" aria-labelledby="mapModalLabel" aria-hidden="true">
                        <div class="modal-dialog modal-lg">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="mapModalLabel">Geotagging Map</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <div id="map" style="height: 500px;"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="modal fade" id="timeOffDetailModal" tabindex="-1" aria-labelledby="timeOffDetailModalLabel" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="timeOffDetailModalLabel">Absence Detail</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <div class="mb-1"><strong>Absence Type:</strong> <span id="detail-absence-type">-</span></div>
                                    <div class="mb-1"><strong>Date Range:</strong> <span id="detail-date-range">-</span></div>
                                    <div class="mb-1"><strong>Evidence:</strong> <div id="detail-evidence" class="mt-1">-</div></div>
                                    <div class="mb-1"><strong>Description:</strong></div>
                                    <div id="detail-description">-</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="modal fade" id="periodExportModal" tabindex="-1" aria-labelledby="periodExportModalLabel" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="periodExportModalLabel">Period Export</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <form action="export_time.php" method="GET" id="periodExportForm">
                                    <input type="hidden" name="type" value="period">
                                    <div class="modal-body">
                                        <div class="mb-2">
                                            <label for="start_date" class="form-label">Start Date</label>
                                            <input type="date" name="start_date" id="start_date" class="form-control" required>
                                        </div>
                                        <div class="mb-2">
                                            <label for="end_date" class="form-label">End Date</label>
                                            <input type="date" name="end_date" id="end_date" class="form-control" required>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                                        <button type="submit" class="btn btn-primary"><i data-feather="download"></i> Export</button>
                                    </div>
                                </form>
                            </div>
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
    <script src="https://unpkg.com/leaflet@1.7.1/dist/leaflet.js"></script>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <script src="https://kit.fontawesome.com/9273de0686.js" crossorigin="anonymous"></script>   

    <script>
        $(window).on('load', function() {
            if (feather) {
                feather.replace({
                    width: 14,
                    height: 14
                });
            }
        })

        function searchTable() {
            var input, filter, table, tr, td, i, j, txtValue;
            input  = document.getElementById("searchInput");
            filter = input.value.toLowerCase();
            table  = document.querySelector(".table-hover-animation");
            tr     = table.getElementsByTagName("tr");

            for (i = 1; i < tr.length; i++) { 
                tr[i].style.display = "none";
                td = tr[i].getElementsByTagName("td");
                for (j = 0; j < td.length; j++) {
                    if (td[j]) {
                        txtValue = td[j].textContent || td[j].innerText;
                        if (txtValue.toLowerCase().indexOf(filter) > -1) {
                            tr[i].style.display = "";
                            break;
                        }
                    }
                }
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            const entriesSelect = document.getElementById('entriesSelect');
            const table = document.querySelector('.table-hover-animation');
            const rows = table.getElementsByTagName('tr');
            const info = document.getElementById('table-info');
            const paginationContainer = document.querySelector('.pagination-container .pagination');

            let currentPage = 1;
            const rowsPerPage = parseInt(entriesSelect.value);

            function updateTable() {
                const entries = parseInt(entriesSelect.value);
                let count = 0;
                let totalEntries = rows.length - 1;
                const totalPages = Math.ceil(totalEntries / entries);

                for (let i = 1; i < rows.length; i++) {
                    if (i > (currentPage - 1) * entries && i <= currentPage * entries) {
                        rows[i].style.display = '';
                        count++;
                    } else {
                        rows[i].style.display = 'none';
                    }
                }
                
                let start = totalEntries > 0 ? (currentPage - 1) * entries + 1 : 0;
                let end = Math.min(currentPage * entries, totalEntries);
                info.textContent = `Showing ${start} to ${end} of ${totalEntries} entries`;
                
                updatePagination(totalPages);
            }

            function updatePagination(totalPages) {
                paginationContainer.innerHTML = '';

                for (let i = 1; i <= totalPages; i++) {
                    const li = document.createElement('li');
                    li.className = 'page-item' + (i === currentPage ? ' active' : '');
                    const a = document.createElement('a');
                    a.className = 'page-link';
                    a.href = '#';
                    a.textContent = i;
                    a.addEventListener('click', function(e) {
                        e.preventDefault();
                        currentPage = i;
                        updateTable();
                    });
                    li.appendChild(a);
                    paginationContainer.appendChild(li);
                }
            }

            entriesSelect.addEventListener('change', function() {
                currentPage = 1;
                updateTable();
            });

            updateTable();
        });

        //Time
        function formatTime(date) {
            let hours   = date.getHours();
            let minutes = date.getMinutes();
            let seconds = date.getSeconds();
            hours   = hours < 10 ? '0' + hours : hours;
            minutes = minutes < 10 ? '0' + minutes : minutes;
            seconds = seconds < 10 ? '0' + seconds : seconds;
            return hours + ':' + minutes + ':' + seconds;
        }

        function updateLogoutTimes() {
            document.querySelectorAll('[id^="logout-time-"]').forEach(function (element) {
                if (element.classList.contains('active')) {
                    let currentTime   = new Date();
                    element.innerText = formatTime(currentTime);
                }
            });
        }

        updateLogoutTimes();
        setInterval(updateLogoutTimes, 1000);

        // Map
        document.addEventListener('DOMContentLoaded', function() {
            const mapModal = document.getElementById('mapModal');
            mapModal.addEventListener('shown.bs.modal', function(event) {
                const button = event.relatedTarget;
                const lat = button.getAttribute('data-lat');
                const lng = button.getAttribute('data-lng');
                const time = button.getAttribute('data-time');

                const mapContainer = document.getElementById('map');
                if (mapContainer._leaflet_id) {
                    mapContainer._leaflet_id = null;
                    mapContainer.innerHTML = '';
                }

                const map = L.map('map').setView([lat, lng], 13);
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
                }).addTo(map);

                L.marker([lat, lng]).addTo(map)
                    .bindPopup(`Time: ${time}`)
                    .openPopup();
            });

            const detailModal = document.getElementById('timeOffDetailModal');
            detailModal.addEventListener('show.bs.modal', function(event) {
                const button = event.relatedTarget;
                const absenceType = button.getAttribute('data-absence-type') || '-';
                const startDate = button.getAttribute('data-start-date') || '-';
                const endDate = button.getAttribute('data-end-date') || '-';
                const evidenceLink = button.getAttribute('data-evidence-link') || '-';
                const evidenceName = button.getAttribute('data-evidence-name') || '-';
                const description = button.getAttribute('data-description') || '-';

                document.getElementById('detail-absence-type').textContent = absenceType;
                document.getElementById('detail-date-range').textContent = startDate + ' to ' + endDate;

                const evidenceContainer = document.getElementById('detail-evidence');
                if (evidenceLink !== '-' && evidenceName !== '-') {
                    evidenceContainer.innerHTML = '<a href="' + evidenceLink + '" target="_blank">' + evidenceName + '</a>';
                } else {
                    evidenceContainer.textContent = '-';
                }

                document.getElementById('detail-description').textContent = description;
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

        document.addEventListener('DOMContentLoaded', function() {
        const periodExportForm = document.getElementById('periodExportForm');
        const startDate = document.getElementById('start_date');
        const endDate = document.getElementById('end_date');

        periodExportForm.addEventListener('submit', function(e) {
            if (startDate.value && endDate.value && startDate.value > endDate.value) {
                e.preventDefault();

                Swal.fire({
                    icon: 'error',
                    title: 'Invalid Date Range',
                    text: 'Start date cannot be greater than end date',
                    confirmButtonText: 'OK'
                });

                return false;
            }
        });
    });
    </script>

</body>
</html>