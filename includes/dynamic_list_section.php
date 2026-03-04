<!-- Dynamic List Section: <?php echo htmlspecialchars($title); ?> -->
<section id="<?php echo htmlspecialchars($id); ?>" class="dynamic-section">
    <div class="container">
        <div class="section-header">
            <h2><span>#</span><?php echo htmlspecialchars($title); ?></h2>
            <div class="section-line"></div>
        </div>

        <div class="dynamic-grid">
            <?php foreach ($items as $item): ?>
            <div class="dynamic-item">
                <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 10px;">
                    <h3><?php echo htmlspecialchars($item['org'] ?? ''); ?></h3>
                </div>
                
                <div class="item-badge"><?php echo htmlspecialchars($item['period'] ?? ''); ?></div>
                
                <?php if (isset($item['role']) || isset($item['degree'])): ?>
                <div class="item-role">
                    <?php echo htmlspecialchars($item['role'] ?? $item['degree'] ?? ''); ?>
                </div>
                <?php endif; ?>

                <?php if (isset($item['points']) && is_array($item['points'])): ?>
                <ul class="item-points">
                    <?php foreach ($item['points'] as $point): ?>
                    <li><?php echo htmlspecialchars($point); ?></li>
                    <?php endforeach; ?>
                </ul>
                <?php endif; ?>

                <?php if (isset($item['details'])): ?>
                <div class="item-details">
                    <?php echo htmlspecialchars($item['details']); ?>
                </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
