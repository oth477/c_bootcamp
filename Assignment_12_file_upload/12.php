<?php
declare(strict_types=1);

// Configuration constants for Defense-in-Depth validation
const MAX_FILE_SIZE = 2 * 1024 * 1024; // 2 MB maximum size in bytes
const UPLOAD_DIR    = __DIR__ . '/uploads/';


// Strict Whitelist mapping allowed extensions to their exact binary MIME types
const ALLOWED_TYPES = [
    'jpg'  => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'png'  => 'image/png',
    'pdf'  => 'application/pdf'
];

$statusType    = '';
$statusMessage = '';
$uploadDetails = [];



// Process the form submission when a POST request with a file is received
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['uploaded_file'])) {
    $file = $_FILES['uploaded_file'];

    // Layer 0: Check for standard PHP upload errors
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $statusType    = 'error';
        $statusMessage = "Upload failed with PHP error code: " . $file['error'];
    } else {
        $originalName = $file['name'];
        $tmpPath      = $file['tmp_name'];
        $fileSize     = $file['size'];
        $browserMime  = $file['type']; // Untrusted header sent by the client browser

        // Layer 1: Validate File Size (Prevent DoS / Disk Exhaustion)
        if ($fileSize <= 0 || $fileSize > MAX_FILE_SIZE) {
            $statusType    = 'error';
            $statusMessage = sprintf(
                "Security Block [Layer 1 - Size]: File size (%.2f KB) exceeds the 2 MB limit or is empty.",
                $fileSize / 1024
            );
        } else {
            // Layer 2: Strict Extension Whitelist Check
            $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

            if (!array_key_exists($extension, ALLOWED_TYPES)) {
                $statusType    = 'error';
                $statusMessage = "Security Block [Layer 2 - Extension]: Extension '.$extension' is not allowed. Allowed: jpg, png, pdf.";
            } else {
                // Layer 3: Actual Content Inspection (Magic Bytes / MIME Type via Fileinfo)
                $finfo    = finfo_open(FILEINFO_MIME_TYPE);
                $realMime = finfo_file($finfo, $tmpPath);
                finfo_close($finfo);

                $expectedMime = ALLOWED_TYPES[$extension];

                if ($realMime !== $expectedMime) {
                    $statusType    = 'error';
                    $statusMessage = "Security Block [Layer 3 - MIME Inspection]: Extension spoofing detected! "
                                   . "File claims to be '.$extension' ($expectedMime), but actual binary content is '$realMime'.";
                } else {

                    // Layer 4: Cryptographically Secure Random Filename Generation (Anti-Path Traversal)
                    $safeRandomName = bin2hex(random_bytes(16)) . '.' . $extension;

                    // Ensure the target upload directory exists with restricted permissions
                    if (!is_dir(UPLOAD_DIR)) {
                        mkdir(UPLOAD_DIR, 0755, true);
                    }

                    $destinationPath = UPLOAD_DIR . $safeRandomName;


                    // Layer 5: Move Uploaded File Safely and Strip Execution Permissions
                    if (move_uploaded_file($tmpPath, $destinationPath)) {
                        // Set read/write for owner, read-only for others (non-executable: 0644)
                        chmod($destinationPath, 0644);

                        $statusType    = 'success';
                        $statusMessage = "File passed all security layers and was uploaded safely!";
                        $uploadDetails = [
                            'Original Filename (Untrusted)' => $originalName,
                            'Browser MIME Header (Untrusted)' => $browserMime,
                            'Verified Real MIME (Fileinfo)' => $realMime,
                            'File Size'                     => round($fileSize / 1024, 2) . ' KB',
                            'New Cryptographic Filename'    => $safeRandomName,
                            'Saved Path'                    => 'uploads/' . $safeRandomName
                        ];
                    } else {
                        $statusType    = 'error';
                        $statusMessage = "Internal Error: Failed to move the uploaded file to the storage directory.";
                    }
                }
            }
        }
    }
}
?>



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assignment 12 - Multi-Layered Secure File Upload</title>
    <style>
        body { font-family: monospace; background-color: #121212; color: #e0e0e0; padding: 25px; }
        .container { max-width: 750px; margin: 0 auto; background: #1e1e1e; padding: 20px; border: 1px solid #333; border-radius: 6px; }
        h2 { color: #4fc3f7; margin-top: 0; }
        .alert-success { background: #1b5e20; color: #a5d6a7; padding: 12px; border-left: 5px solid #00e676; margin-bottom: 15px; }
        .alert-error { background: #b71c1c; color: #ffcdd2; padding: 12px; border-left: 5px solid #ff5252; margin-bottom: 15px; }
        .form-group { margin-bottom: 15px; }
        input[type="file"] { background: #2c2c2c; padding: 10px; border: 1px solid #444; width: 96%; color: #fff; }
        button { padding: 10px 18px; background: #0288d1; color: white; border: none; border-radius: 4px; cursor: pointer; font-family: monospace; font-size: 14px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { text-align: left; padding: 8px; border-bottom: 1px solid #333; }
        th { color: #4fc3f7; width: 45%; }
    </style>
</head>
<body>

<div class="container">
    <h2>Multi-Layered Secure File Upload (Defense-in-Depth)</h2>
    <p>Allowed Extensions: <strong>.jpg, .jpeg, .png, .pdf</strong> | Max Size: <strong>2 MB</strong></p>

    <?php if ($statusMessage !== ''): ?>
        <div class="<?= $statusType === 'success' ? 'alert-success' : 'alert-error' ?>">
            <strong><?= $statusType === 'success' ? '[UPLOAD ACCEPTED]' : '[SECURITY REJECTED]' ?></strong><br>
            <?= htmlspecialchars($statusMessage) ?>
        </div>
    <?php endif; ?>

    <form action="" method="post" enctype="multipart/form-data">
        <div class="form-group">
            <label for="uploaded_file">Select a file to upload:</label><br><br>
            <input type="file" name="uploaded_file" id="uploaded_file" required>
        </div>
        <button type="submit">Upload & Verify File</button>
    </form>

    <?php if (!empty($uploadDetails)): ?>
        <h3>Security Inspection Report:</h3>
        <table>
            <?php foreach ($uploadDetails as $key => $value): ?>
                <tr>
                    <th><?= htmlspecialchars($key) ?></th>
                    <td><?= htmlspecialchars((string)$value) ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
    <?php endif; ?>
</div>

</body>
</html>