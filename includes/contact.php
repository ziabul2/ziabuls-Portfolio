    <!-- Contacts Section -->
    <section id="contacts" class="contacts">
        <div class="container">
            <div class="section-header">
                <h2><span>#</span><?php echo $data['contact_section']['title']; ?></h2>
                <div class="section-line"></div>
            </div>
            
            <div class="contacts-content">
                <div class="contacts-left-col" style="flex: 1.5;">
                    <div class="contacts-text" style="margin-bottom: 30px;">
                        <p><?php echo $data['contact_section']['intro']; ?></p>
                    </div>
                    
                    <div class="contact-form-container">
                    <?php if(isset($_GET['contact']) && $_GET['contact'] == 'success'): ?>
                        <div style="background: rgba(152, 195, 121, 0.1); color: #98c379; padding: 15px; border-radius: 4px; margin-bottom: 20px; border: 1px solid #98c379;">
                            <i class="fas fa-check-circle"></i> Message sent successfully! I'll get back to you soon.
                        </div>
                    <?php endif; ?>
                    <?php if(isset($_GET['contact']) && $_GET['contact'] == 'error'): ?>
                        <div style="background: rgba(224, 108, 117, 0.1); color: #e06c75; padding: 15px; border-radius: 4px; margin-bottom: 20px; border: 1px solid #e06c75;">
                            <i class="fas fa-exclamation-circle"></i> Error sending message. Please try again.
                        </div>
                    <?php endif; ?>

                    <form action="contact_handler.php" method="POST" class="contact-form">
                        <!-- Honeypot anti-spam -->
                        <input type="text" name="website" style="display:none !important" tabindex="-1" autocomplete="off">
                        
                        <div style="display:grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                            <div class="form-group">
                                <input type="text" name="name" placeholder="Your Name" required>
                            </div>
                            <div class="form-group">
                                <input type="email" name="email" placeholder="Your Email" required>
                            </div>
                        </div>
                        <div class="form-group">
                            <input type="text" name="subject" placeholder="Subject">
                        </div>
                        <div class="form-group">
                            <textarea name="message" placeholder="Talk to me about your project..." rows="5" required></textarea>
                        </div>
                        <div>
                            <button type="submit" class="btn-contact">
                                <i class="fas fa-paper-plane"></i> Send Secure Message
                            </button>
                        </div>
                    </form>
                </div>
                </div>
                
                <div class="contact-box" style="box-shadow: 0 15px 35px rgba(0,0,0,0.3); z-index: 10; border: 1px solid rgba(199, 120, 221, 0.2); background: rgba(40, 44, 51, 0.8); backdrop-filter: blur(10px);">
                    <h4 style="display:flex; align-items:center; gap:10px;">
                        <i class="fas fa-id-card" style="color:var(--primary-color)"></i>
                        <?php echo htmlspecialchars($data['contact_section']['message_title'] ?? 'Contact Info'); ?>
                    </h4>
                    
                    <div class="contact-item">
                        <i class="fas fa-phone"></i>
                        <span><?php echo htmlspecialchars($data['contact_section']['phone'] ?? ''); ?></span>
                    </div>
                    
                    <div class="contact-item" style="position: relative; flex-wrap: wrap;">
                        <i class="fas fa-envelope"></i>
                        <span style="word-break: break-all;"><?php echo htmlspecialchars($data['contact_section']['email'] ?? ''); ?></span>
                        <?php $emailToCopy = htmlspecialchars($data['contact_section']['email'] ?? ''); ?>
                        <button data-email="<?php echo $emailToCopy; ?>" onclick="copyEmailToClipboard(this)" 
                                class="btn-copy-email" 
                                title="Copy Email to Clipboard">
                            <i class="fas fa-copy"></i> Copy
                        </button>
                    </div>

                    <!-- Direct Email Button - Ultimate Reliability & Premium Feel -->
                    <?php $directEmail = $data['contact_section']['email'] ?? ''; ?>
                    <div class="direct-mail-wrapper" style="margin-top: 30px; border-top: 1px solid rgba(255,255,255,0.05); padding-top: 25px;">
                        <a href="mailto:<?php echo $directEmail; ?>" class="btn-direct-mail-premium">
                            <span class="btn-content">
                                <i class="fas fa-envelope-open-text"></i>
                                <span>Send Direct Email</span>
                            </span>
                            <i class="fas fa-external-link-alt" style="font-size: 0.7rem; opacity: 0.5;"></i>
                        </a>
                        <p style="font-size: 0.65rem; color: #666; margin-top: 10px; text-align: center; letter-spacing: 0.5px;">
                            <i class="fas fa-info-circle"></i> Best for professional inquiries via mobile/desktop apps
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>
