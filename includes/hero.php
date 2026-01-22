    <!-- Hero Section -->
    <section id="home" class="hero">
        <div class="container">
            <div class="hero-text">
                <h1><?php echo $data['hero']['name']; ?> is a <?php 
                    $roles = $data['hero']['roles'];
                    foreach ($roles as $index => $role) {
                        echo '<span class="text-primary">' . $role . '</span>';
                        if ($index < count($roles) - 1) echo ' and ';
                    }
                ?></h1>
                <p><?php echo $data['hero']['description']; ?></p>
                <a href="#contacts" class="btn"><?php echo $data['hero']['contact_button_text']; ?></a>
            </div>
            <div class="hero-img">
                <img src="<?php echo $data['hero']['image']; ?>" alt="<?php echo $data['hero']['name']; ?>">
                <div class="hero-bg-logo"><i class="fas fa-code fa-3x" style="color:var(--text-color)"></i></div>
            </div>
        </div>
    </section>

    <!-- Quote Section -->
    <section class="quote-section">
        <div class="container">
            <div class="quote-box">
                <p><?php echo $data['hero']['quote']; ?></p>
                <div class="quote-author">- <?php echo $data['hero']['quote_author']; ?></div>
            </div>
        </div>
    </section>
