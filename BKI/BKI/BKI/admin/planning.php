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

    $result = mysqli_query($koneksi, "SELECT id, nup, nama, divisi FROM users WHERE status = 'Active'");

    function is_superadmin() {
        return $_SESSION['role'] === 'Super-Admin';
    }

    function is_admin() {
        return $_SESSION['role'] === 'Admin';
    }

    function is_user() {
        return $_SESSION['role'] === 'User';
    }

    $selected_month = date('m');
    $selected_year = date('Y');

    $update_incomplete_query = "
        UPDATE planning
        SET status = 'Incomplete'
        WHERE tanggal < CURDATE()
        AND status = 'On-progress'
        AND (gambar IS NULL OR gambar = '')
    ";
    mysqli_query($koneksi, $update_incomplete_query);

    if ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET['search'])) {
        $selected_month = htmlspecialchars($_GET['month']);
        $selected_year  = htmlspecialchars($_GET['year']);
    }

    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $tanggal        = htmlspecialchars($_POST['tanggal']);
        $user_id        = htmlspecialchars($_POST['user_id']);
        $deskripsi      = htmlspecialchars($_POST['deskripsi']);
        $time_upload_activity_planning = date('Y-m-d H:i:s');
        $history_update = htmlspecialchars($_POST['history_update']);
        $gambar         = '';

        if (isset($_FILES['gambar']) && $_FILES['gambar']['error'] == 0) {
            $gambar = $_FILES['gambar']['name'];
            move_uploaded_file($_FILES['gambar']['tmp_name'], 'img/' . $gambar);
            $status = 'Completed';
        } else {
            $status = 'On-progress';
        }
    }
    
    // Switch Alert - Delete
    if (isset($_GET['id']) && isset($_GET['action']) && $_GET['action'] == 'delete') {
        $id = intval($_GET['id']);
        $result = hapus_planning($id);
        if ($result > 0) {
            echo "<script>
                window.onload = function() {
                    Swal.fire({
                        icon: 'success',
                        title: 'Planning successfully deleted!',
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
                        title: 'Planning failed to deleted!',
                        showConfirmButton: false,
                        timer: 1500
                    });
                }
            </script>";
        }
    }

    $query = "
        SELECT p.id, p.tanggal, p.deskripsi, p.time_upload_activity_planning, p.status, p.gambar, p.history_update,
            u.nup, u.nama, u.divisi
        FROM planning p
        JOIN users u ON p.user_id = u.id
        WHERE u.status = 'Active' 
        AND MONTH(p.tanggal) = '$selected_month' 
        AND YEAR(p.tanggal) = '$selected_year'
    ";
    
    if (is_user()) {
        $query .= " AND u.nama = '" . mysqli_real_escape_string($koneksi, $nama) . "'";
    }

    $query .= " ORDER BY p.status, p.time_upload_activity_planning ASC";

    
    $result = mysqli_query($koneksi, $query);

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
    <title>BKI - Planning</title>
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
    <!-- END: Page CSS -->

    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.3/css/lightbox.min.css" rel="stylesheet">

    <style>
        .btn-primary {
            background: linear-gradient(135deg, #AFC8AD, #88AB8E);
            color: #FFF;
            border: none;
            padding: 12px 24px;
            text-decoration: none;
        }
        .gallery-icon {
            color: #2A629A;
            font-size: 24px;
            transition: color 0.3s ease;
        }
        .gallery-icon:hover {
            color: #1A4F6A;
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
        .pagination {
            margin-bottom: 20px;
            gap: 4px;
        }
        .pagination .page-item .page-link {
            border-radius: 6px;
            min-width: 34px;
            height: 34px;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0 10px;
            font-size: 14px;
            border: none;
        }
        .pagination .page-item.active .page-link {
            background: #003285;
            color: #fff;
        }
        .pagination .page-item:not(.active):not(.disabled) .page-link:hover {
            background: #eef3f9;
            color: #003285;
        }
        .pagination .page-item.disabled .page-link {
            background: transparent;
            color: #999;
            cursor: default;
        }
        .pagination-container {
            display: flex;
            align-items: center;
        }
        @media (max-width: 767.98px) {
        
            /* Area info + pagination */
            .card > .d-flex.justify-content-between.align-items-center.mt-2 {
                flex-direction: column !important;
                align-items: stretch !important;
                gap: 15px;
                padding: 15px 15px 20px !important;
            }
            
            /* Info "Showing 1 to..." */
            #table-info {
                text-align: left !important;
                width: 100%;
            }
        
            /* Pagination */
            .pagination-container {
                width: 100%;
                justify-content: center;
            }
        
            .pagination {
                justify-content: center;
                flex-wrap: nowrap;
                margin-bottom: 0;
            }
        
            /* Jarak card dengan footer di mobile */
            .app-content {
                padding-bottom: 80px !important;
            }
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
                            <li class="active"><a class="d-flex align-items-center" href="planning.php"><i data-feather="circle"></i><span class="menu-item text-truncate" data-i18n="Planning">Planning</span></a>
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
                        <h2 class="float-start mb-0">Employee Planning</h2>
                    </div>
                </div>
            </div>
            <div class="content-body">
                <!-- Table Hover Animation start -->
                <div class="row" id="table-hover-animation">
                    <div class="col-12">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <?php if (is_superadmin() || is_user()): ?>
                                <a href="add_planning.php" class="btn btn-primary">Add Data</a>
                            <?php endif; ?>
                            <form method="get" action="planning.php" class="d-flex">
                                <div class="me-2">
                                    <label for="month" class="form-label d-none">Month</label>
                                    <select id="month" name="month" class="form-select">
                                        <?php for ($m = 1; $m <= 12; $m++): ?>
                                            <option value="<?php echo str_pad($m, 2, '0', STR_PAD_LEFT); ?>" <?php echo str_pad($m, 2, '0', STR_PAD_LEFT) == $selected_month ? 'selected' : ''; ?>>
                                                <?php echo date('F', mktime(0, 0, 0, $m, 1)); ?>
                                            </option>
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
                                <div>
                                    <input type="text" id="searchInput" placeholder="Search..." class="form-control search-input ms-auto" style="width: 165px;" onkeyup="searchTable()">
                                    <form action="export_per_hari.php" method="POST" class="d-inline">
                                        <div class="input-group mt-1">
                                            <input type="date" name="export_date" id="export_date" class="form-control" required>
                                            <button type="submit" class="btn btn-outline-secondary" style="margin-right: 25px; border-radius: 5px;"><i data-feather="download"></i> Daily Export</button>
                                            <a href="export.php" class="btn btn-outline-secondary" style="border-radius: 5px;"><i data-feather="download"></i> Export All</a>
                                        </div>
                                    </form>
                                </div>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-hover-animation" style="min-width: 2000px;">
                                    <thead>
                                        <tr style="text-align: center;">
                                            <th>No.</th>
                                            <th style="min-width: 150px;">Date</th>
                                            <th style="min-width: 150px;">NUP</th>
                                            <th style="min-width: 350px;">Name</th>
                                            <th style="min-width: 150px;">Division</th>
                                            <th style="min-width: 350px;">Description</th>
                                            <th style="min-width: 150px;">Upload Time</th>
                                            <th style="min-width: 250px;">Image</th>
                                            <th style="min-width: 150px;">Status</th>
                                            <th style="min-width: 150px;">History</th>
                                            <?php if (is_superadmin() || is_user()): ?>
                                            <th style="min-width: 250px;">Action</th>
                                            <?php endif; ?>
                                        </tr>
                                    </thead>
                                    <tbody id="tableBody" style="text-align: center;">
                                        <?php
                                            $i = 1;
                                            while ($row = mysqli_fetch_assoc($result)) {
                                                $gambar_arr = explode(',', $row['gambar']);
                                        ?>
                                        <tr>
                                            <td><?php echo $i; ?></td>
                                            <td><?php echo date('d-m-Y', strtotime($row['tanggal'])); ?></td>
                                            <td><?php echo $row['nup']; ?></td>
                                            <td><?php echo $row['nama']; ?></td>
                                            <td><?php echo $row['divisi']; ?></td>
                                            <td style="text-align: justify;"><?php $deskripsi = $row['deskripsi']; echo nl2br(htmlspecialchars($deskripsi)); ?></td>
                                            <td><?php echo $row['time_upload_activity_planning']; ?></td>
                                            <td>
                                                <?php if (!empty($row['gambar'])): ?>
                                                    <?php
                                                        $gambar_arr = explode(',', $row['gambar']);
                                                            foreach ($gambar_arr as $gambar):
                                                    ?>
                                                            <a href="img/<?= htmlspecialchars($gambar) ?>" data-lightbox="gallery-<?= $row['id'] ?>" data-title="<?= htmlspecialchars($gambar) ?>" class="gallery-icon">
                                                                <i class="fas fa-images" style="font-size: 18px;"></i>
                                                            </a>
                                                    <?php endforeach; ?>
                                                <?php else: ?>
                                                    No Image
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php
                                                    if ($row['status'] === 'Completed') {
                                                        $statusClass = 'badge bg-success';
                                                    } elseif ($row['status'] === 'Incomplete') {
                                                        $statusClass = 'badge bg-danger';
                                                    } else {
                                                        $statusClass = 'badge bg-warning';
                                                    }
                                                ?>
                                                <span class="<?php echo $statusClass; ?>"><?php echo htmlspecialchars($row['status']); ?></span>
                                            </td>
                                            <td><?php echo $row['history_update']; ?></td>
                                            <?php if (is_superadmin() || is_user()): ?>
                                            <td>
                                                <a href="edit_planning.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-primary_4">Edit</a>
                                                <a href="#" class="btn btn-sm btn-danger" onclick="confirmDelete(<?php echo $row['id']; ?>); return false;">Delete</a>
                                            </td>
                                            <?php endif; ?>
                                        </tr>
                                        <?php
                                            $i++;
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
    <script src="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.3/js/lightbox.min.js"></script>

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
            
                if (totalPages <= 1) {
                    return;
                }
            
                function createPage(page, text = page, disabled = false, active = false) {
                    const li = document.createElement('li');
            
                    li.className = 'page-item';
            
                    if (disabled) {
                        li.classList.add('disabled');
                    }
            
                    if (active) {
                        li.classList.add('active');
                    }
            
                    const a = document.createElement('a');
                    a.className = 'page-link';
                    a.href = '#';
                    a.textContent = text;
            
                    if (!disabled) {
                        a.addEventListener('click', function(e) {
                            e.preventDefault();
            
                            if (page < 1 || page > totalPages) {
                                return;
                            }
            
                            currentPage = page;
                            updateTable();
                        });
                    }
            
                    li.appendChild(a);
                    paginationContainer.appendChild(li);
                }
            
                // Previous
                createPage(
                    currentPage - 1,
                    '',
                    currentPage === 1
                );
            
                // Kalau halaman sedikit, tampilkan semuanya
                if (totalPages <= 7) {
            
                    for (let i = 1; i <= totalPages; i++) {
                        createPage(
                            i,
                            i,
                            false,
                            i === currentPage
                        );
                    }
            
                } else {
            
                    // Halaman pertama
                    createPage(
                        1,
                        1,
                        false,
                        currentPage === 1
                    );
            
                    // Kondisi awal
                    if (currentPage <= 4) {
            
                        createPage(2, 2, false, currentPage === 2);
                        createPage(3, 3, false, currentPage === 3);
                        createPage(4, 4, false, currentPage === 4);
            
                        // ...
                        const dots = document.createElement('li');
                        dots.className = 'page-item disabled';
                        dots.innerHTML = '<span class="page-link">...</span>';
                        paginationContainer.appendChild(dots);
            
                        // Halaman terakhir
                        createPage(
                            totalPages,
                            totalPages,
                            false,
                            currentPage === totalPages
                        );
            
                    }
            
                    // Kondisi tengah
                    else if (currentPage >= 5 && currentPage <= totalPages - 4) {
            
                        // ...
                        const dotsStart = document.createElement('li');
                        dotsStart.className = 'page-item disabled';
                        dotsStart.innerHTML = '<span class="page-link">...</span>';
                        paginationContainer.appendChild(dotsStart);
            
                        createPage(
                            currentPage - 1,
                            currentPage - 1,
                            false,
                            false
                        );
            
                        createPage(
                            currentPage,
                            currentPage,
                            false,
                            true
                        );
            
                        createPage(
                            currentPage + 1,
                            currentPage + 1,
                            false,
                            false
                        );
            
                        // ...
                        const dotsEnd = document.createElement('li');
                        dotsEnd.className = 'page-item disabled';
                        dotsEnd.innerHTML = '<span class="page-link">...</span>';
                        paginationContainer.appendChild(dotsEnd);
            
                        createPage(
                            totalPages,
                            totalPages,
                            false,
                            currentPage === totalPages
                        );
            
                    }
            
                    // Kondisi akhir
                    else {
            
                        const dots = document.createElement('li');
                        dots.className = 'page-item disabled';
                        dots.innerHTML = '<span class="page-link">...</span>';
                        paginationContainer.appendChild(dots);
            
                        createPage(
                            totalPages - 3,
                            totalPages - 3,
                            false,
                            currentPage === totalPages - 3
                        );
            
                        createPage(
                            totalPages - 2,
                            totalPages - 2,
                            false,
                            currentPage === totalPages - 2
                        );
            
                        createPage(
                            totalPages - 1,
                            totalPages - 1,
                            false,
                            currentPage === totalPages - 1
                        );
            
                        createPage(
                            totalPages,
                            totalPages,
                            false,
                            currentPage === totalPages
                        );
                    }
                }
            
                // Next
                createPage(
                    currentPage + 1,
                    '›',
                    currentPage === totalPages
                );
            }

            entriesSelect.addEventListener('change', function() {
                currentPage = 1;
                updateTable();
            });

            updateTable();
        });

        function confirmDelete(id) {
            Swal.fire({
                title: 'Are you sure?',
                text: "You won't be able to revert this!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, delete it!',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({
                        title: 'This action cannot be undone!',
                        text: "Deleting this planning will remove all associated data. Are you sure you want to proceed?",
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#3085d6',
                        cancelButtonColor: '#d33',
                        confirmButtonText: 'Yes, delete it!',
                        cancelButtonText: 'Cancel'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            window.location.href = 'planning.php?id=' + id + '&action=delete';
                        }
                    });
                }
            });
        }
        
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