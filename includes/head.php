<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <?php
    // Determine Page Title & Meta
    $pageTitle = $page_title ?? $data['seo']['title'] ?? 'ZIMBABU';
    $metaDesc = $meta_description ?? $data['seo']['description'] ?? '';
    $metaKeywords = $meta_tags ?? $data['seo']['keywords'] ?? '';
    $metaRobot = $data['seo']['robots'] ?? 'index, follow';
    $metaAuthor = $data['seo']['author'] ?? 'Ziabul Islam';
    $ogImageSource = $og_image ?? $data['seo']['og_image'] ?? $data['seo']['favicon'] ?? '';
    
    // Absolute URL logic for Social Sharing
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
    $domainName = $_SERVER['HTTP_HOST'];
    $currentUrl = $protocol . $domainName . $_SERVER['REQUEST_URI'];
    
    // Improved siteRoot detection for portability (Subfolder vs Root)
    $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
    $siteRoot = $protocol . $domainName . rtrim($scriptDir, '/') . '/';
    
    $ogImageUrl = $ogImageSource;
    if (!empty($ogImageSource) && !str_starts_with($ogImageSource, 'http')) {
        $ogImageUrl = $siteRoot . ltrim($ogImageSource, '/');
    }
    ?>

    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    
    <!-- Global SEO -->
    <link rel="canonical" href="<?php echo htmlspecialchars($currentUrl); ?>">
    <meta name="description" content="<?php echo htmlspecialchars($metaDesc); ?>">
    <meta name="keywords" content="<?php echo htmlspecialchars($metaKeywords); ?>">
    <meta name="author" content="<?php echo htmlspecialchars($metaAuthor); ?>">
    <meta name="robots" content="<?php echo htmlspecialchars($metaRobot); ?>">
    
    <!-- Open Graph / Social -->
    <meta property="og:title" content="<?php echo htmlspecialchars($pageTitle); ?>">
    <meta property="og:description" content="<?php echo htmlspecialchars($metaDesc); ?>">
    <meta property="og:image" content="<?php echo htmlspecialchars($ogImageUrl); ?>">
    <meta property="og:url" content="<?php echo htmlspecialchars($currentUrl); ?>">
    <meta property="og:type" content="website">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?php echo htmlspecialchars($pageTitle); ?>">
    <meta name="twitter:description" content="<?php echo htmlspecialchars($metaDesc); ?>">
    <meta name="twitter:image" content="<?php echo htmlspecialchars($ogImageUrl); ?>">

    <!-- JSON-LD Structured Data (Person & WebSite) -->
    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "Person",
      "name": "<?php echo htmlspecialchars($data['hero']['name'] ?? 'Ziabul Islam'); ?>",
      "url": "<?php echo htmlspecialchars($siteRoot); ?>",
      "image": "<?php echo htmlspecialchars($ogImageUrl); ?>",
      "sameAs": [
        <?php 
        $socials = [];
        if (isset($data['social_links'])) {
            foreach ($data['social_links'] as $link) {
                if (!empty($link['url'])) $socials[] = '"' . htmlspecialchars($link['url']) . '"';
            }
        }
        echo implode(",\n        ", $socials);
        ?>
      ],
      "jobTitle": "<?php echo htmlspecialchars($data['footer']['role'] ?? 'Full Stack Developer'); ?>",
      "description": "<?php echo htmlspecialchars($metaDesc); ?>"
    }
    </script>
    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "WebSite",
      "url": "<?php echo htmlspecialchars($siteRoot); ?>",
      "name": "<?php echo htmlspecialchars($data['seo']['title'] ?? 'Ziabul Portfolio'); ?>",
      "author": {
        "@type": "Person",
        "name": "<?php echo htmlspecialchars($data['hero']['name'] ?? 'Ziabul Islam'); ?>"
      }
    }
    </script>
    
    <?php 
    $gTags = $data['seo']['google_tags'] ?? [];
    if (!empty($data['seo']['google_analytics']) && !in_array($data['seo']['google_analytics'], $gTags)) {
        $gTags[] = $data['seo']['google_analytics'];
    }
    ?>
    <?php if(!empty($gTags)): ?>
    <!-- Google Tags (G-TAG / GA4) -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=<?php echo htmlspecialchars($gTags[0]); ?>"></script>
    <script>
      window.dataLayer = window.dataLayer || [];
      function gtag(){dataLayer.push(arguments);}
      gtag('js', new Date());
      <?php foreach ($gTags as $tagId): ?>
      gtag('config', '<?php echo htmlspecialchars($tagId); ?>');
      <?php endforeach; ?>
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
