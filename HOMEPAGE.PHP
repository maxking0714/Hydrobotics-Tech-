<?php
/**
 * HYDROBOTICS Homepage
 * Modern, responsive website for tech-driven contracting and logistics
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HYDROBOTICS - Smart Tech. Reliable Service.</title>
    <link rel="stylesheet" href="css/theme.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --primary: #00A8E8;
            --secondary: #004E89;
            --accent: #00FF41;
            --dark: #1A1A1A;
            --grey: #3A3A3A;
            --light: #F5F5F5;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: var(--dark);
            overflow-x: hidden;
        }

        /* Navigation */
        nav {
            background: var(--dark);
            padding: 1rem 2rem;
            position: sticky;
            top: 0;
            z-index: 100;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            font-size: 1.8rem;
            font-weight: bold;
            color: var(--accent);
            letter-spacing: 2px;
        }

        .nav-links {
            display: flex;
            gap: 2rem;
            list-style: none;
        }

        .nav-links a {
            color: var(--light);
            text-decoration: none;
            transition: color 0.3s;
        }

        .nav-links a:hover {
            color: var(--accent);
        }

        /* Floating Menu */
        .menu-toggle {
            position: fixed;
            top: 100px;
            left: 20px;
            z-index: 101;
            background: var(--accent);
            color: var(--dark);
            border: none;
            padding: 10px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 1.2rem;
        }

        .floating-menu {
            position: fixed;
            top: 180px;
            left: 20px;
            background: var(--dark);
            color: var(--light);
            display: none;
            flex-direction: column;
            padding: 10px;
            border-radius: 4px;
            z-index: 101;
        }

        .floating-menu a {
            color: var(--light);
            text-decoration: none;
            padding: 5px 0;
            transition: color 0.3s;
        }

        .floating-menu a:hover {
            color: var(--accent);
        }

        /* Hero Section */
        .hero {
            background: linear-gradient(135deg, rgba(0, 78, 137, 0.8) 0%, rgba(26, 26, 26, 0.8) 100%), url('https://images.unsplash.com/photo-1485827404703-89b55fcc595e?ixlib=rb-4.0.3&auto=format&fit=crop&w=1600&q=80') no-repeat center center;
            background-size: cover;
            color: var(--light);
            padding: 10rem 2rem 8rem;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .hero-content {
            position: relative;
            z-index: 1;
        }

        .hero h1 {
            font-size: 3.5rem;
            margin-bottom: 1rem;
            letter-spacing: 3px;
            text-transform: uppercase;
        }

        .hero-tagline {
            font-size: 1.5rem;
            color: var(--accent);
            margin-bottom: 2rem;
            font-weight: 600;
        }

        .btn {
            display: inline-block;
            padding: 0.8rem 2rem;
            margin: 0.5rem;
            background: var(--accent);
            color: var(--dark);
            text-decoration: none;
            border-radius: 4px;
            font-weight: bold;
            transition: transform 0.3s, box-shadow 0.3s;
            border: none;
            cursor: pointer;
        }

        .btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(0, 255, 65, 0.3);
        }

        .btn-outline {
            background: transparent;
            border: 2px solid var(--accent);
            color: var(--accent);
        }

        /* Sections */
        section {
            padding: 4rem 2rem;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        h2 {
            font-size: 2.5rem;
            margin-bottom: 2rem;
            color: var(--secondary);
            text-align: center;
            text-transform: uppercase;
            letter-spacing: 2px;
        }

        /* About Section */
        .about {
            background: var(--light);
        }

        .values {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
            margin-top: 2rem;
        }

        .value-card {
            background: white;
            padding: 2rem;
            border-left: 4px solid var(--primary);
            border-radius: 4px;
            transition: transform 0.3s, box-shadow 0.3s;
        }

        .value-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0, 168, 232, 0.2);
        }

        .value-card h3 {
            color: var(--primary);
            margin-bottom: 1rem;
        }

        /* Services Section */
        .services {
            background: white;
        }

        .service-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            margin-top: 2rem;
        }

        .service-card {
            background: linear-gradient(135deg, var(--dark), var(--grey));
            color: var(--light);
            padding: 2rem;
            border-radius: 8px;
            text-align: center;
            transition: transform 0.3s, box-shadow 0.3s;
        }

        .service-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
            border-radius: 8px 8px 0 0;
            margin-bottom: 1rem;
        }

        .footer {
            background: var(--dark);
            color: var(--light);
            padding: 3rem 2rem;
        }

        .footer-content {
            max-width: 1200px;
            margin: 0 auto;
            text-align: center;
        }

        .social-links {
            display: flex;
            justify-content: center;
            flex-wrap: wrap;
            gap: 1rem;
            margin-top: 1.5rem;
        }

        .social-links a {
            color: var(--light);
            background: rgba(5, 243, 56, 0.08);
            padding: 0.75rem 1.25rem;
            border-radius: 999px;
            text-decoration: none;  
            transition: background 0.3s, transform 0.3s;    
        }

        .social-links a:hover {
            background: rgba(0,255,65,0.18);
            transform: translateY(-2px);
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-20px); }
        }
    </style>
