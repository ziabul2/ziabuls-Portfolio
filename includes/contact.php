    <!-- Contacts Section -->
    <section id="contacts" class="contacts">
        <div class="container">
            <div class="section-header">
                <h2><span>#</span><?php echo $data['contact_section']['title']; ?></h2>
                <div class="section-line"></div>
            </div>
            
            <div class="contacts-content">
                <div class="contacts-text">
                    <p><?php echo $data['contact_section']['intro']; ?></p>
                </div>
                
                <div class="contact-box">
                    <h4><?php echo $data['contact_section']['message_title']; ?></h4>
                    <div class="contact-item">
                        <i class="fas fa-phone"></i>
                        <span><?php echo $data['contact_section']['phone']; ?></span>
                    </div>
                    <div class="contact-item">
                        <i class="fas fa-envelope"></i>
                        <span><?php echo $data['contact_section']['email']; ?></span>
                    </div>
                    <!-- Email Now Button -->
                    <div style="margin-top: 20px;">
                        <a href="mailto:<?php echo $data['contact_section']['email']; ?>" class="btn btn-sm">
                            <i class="fas fa-paper-plane"></i> <?php echo $data['contact_section']['email_button_text']; ?>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>
