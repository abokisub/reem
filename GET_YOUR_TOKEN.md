# How to Get Your Access Token

## Step 1: Open Browser Console
1. Go to https://app.pointwave.ng/secure/webhooks
2. Press F12 (or right-click → Inspect)
3. Click on "Console" tab

## Step 2: Get Token from localStorage
In the console, type this and press Enter:

```javascript
localStorage.getItem('accessToken')
```

Copy the token that appears (it will be a long string).

## Step 3: Run the Test Script
Replace `YOUR_TOKEN_HERE` with the actual token you copied:

```bash
php test_actual_webhook_api.php YOUR_TOKEN_HERE
```

Example:
```bash
php test_actual_webhook_api.php eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...
```

This will show you what the API is actually returning.
