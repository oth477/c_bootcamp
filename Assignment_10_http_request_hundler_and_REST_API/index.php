<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - REST API Client</title>
</head>
<body>

    <h1>Create Account (REST API Tester)</h1>
    
    <form id="apiForm" action="Assignment_10_http_request_hundler&REST_API.php" method="post">
        <div>
            <label for="name">Your Name</label>
            <input type="text" name="name" id="name" required maxlength="10">
        </div>
        <br>
        <div>
            <label for="username">Username</label>
            <input type="text" name="username" id="username" required>
        </div>
        <br>
        <div>
            <label for="phone">Phone</label>
            <input type="text" name="phone" id="phone">
        </div>
        <br>
        <div>
            <label for="email">Email Address</label>
            <input type="email" name="email" id="email">
        </div>
        <br>
        <div>
            <label for="address">Address</label>
            <input type="text" name="address" id="address">
        </div>
        <br>
        <div>
            <label for="birth_date">Birth Date</label>
            <input type="date" name="birth_date" id="birth_date">
        </div>
        <br>
        <div>
            <label for="method">HTTP Method:</label>
            <select id="method">
                <option value="POST">POST (Create)</option>
                <option value="GET">GET (Read)</option>
                <option value="PUT">PUT (Update)</option>
                <option value="DELETE">DELETE (Remove)</option>
                <option value="PATCH">PATCH (Test 405 Not Allowed)</option>
            </select>
        </div>
        <br>
        <button type="submit">Send Request as JSON</button>
    </form>

    <h3>Server JSON Response:</h3>
    <pre id="responseBox" style="background: #1e1e1e; color: #00ff00; padding: 15px; border-radius: 5px;">Waiting for request...</pre>

    <script>
        document.getElementById('apiForm').addEventListener('submit', async function(e) {
            e.preventDefault(); // Prevent default URL-encoded form submission

            const method = document.getElementById('method').value;
            const formData = new FormData(this);
            const jsonObject = Object.fromEntries(formData.entries());

            const options = {
                method: method,
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': 'Bearer MySecretToken123'
                }
            };

            // Attach JSON body only for non-GET requests
            if (method !== 'GET') {
                options.body = JSON.stringify(jsonObject);
            }

            const response = await fetch(this.action, options);
            const resultText = await response.text();
            document.getElementById('responseBox').textContent = resultText;
        });
    </script>

</body>
</html>