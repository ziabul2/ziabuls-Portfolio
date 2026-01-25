    </div>
    <?php
    $adminFooter = $portfolioData['admin_settings']['footer_text'] ?? 'ZIMBABU Admin Panel';
    ?>
    <footer style="text-align:center; padding: 40px; color: #555; border-top: 1px solid #444; margin-top: 60px;">
        <p>&copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars($adminFooter); ?>. All rights reserved.</p>
        
        <!-- Navigation Buttons -->
        <div style="margin-top: 20px; display: flex; justify-content: center; gap: 10px; flex-wrap: wrap;">
            <!-- Back to Dashboard Button (if not on dashboard) -->
            <?php if (basename($_SERVER['PHP_SELF']) !== 'index.php'): ?>
                <a href="index.php" class="footer-btn" style="background: #61afef; color: white; padding: 10px 20px; border-radius: 4px; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; transition: 0.3s;">
                    <i class="fas fa-home"></i> Dashboard
                </a>
            <?php endif; ?>
            
            <!-- Back to Top Button -->
            <button id="backToTop" title="Go to top" style="background: var(--accent-color); color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; display: none; transition: 0.3s; font-size: 14px; align-items: center; gap: 8px;">
                <i class="fas fa-arrow-up"></i> Top
            </button>
        </div>
    </footer>
    <script src="js/admin-script.js"></script>
</body>
</html>
