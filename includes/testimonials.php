<?php
require_once __DIR__ . '/../helpers/TestimonialManager.php';
$tm = new TestimonialManager();
$testimonials = $tm->getTestimonials();

if (!empty($testimonials)): 
?>
<section id="testimonials" class="testimonials" style="padding: 60px 0;">
    <div class="container">
        <div class="section-header">
            <h2><span>#</span>testimonials</h2>
            <div class="section-line"></div>
        </div>
        
        <div class="testimonial-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 30px; margin-top: 40px;">
            <?php foreach ($testimonials as $t): ?>
            <div class="testimonial-card">
                <i class="fas fa-quote-left" style="color: var(--accent-color); font-size: 1.5rem; opacity: 0.2; position: absolute; top: 20px; left: 20px;"></i>
                <p style="color: #abb2bf; margin-bottom: 25px; font-style: italic; line-height: 1.6; min-height: 80px;">
                    "<?php echo htmlspecialchars($t['content']); ?>"
                </p>
                <div style="display: flex; align-items: center; gap: 15px; border-top: 1px solid #333; padding-top: 20px;">
                    <img src="<?php echo htmlspecialchars($t['image']); ?>" alt="<?php echo htmlspecialchars($t['client_name']); ?>" style="width: 45px; height: 45px; border-radius: 50%; object-fit: cover; border: 1px solid #444;">
                    <div>
                        <h4 style="color: #fff; margin: 0; font-size: 1rem;"><?php echo htmlspecialchars($t['client_name']); ?></h4>
                        <p style="color: var(--accent-color); margin: 0; font-size: 0.8rem;"><?php echo htmlspecialchars($t['client_role']); ?></p>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>
