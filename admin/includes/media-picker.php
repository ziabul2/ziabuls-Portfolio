<!-- Media Picker Modal -->
<div id="mediaPickerModal" class="modal">
    <div class="modal-content">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
            <h2>Select Image</h2>
            <button type="button" class="btn-remove" style="position:static" onclick="closeMediaPicker()">&times;</button>
        </div>
        
        <div style="margin-bottom: 20px; padding: 15px; background: rgba(255,255,255,0.05); border-radius: 4px;">
            <h3>Upload New Photo</h3>
            <form id="uploadForm" enctype="multipart/form-data" style="display:flex; gap:10px; margin-top:10px;">
                <input type="file" name="file" id="fileInput" accept="image/*" required>
                <button type="button" class="btn-login" style="width:auto; padding: 5px 15px;" onclick="uploadMedia()">Upload</button>
            </form>
            <div id="uploadStatus" style="margin-top:10px; font-size:12px;"></div>
        </div>

        <div class="asset-grid" id="assetGrid">
            <!-- Assets will be loaded here via JS -->
        </div>
    </div>
</div>

<?php
// Calculate Dynamic Web Root to avoid hardcoded /cv/
$uploadScriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])); // e.g., /cv/admin
// Go up one level to get project root
$projectRoot = dirname($uploadScriptDir); // e.g., /cv
// Normalize and ensure trailing slash
$webRoot = rtrim($projectRoot, '/') . '/'; 
?>
<script>
let currentTargetInputId = '';
let currentTargetPreviewId = '';
const WEB_ROOT = "<?php echo $webRoot; ?>"; // Pass to JS

function openMediaPicker(inputId, previewId) {
    currentTargetInputId = inputId;
    currentTargetPreviewId = previewId;
    document.getElementById('mediaPickerModal').style.display = 'block';
    loadAssets();
}

function closeMediaPicker() {
    document.getElementById('mediaPickerModal').style.display = 'none';
}

function loadAssets() {
    const grid = document.getElementById('assetGrid');
    grid.innerHTML = '<p>Loading assets...</p>';
    
    fetch('api/assets.php')
        .then(response => response.json())
        .then(assets => {
            grid.innerHTML = '';
            assets.forEach(asset => {
                const div = document.createElement('div');
                div.className = 'asset-item';
                div.onclick = () => selectAsset(asset);
                div.innerHTML = `
                    <img src="../${asset}" alt="${asset}">
                    <p>${asset.split('/').pop()}</p>
                `;
                grid.appendChild(div);
            });
        });
}

function selectAsset(assetPath) {
    // Generate full path using dynamic WEB_ROOT
    let fullPath = assetPath;
    if (!assetPath.startsWith('/')) {
        fullPath = WEB_ROOT + assetPath;
    }
    
    document.getElementById(currentTargetInputId).value = fullPath;
    if (currentTargetPreviewId) {
        document.getElementById(currentTargetPreviewId).src = fullPath;
    }
    closeMediaPicker();
}

function uploadMedia() {
    const status = document.getElementById('uploadStatus');
    const fileInput = document.getElementById('fileInput');
    if (!fileInput.files.length) return;

    status.innerHTML = 'Uploading...';
    const formData = new FormData();
    formData.append('file', fileInput.files[0]);

    fetch('api/upload.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            status.innerHTML = '<span style="color:var(--success-color)">Upload successful!</span>';
            loadAssets();
        } else {
            status.innerHTML = '<span style="color:var(--error-color)">' + result.error + '</span>';
        }
    });
}

// Close modal when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById('mediaPickerModal');
    if (event.target == modal) {
        closeMediaPicker();
    }
}
</script>
