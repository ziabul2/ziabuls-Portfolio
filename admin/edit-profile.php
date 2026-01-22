<?php
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/functions.php';

$data = getPortfolioData();
$flash = getFlashMessage();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Update Hero section
    $data['hero']['name'] = sanitizeInput($_POST['hero_name']);
    $data['hero']['description'] = sanitizeInput($_POST['hero_desc']);
    $data['hero']['quote'] = sanitizeInput($_POST['hero_quote']);
    $data['hero']['quote_author'] = sanitizeInput($_POST['hero_author']);
    $data['hero']['status_highlight'] = sanitizeInput($_POST['status_highlight']);
    
    // Update About section
    $data['about_section']['intro'] = sanitizeInput($_POST['about_intro']);
    if (isset($_POST['about_paragraphs']) && is_array($_POST['about_paragraphs'])) {
        $data['about_section']['paragraphs'] = array_map('sanitizeInput', array_filter($_POST['about_paragraphs']));
    }

    // Update Social Links
    if (isset($_POST['socials']) && is_array($_POST['socials'])) {
        $data['social_links'] = [];
        foreach ($_POST['socials'] as $social) {
            if (!empty($social['platform']) && !empty($social['url'])) {
                $data['social_links'][] = [
                    'platform' => sanitizeInput($social['platform']),
                    'url' => sanitizeInput($social['url']),
                    'icon' => sanitizeInput($social['icon'])
                ];
            }
        }
    }

    if (savePortfolioData($data)) {
        setFlashMessage('Profile updated successfully!');
        header('Location: edit-profile.php');
        exit;
    } else {
        setFlashMessage('Error saving profile', 'error');
    }
}
?>

<div class="section-header" style="margin-bottom: 30px;">
    <h1>Edit Profile & About</h1>
    <a href="index.php" class="btn-edit"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
</div>

<?php if ($flash): ?>
    <div class="<?php echo $flash['type']; ?>-msg"><?php echo $flash['message']; ?></div>
<?php endif; ?>

<form method="POST">
    <div class="editor-card">
        <h2>Hero Section</h2>
        <div class="form-group">
            <label for="hero_name">Display Name</label>
            <input type="text" id="hero_name" name="hero_name" value="<?php echo htmlspecialchars($data['hero']['name']); ?>" required>
        </div>
        <div class="form-group">
            <label for="hero_desc">Short Description</label>
            <textarea id="hero_desc" name="hero_desc" required><?php echo htmlspecialchars($data['hero']['description']); ?></textarea>
        </div>
        <div class="form-group">
            <label for="hero_quote">Quote</label>
            <input type="text" id="hero_quote" name="hero_quote" value="<?php echo htmlspecialchars($data['hero']['quote']); ?>">
        </div>
        <div class="form-group">
            <label for="hero_author">Quote Author</label>
            <input type="text" id="hero_author" name="hero_author" value="<?php echo htmlspecialchars($data['hero']['quote_author']); ?>">
        </div>
        <div class="form-group">
            <label for="status_highlight">Status Highlight (Working on...)</label>
            <input type="text" id="status_highlight" name="status_highlight" value="<?php echo htmlspecialchars($data['hero']['status_highlight']); ?>">
        </div>
    </div>

    <div class="editor-card">
        <h2>About Me Section</h2>
        <div class="form-group">
            <label for="about_intro">Intro Text</label>
            <input type="text" id="about_intro" name="about_intro" value="<?php echo htmlspecialchars($data['about_section']['intro']); ?>" required>
        </div>
        
        <h3>Paragraphs</h3>
        <div id="about-paragraphs-container">
            <?php foreach ($data['about_section']['paragraphs'] as $p): ?>
                <div class="form-group repeater-item">
                    <textarea name="about_paragraphs[]" required><?php echo htmlspecialchars($p); ?></textarea>
                    <button type="button" class="btn-remove" onclick="this.parentElement.remove()">Remove</button>
                </div>
            <?php endforeach; ?>
        </div>
        <button type="button" class="btn-add" id="add-about-p">+ Add Paragraph</button>
    </div>

    <div class="editor-card">
        <h2>Social Links</h2>
        <div id="socials-container">
            <?php foreach ($data['social_links'] as $index => $social): ?>
                <div class="repeater-item" style="display:grid; grid-template-columns: 1fr 1fr 1fr; gap: 10px;">
                    <div class="form-group">
                        <label>Platform</label>
                        <input type="text" name="socials[<?php echo $index; ?>][platform]" value="<?php echo htmlspecialchars($social['platform']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>URL</label>
                        <input type="text" name="socials[<?php echo $index; ?>][url]" value="<?php echo htmlspecialchars($social['url']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Icon Class (e.g. fab fa-github)</label>
                        <input type="text" name="socials[<?php echo $index; ?>][icon]" value="<?php echo htmlspecialchars($social['icon']); ?>" required>
                    </div>
                    <button type="button" class="btn-remove" onclick="this.parentElement.remove()">Remove</button>
                </div>
            <?php endforeach; ?>
        </div>
        <button type="button" class="btn-add" id="add-social">+ Add Social Link</button>
    </div>

    <div style="margin: 40px 0; text-align: right;">
        <button type="submit" class="btn-login" style="width: 200px;">Save Profile</button>
    </div>
</form>

<script>
document.getElementById('add-about-p').addEventListener('click', function() {
    const container = document.getElementById('about-paragraphs-container');
    const div = document.createElement('div');
    div.className = 'form-group repeater-item';
    div.innerHTML = `
        <textarea name="about_paragraphs[]" required></textarea>
        <button type="button" class="btn-remove" onclick="this.parentElement.remove()">Remove</button>
    `;
    container.appendChild(div);
});

let socialCount = <?php echo count($data['social_links']); ?>;
document.getElementById('add-social').addEventListener('click', function() {
    const container = document.getElementById('socials-container');
    const div = document.createElement('div');
    div.className = 'repeater-item';
    div.style.display = 'grid';
    div.style.gridTemplateColumns = '1fr 1fr 1fr';
    div.style.gap = '10px';
    div.innerHTML = `
        <div class="form-group">
            <label>Platform</label>
            <input type="text" name="socials[${socialCount}][platform]" required>
        </div>
        <div class="form-group">
            <label>URL</label>
            <input type="text" name="socials[${socialCount}][url]" required>
        </div>
        <div class="form-group">
            <label>Icon Class</label>
            <input type="text" name="socials[${socialCount}][icon]" required>
        </div>
        <button type="button" class="btn-remove" onclick="this.parentElement.remove()">Remove</button>
    `;
    container.appendChild(div);
    socialCount++;
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
