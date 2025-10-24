<?php
require_once 'includes/header.php';
?>

<div class="hero-section">
    <div class="container">
        <h1>Welcome to Our NGO</h1>
        <p>Making a difference in lives, one step at a time</p>
    </div>
</div>

<!-- About Us Section -->
<section id="about" class="py-5">
    <div class="container">
        <h2>About Us</h2>
        
        <div class="about-subsections">
            <div class="subsection">
                <h3>Mission & Vision</h3>
                <p><?= e(getSetting('mission_vision', 'Our mission is to serve humanity...')) ?></p>
            </div>
            
            <div class="subsection">
                <h3>Our History / Story</h3>
                <p><?= e(getSetting('history_story', 'Founded in...')) ?></p>
            </div>
            
            <div class="subsection">
                <h3>The Problem We Address</h3>
                <p><?= e(getSetting('problem_statement', 'We tackle...')) ?></p>
            </div>
            
            <div class="subsection">
                <h3>Leadership / Team</h3>
                <p><?= e(getSetting('leadership_team', 'Our dedicated team...')) ?></p>
            </div>
            
            <div class="subsection">
                <h3>Legal & Registration</h3>
                <p><?= e(getSetting('legal_registration', 'Registered under...')) ?></p>
            </div>
        </div>
    </div>
</section>

<!-- What We Do Section -->
<section id="what-we-do" class="py-5 bg-light">
    <div class="container">
        <h2>What We Do</h2>
        
        <div class="programs">
            <div class="program-item">
                <h3>Core Programs</h3>
                <p><?= e(getSetting('core_programs', 'Education, Healthcare...')) ?></p>
            </div>
            
            <div class="program-item">
                <h3>Current Projects</h3>
                <p><?= e(getSetting('current_projects', 'Active initiatives...')) ?></p>
            </div>
            
            <div class="program-item">
                <h3>Success Stories</h3>
                <p><?= e(getSetting('success_stories', 'Lives we\'ve touched...')) ?></p>
            </div>
            
            <div class="program-item">
                <h3>Testimonials</h3>
                <p><?= e(getSetting('testimonials', 'What people say...')) ?></p>
            </div>
        </div>
    </div>
</section>

<!-- Call to Action -->
<section class="cta-section py-5">
    <div class="container text-center">
        <h2>Get Involved</h2>
        <div class="cta-buttons">
            <a href="/donate" class="btn btn-primary btn-lg">Donate Now</a>
            <a href="/volunteer" class="btn btn-success btn-lg">Become a Volunteer</a>
            <a href="/partner" class="btn btn-info btn-lg">Partnership</a>
        </div>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>