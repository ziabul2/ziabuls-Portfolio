    <!-- Footer -->
    <footer>
        <div class="container">
            <div class="footer-content">
                <div class="footer-left">
                    <div class="logo">
                         <i class="fas fa-terminal"></i> <?php echo $data['footer']['logo_text']; ?>
                    </div>
                    <p><?php echo $data['footer']['email']; ?></p>
                    <p><?php echo $data['footer']['role']; ?></p>
                </div>
                <div class="footer-right">
                    <h3><?php echo $data['footer']['media_title']; ?></h3>
                    <div class="footer-socials">
                        <?php foreach ($data['social_links'] as $link): ?>
                            <a href="<?php echo $link['url']; ?>" target="_blank"><i class="<?php echo $link['icon']; ?>"></i></a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <div style="text-align:center; margin-top:30px; font-size:0.9rem; color:#666;">
                &copy; Copyright <?php echo date("Y"); ?>. <?php echo $data['footer']['copyright_text']; ?>.
            </div>
        </div>
    </footer>

    <!-- Back to Top Button -->
     <button id="backToTop" title="Go to top"><i class="fas fa-arrow-up"></i></button>
