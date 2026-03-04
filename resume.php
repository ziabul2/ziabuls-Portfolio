<?php
require_once __DIR__ . '/helpers/data_loader.php';
$data = loadPortfolioData();
$resume = $data['resume_data'] ?? [];
$personal = $resume['personal_info'] ?? [];
$achievementsData = json_decode(file_get_contents(__DIR__ . '/data/achievements.json'), true) ?? [];

// Helper to format lists
function renderList($items) {
    if (empty($items)) return '';
    $html = '<ul>';
    foreach ($items as $item) {
        $html .= '<li>' . htmlspecialchars($item) . '</li>';
    }
    $html .= '</ul>';
    return $html;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resume | <?php echo htmlspecialchars($data['hero']['name']); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --accent-color: <?php echo $data['theme']['primary_color'] ?? '#c778dd'; ?>;
            --text-color: #333;
            --bg-color: #fff;
            --sidebar-width: 280px;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background: #f0f0f0;
            color: var(--text-color);
            line-height: 1.5;
        }

        /* Page Layout */
        .cv-page {
            width: 210mm;
            min-height: 297mm;
            background: #fff;
            margin: 20px auto;
            padding: 40px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            position: relative;
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }

        .cv-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            border-bottom: 2px solid #eee;
            padding-bottom: 30px;
            margin-bottom: 30px;
        }

        .header-left h1 {
            font-size: 32px;
            text-transform: uppercase;
            letter-spacing: 2px;
            margin-bottom: 5px;
        }
        .header-left .role {
            font-size: 18px;
            color: var(--accent-color);
            text-transform: uppercase;
            font-weight: 600;
        }

        .profile-img {
            width: 120px;
            height: 120px;
            object-fit: cover;
            border-radius: 4px;
        }

        .cv-body {
            display: grid;
            grid-template-columns: 200px 1fr;
            gap: 40px;
            flex: 1;
        }

        .cv-sidebar {
            border-right: 1px solid #eee;
            padding-right: 20px;
        }

        .section-title {
            font-size: 14px;
            font-weight: 800;
            text-transform: uppercase;
            margin-bottom: 15px;
            color: #000;
            letter-spacing: 1px;
            display: flex;
            align-items: center;
        }

        .sidebar-item { margin-bottom: 25px; }
        .sidebar-item h4 { font-size: 12px; font-weight: 700; margin-bottom: 5px; color: #555; }
        .sidebar-item p { font-size: 13px; color: #666; word-break: break-word; }

        .main-section { margin-bottom: 35px; }
        .main-section h3 { font-size: 16px; font-weight: 700; margin-bottom: 15px; border-bottom: 1px solid #eee; padding-bottom: 5px; }

        .exp-item, .edu-item { margin-bottom: 20px; }
        .exp-header, .edu-header { display: flex; justify-content: space-between; font-weight: 700; font-size: 14px; margin-bottom: 5px; }
        .exp-role, .edu-degree { font-style: italic; font-size: 13px; color: #555; margin-bottom: 8px; }
        
        ul { list-style: none; }
        ul li { position: relative; padding-left: 15px; font-size: 13px; color: #444; margin-bottom: 4px; }
        ul li::before { content: "•"; position: absolute; left: 0; color: var(--accent-color); }

        .cert-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
        .cert-item h5 { font-size: 13px; font-weight: 700; }
        .cert-item p { font-size: 12px; color: #777; }

        /* Training & Skills Page 2 */
        .page-break { page-break-before: always; }

        @media print {
            body { background: none; }
            .cv-page { margin: 0; box-shadow: none; }
            .no-print { display: none; }
        }

        .print-btn {
            position: fixed;
            bottom: 30px;
            right: 30px;
            background: var(--accent-color);
            color: #fff;
            border: none;
            padding: 15px 25px;
            border-radius: 30px;
            cursor: pointer;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
            font-weight: 600;
            z-index: 1000;
            display: flex;
            align-items: center;
            gap: 10px;
        }
    </style>
</head>
<body>

    <button class="print-btn no-print" onclick="window.print()">
        <i class="fas fa-print"></i> Print / Save as PDF
    </button>

    <!-- Page 1 -->
    <div class="cv-page">
        <div class="cv-header">
            <div class="header-left">
                <h1><?php echo htmlspecialchars($data['hero']['name']); ?></h1>
                <div class="role"><?php echo htmlspecialchars($data['hero']['roles'][0] ?? ''); ?></div>
            </div>
            <img src="<?php echo htmlspecialchars($data['hero']['image']); ?>" class="profile-img" alt="Profile">
        </div>

        <div class="cv-body">
            <div class="cv-sidebar">
                <div class="sidebar-item">
                    <h2 class="section-title">Contact</h2>
                    <h4>Phone</h4>
                    <p><?php echo htmlspecialchars($data['contact_section']['phone']); ?></p>
                </div>
                <div class="sidebar-item">
                    <h4>Email</h4>
                    <p><?php echo htmlspecialchars($data['contact_section']['email']); ?></p>
                </div>
                <div class="sidebar-item">
                    <h4>Address</h4>
                    <p><?php echo htmlspecialchars($personal['nationality'] === 'Bangladeshi' ? 'Rangpur, Bangladesh' : 'Global'); ?></p>
                </div>
                <div class="sidebar-item">
                    <h4>Portfolio</h4>
                    <p><?php echo htmlspecialchars($_SERVER['HTTP_HOST']); ?></p>
                </div>
            </div>

            <div class="main-content">
                <section class="main-section">
                    <h2 class="section-title">Professional Experience</h2>
                    <?php foreach($resume['professional_experience'] ?? [] as $exp): ?>
                    <div class="exp-item">
                        <div class="exp-header">
                            <span><?php echo htmlspecialchars($exp['org']); ?></span>
                            <span><?php echo htmlspecialchars($exp['period']); ?></span>
                        </div>
                        <div class="exp-role"><?php echo htmlspecialchars($exp['role']); ?></div>
                        <?php echo renderList($exp['points']); ?>
                    </div>
                    <?php endforeach; ?>
                </section>

                <section class="main-section">
                    <h2 class="section-title">Education</h2>
                    <?php foreach($resume['education'] ?? [] as $edu): ?>
                    <div class="edu-item">
                        <div class="edu-header">
                            <span><?php echo htmlspecialchars($edu['org']); ?></span>
                            <span><?php echo htmlspecialchars($edu['period']); ?></span>
                        </div>
                        <div class="edu-degree"><?php echo htmlspecialchars($edu['degree']); ?></div>
                        <p style="font-size: 12px; color: #666;"><?php echo htmlspecialchars($edu['details']); ?></p>
                    </div>
                    <?php endforeach; ?>
                </section>

                <section class="main-section">
                    <h2 class="section-title">Certificates</h2>
                    <div class="cert-grid">
                        <?php 
                        $certs = array_slice($achievementsData, 0, 6); // Take top 6
                        foreach($certs as $cert): ?>
                        <div class="cert-item">
                            <h5><?php echo htmlspecialchars($cert['title']); ?> - <?php echo date('Y', strtotime($cert['completion_date'])); ?></h5>
                            <p><?php echo htmlspecialchars($cert['organization']); ?></p>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </section>
            </div>
        </div>
    </div>

    <!-- Page 2 -->
    <div class="cv-page page-break">
        <div class="cv-body" style="grid-template-columns: 200px 1fr;">
            <div class="cv-sidebar">
                 <div class="sidebar-item">
                    <h2 class="section-title">Programming</h2>
                    <?php 
                    $langs = array_filter($data['skills_section']['categories'], fn($c) => $c['name'] === 'Languages' || $c['name'] === 'Other');
                    foreach($langs as $cat): ?>
                        <p style="font-weight:bold; margin-top:5px; font-size:12px;"><?php echo $cat['name']; ?>:</p>
                        <p><?php echo implode(', ', $cat['items']); ?></p>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="main-content">
                <section class="main-section">
                    <h2 class="section-title">Training Summary</h2>
                    <?php foreach($resume['training_summary'] ?? [] as $train): ?>
                    <div class="exp-item">
                        <div class="exp-header">
                            <span><?php echo htmlspecialchars($train['title']); ?></span>
                        </div>
                        <div class="exp-role"><?php echo htmlspecialchars($train['org']); ?></div>
                        <?php echo renderList($train['points']); ?>
                    </div>
                    <?php endforeach; ?>
                </section>

                <section class="main-section">
                    <h2 class="section-title">Expertise</h2>
                    <?php echo renderList($resume['expertise'] ?? []); ?>
                </section>

                <section class="main-section" style="display:grid; grid-template-columns: 1fr 1fr; gap: 40px;">
                    <div>
                        <h2 class="section-title">Interests</h2>
                        <?php echo renderList($resume['interests'] ?? []); ?>
                    </div>
                    <div>
                        <h2 class="section-title">Personal Information</h2>
                        <div style="font-size:12px; color:#555;">
                            <p><strong>Father's Name:</strong> <?php echo htmlspecialchars($personal['father_name'] ?? ''); ?></p>
                            <p><strong>Mother's Name:</strong> <?php echo htmlspecialchars($personal['mother_name'] ?? ''); ?></p>
                            <p><strong>DOB:</strong> <?php echo htmlspecialchars($personal['dob'] ?? ''); ?></p>
                            <p><strong>NID:</strong> <?php echo htmlspecialchars($personal['nid'] ?? ''); ?></p>
                            <p><strong>Religion:</strong> <?php echo htmlspecialchars($personal['religion'] ?? ''); ?></p>
                            <p><strong>Nationality:</strong> <?php echo htmlspecialchars($personal['nationality'] ?? ''); ?></p>
                        </div>
                    </div>
                </section>

                <section class="main-section">
                    <h2 class="section-title">References</h2>
                    <?php foreach($resume['references'] ?? [] as $ref): ?>
                    <div style="font-size:13px; color:#444; border-left: 3px solid var(--accent-color); padding-left: 15px;">
                        <p><strong><?php echo htmlspecialchars($ref['name']); ?></strong></p>
                        <p><?php echo htmlspecialchars($ref['org']); ?></p>
                        <p><?php echo htmlspecialchars($ref['designation']); ?></p>
                        <p>Phone: <?php echo htmlspecialchars($ref['phone']); ?></p>
                        <p>Email: <?php echo htmlspecialchars($ref['email']); ?></p>
                        <p>Relation: <?php echo htmlspecialchars($ref['relation']); ?></p>
                    </div>
                    <?php endforeach; ?>
                </section>

                <div style="margin-top: 50px; display:flex; justify-content: space-between; align-items: flex-end;">
                    <div style="text-align:center;">
                        <div style="width: 150px; border-top: 1px solid #333; padding-top: 5px; font-size:12px;">Date</div>
                    </div>
                    <div style="text-align:center;">
                        <div style="font-family: 'cursive'; font-size: 20px; margin-bottom: 5px;"><?php echo htmlspecialchars($data['hero']['name']); ?></div>
                        <div style="width: 150px; border-top: 1px solid #333; padding-top: 5px; font-size:12px;">Signature</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

</body>
</html>
