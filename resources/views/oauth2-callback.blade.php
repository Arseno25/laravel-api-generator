<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OAuth2 Callback - API Magic</title>
</head>
<body style="background: radial-gradient(circle at top, rgba(34,211,238,0.12), transparent 35%), #020617; color: #cbd5e1; font-family: Inter, sans-serif; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0;">
    <div style="text-align: center; border: 1px solid rgba(148,163,184,0.15); background: rgba(15,23,42,0.78); backdrop-filter: blur(14px); border-radius: 24px; padding: 32px 36px; box-shadow: 0 20px 60px rgba(2,6,23,0.45); max-width: 420px;">
        <div style="width: 52px; height: 52px; margin: 0 auto 18px; border-radius: 18px; background: linear-gradient(135deg, rgba(34,211,238,0.18), rgba(59,130,246,0.18)); display: flex; justify-content: center; align-items: center; color: #67e8f9; font-size: 20px;">🔐</div>
        <h2 style="margin-bottom: 10px; color: white;">Authenticating…</h2>
        <p style="margin: 0; color: #94a3b8; line-height: 1.7;">Please wait while API Magic finalizes your OAuth login and returns to the docs console.</p>
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
            window.close();
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
