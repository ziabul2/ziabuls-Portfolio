<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $data['seo']['title']; ?></title>
    <!-- CSS -->
    <?php foreach ($data['seo']['stylesheets'] as $sheet): ?>
        <link rel="stylesheet" href="<?php echo $sheet; ?>?v=<?php echo time(); ?>">
    <?php endforeach; ?>
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="<?php echo $data['seo']['favicon']; ?>">
</head>
