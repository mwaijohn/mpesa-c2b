<?php include_once 'daraja_c2b.php' ?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Safaricom Daraja API - URL Registration</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f5f5f5;
            margin: 0;
            padding: 20px;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
            background: #fff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        h1 {
            color: #00A551;
            margin-bottom: 20px;
            border-bottom: 2px solid #00A551;
            padding-bottom: 10px;
        }

        .description {
            margin-bottom: 25px;
            font-size: 16px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
        }

        input[type="text"],
        input[type="password"],
        select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
        }

        .form-row {
            display: flex;
            gap: 20px;
        }

        .form-row .form-group {
            flex: 1;
        }

        button {
            background-color: #00A551;
            color: white;
            border: none;
            padding: 12px 24px;
            font-size: 16px;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        button:hover {
            background-color: #008043;
        }

        .response-container {
            margin-top: 30px;
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
            background-color: #f9f9f9;
            display: none;
        }

        .response-heading {
            font-weight: 600;
            margin-bottom: 10px;
        }

        .response-content {
            font-family: monospace;
            white-space: pre-wrap;
            background-color: #fff;
            padding: 10px;
            border-radius: 4px;
            border: 1px solid #eee;
        }

        .info-box {
            background-color: #e7f3ff;
            border-left: 4px solid #1a73e8;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
    </style>
</head>

<body>
    <div class="container">
        <h1>Safaricom Daraja API - URL Registration</h1>

        <div class="description">
            Use this form to register your validation and confirmation URLs with Safaricom's Daraja API for C2B
            transactions.
        </div>

        <div class="info-box">
            <strong>Note:</strong> Your callback URLs must be publicly accessible with HTTPS enabled. For testing, you
            can use services like ngrok to expose your local server.
        </div>

        <form id="registerUrlsForm">
            <div class="form-row">
                <div class="form-group">
                    <label for="consumer_key">Consumer Key</label>
                    <input type="text" id="consumer_key" name="consumer_key" required
                        placeholder="Enter your Daraja API consumer key">
                </div>

                <div class="form-group">
                    <label for="consumer_secret">Consumer Secret</label>
                    <input type="password" id="consumer_secret" name="consumer_secret" required
                        placeholder="Enter your Daraja API consumer secret">
                </div>
            </div>

            <div class="form-group">
                <label for="shortcode">Business Shortcode (Till/Paybill Number)</label>
                <input type="text" id="shortcode" name="shortcode" required
                    placeholder="Your business shortcode/till number">
            </div>

            <div class="form-group">
                <label for="environment">Environment</label>
                <select id="environment" name="environment">
                    <option value="sandbox">Sandbox (Testing)</option>
                    <option value="production">Production (Live)</option>
                </select>
            </div>

            <div class="form-group">
                <label for="validation_url">Validation URL</label>
                <input type="text" id="validation_url" name="validation_url" required
                    placeholder="https://your-domain.com/validation.php">
            </div>

            <div class="form-group">
                <label for="confirmation_url">Confirmation URL</label>
                <input type="text" id="confirmation_url" name="confirmation_url" required
                    placeholder="https://your-domain.com/confirmation.php">
            </div>

            <div class="form-group">
                <label>Response Type</label>
                <div style="display: flex; gap: 20px;">
                    <label style="display: inline-flex; align-items: center;">
                        <input type="radio" name="response_type" value="Completed" checked>
                        <span style="margin-left: 8px;">Completed</span>
                    </label>
                    <label style="display: inline-flex; align-items: center;">
                        <input type="radio" name="response_type" value="Cancelled">
                        <span style="margin-left: 8px;">Cancelled</span>
                    </label>
                </div>
            </div>

            <button type="submit" id="registerButton">Register URLs</button>
        </form>

        <div class="response-container" id="responseContainer">
            <div class="response-heading">API Response:</div>
            <div class="response-content" id="responseContent"></div>
        </div>
    </div>

    <script>
        document.getElementById('registerUrlsForm').addEventListener('submit', async function (e) {
            e.preventDefault();

            const formData = {
                consumer_key: document.getElementById('consumer_key').value,
                consumer_secret: document.getElementById('consumer_secret').value,
                shortcode: document.getElementById('shortcode').value,
                environment: document.getElementById('environment').value,
                validation_url: document.getElementById('validation_url').value,
                confirmation_url: document.getElementById('confirmation_url').value,
                response_type: document.querySelector('input[name="response_type"]:checked').value
            };

            const registerButton = document.getElementById('registerButton');
            registerButton.textContent = 'Registering...';
            registerButton.disabled = true;

            try {
                // For security, we'll send this to our own backend endpoint that will
                // handle the actual Safaricom API communication
                const response = await fetch('register_urls_handler.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(formData)
                });

                const result = await response.json();

                // Display the response
                const responseContainer = document.getElementById('responseContainer');
                const responseContent = document.getElementById('responseContent');

                responseContainer.style.display = 'block';
                responseContent.textContent = JSON.stringify(result, null, 2);

                // Style the response based on success/failure
                if (result.ResponseCode === '0' || result.ResponseDescription?.includes('success')) {
                    responseContainer.style.borderColor = '#00A551';
                    responseContainer.style.backgroundColor = '#f0fff0';
                } else {
                    responseContainer.style.borderColor = '#e74c3c';
                    responseContainer.style.backgroundColor = '#fff0f0';
                }
            } catch (error) {
                console.error('Error:', error);
                const responseContainer = document.getElementById('responseContainer');
                const responseContent = document.getElementById('responseContent');

                responseContainer.style.display = 'block';
                responseContainer.style.borderColor = '#e74c3c';
                responseContainer.style.backgroundColor = '#fff0f0';
                responseContent.textContent = 'Error: ' + error.message;
            } finally {
                registerButton.textContent = 'Register URLs';
                registerButton.disabled = false;
            }
        });
    </script>
</body>

</html>