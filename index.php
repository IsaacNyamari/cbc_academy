<?php session_start();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Yeah Kenyan Academy - Unlock Your Potential</title>
    <link href="assets/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/fontawesome/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="shortcut icon" href="assets/images/logo.png" type="image/x-icon">
    <style>
        :root {
            --kenya-red: #BB0000;
            --kenya-green: #006600;
            --kenya-black: #000000;
            --kenya-white: #FFFFFF;
            --accent-color: #F0C808;
        }

        /* Kenyan flag inspired scrollbar with gradient background */
        body::-webkit-scrollbar {
            width: 14px;
        }
        body::-webkit-scrollbar-track {
            background: linear-gradient(180deg, var(--kenya-black) 0%, var(--kenya-red) 33%, var(--kenya-green) 66%, var(--kenya-white) 100%);
        }
        body::-webkit-scrollbar-thumb {
            background: linear-gradient(180deg, var(--kenya-black) 0%, var(--kenya-red) 33%, var(--kenya-green) 66%, var(--kenya-white) 100%);
            border-radius: 8px;
            border: 3px solid var(--kenya-white);
        }
        body::-webkit-scrollbar-thumb:hover {
            background: linear-gradient(180deg, var(--kenya-green) 0%, var(--kenya-red) 33%, var(--kenya-black) 66%, var(--kenya-white) 100%);
        }
        /* Firefox scrollbar */
        html {
            scrollbar-width: thin;
            scrollbar-color: var(--kenya-red) var(--kenya-black);
        }

        body {
            font-family: 'Poppins', sans-serif;
            overflow-x: hidden;
        }

        .navbar {
            padding: 15px 0;
            transition: all 0.3s ease;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            background-color: var(--kenya-white) !important;
        }

        .navbar.scrolled {
            padding: 10px 0;
            background-color: var(--kenya-white) !important;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .navbar-brand {
            font-weight: 700;
            color: var(--kenya-black) !important;
            display: flex;
            align-items: center;
        }

        .navbar-brand img {
            margin-right: 10px;
        }

        .nav-link {
            color: var(--kenya-black) !important;
            font-weight: 500;
            margin: 0 10px;
            position: relative;
        }

        .nav-link:after {
            content: '';
            position: absolute;
            width: 0;
            height: 2px;
            background: var(--kenya-red);
            bottom: 0;
            left: 0;
            transition: width 0.3s ease;
        }

        .nav-link:hover:after {
            width: 100%;
        }

        .btn-primary {
            background-color: var(--kenya-red);
            border-color: var(--kenya-red);
            padding: 8px 20px;
            border-radius: 50px;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            background-color: #9e0000;
            border-color: #9e0000;
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(187, 0, 0, 0.3);
        }

        .btn-outline-primary {
            color: var(--kenya-red);
            border-color: var(--kenya-red);
            padding: 8px 20px;
            border-radius: 50px;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .btn-outline-primary:hover {
            background-color: var(--kenya-red);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(187, 0, 0, 0.3);
        }

        .hero {
            background: linear-gradient(135deg, var(--kenya-black) 0%, var(--kenya-red) 33%, var(--kenya-green) 66%, var(--kenya-white) 100%);
            color: white;
            padding: 120px 0 100px;
            position: relative;
            overflow: hidden;
        }

        .hero:before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: url('assets/images/students.avif') no-repeat center center;
            background-size: cover;
            opacity: 0.5;
            z-index: 0;
        }

        .hero-content {
            position: relative;
            z-index: 1;
        }

        .hero h1 {
            font-size: 3.5rem;
            font-weight: 700;
            margin-bottom: 20px;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        }

        .hero p {
            font-size: 1.25rem;
            max-width: 600px;
            margin: 0 auto 30px;
            opacity: 0.9;
        }

        .hero-btns .btn {
            margin: 0 10px;
            padding: 12px 30px;
            font-size: 1.1rem;
            font-weight: 500;
        }

        .hero-btns .btn-light {
            background-color: white;
            color: var(--kenya-red);
            border-radius: 50px;
            transition: all 0.3s ease;
        }

        .hero-btns .btn-light:hover {
            transform: translateY(-3px);
            box-shadow: 0 7px 20px rgba(255, 255, 255, 0.3);
        }

        section {
            padding: 80px 0;
        }

        .section-title {
            font-weight: 700;
            color: var(--kenya-black);
            margin-bottom: 50px;
            position: relative;
            display: inline-block;
        }

        .section-title:after {
            content: '';
            position: absolute;
            width: 50%;
            height: 3px;
            background: linear-gradient(90deg, var(--kenya-red), var(--kenya-green));
            bottom: -10px;
            left: 0;
            border-radius: 3px;
        }

        .about-content {
            max-width: 800px;
            margin: 0 auto;
            text-align: center;
        }

        .contact-form {
            max-width: 600px;
            margin: 0 auto;
            background: white;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 5px 25px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(0, 0, 0, 0.1);
        }

        .form-control {
            height: 50px;
            border-radius: 8px;
            border: 1px solid #e0e0e0;
            padding: 10px 15px;
            margin-bottom: 20px;
        }

        .form-control:focus {
            border-color: var(--kenya-green);
            box-shadow: 0 0 0 0.25rem rgba(0, 102, 0, 0.25);
        }

        textarea.form-control {
            height: auto;
            min-height: 150px;
        }

        footer {
            background-color: var(--kenya-black);
            color: white;
            padding: 40px 0 20px;
        }

        .social-icons a {
            color: white;
            font-size: 1.2rem;
            margin: 0 10px;
            transition: all 0.3s ease;
        }

        .social-icons a:hover {
            color: var(--accent-color);
            transform: translateY(-3px);
        }

        .footer-links {
            margin-bottom: 20px;
        }

        .footer-links a {
            color: white;
            margin: 0 15px;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .footer-links a:hover {
            color: var(--kenya-red);
        }

        /* Kenyan flag inspired decorative elements */
        .kenya-flag-theme {
            position: relative;
        }

        .kenya-flag-theme:after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: linear-gradient(90deg,
                    var(--kenya-black) 0%,
                    var(--kenya-black) 33%,
                    var(--kenya-red) 33%,
                    var(--kenya-red) 66%,
                    var(--kenya-green) 66%,
                    var(--kenya-green) 100%);
        }

        @media (max-width: 768px) {
            .hero h1 {
                font-size: 2.5rem;
            }

            .hero p {
                font-size: 1rem;
            }

            section {
                padding: 60px 0;
            }
        }
    </style>
</head>

<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white fixed-top kenya-flag-theme">
        <div class="container">
            <a class="navbar-brand" href="#">
                <img src="assets/images/logo.png" alt="Yeah Kenyan Academy Logo" width="40" height="40">
                Yeah Kenyan Academy
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="#home">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#about">About</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#curriculum">Curriculum</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#contact">Contact</a>
                    </li>
                    <li class="nav-item ms-lg-3 my-2 my-lg-0">
                        <?php if (isset($_SESSION["is_logged_in"]) && $_SESSION["is_logged_in"]): ?>
                            <a class="btn btn-outline-success" href="<?php echo strtolower($_SESSION["role"]) === "teacher" ? "../admin/dashboard" : "../student/dashboard"; ?>.php">Dashboard</a>
                            <a class="btn btn-outline-primary" href="logout.php">Logout</a>
                            <?php else: ?>
                            <a class="btn btn-outline-primary" href="login.php">Login</a>

                    </li>
                    <li class="nav-item ms-lg-2">
                        <a class="btn btn-primary" href="register.php">Register</a>
                    </li><?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero" id="home">
        <div class="container">
            <div class="hero-content text-center">
                <h1 class="display-3 fw-bold mb-4">Proudly Kenyan Education</h1>
                <p class="lead mb-5">Empowering the nation through CBC and 8-4-4 curriculum excellence</p>
                <div class="hero-btns">
                    <?php if (!isset($_SESSION["is_logged_in"]) || !$_SESSION["is_logged_in"]): ?>
                        <a href="register.php" class="btn btn-light btn-lg">Enroll Now</a>
                    <?php endif; ?>
                    <a href="#curriculum" class="btn btn-outline-light btn-lg">Our Curriculum</a>
                </div>
            </div>
        </div>
    </section>

    <!-- About Section -->
    <section id="about" class="bg-light">
        <div class="container">
            <h2 class="text-center section-title">About Us</h2>
            <div class="about-content">
                <p class="lead">Yeah Kenyan Academy is a pioneer in implementing both CBC and 8-4-4 curricula with excellence.</p>
                <p>We bridge the gap between traditional and modern education systems, offering parents and students the best of both worlds. Our institution is registered and accredited by the Kenyan Ministry of Education to deliver quality education under both systems.</p>
                <p>With state-of-the-art facilities and a team of highly trained educators, we provide a nurturing environment that recognizes each learner's unique potential.</p>
            </div>
        </div>
    </section>

    <!-- Curriculum Section -->
    <section id="curriculum" class="py-5">
        <div class="container">
            <h2 class="text-center section-title">Our Curriculum</h2>

            <div class="row align-items-center mb-5">
                <div class="col-lg-6">
                    <div class="p-4">
                        <h3 class="fw-bold mb-3">Competency Based Curriculum (CBC)</h3>
                        <p>The CBC is Kenya's new education system that focuses on nurturing learners' competencies rather than just content mastery. At Yeah Kenyan Academy, we've fully embraced this learner-centered approach with:</p>
                        <ul class="list-unstyled">
                            <li class="mb-2"><i class="fas fa-check-circle text-success me-2"></i> <strong>2-6-6-3 Structure:</strong> 2 years Pre-Primary, 6 years Primary (Grade 1-6), 6 years Secondary (Junior and Senior), and 3 years University</li>
                            <li class="mb-2"><i class="fas fa-check-circle text-success me-2"></i> <strong>7 Core Competencies:</strong> Communication, Collaboration, Critical Thinking, Creativity, Citizenship, Digital Literacy, and Learning to Learn</li>
                            <li class="mb-2"><i class="fas fa-check-circle text-success me-2"></i> <strong>Practical Assessment:</strong> Continuous assessment through projects, presentations, and practical demonstrations</li>
                            <li class="mb-2"><i class="fas fa-check-circle text-success me-2"></i> <strong>Parental Engagement:</strong> Regular parent-teacher conferences and involvement in school projects</li>
                            <li class="mb-2"><i class="fas fa-check-circle text-success me-2"></i> <strong>Pathways:</strong> Arts & Sports, Social Sciences, and STEM pathways in Senior School</li>
                        </ul>
                        <p>Our CBC program features modern classrooms, digital learning resources, and specially trained teachers to deliver this dynamic curriculum effectively.</p>
                    </div>
                </div>
                <div class="col-lg-6">
                    <img src="https://images.unsplash.com/photo-1588072432836-e10032774350?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1470&q=80" alt="CBC Learning" class="img-fluid rounded shadow">
                </div>
            </div>

            <div class="row align-items-center mt-5">
                <div class="col-lg-6 order-lg-2">
                    <div class="p-4">
                        <h3 class="fw-bold mb-3">8-4-4 Curriculum</h3>
                        <p>For parents who prefer the traditional system, we offer the proven 8-4-4 curriculum with modern enhancements:</p>
                        <ul class="list-unstyled">
                            <li class="mb-2"><i class="fas fa-check-circle text-success me-2"></i> <strong>Structure:</strong> 8 years Primary, 4 years Secondary, and 4 years University</li>
                            <li class="mb-2"><i class="fas fa-check-circle text-success me-2"></i> <strong>Exam Preparation:</strong> Intensive KCPE and KCSE preparation with mock exams and revision programs</li>
                            <li class="mb-2"><i class="fas fa-check-circle text-success me-2"></i> <strong>Subject Specialization:</strong> Focus on core subjects with optional technical and vocational subjects</li>
                            <li class="mb-2"><i class="fas fa-check-circle text-success me-2"></i> <strong>Enhanced Features:</strong> Digital learning integration, career guidance, and life skills training</li>
                            <li class="mb-2"><i class="fas fa-check-circle text-success me-2"></i> <strong>Transition Support:</strong> Special programs to help students transition to tertiary education</li>
                        </ul>
                        <p>Our 8-4-4 program maintains the rigorous academic standards while incorporating modern teaching methodologies and technology integration.</p>
                    </div>
                </div>
                <div class="col-lg-6 order-lg-1">
                    <img src="https://images.unsplash.com/photo-1521493959102-bdd6677fdd81?q=80&w=1470&auto=format&fit=crop&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D" alt="8-4-4 Learning" class="img-fluid rounded shadow">
                </div>
            </div>

            <div class="row mt-5">
                <div class="col-12">
                    <div class="p-4 bg-light rounded">
                        <h4 class="fw-bold mb-3 text-center">Choosing Between CBC and 8-4-4</h4>
                        <p>We understand that choosing between these systems can be challenging. Our education advisors are available to guide parents in making the best choice for their child based on:</p>
                        <div class="row text-center">
                            <div class="col-md-4 mb-3">
                                <div class="p-3 h-100 bg-white rounded shadow-sm">
                                    <i class="fas fa-child fa-2x mb-3 text-primary"></i>
                                    <h5>Child's Learning Style</h5>
                                    <p>CBC for hands-on learners, 8-4-4 for traditional academic learners</p>
                                </div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <div class="p-3 h-100 bg-white rounded shadow-sm">
                                    <i class="fas fa-graduation-cap fa-2x mb-3 text-primary"></i>
                                    <h5>Future Education Plans</h5>
                                    <p>Local vs international university considerations</p>
                                </div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <div class="p-3 h-100 bg-white rounded shadow-sm">
                                    <i class="fas fa-briefcase fa-2x mb-3 text-primary"></i>
                                    <h5>Career Aspirations</h5>
                                    <p>Professional paths and required qualifications</p>
                                </div>
                            </div>
                        </div>
                        <p class="text-center mt-3">Book a consultation with our education experts to discuss which system would be most suitable for your child.</p>
                        <div class="text-center">
                            <a href="#contact" class="btn btn-primary">Schedule Consultation</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Contact Section -->
    <section id="contact">
        <div class="container">
            <h2 class="text-center section-title">Contact Us</h2>
            <div class="row">
                <div class="col-lg-6 mb-4">
                    <div class="contact-info p-4 h-100 bg-light rounded">
                        <h4 class="fw-bold mb-4">Get In Touch</h4>
                        <!-- <div class="mb-4">
                            <h5><i class="fas fa-map-marker-alt text-primary me-2"></i> Main Campus</h5>
                            <p>Pioneer House, 5th Floor<br>Nairobi CBD, Kenya</p>
                        </div> -->
                        <div class="mb-4">
                            <h5><i class="fas fa-phone-alt text-primary me-2"></i> Phone</h5>
                            <p>+254 728 432784<br></p>
                        </div>
                        <div class="mb-4">
                            <h5><i class="fas fa-envelope text-primary me-2"></i> Email</h5>
                            <p>admissions@learn.yeahkenyan.com<br>info@learn.yeahkenyan.com</p>
                        </div>
                        <div class="mb-4">
                            <h5><i class="fas fa-clock text-primary me-2"></i> Office Hours</h5>
                            <p>Monday - Friday: 8:00 AM - 5:00 PM<br>Saturday: 9:00 AM - 2:00 PM</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="contact-form bg-white p-4 rounded shadow">
                        <h4 class="fw-bold mb-4">Send Us a Message</h4>
                        <form>
                            <div class="mb-3">
                                <input type="text" class="form-control" placeholder="Your Name" required>
                            </div>
                            <div class="mb-3">
                                <input type="email" class="form-control" placeholder="Your Email" required>
                            </div>
                            <div class="mb-3">
                                <select class="form-select">
                                    <option selected>Select Inquiry Type</option>
                                    <option>Admissions (CBC)</option>
                                    <option>Admissions (8-4-4)</option>
                                    <option>Curriculum Inquiry</option>
                                    <option>General Information</option>
                                    <option>Other</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <textarea class="form-control" placeholder="Your Message" rows="5" required></textarea>
                            </div>
                            <div class="text-center">
                                <button type="submit" class="btn btn-primary btn-lg">Send Message</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="kenya-flag-theme">
        <div class="container">
            <div class="row">
                <div class="col-md-4 mb-4 mb-md-0">
                    <h5>Yeah Kenyan Academy</h5>
                    <p>Committed to excellence in both CBC and 8-4-4 education systems for holistic student development.</p>
                    <div class="social-icons mt-3">
                        <a href="#" class="me-2"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" class="me-2"><i class="fab fa-twitter"></i></a>
                        <a href="#" class="me-2"><i class="fab fa-instagram"></i></a>
                        <a href="#" class="me-2"><i class="fab fa-linkedin-in"></i></a>
                        <a href="#" class="me-2"><i class="fab fa-youtube"></i></a>
                    </div>
                </div>
                <div class="col-md-4 mb-4 mb-md-0">
                    <h5>Quick Links</h5>
                    <div class="footer-links">
                        <a href="#home" class="d-block mb-2">Home</a>
                        <a href="#about" class="d-block mb-2">About Us</a>
                        <a href="#curriculum" class="d-block mb-2">Curriculum</a>
                        <a href="#contact" class="d-block mb-2">Contact</a>
                        <a href="login.php" class="d-block mb-2">Login Portal</a>
                    </div>
                </div>
                <div class="col-md-4">
                    <h5>Newsletter</h5>
                    <p>Subscribe to get updates on our programs and admission dates.</p>
                    <form class="mt-3">
                        <div class="input-group mb-3">
                            <input type="email" class="form-control" placeholder="Your Email" required>
                            <button class="btn btn-primary" type="submit">Subscribe</button>
                        </div>
                    </form>
                </div>
            </div>
            <hr class="mt-4 mb-3" style="border-color: rgba(255,255,255,0.1);">
            <div class="text-center">
                <p class="mb-0">&copy; 2023 Yeah Kenyan Academy. All rights reserved. | <a href="#" class="text-white">Privacy Policy</a> | <a href="#" class="text-white">Terms of Service</a></p>
            </div>
        </div>
    </footer>

    <script src="bootstrap.bundle.min.js"></script>
    <script>
        // Navbar scroll effect
        window.addEventListener('scroll', function() {
            const navbar = document.querySelector('.navbar');
            if (window.scrollY > 50) {
                navbar.classList.add('scrolled');
            } else {
                navbar.classList.remove('scrolled');
            }
        });

        // Smooth scrolling for all links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function(e) {
                e.preventDefault();
                document.querySelector(this.getAttribute('href')).scrollIntoView({
                    behavior: 'smooth'
                });
            });
        });
    </script>
</body>

</html>