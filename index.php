<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ZIMBABU | Computer Engineer</title>
    <!-- CSS -->
    <link rel="stylesheet" href="css/style.css">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="assets/ziabul islam - non bg.png">
</head>
<body>

    <!-- Social Sidebar (Left Fixed) -->
    <div class="social-sidebar">
        <div class="social-line"></div>
        <a href="https://github.com/ziabul2" target="_blank" class="social-icon"><i class="fab fa-github"></i></a>
        <a href="https://www.linkedin.com/in/md-ziabul-islam-zimbabu-14b5b21a3/" target="_blank" class="social-icon"><i class="fab fa-linkedin"></i></a>
        <a href="https://www.facebook.com/ziabul123" target="_blank" class="social-icon"><i class="fab fa-facebook"></i></a>
    </div>

    <!-- Navbar -->
    <nav class="navbar">
        <div class="container">
            <a href="#" class="logo">
                <i class="fas fa-terminal"></i> ZIMBABU
            </a>
            <div class="nav-menu">
                <a href="#home" class="nav-link"><span>#</span>home</a>
                <a href="#projects" class="nav-link"><span>#</span>projects</a>
                <a href="#skills" class="nav-link"><span>#</span>skills</a>
                <a href="#about-me" class="nav-link"><span>#</span>about-me</a>
                <a href="#contacts" class="nav-link"><span>#</span>contacts</a>
            </div>
            <div class="hamburger">
                <i class="fas fa-bars"></i>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section id="home" class="hero">
        <div class="container">
            <div class="hero-text">
                <h1>Zimbabu is a <span class="text-primary">computer engineer</span> and <span class="text-primary">account manager</span></h1>
                <p>He crafts responsive websites and manages complex systems with precision.</p>
                <a href="#contacts" class="btn">Contact me !!</a>
            </div>
            <div class="hero-img">
                <!-- Using specified path -->
                <img src="assets/ziabul islam - non bg.png" alt="Ziabul Islam">
                <!-- Decorative elements could mimic the PDF, e.g. a logo outline in bg -->
                <div class="hero-bg-logo"><i class="fas fa-code fa-3x" style="color:var(--text-color)"></i></div>
            </div>
        </div>
    </section>

    <!-- Quote Section -->
    <section class="quote-section">
        <div class="container">
            <div class="quote-box">
                <p>With great power comes great electricity bill</p>
                <div class="quote-author">- Dr. Who</div>
            </div>
        </div>
    </section>

    <!-- Projects Section -->
    <section id="projects" class="projects">
        <div class="container">
            <div class="section-header">
                <h2><span>#</span>projects</h2>
                <div class="section-line"></div>
                <a href="projects.php" class="btn btn-sm">View all ~~></a>
            </div>
            
            <div class="project-grid">
                <!-- Project 1 -->
                <div class="project-card">
                    <div class="project-img">
                        <!-- Placeholder or reuse existing images -->
                        <div style="width:100%; height:100%; display:flex; align-items:center; justify-content:center; background:#444;">
                            <i class="fas fa-laptop-code fa-3x"></i>
                        </div>
                    </div>
                    <div class="project-tags">HTML CSS PHP</div>
                    <div class="project-info">
                        <h3>Portfolio Website</h3>
                        <p>Personal portfolio built with raw PHP and custom CSS.</p>
                        <a href="#" class="btn btn-sm">Live <~></a>
                    </div>
                </div>

                 <!-- Project 2 -->
                 <div class="project-card">
                    <div class="project-img">
                        <div style="width:100%; height:100%; display:flex; align-items:center; justify-content:center; background:#444;">
                            <i class="fas fa-file-invoice fa-3x"></i>
                        </div>
                    </div>
                    <div class="project-tags">Python Excel</div>
                    <div class="project-info">
                        <h3>Accounts System</h3>
                        <p>Automated accounting tool for Rangpur Eye Hospital.</p>
                        <a href="#" class="btn btn-sm">Cached >=></a>
                    </div>
                </div>

                 <!-- Project 3 -->
                 <div class="project-card">
                    <div class="project-img">
                        <div style="width:100%; height:100%; display:flex; align-items:center; justify-content:center; background:#444;">
                            <i class="fas fa-network-wired fa-3x"></i>
                        </div>
                    </div>
                    <div class="project-tags">Networking Cisco</div>
                    <div class="project-info">
                        <h3>Network Setup</h3>
                        <p>Hospital networking infrastructure setup and management.</p>
                        <a href="#" class="btn btn-sm">View -></a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Skills Section -->
    <section id="skills" class="skills">
        <div class="container">
            <div class="section-header">
                <h2><span>#</span>skills</h2>
                <div class="section-line"></div>
            </div>
            
            <div class="skills-content">
                <div class="skills-decor">
                   <!-- Decorative shapes as seen in PDF could go here -->
                   <div style="margin:20px; color:var(--text-color);">
                       <i class="fas fa-shapes fa-2x"></i>
                       <br><br>
                       <i class="fas fa-draw-polygon fa-2x"></i>
                   </div>
                </div>
                
                <div class="skills-boxes">
                    <div class="skill-box">
                        <div class="skill-title">Languages</div>
                        <div class="skill-list">
                            Python C C++<br>Java PHP<br>JavaScript
                        </div>
                    </div>
                    
                    <div class="skill-box">
                        <div class="skill-title">Databases</div>
                        <div class="skill-list">
                            MySQL<br>SQLite
                        </div>
                    </div>
                    
                    <div class="skill-box">
                        <div class="skill-title">Tools</div>
                        <div class="skill-list">
                            VSCode<br>Figma<br>Photoshop<br>Git
                        </div>
                    </div>
                    
                    <div class="skill-box">
                        <div class="skill-title">Other</div>
                        <div class="skill-list">
                            HTML CSS<br>SCSS<br>Rest API
                        </div>
                    </div>
                    
                     <div class="skill-box">
                        <div class="skill-title">Frameworks</div>
                        <div class="skill-list">
                            React<br>Flask<br>Django
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- About Me Section -->
    <section id="about-me" class="about">
        <div class="container">
            <div class="section-header">
                <h2><span>#</span>about-me</h2>
                <div class="section-line"></div>
            </div>
            
            <div class="about-content">
                <div class="about-text">
                    <p>
                        Hello, i’m Ziabul Islam!
                    </p>
                    <p>
                        I’m a self-taught computer engineer based in Rangpur, Bangladesh. I can develop responsive websites from scratch and raise them into modern user-friendly web experiences.
                    </p>
                    <p>
                        Transforming my creativity and knowledge into wwebsites has been my passion for over a year. I have been helping various clients to establish their presence online. I always strive to learn about the newest technologies and frameworks.
                    </p>
                    <br>
                    <a href="#" class="btn">Read more -></a>
                </div>
                <div class="about-img">
                   <!-- Reusing profile generic or placeholder -->
                   <img src="assets/ziabul islam - non bg.png" alt="Profile" style="border-bottom:none; opacity:0.8;">
                </div>
            </div>
        </div>
    </section>

    <!-- Contacts Section -->
    <section id="contacts" class="contacts">
        <div class="container">
            <div class="section-header">
                <h2><span>#</span>contacts</h2>
                <div class="section-line"></div>
            </div>
            
            <div class="contacts-content">
                <div class="contacts-text">
                    <p>
                        I’m interested in freelance opportunities. However, if you have other request or question, don’t hesitate to contact me
                    </p>
                </div>
                
                <div class="contact-box">
                    <h4>Message me here</h4>
                    <div class="contact-item">
                        <i class="fas fa-phone"></i>
                        <span>01581205088</span>
                    </div>
                    <div class="contact-item">
                        <i class="fas fa-envelope"></i>
                        <span>ziabulislam2222@gmail.com</span>
                    </div>
                    <!-- Email Now Button -->
                    <div style="margin-top: 20px;">
                        <a href="mailto:ziabulislam2222@gmail.com" class="btn btn-sm">
                            <i class="fas fa-paper-plane"></i> Email Now
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer>
        <div class="container">
            <div class="footer-content">
                <div class="footer-left">
                    <div class="logo">
                         <i class="fas fa-terminal"></i> ZIMBABU
                    </div>
                    <p>ziabulislam2222@gmail.com</p>
                    <p>Computer Engineer</p>
                </div>
                <div class="footer-right">
                    <h3>Media</h3>
                    <div class="footer-socials">
                        <a href="https://github.com/ziabul2" target="_blank"><i class="fab fa-github"></i></a>
                        <a href="https://www.facebook.com/ziabul123" target="_blank"><i class="fab fa-facebook"></i></a>
                        <a href="https://www.hackerrank.com/profile/ziabul" target="_blank" title="HackerRank"><i class="fab fa-hackerrank"></i></a>
                        <a href="https://www.linkedin.com/in/md-ziabul-islam-zimbabu-14b5b21a3/" target="_blank"><i class="fab fa-linkedin"></i></a>
                    </div>
                </div>
            </div>
            <div style="text-align:center; margin-top:30px; font-size:0.9rem; color:#666;">
                &copy; Copyright <?php echo date("Y"); ?>. Made by Ziabul Islam.
            </div>
        </div>
    </footer>

    <!-- JS -->
    <script src="js/script.js"></script>
</body>
</html>