</head>
<body>
    <nav>
        <div class="logo"> <a> HYDROBOTICS </a> </div>
        <ul class="nav-links">
            <li><a href="about.php">About</a></li>
            <li><a href="faq.php">FAQ</a></li>
            <li><a href="feed.php">Social Feed</a></li>
            <li><a href="login.php">Login</a></li>
        </ul>
    </nav>

    <button id="menu-toggle" class="menu-toggle">☰</button>
    <div id="floating-menu" class="floating-menu">
        <a href="index.php">Home</a>
        <a href="about.php">About</a>
        <a href="faq.php">FAQ</a>
        <a href="feed.php">Social Feed</a>
        <a href="login.php">Login</a>
    </div>

    <section class="hero">
        <div class="hero-content">
            <h1>HYDROBOTICS</h1>
            <p class="hero-tagline">Smart Tech. Reliable Service.</p>
            <a href="#services" class="btn">Explore Services</a>
            <a href="#contact" class="btn btn-outline">Get in Touch</a>
        </div>
    </section>

    <section class="about" id="about">
        <div class="container">
            <h2>About Us</h2>
            <p>Founded in 2024, HYDROBOTICS delivers cutting-edge contracting and logistics solutions powered by innovative technology and unwavering reliability.</p>
            <div class="values">
                <div class="value-card">
                    <h3>Innovation</h3>
                    <p>We leverage the latest technology to solve complex challenges.</p>
                </div>
                <div class="value-card">
                    <h3>Reliability</h3>
                    <p>Our track record speaks for itself with consistent on-time delivery.</p>
                </div>
                <div class="value-card">
                    <h3>Excellence</h3>
                    <p>We maintain the highest standards in every project we undertake.</p>
                </div>
            </div>
        </div>
    </section>

    <section class="services" id="services">
        <div class="container">
            <h2>Our Services</h2>
            <div class="service-grid">
                <div class="service-card">
                    <img src="https://images.unsplash.com/photo-1581091226825-a6a2a5aee158?ixlib=rb-4.0.3&auto=format&fit=crop&w=300&q=80" alt="Contracting" class="service-image">
                    <h3>Contracting</h3>
                    <p>Full-scale contracting solutions with precision engineering and advanced project management.</p>
                </div>
                <div class="service-card">
                    <img src="https://images.unsplash.com/photo-1586528116311-ad8dd3c8310d?ixlib=rb-4.0.3&auto=format&fit=crop&w=300&q=80" alt="Logistics" class="service-image">
                    <h3>Logistics</h3>
                    <p>End-to-end logistics coordination with real-time tracking and optimization.</p>
                </div>
                <div class="service-card">
                    <img src="https://tse2.mm.bing.net/th/id/OIP.bVi3Ifb2hoB1zWH1JqCHQAHaHa?cb=thfc1falcon&rs=1&pid=ImgDetMain&o=7&rm=3" alt="Innovative Technology" class="service-image">
                    <h3>Innovative Technology</h3>
                    <p>Bringing forth new technology devices and equipment to modernize your business and power future-ready systems.</p>
                </div>
                <div class="service-card">
                    <img src="https://images.unsplash.com/photo-1500530855697-b586d89ba3ee?ixlib=rb-4.0.3&auto=format&fit=crop&w=300&q=80" alt="Inventor Network" class="service-image">
                    <h3>Inventor Network</h3>
                    <p>Explore our HYDROBOTICS social platform with an activity feed, post threads, comments, and recruitment tools built on our own technology.</p>
                    <a href="feed.php" class="btn">Open HYDROBOTICS Social</a>
                </div>
            </div>
        </div>
    </section>

    <section class="about" id="contact">
        <div class="container">
            <h2>Get in Touch</h2>
            <p>Ready to partner with HYDROBOTICS? Contact us today for a consultation.</p>
            <a href="mailto:hydrobotics.tech@gmail.com" class="btn">Email Us</a>
        </div>
    </section>

    <section class="footer">
        <div class="footer-content">
            <p>HYDROBOTICS was founded in 2024 to bring smart contracting and logistics solutions to modern businesses.</p>
            <div class="social-links">
                <a href="feed.php">Hydrobotics Social</a>
                <a href="https://www.twitter.com/hydrobotics" target="_blank" rel="noopener noreferrer">Twitter</a>
                <a href="https://www.linkedin.com/company/hydrobotics" target="_blank" rel="noopener noreferrer">LinkedIn</a>
                <a href="https://www.instagram.com/hydrobotics" target="_blank" rel="noopener noreferrer">Instagram</a>
            </div>
        </div>
    </section>

    <script>
        var toggleButton = document.getElementById('menu-toggle');
        var floatingMenu = document.getElementById('floating-menu');
        if (toggleButton && floatingMenu) {
            toggleButton.addEventListener('click', function() {
                floatingMenu.style.display = floatingMenu.style.display === 'flex' ? 'none' : 'flex';
            });
        }
    </script>
<?php include 'ai_chatbot_widget.php'; ?>
</body>
</html>

