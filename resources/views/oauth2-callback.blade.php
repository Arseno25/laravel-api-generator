<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OAuth2 Callback - API Magic</title>
</head>
<body style="background: #0f172a; color: #cbd5e1; font-family: sans-serif; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0;">
    <div style="text-align: center;">
        <h2 style="margin-bottom: 10px;">Authenticating...</h2>
        <p>Please wait while we finalize your login.</p>
    </div>

    <script>
        // Extract token from URL hash (Implicit Flow) or query params
        const hash = window.location.hash.substring(1);
        const params = new URLSearchParams(hash || window.location.search);
        
        const token = params.get('access_token');
        
        if (token && window.opener) {
            window.opener.postMessage({
                type: 'oauth2_token',
                token: token
            }, window.location.origin);
        } else if (window.opener) {
            // Some error occurred
            console.error('OAuth2 Error:', params.get('error_description') || 'No access token found');
            window.close();
        } else {
            document.body.innerHTML = '<h3>Error: This window should be opened as a popup.</h3>';
        }
    </script>
</body>
</html>
