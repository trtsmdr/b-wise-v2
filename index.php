<?php
    require 'BKI/BKI/BKI/admin/koneksi.php';

    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $name        = htmlspecialchars($_POST['name']);
        $email       = htmlspecialchars($_POST['email']);
        $subject     = htmlspecialchars($_POST['subject']);
        $message     = htmlspecialchars($_POST['message']);
        
        $data = [
            'name'   => $name,
            'email'  => $email,
            'subject' => $subject,
            'message' => $message,
        ];

        if (function_exists('feedback')) {
        $result = feedback($data);

            if ($result > 0) {
                echo "<script>
                    window.onload = function() {
                        Swal.fire({
                            icon: 'success',
                            title: 'Feedback successfully sent!',
                            showConfirmButton: false,
                            timer: 1500
                        }).then(() => {
                            document.location.href = 'index.php';
                        });
                    }
                    </script>";
            } else {
                echo "<script>
                    window.onload = function() {
                        Swal.fire({
                            icon: 'error',
                            title: 'Feedback failed sent!',
                            showConfirmButton: false,
                            timer: 1500
                        });
                    }
                    </script>";
                error_log('Failed to insert feedback: ' . $koneksi->error);
            }
            } else {
                echo "<script>alert('Feedback function not found!');</script>";
        }
    }
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <title>BKI - Landing Page</title>
    <meta content="" name="description">
    <meta content="" name="keywords">

    <!-- Favicons -->
    <link href="BKI/assets/img/logo.png" rel="icon">

    <!-- Fonts -->
    <link href="https://fonts.googleapis.com" rel="preconnect">
    <link href="https://fonts.gstatic.com" rel="preconnect" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,100;0,300;0,400;0,500;0,700;0,900;1,100;1,300;1,400;1,500;1,700;1,900&family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&family=Raleway:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">

    <!-- Vendor CSS Files -->
    <link href="BKI/assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="BKI/assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
    <link href="BKI/assets/vendor/aos/aos.css" rel="stylesheet">
    <link href="BKI/assets/vendor/glightbox/css/glightbox.min.css" rel="stylesheet">
    <link href="BKI/assets/vendor/swiper/swiper-bundle.min.css" rel="stylesheet">

    <!-- Main CSS File -->
    <link href="BKI/assets/css/main.css" rel="stylesheet">
</head>

