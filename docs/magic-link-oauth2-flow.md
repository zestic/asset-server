# Magic Link OAuth2 Flow with PKCE

This document explains how to implement the Magic Link OAuth2 flow with PKCE support for ReactNative applications.

## Overview

The Magic Link OAuth2 flow allows users to authenticate using email links instead of passwords, while maintaining OAuth2 security standards with PKCE (Proof Key for Code Exchange).

## Flow Steps

### 1. Generate PKCE Parameters

```javascript
import { randomBytes } from 'crypto';
import { createHash } from 'crypto';

// Generate code verifier (43-128 characters)
const codeVerifier = randomBytes(32).toString('base64url');

// Generate code challenge (SHA256 hash of verifier, base64url encoded)
const codeChallenge = createHash('sha256')
  .update(codeVerifier)
  .digest('base64url');

const pkceData = {
  codeVerifier,
  codeChallenge,
  codeChallengeMethod: 'S256',
  state: `state-${Date.now()}`,
  redirectUri: 'http://localhost:19006/auth/callback', // Your app's callback
};
```

### 2. Request Magic Link

```bash
curl -X POST http://localhost:8088/magic-link/send \
  -H "Content-Type: application/json" \
  -d '{
    "email": "user@example.com",
    "clientId": "0266d5f0-3054-439e-8f72-3cdc9c1a35d8",
    "codeChallenge": "CODE_CHALLENGE_FROM_STEP_1",
    "codeChallengeMethod": "S256",
    "redirectUri": "http://localhost:19006/auth/callback",
    "state": "state-12345"
  }'
```

**Response:**
```json
{
  "success": true,
  "message": "Success",
  "code": "MAGIC_LINK_SUCCESS"
}
```

### 3. User Clicks Magic Link

The user receives an email with a magic link. When clicked, it verifies the user and redirects them back to your app.

### 4. Exchange Magic Link Token for Access Token

**Important:** Use the `magic_link` grant type, NOT `authorization_code`.

```bash
curl -X POST http://localhost:8088/oauth/token \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -d "grant_type=magic_link&client_id=0266d5f0-3054-439e-8f72-3cdc9c1a35d8&token=MAGIC_LINK_TOKEN&code_verifier=CODE_VERIFIER_FROM_STEP_1"
```

**Response:**
```json
{
  "token_type": "Bearer",
  "expires_in": 3600,
  "access_token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9...",
  "refresh_token": "def5020080de45fbdc2a1df727dd97e165479f9d..."
}
```

## ReactNative Implementation

### Complete Example

```javascript
import { randomBytes, createHash } from 'crypto';

class MagicLinkAuth {
  constructor(clientId = '0266d5f0-3054-439e-8f72-3cdc9c1a35d8', apiBaseUrl = 'http://localhost:8088') {
    this.clientId = clientId;
    this.apiBaseUrl = apiBaseUrl;
  }

  generatePKCE() {
    const codeVerifier = randomBytes(32).toString('base64url');
    const codeChallenge = createHash('sha256')
      .update(codeVerifier)
      .digest('base64url');

    return {
      codeVerifier,
      codeChallenge,
      codeChallengeMethod: 'S256',
      state: `state-${Date.now()}`,
      redirectUri: 'http://localhost:19006/auth/callback',
    };
  }

  async sendMagicLink(email) {
    const pkceData = this.generatePKCE();
    
    // Store PKCE data for later use
    this.pkceData = pkceData;

    const response = await fetch(`${this.apiBaseUrl}/magic-link/send`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({
        email,
        clientId: this.clientId,
        codeChallenge: pkceData.codeChallenge,
        codeChallengeMethod: pkceData.codeChallengeMethod,
        redirectUri: pkceData.redirectUri,
        state: pkceData.state,
      }),
    });

    return response.json();
  }

  async exchangeTokenForAccessToken(magicLinkToken) {
    if (!this.pkceData) {
      throw new Error('PKCE data not found. Call sendMagicLink first.');
    }

    const response = await fetch(`${this.apiBaseUrl}/oauth/token`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded',
      },
      body: new URLSearchParams({
        grant_type: 'magic_link',
        client_id: this.clientId,
        token: magicLinkToken,
        code_verifier: this.pkceData.codeVerifier,
      }),
    });

    const tokenData = await response.json();
    
    if (response.ok) {
      // Store tokens securely
      await this.storeTokens(tokenData);
      return tokenData;
    } else {
      throw new Error(`Token exchange failed: ${tokenData.error_description}`);
    }
  }

  async storeTokens(tokenData) {
    // Store tokens securely (use react-native-keychain or similar)
    // This is a simplified example
    await AsyncStorage.setItem('access_token', tokenData.access_token);
    await AsyncStorage.setItem('refresh_token', tokenData.refresh_token);
    await AsyncStorage.setItem('token_expires_at', 
      String(Date.now() + (tokenData.expires_in * 1000))
    );
  }
}

// Usage (uses default mobile client ID)
const auth = new MagicLinkAuth();

// Step 1: Send magic link
await auth.sendMagicLink('user@example.com');

// Step 2: User clicks magic link, your app receives the magic link token
// (This happens through deep linking or URL handling)

// Step 3: Exchange token for access token
const tokens = await auth.exchangeTokenForAccessToken(magicLinkToken);
```

## Security Notes

1. **PKCE is mandatory** for public clients (mobile/web apps)
2. **Store tokens securely** using react-native-keychain or similar
3. **Validate state parameter** to prevent CSRF attacks
4. **Magic link tokens expire** in 10 minutes
5. **Access tokens expire** in 1 hour (use refresh tokens)

## Error Handling

Common errors and solutions:

- `invalid_request` + `Invalid or expired token`: Magic link token has expired or been used
- `invalid_request` + `PKCE code verifier is required`: Missing code_verifier parameter
- `invalid_request` + `Invalid PKCE code verifier`: code_verifier doesn't match code_challenge

## Client Configuration

The API template includes pre-configured OAuth2 clients with fixed UUIDs:

```sql
-- Development clients (fixed IDs for consistency)
SELECT client_id, name FROM oauth_clients ORDER BY name;

-- Results:
-- 0266d5f0-3054-439e-8f72-3cdc9c1a35d8 | Zestic Mobile App (Development)
-- 02b54777-ebe3-4e25-9bc0-3d1a97663b8f | Zestic Web App (Development)
```

### Client Details:

**Mobile Client** (`0266d5f0-3054-439e-8f72-3cdc9c1a35d8`):
- `is_confidential = false` (public client)
- `redirect_uri` includes mobile app callback URLs
- No `client_secret` (null for public clients)
- Used for ReactNative mobile apps

**Web Client** (`02b54777-ebe3-4e25-9bc0-3d1a97663b8f`):
- `is_confidential = false` (public client)
- `redirect_uri` includes web app callback URLs
- No `client_secret` (null for public clients)
- Used for ReactNative web apps

These fixed UUIDs ensure consistency across database rebuilds and deployments.
