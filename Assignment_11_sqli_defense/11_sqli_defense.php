<?php
declare(strict_types=1);

$results       = [];
$executedQuery = '';
$errorMessage  = '';
$mode          = $_GET['mode'] ?? 'safe';
$username      = $_GET['username'] ?? '';



try {
    // 1. Initialize an in-memory SQLite database via PDO with strict exception handling
    $pdo = new PDO('sqlite::memory:', null, null, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false
    ]);



    // 2. Programmatically create the 'users' table and insert sample records
    $pdo->exec("
        CREATE TABLE users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            username TEXT NOT NULL,
            role TEXT NOT NULL,
            email TEXT NOT NULL,
            secret_token TEXT NOT NULL
        );

        INSERT INTO users (username, role, email, secret_token) VALUES
        ('admin', 'System Administrator', 'admin@soc-local.org', 'FLAG{ADMIN_MASTER_KEY_9981}'),
        ('othman', 'SOC Analyst', 'othman@soc-local.org', 'TOKEN_SOC_7741'),
        ('ahmed', 'Penetration Tester', 'ahmed@soc-local.org', 'TOKEN_PENTEST_3310'),
        ('mohammed', 'Network Engineer', 'mohammed@soc-local.org', 'TOKEN_NET_5529');
    ");



    // Process the search query if the 'username' GET parameter is provided
    if ($username !== '') {
        if ($mode === 'unsafe') {
            // 3. UNSAFE MECHANISM: Direct string concatenation (Vulnerable to SQL Injection)
            $executedQuery = "SELECT id, username, role, email, secret_token FROM users WHERE username = '$username'";
            
            // Directly executing the concatenated string
            $stmt    = $pdo->query($executedQuery);
            $results = $stmt->fetchAll();
        } else {
            // 4. SAFE MECHANISM: PDO Prepared Statements with bound parameters
            $executedQuery = "SELECT id, username, role, email, secret_token FROM users WHERE username = :username";
            
            // Prepare the SQL template first, separating SQL code from user data
            $stmt = $pdo->prepare($executedQuery);
            
            // Bind the untrusted input safely as a literal parameter
            $stmt->execute([':username' => $username]);
            $results = $stmt->fetchAll();
        }
    }

} catch (PDOException $e) {
    // 5. Catch database exceptions safely without leaking sensitive schema or stack traces
    error_log("Database Error: " . $e->getMessage());
    $errorMessage = "A database query error occurred. Request blocked for security.";
}
?>



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assignment 11 - SQL Injection (SQLi) Defense Lab</title>
    <style>
        body { font-family: monospace; background-color: #121212; color: #e0e0e0; padding: 25px; }
        .container { max-width: 850px; margin: 0 auto; background: #1e1e1e; padding: 20px; border: 1px solid #333; border-radius: 6px; }
        h2, h3 { color: #4fc3f7; margin-top: 0; }
        .form-box { background: #161616; padding: 15px; border: 1px solid #444; border-radius: 4px; margin-bottom: 20px; }
        input[type="text"], select { padding: 9px; font-family: monospace; font-size: 14px; background: #2c2c2c; color: #fff; border: 1px solid #555; border-radius: 4px; }
        input[type="text"] { width: 55%; }
        button { padding: 9px 15px; font-family: monospace; font-size: 13px; border: none; border-radius: 4px; cursor: pointer; font-weight: bold; margin-right: 5px; }
        .btn-submit { background-color: #0288d1; color: #fff; }
        .btn-preset { background-color: #424242; color: #ffb74d; margin-top: 10px; }
        .query-box { background: #000; padding: 12px; border-left: 4px solid #4fc3f7; margin-bottom: 15px; overflow-x: auto; }
        .alert-error { background: #b71c1c; color: #ffcdd2; padding: 12px; border-left: 5px solid #ff5252; margin-bottom: 15px; }
        .badge-unsafe { color: #ff5252; font-weight: bold; }
        .badge-safe { color: #00e676; font-weight: bold; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { text-align: left; padding: 10px; border-bottom: 1px solid #333; }
        th { background: #262626; color: #4fc3f7; }
    </style>
</head>




<body>

<div class="container">
    <h2>SQL Injection (SQLi) Defense Lab: Concatenation vs. PDO Prepared Statements</h2>
    <p> In-Memory Database (<code>sqlite::memory:</code>) initialized with 4 users: <code>admin</code>, <code>othman</code>, <code>ahmed</code>, <code>mohammed</code>.</p>

    <!-- Search Form using GET method -->
    <div class="form-box">
        <form method="get" action="">
            <label for="username"><strong>Search Username:</strong></label><br><br>
            <input type="text" name="username" id="username" value="<?= htmlspecialchars($username) ?>" placeholder="Enter username or SQLi payload..." required>
            
            <select name="mode" id="mode">
                <option value="safe" <?= $mode === 'safe' ? 'selected' : '' ?>>Safe Mode (PDO Prepared Statement)</option>
                <option value="unsafe" <?= $mode === 'unsafe' ? 'selected' : '' ?>>Unsafe Mode (String Concatenation)</option>
            </select>
            
            <button type="submit" class="btn-submit">Execute Query</button>
        </form>

        <!-- Quick Test Presets -->
        <div>
            <button type="button" class="btn-preset" onclick="setTest('othman', 'safe')">Test 1: Normal User (Safe)</button>
            <button type="button" class="btn-preset" onclick="setTest(`' OR '1'='1`, 'unsafe')">Test 2: SQLi Attack (Unsafe Mode)</button>
            <button type="button" class="btn-preset" onclick="setTest(`' OR '1'='1`, 'safe')">Test 3: SQLi Attack (Safe Mode)</button>
        </div>
    </div>



    <!-- Display Executed SQL Structure -->
    <?php if ($username !== ''): ?>
        <div class="query-box">
            <strong>Security Mode:</strong> 
            <span class="<?= $mode === 'unsafe' ? 'badge-unsafe' : 'badge-safe' ?>">
                <?= $mode === 'unsafe' ? 'UNSAFE (Direct Concatenation)' : 'SAFE (PDO Prepared Statement)' ?>
            </span><br><br>
            <strong>SQL Statement Structure:</strong><br>
            <code><?= htmlspecialchars($executedQuery) ?></code>
            <?php if ($mode === 'safe'): ?>
                <br><br><strong>Bound Parameter <code>[:username]</code>:</strong> <code><?= htmlspecialchars($username) ?></code> (Treated strictly as a literal string)
            <?php endif; ?>
        </div>
    <?php endif; ?>


    <!-- Display Error Message (if any) -->
    <?php if ($errorMessage !== ''): ?>
        <div class="alert-error">
            <strong>[SECURITY BLOCK]</strong> <?= htmlspecialchars($errorMessage) ?>
        </div>
    <?php endif; ?>

    
    <!-- Display Query Results -->
    <?php if ($username !== '' && $errorMessage === ''): ?>
        <h3>Query Results (<?= count($results) ?> record(s) found):</h3>
        <?php if (empty($results)): ?>
            <p>No matching user found for <code><?= htmlspecialchars($username) ?></code>.</p>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Username</th>
                        <th>Role</th>
                        <th>Email</th>
                        <th>Secret Token</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($results as $row): ?>
                        <tr>
                            <td><?= htmlspecialchars((string)$row['id']) ?></td>
                            <td><?= htmlspecialchars($row['username']) ?></td>
                            <td><?= htmlspecialchars($row['role']) ?></td>
                            <td><?= htmlspecialchars($row['email']) ?></td>
                            <td><code><?= htmlspecialchars($row['secret_token']) ?></code></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    <?php endif; ?>
</div>

<script>
    function setTest(userVal, modeVal) {
        document.getElementById('username').value = userVal;
        document.getElementById('mode').value = modeVal;
        document.forms[0].submit();
    }
</script>

</body>
</html>