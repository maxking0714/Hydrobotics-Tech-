<?php
$page_title = 'About HYDROBOTICS';
include 'header.php';
?>

    <style>
        .hero {
            background: linear-gradient(135deg, rgba(0,78,137,0.85) 0%, rgba(26,26,26,0.85) 100%), url('https://images.unsplash.com/photo-1485827404703-89b55fcc595e?ixlib=rb-4.0.3&auto=format&fit=crop&w=1600&q=80') no-repeat center center;
            background-size: cover;
            color: var(--light);
            padding: 5rem 1rem 3rem;
            text-align: center;
        }

        .hero h1 {
            font-size: 2.5rem;
            margin-bottom: 0.5rem;
            letter-spacing: 2px;
            text-transform: uppercase;
            color: var(--light);
        }

        .hero-tagline {
            color: var(--accent);
            font-weight: 600;
            margin-bottom: 1rem;
        }

        .container {
            max-width: 1100px;
            margin: 0 auto;
            padding: 2rem;
        }

        .about {
            background: var(--light);
            color: var(--dark);
            padding: 3rem 0 4rem;
        }

        h2.about-title {
            color: var(--secondary);
            text-transform: uppercase;
            letter-spacing: 2px;
            text-align: center;
            margin-bottom: 1rem;
        }

        .feature-list li {
            margin-bottom: 0.5rem;
        }
    </style>

    <section class="hero">
        <div class="hero-content">
            <h1>HYDROBOTICS</h1>
            <p class="hero-tagline">Smart Tech. Reliable Service.</p>
        </div>
    </section>

    <section class="about" id="about">
        <div class="container">
            <h2 class="about-title">About Us</h2>
            <p>Our platform combines:</p>
            <ul class="feature-list">
                <li>Innovation in smart equipment and device deployment</li>
                <li>A dedicated social feed for inventors, tech discussions, and recruitment</li>
                <li>Secure login, dashboard access, and audit logging for registrations and posts</li>
            </ul>
            <p>All account actions and published posts are recorded in the admin log so the HYDROBOTICS team can monitor activity and manage the community safely.</p>
        </div>
    </section>

<?php include 'footer.php'; ?>

