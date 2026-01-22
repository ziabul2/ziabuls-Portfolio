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
    <button id="backToTop" title="Go to top" style="display: none; position: fixed; bottom: 30px; right: 30px; z-index: 10000; border: none; outline: none; background-color: var(--primary-color); color: white; cursor: pointer; padding: 15px; border-radius: 50%; font-size: 18px; transition: all 0.3s ease; box-shadow: 0 4px 10px rgba(0, 0, 0, 0.3); opacity: 1; width: 50px; height: 50px; display: flex; align-items: center; justify-content: center;"><i class="fas fa-arrow-up"></i></button>
