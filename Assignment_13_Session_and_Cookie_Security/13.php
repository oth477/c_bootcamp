<?php
declare(strict_types=1);

// 1. Configure strict default security parameters for the session cookie BEFORE session_start()
session_set_cookie_params([
    'lifetime' => 3600,          // Session cookie valid for 1 hour
    'path'     => '/',           // Available across the entire domain
    'domain'   => '',            // Current domain only
    'secure'   => true,          // Transmit cookie only over HTTPS (or localhost)
    'httponly' => true,          // Block client-side JavaScript (document.cookie) access
    'samesite' => 'Strict'       // Prevent Cross-Site Request Forgery (CSRF) cookie sending
]);

// Start or resume the session using the hardened parameters above
session_start();





// 2. Issue two demonstration cookies using the modern array options syntax in setcookie()
// Cookie A: Protected Cookie (HttpOnly + Secure + SameSite=Strict)
setcookie('protected_auth_token', 'Secret_JWT_987654321', [
    'expires'  => time() + 3600,
    'path'     => '/',
    'secure'   => true,
    'httponly' => true,
    'samesite' => 'Strict'
]);

// Cookie B: Unprotected Cookie (httponly = false) for comparison in the inspection UI
setcookie('public_ui_theme', 'dark_mode_v2', [
    'expires'  => time() + 3600,
    'path'     => '/',
    'secure'   => false,
    'httponly' => false,
    'samesite' => 'Lax'
]);

$statusMessage = "Session initialized. Ready for inspection.";






// 3. Handle Authentication & Session Regeneration Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'login') {
        $oldSessionId = session_id();

        // Mitigate Session Fixation by regenerating the Session ID and deleting the old session file
        session_regenerate_id(true);
        $newSessionId = session_id();

        $_SESSION['username']     = trim($_POST['username'] ?? 'sec_analyst');
        $_SESSION['is_logged_in'] = true;
        $_SESSION['old_sess_id']  = $oldSessionId;
        $_SESSION['login_time']   = date('Y-m-d H:i:s');

        $statusMessage = "Login successful! Session ID regenerated from [$oldSessionId] to [$newSessionId].";
    } elseif ($action === 'logout') {
        // Clear session data and destroy the active session
        $_SESSION = [];
        session_destroy();
        session_start();
        session_regenerate_id(true);
        $statusMessage = "Logged out and destroyed previous session successfully.";
    }
}


// Capture outgoing Set-Cookie headers sent by the server for verification
$responseHeaders = headers_list();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assignment 13 - Session & Cookie Security Inspector</title>
    <style>
        body { font-family: monospace; background-color: #121212; color: #e0e0e0; padding: 20px; }
        .card { background: #1e1e1e; border: 1px solid #333; padding: 15px; margin-bottom: 20px; border-radius: 6px; }
        h2, h3 { color: #4fc3f7; margin-top: 0; }
        .badge-ok { color: #00e676; font-weight: bold; }
        .badge-warn { color: #ff5252; font-weight: bold; }
        button, input[type="text"] { padding: 8px 12px; font-family: monospace; margin-right: 5px; }
        button { cursor: pointer; background: #0288d1; color: white; border: none; border-radius: 4px; }
        pre { background: #000; padding: 10px; border-left: 3px solid #4fc3f7; overflow-x: auto; }
    </style>
</head>
<body>

    <h2>Session & Cookie Security Inspector (XSS / CSRF / Session Fixation Defense)</h2>
    <p><strong>Status:</strong> <span class="badge-ok"><?= htmlspecialchars($statusMessage) ?></span></p>

    <!-- Section 1: Authentication & Session Fixation Test -->
    <div class="card">
        <h3>1. Session Fixation Defense (session_regenerate_id)</h3>
        <p><strong>Current Session ID:</strong> <span class="badge-ok"><?= htmlspecialchars(session_id()) ?></span></p>
        <p><strong>Previous Session ID (Before Login):</strong> <?= htmlspecialchars($_SESSION['old_sess_id'] ?? 'None (Not regenerated yet)') ?></p>
        <p><strong>Authenticated User:</strong> <?= htmlspecialchars($_SESSION['username'] ?? 'Guest (Unauthenticated)') ?></p>
        <p><strong>Login Timestamp:</strong> <?= htmlspecialchars($_SESSION['login_time'] ?? 'N/A') ?></p>

        <form method="post" style="display:inline;">
            <input type="hidden" name="action" value="login">
            <input type="text" name="username" placeholder="Enter username" value="othman_soc" required>
            <button type="submit">Simulate Login & Regenerate ID</button>
        </form>

        <a href="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>"><button type="button">Test Navigation / Reload Page (GET)</button></a>

        <form method="post" style="display:inline;">
            <input type="hidden" name="action" value="logout">
            <button type="submit" style="background:#d32f2f;">Logout & Destroy</button>
        </form>
    </div>



    <!-- Section 2: Server-Side Set-Cookie Header Inspection -->
    <div class="card">
        <h3>2. Outgoing Server Response Headers (Set-Cookie Verification)</h3>
        <pre><?php
        foreach ($responseHeaders as $hdr) {
            if (stripos($hdr, 'Set-Cookie') === 0) {
                echo htmlspecialchars($hdr) . "\n";
            }
        }
        ?></pre>
    </div>



    <!-- Section 3: Protected vs Unprotected Cookie Comparison -->
    <div class="card">
        <h3>3. Cookie Visibility Comparison (Server PHP vs. Client JavaScript)</h3>
        
        <p><strong>A) Server-Side PHP <code>$_COOKIE</code> Array (Reads ALL Cookies):</strong></p>
        <pre><?php
        if (empty($_COOKIE)) {
            echo "No cookies received in the initial request yet. Click 'Test Navigation / Reload Page' to send cookies back to the server.";
        } else {
            print_r($_COOKIE);
        }
        ?></pre>

        <p><strong>B) Client-Side JavaScript <code>document.cookie</code> (XSS Simulation):</strong></p>
        <pre id="jsCookieOutput">Reading document.cookie...</pre>
        <button type="button" onclick="simulateXssTheft()">Simulate XSS Attack: alert(document.cookie)</button>
    </div>

    <script>
        
        // Attempt to read cookies via client-side JavaScript
        const rawJsCookies = document.cookie;
        const outputBox = document.getElementById('jsCookieOutput');

        if (!rawJsCookies) {
            outputBox.textContent = "[Empty] JavaScript cannot see any cookies (or reload needed).";
        } else {
            outputBox.textContent = "Visible to JavaScript: " + rawJsCookies +
                "\n\n[SECURITY VERIFIED]: Notice that 'PHPSESSID' and 'protected_auth_token' are BLOCKED from JavaScript because of the HttpOnly flag!";
        }

        function simulateXssTheft() {
            alert("XSS Payload Executed!\n\nCookies stolen via document.cookie:\n" + (document.cookie || "NONE (All cookies protected by HttpOnly)"));
        }
    </script>

</body>
</html>