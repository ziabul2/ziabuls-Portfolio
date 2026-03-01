<?php
/**
 * API Endpoint: Validate Portfolio Data
 * 
 * Validates form data before saving to check for errors
 * 
 * Usage:
 *   POST /admin/api/validate.php
 *   Parameters: section, data (as JSON or form fields)
 *   
 * Returns:
 *   {
 *     "valid": true/false,
 *     "errors": {},
 *     "timestamp": 1234567890
 *   }
 */

header('Content-Type: application/json');

// Only accept POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['valid' => false, 'errors' => ['method' => 'Method not allowed']]);
    exit;
}

$section = $_POST['section'] ?? null;

if (!$section) {
    http_response_code(400);
    echo json_encode(['valid' => false, 'errors' => ['section' => 'Missing section parameter']]);
    exit;
}

// Get data
$data = [];
$raw_data = $_POST['data'] ?? null;

if ($raw_data && is_string($raw_data)) {
    $data = json_decode($raw_data, true);
} else {
    $data = $_POST;
    unset($data['section']);
}

// Run validation
$errors = validateSection($section, $data);

// Return result
http_response_code(empty($errors) ? 200 : 400);
echo json_encode([
    'valid' => empty($errors),
    'errors' => $errors,
    'timestamp' => time()
]);
exit;

/**
 * Validate a section's data
 */
function validateSection($section, $data) {
    $errors = [];
    
    switch ($section) {
        case 'hero':
            $errors = validateHero($data);
            break;
        case 'about_section':
            $errors = validateAbout($data);
            break;
        case 'skills':
            $errors = validateSkills($data);
            break;
        case 'projects_section':
            $errors = validateProjects($data);
            break;
        case 'blog_posts':
            $errors = validateBlog($data);
            break;
        case 'contact':
            $errors = validateContact($data);
            break;
        case 'seo':
            $errors = validateSeo($data);
            break;
        case 'social_links':
            $errors = validateSocial($data);
            break;
        default:
            if (empty($data)) {
                $errors['general'] = "Section data cannot be empty";
            }
    }
    
    return $errors;
}

function validateHero($data) {
    $errors = [];
    
    if (empty($data['hero_name'])) {
        $errors['hero_name'] = 'Display name is required';
    } elseif (strlen($data['hero_name']) > 100) {
        $errors['hero_name'] = 'Name must be less than 100 characters';
    }
    
    if (empty($data['hero_image'])) {
        $errors['hero_image'] = 'Image is required';
    } elseif (strpos($data['hero_image'], 'assets/') !== 0) {
        $errors['hero_image'] = 'Image must be in assets folder';
    }
    
    if (empty($data['hero_desc'])) {
        $errors['hero_desc'] = 'Description is required';
    } elseif (strlen($data['hero_desc']) > 500) {
        $errors['hero_desc'] = 'Description must be less than 500 characters';
    }
    
    if (isset($data['hero_quote']) && strlen($data['hero_quote']) > 200) {
        $errors['hero_quote'] = 'Quote must be less than 200 characters';
    }
    
    return $errors;
}

function validateAbout($data) {
    $errors = [];
    
    if (empty($data['about_image'])) {
        $errors['about_image'] = 'Image is required';
    } elseif (strpos($data['about_image'], 'assets/') !== 0) {
        $errors['about_image'] = 'Image must be in assets folder';
    }
    
    if (empty($data['about_intro'])) {
        $errors['about_intro'] = 'Introduction is required';
    }
    
    return $errors;
}

function validateSkills($data) {
    $errors = [];
    
    if (empty($data['skills']) || !is_array($data['skills'])) {
        $errors['skills'] = 'Skills data is required';
    } else {
        foreach ($data['skills'] as $idx => $skill) {
            if (empty($skill['category'])) {
                $errors["skill_{$idx}_category"] = "Skill category {$idx} is required";
            }
            if (empty($skill['items'])) {
                $errors["skill_{$idx}_items"] = "Skill items for {$idx} are required";
            }
        }
    }
    
    return $errors;
}

function validateProjects($data) {
    $errors = [];
    
    if (empty($data['projects']) || !is_array($data['projects'])) {
        $errors['projects'] = 'Projects data is required';
    } else {
        foreach ($data['projects'] as $idx => $project) {
            if (empty($project['title'])) {
                $errors["project_{$idx}_title"] = "Project title {$idx} is required";
            }
            if (empty($project['description'])) {
                $errors["project_{$idx}_description"] = "Project description {$idx} is required";
            }
        }
    }
    
    return $errors;
}

function validateBlog($data) {
    $errors = [];
    
    if (!is_array($data)) {
        $errors['general'] = 'Blog data must be an array';
    }
    
    return $errors;
}

function validateContact($data) {
    $errors = [];
    
    if (isset($data['email']) && !empty($data['email'])) {
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Email format is invalid';
        }
    }
    
    return $errors;
}

function validateSeo($data) {
    $errors = [];
    
    if (empty($data['title'])) {
        $errors['title'] = 'SEO title is required';
    }
    
    if (isset($data['favicon']) && !empty($data['favicon'])) {
        if (strpos($data['favicon'], 'assets/') !== 0) {
            $errors['favicon'] = 'Favicon must be in assets folder';
        }
    }
    
    return $errors;
}

function validateSocial($data) {
    $errors = [];
    
    if (!is_array($data)) {
        $errors['general'] = 'Social links must be an array';
    } else {
        foreach ($data as $idx => $link) {
            if (empty($link['platform'])) {
                $errors["social_{$idx}_platform"] = "Platform {$idx} is required";
            }
            if (empty($link['url'])) {
                $errors["social_{$idx}_url"] = "URL for {$idx} is required";
            } elseif (!filter_var($link['url'], FILTER_VALIDATE_URL)) {
                $errors["social_{$idx}_url"] = "URL for {$idx} is invalid";
            }
        }
    }
    
    return $errors;
}
?>
