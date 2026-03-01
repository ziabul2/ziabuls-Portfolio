<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <?php
    // Determine Page Title & Meta
    // Allow overriding from including page (e.g. blog post)
    $pageTitle = $page_title ?? $data['seo']['title'] ?? 'ZIMBABU';
    $metaDesc = $meta_description ?? $data['seo']['description'] ?? '';
    $metaKeywords = $meta_tags ?? $data['seo']['keywords'] ?? '';
    $metaRobot = $data['seo']['robots'] ?? 'index, follow';
    $metaAuthor = $data['seo']['author'] ?? 'Ziabul Islam';
    $ogImage = $og_image ?? $data['seo']['og_image'] ?? $data['seo']['favicon'];
    
    // Convert relative OG image to absolute if needed (for social platforms)
    // Assuming site base is needed, but for now we output as is or try to make absolute
    ?>

    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    
    <!-- Global SEO -->
    <meta name="description" content="<?php echo htmlspecialchars($metaDesc); ?>">
    <meta name="keywords" content="<?php echo htmlspecialchars($metaKeywords); ?>">
    <meta name="author" content="<?php echo htmlspecialchars($metaAuthor); ?>">
    <meta name="robots" content="<?php echo htmlspecialchars($metaRobot); ?>">
    
    <!-- Open Graph / Social -->
    <meta property="og:title" content="<?php echo htmlspecialchars($pageTitle); ?>">
    <meta property="og:description" content="<?php echo htmlspecialchars($metaDesc); ?>">
    <meta property="og:image" content="<?php echo htmlspecialchars($ogImage); ?>">
    <meta property="og:type" content="website">
    <meta name="twitter:card" content="summary_large_image">
    
    <?php if(!empty($data['seo']['google_analytics'])): ?>
    <!-- Google Analytics -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=<?php echo htmlspecialchars($data['seo']['google_analytics']); ?>"></script>
    <script>
      window.dataLayer = window.dataLayer || [];
      function gtag(){dataLayer.push(arguments);}
      gtag('js', new Date());
      gtag('config', '<?php echo htmlspecialchars($data['seo']['google_analytics']); ?>');
    </script>
    <?php endif; ?>
    
    <?php if(!empty($data['seo']['search_console'])): ?>
    <!-- Search Console -->
    <meta name="google-site-verification" content="<?php echo htmlspecialchars($data['seo']['search_console']); ?>">
    <?php endif; ?>

    <!-- CSS -->
    <?php if(isset($data['seo']['stylesheets'])): ?>
        <?php foreach ($data['seo']['stylesheets'] as $sheet): ?>
            <link rel="stylesheet" href="<?php echo $sheet; ?>?v=<?php echo time(); ?>">
        <?php endforeach; ?>
    <?php endif; ?>
    
    <!-- Dynamic Theme Personalization -->
    <style>
        :root {
            --primary-color: <?php echo htmlspecialchars($data['theme']['primary_color'] ?? '#c778dd'); ?>;
            --accent-blue: <?php echo htmlspecialchars($data['theme']['accent_color'] ?? '#61afef'); ?>;
            /* Update dependent vars if needed */
        }
    </style>
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="<?php echo $data['seo']['favicon']; ?>">
</head>