<body class="index-page">

    <header id="header" class="header d-flex flex-column justify-content-center">

        <i class="header-toggle d-xl-none bi bi-list"></i>

        <nav id="navmenu" class="navmenu">
        <ul>
            <li><a href="#home" class="active"><i class="bi bi-house navicon"></i><span>Home</span></a></li>
            <li><a href="#guide"><i class="bi bi-hdd-stack navicon"></i><span>How To Use?</span></a></li>
            <li><a href="BKI/BKI/BKI/admin/Halaman_login.php"><i class="bi bi-person navicon"></i><span>Login</span></a></li>
        </ul>
        </nav>

    </header>

    <main class="main">

        <!-- Home Section -->
        <section id="home" class="home section light-background">

        <img src="BKI/assets/img/BG.jpeg" alt="">
        <div class="container" data-aos="zoom-out">
        <div style="padding-left: 30.6667px; margin-top: -50px; line-height: 1;" align="left">
            <div class="col-lg-20">
                <h2> BIRO KLASIFIKASI </h2> 
                  <h2>INDONESIA ( PERSERO )</h2>
                <p><span class="typed" data-typed-items="BKI Workload and Individual Staff Efficiency, JAKARTA MAIN COMMERCIAL BRANCH"></span><span class="typed-cursor typed-cursor--blink" aria-hidden="true"></span></p>
              </div>
            </div>
        </div>

        </section><!-- End Home Section -->

        <!-- Guide Section -->
        <section id="guide" class="guides section">

        <!-- Section Title -->
        <div class="container section-title" data-aos="fade-up">
            <h2>How to Use the Attendance System</h2>
            <p>Attendance system usage guide, follow these steps to do daily attendance easily</p>
        </div><!-- End Section Title -->

        <div class="container">

            <div class="row gy-4">

            <div class="col-lg-6 col-md-6" data-aos="fade-up" data-aos-delay="100">
                <div class="guide-item item-cyan position-relative">
                <div class="icon">
                    <svg width="100" height="100" viewBox="0 0 600 600" xmlns="http://www.w3.org/2000/svg">
                    <path stroke="none" stroke-width="0" fill="#f5f5f5" d="M300,521.0016835830174C376.1290562159157,517.8887921683347,466.0731472004068,529.7835943286574,510.70327084640275,468.03025145048787C554.3714126377745,407.6079735673963,508.03601936045806,328.9844924480964,491.2728898941984,256.3432110539036C474.5976632858925,184.082847569629,479.9380746630129,96.60480741107993,416.23090153303,58.64404602377083C348.86323505073057,18.502131276798302,261.93793281208167,40.57373210992963,193.5410806939664,78.93577620505333C130.42746243093433,114.334589627462,98.30271207620316,179.96522072025542,76.75703585869454,249.04625023123273C51.97151888228291,328.5150500222984,13.704378332031375,421.85034740162234,66.52175969318436,486.19268352777647C119.04800174914682,550.1803526380478,217.28368757567262,524.383925680826,300,521.0016835830174"></path>
                    </svg>
                    <i class="fa-solid fa-1"></i>
                </div>
                    <h3>Click the 'Login' button in the side menu</h3>
                    <p>Find and click the "Login" button located in the side navigation menu. This will take you to the login page.</p>
                </div>
            </div><!-- End Guide Item -->

            <div class="col-lg-6 col-md-6" data-aos="fade-up" data-aos-delay="300">
                <div class="guide-item item-teal position-relative">
                <div class="icon">
                    <svg width="100" height="100" viewBox="0 0 600 600" xmlns="http://www.w3.org/2000/svg">
                        <path stroke="none" stroke-width="0" fill="#f5f5f5" d="M300,541.5067337569781C382.14930387511276,545.0595476570109,479.8736841581634,548.3450877840088,526.4010558755058,480.5488172755941C571.5218469581645,414.80211281144784,517.5187510058486,332.0715597781072,496.52539010469104,255.14436215662573C477.37192572678356,184.95920475031193,473.57363656557914,105.61284051026155,413.0603344069578,65.22779650032875C343.27470386102294,18.654635553484475,251.2091493199835,5.337323636656869,175.0934190732945,40.62881213300186C97.87086631185822,76.43348514350839,51.98124368387456,156.15599469081315,36.44837278890362,239.84606092416172C21.716077023791087,319.22268207091537,43.775223500013084,401.1760424656574,96.891909868211,461.97329694683043C147.22146801428983,519.5804099606455,223.5754009179313,538.201503339737,300,541.5067337569781"></path>
                    </svg>
                    <i class="fa-solid fa-2"></i>
                </div>
                    <h3>Enter Username and Password</h3>
                </a>
                    <p>Enter your username and password in the columns provided, then click the "Login" button to enter attendance.</p>
                </div>
            </div><!-- End Guide Item -->

            <div class="col-lg-6 col-md-6" data-aos="fade-up" data-aos-delay="400">
                <div class="guide-item item-red position-relative">
                <div class="icon">
                    <svg width="100" height="100" viewBox="0 0 600 600" xmlns="http://www.w3.org/2000/svg">
                        <path stroke="none" stroke-width="0" fill="#f5f5f5" d="M300,503.46388370962813C374.79870501325706,506.71871716319447,464.8034551963731,527.1746412648533,510.4981551193396,467.86667711651364C555.9287308511215,408.9015244558933,512.6030010748507,327.5744911775523,490.211057578863,256.5855673507754C471.097692560561,195.9906835881958,447.69079081568157,138.11976852964426,395.19560036434837,102.3242989838813C329.3053358748298,57.3949838291264,248.02791733380457,8.279543830951368,175.87071277845988,42.242879143198664C103.41431057327972,76.34704239035025,93.79494320519305,170.9812938413882,81.28167332365135,250.07896920659033C70.17666984294237,320.27484674793965,64.84698225790005,396.69656628748305,111.28512138212992,450.4950937839243C156.20124167950087,502.5303643271138,231.32542653798444,500.4755392045468,300,503.46388370962813"></path>
                    </svg>
                    <i class="fa-solid fa-3"></i>
                </div>
                    <h3>Auto Saved Login Time</h3>
                </a>
                
                <p>After successfully logging in, your entry time will be automatically recorded by the system as a sign of attendance.</p>
                
                </div>
            </div><!-- End Guide Item -->

            <div class="col-lg-6 col-md-6" data-aos="fade-up" data-aos-delay="500">
                <div class="guide-item item-indigo position-relative">
                <div class="icon">
                    <svg width="100" height="100" viewBox="0 0 600 600" xmlns="http://www.w3.org/2000/svg">
                        <path stroke="none" stroke-width="0" fill="#f5f5f5" d="M300,532.3542879108572C369.38199826031484,532.3153073249985,429.10787420159085,491.63046689027357,474.5244479745417,439.17860296908856C522.8885846962883,383.3225815378663,569.1668002868075,314.3205725914397,550.7432151929288,242.7694973846089C532.6665558377875,172.5657663291529,456.2379748765914,142.6223662098291,390.3689995646985,112.34683881706744C326.66090330228417,83.06452184765237,258.84405631176094,53.51806209861945,193.32584062364296,78.48882559362697C121.61183558270385,105.82097193414197,62.805066853699245,167.19869350419734,48.57481801355237,242.6138429142374C34.843463184063346,315.3850353017275,76.69343916112496,383.4422959591041,125.22947124332185,439.3748458443577C170.7312796277747,491.8107796887764,230.57421082200815,532.3932930995766,300,532.3542879108572"></path>
                    </svg>
                    <i class="fa-solid fa-4"></i>
                </div>
                    <h3>Logout to Record Home Time</h3>
                    <p>Once you are done working, click the "Logout" button available on the profile icon. Your logout time will be automatically recorded by the system.</p>
                </div>
            </div>
            </div><!-- End Guide Item -->
        </div>
        </section><!-- Guides Section -->
    </main>

    <footer id="footer" class="footer feedback section-2 position-relative light-background">
        <div class="container">
            <div class="row gy-4" data-aos="fade-up" data-aos-delay="200">

                <!-- Left Content -->
                <div class="col-lg-6">
                    <h3 class="sitename">B - WISE</h3>

                    <p class="text-start">
                        If you run into any issues or have further questions,
                        please feel free to send a feedback form or contact IT team.
                        Good luck!
                    </p>

                    <div class="info-item d-flex">
                        <i class="bi bi-geo-alt flex-shrink-0"></i>
                        <div class="text-start">
                            <h3>Address</h3>
                            <p class="fw-bold">PT Biro Klasifikasi Indonesia (Persero)</p>
                            <p>
                                Jalan Yos Sudarso No.38 - 40, RT.04/RW.10
                                Kelurahan Kebon Bawang, Kecamatan Tanjung Priok,
                                Kota Jakarta Utara, DKI Jakarta, 14320.
                            </p>
                        </div>
                    </div>

                    <div class="info-item d-flex">
                        <i class="bi bi-telephone flex-shrink-0"></i>
                        <div class="text-start">
                            <h3>Telp</h3>
                            <p>(+6221) 4300139</p>
                        </div>
                    </div>
                </div>

                <!-- Spacer -->
                <div class="col-lg-1"></div>

                <!-- Feedback -->
                <div class="col-lg-5" style="text-align: end;">
                    <div class="container section-title"
                        style="margin-bottom: 5px; padding-bottom: 5px;">
                        <h2>Feedback</h2>
                    </div>

                    <form action="" method="POST">
                        <div class="row gy-4">

                            <div class="col-md-6 php-email-form">
                                <input type="text"
                                    name="name"
                                    class="form-control"
                                    placeholder="Your Name"
                                    required>
                            </div>

                            <div class="col-md-6 php-email-form">
                                <input type="email"
                                    name="email"
                                    class="form-control"
                                    placeholder="Your Email"
                                    required>
                            </div>

                            <div class="col-md-12 php-email-form">
                                <input type="text"
                                    name="subject"
                                    class="form-control"
                                    placeholder="Subject"
                                    required>
                            </div>

                            <div class="col-md-12 php-email-form">
                                <textarea name="message"
                                        class="form-control"
                                        placeholder="Message"
                                        rows="6"
                                        required></textarea>
                            </div>

                            <div class="col-md-12 text-center php-email-form">
                                <button type="submit"
                                        name="feedback"
                                        class="btn btn-primary_2 me-1">
                                    Send
                                </button>
                            </div>

                        </div>
                    </form>
                </div>

            </div>

            <!-- Logo Footer -->
            <div class="footer-logo"
                style="
                    display: flex;
                    justify-content: center;
                    align-items: center;
                    width: 100%;
                    margin-top: 5px;
                    margin-bottom: 0;
                    padding: 0;
                ">

                <img src="BKI/assets/img/ChatGPT Image Aug 9, 2026 at 07_22_07 PM.png"
                    style="
                        display: block;
                        width: auto;
                        height: 300px;
                        object-fit: contain;
                        margin-bottom: -80px;
                        padding: 0;
                    ">
            </div>

        </div>
    </footer>

    <!-- Scroll Top -->
    <a href="#" id="scroll-top" class="scroll-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>

    <!-- Preloader -->
    <div id="preloader"></div>

    <!-- Vendor JS Files -->
    <script src="BKI/assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="BKI/assets/vendor/php-email-form/validate.js"></script>
    <script src="BKI/assets/vendor/aos/aos.js"></script>
    <script src="BKI/assets/vendor/typed.js/typed.umd.js"></script>
    <script src="BKI/assets/vendor/purecounter/purecounter_vanilla.js"></script>
    <script src="BKI/assets/vendor/waypoints/noframework.waypoints.js"></script>
    <script src="BKI/assets/vendor/glightbox/js/glightbox.min.js"></script>
    <script src="BKI/assets/vendor/imagesloaded/imagesloaded.pkgd.min.js"></script>
    <script src="BKI/assets/vendor/isotope-layout/isotope.pkgd.min.js"></script>
    <script src="BKI/assets/vendor/swiper/swiper-bundle.min.js"></script>
    <script src="https://kit.fontawesome.com/9273de0686.js" crossorigin="anonymous"></script>   

    <!-- Main JS File -->
    <script src="BKI/assets/js/main.js"></script>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

</body>
</html>