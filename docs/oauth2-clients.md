# Zestic OAuth2 Development Clients

This document describes the OAuth2 clients configured for local development of Zestic ReactNative applications.

## Development Clients

### Web Client (Development)
- **Client ID**: `{WEB_CLIENT_ID}` (generated during seed)
- **Name**: Zestic Web App (Development)
- **Type**: Public Client (no client secret)
- **Confidential**: No
- **Redirect URIs**:
  - `http://localhost:3000/auth/callback` (React dev server)
  - `http://localhost:3001/auth/callback` (React dev server alt port)
  - `http://127.0.0.1:3000/auth/callback` (React dev server)
  - `http://127.0.0.1:3001/auth/callback` (React dev server alt port)

### Mobile Client (Development)
- **Client ID**: `{MOBILE_CLIENT_ID}` (generated during seed)
- **Name**: Zestic Mobile App (Development)
- **Type**: Public Client (no client secret)
- **Confidential**: No
- **Redirect URIs**:
  - `http://localhost:19006/auth/callback` (Expo development server)
  - `http://localhost:8081/auth/callback` (Metro bundler)
  - `http://127.0.0.1:19006/auth/callback` (Expo development server)
  - `http://127.0.0.1:8081/auth/callback` (Metro bundler)
  - `exp://localhost:19000/--/auth/callback` (Expo development)
  - `exp://127.0.0.1:19000/--/auth/callback` (Expo development)
  - `zestic://auth/callback` (custom scheme for testing)
  - `com.zestic.app://auth/callback` (bundle ID scheme for testing)

## OAuth2 Flow Usage

### Authorization Request
```
GET /oauth/authorize?
  response_type=code&
  client_id={CLIENT_ID}&
  redirect_uri={REDIRECT_URI}&
  scope={SCOPES}&
  state={STATE}&
  code_challenge={CODE_CHALLENGE}&
  code_challenge_method=S256
```

### Token Exchange
```
POST /oauth/token
Content-Type: application/x-www-form-urlencoded

grant_type=authorization_code&
client_id={CLIENT_ID}&
code={AUTHORIZATION_CODE}&
redirect_uri={REDIRECT_URI}&
code_verifier={CODE_VERIFIER}
```

## PKCE Implementation

Both clients are configured as public clients and **MUST** use PKCE (Proof Key for Code Exchange) for security:

1. Generate a `code_verifier` (43-128 character random string)
2. Generate a `code_challenge` using SHA256 hash of the verifier, base64url encoded
3. Use `code_challenge_method=S256` in the authorization request
4. Include the original `code_verifier` in the token exchange request

## ReactNative Integration

### Web App (React Native Web)
```javascript
// Get the client ID from the seed output or database
const CLIENT_ID = '{WEB_CLIENT_ID}'; // Replace with actual client ID from seed
const REDIRECT_URI = 'http://localhost:3000/auth/callback';

// Authorization URL
const authUrl = `http://localhost:8088/oauth/authorize?` +
  `response_type=code&` +
  `client_id=${CLIENT_ID}&` +
  `redirect_uri=${encodeURIComponent(REDIRECT_URI)}&` +
  `scope=read write&` +
  `state=${state}&` +
  `code_challenge=${codeChallenge}&` +
  `code_challenge_method=S256`;
```

### Mobile App (React Native)
```javascript
// Get the client ID from the seed output or database
const CLIENT_ID = '{MOBILE_CLIENT_ID}'; // Replace with actual client ID from seed
const REDIRECT_URI = 'http://localhost:19006/auth/callback'; // Expo dev server

// Use react-native-app-auth or similar library
import {authorize} from 'react-native-app-auth';

const config = {
  issuer: 'http://localhost:8088',
  clientId: CLIENT_ID,
  redirectUrl: REDIRECT_URI,
  scopes: ['read', 'write'],
  usePKCE: true, // Required for public clients
};
```

## Magic Link Integration

Magic links work seamlessly with OAuth2 clients:

1. User requests magic link via GraphQL mutation
2. Magic link email contains URL to `/magic-link/verify?token={TOKEN}`
3. Verification endpoint can redirect to OAuth2 authorization with client context
4. Complete OAuth2 flow with PKCE for secure token exchange

## Security Notes

- Both clients are **public clients** (no client secret)
- **PKCE is mandatory** for all authorization requests
- Redirect URIs are strictly validated
- Custom URL schemes are configured for mobile deep linking
- State parameter should be used to prevent CSRF attacks

## Getting Client IDs

After running the OAuth2ClientsSeed, the client IDs will be displayed in the console output. You can also query the database:

```sql
SELECT client_id, name, redirect_uri FROM oauth_clients;
```

## Environment Configuration

Update your ReactNative apps with the generated client IDs:

```env
# Web App (Development)
OAUTH_CLIENT_ID={WEB_CLIENT_ID}
OAUTH_REDIRECT_URI=http://localhost:3000/auth/callback
OAUTH_API_BASE_URL=http://localhost:8088

# Mobile App (Development)
OAUTH_CLIENT_ID={MOBILE_CLIENT_ID}
OAUTH_REDIRECT_URI=http://localhost:19006/auth/callback
OAUTH_API_BASE_URL=http://localhost:8088
```

## Production Setup

For production environments:
1. Create separate OAuth2 clients with production redirect URIs
2. Use HTTPS URLs for all redirect URIs
3. Implement proper custom URL schemes for mobile apps
4. Consider using environment-specific client configurations
