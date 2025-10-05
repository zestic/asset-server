# Ideal Magic Link Authentication Flow for Registered Users

Here's the ideal flow for a registered and validated user, focusing on the client-server interactions:

## 1. User Enters Email on Login Page

````typescript path=app/login.tsx mode=EDIT
const handleEmailSubmit = async () => {
  if (!validateEmail(email)) {
    setEmailError('Please enter a valid email address');
    return;
  }

  setIsLoading(true);
  setErrorMessage(null);

  try {
    // Generate PKCE parameters
    const pkceParams = await PKCEService.generatePKCE();
    const state = PKCEService.generateState();
    
    // Store PKCE parameters securely on device
    await PKCEService.storePKCE(pkceParams.codeVerifier, state);
    
    // Request magic link with PKCE challenge
    const response = await AuthService.sendMagicLink(
      email, 
      pkceParams.codeChallenge, 
      state
    );
    
    if (response.success) {
      setState('magic-link-sent');
    } else {
      setState('general-error');
      setErrorMessage(response.message || 'Something went wrong - please try again');
    }
  } catch (error) {
    setState('general-error');
    setErrorMessage('Something went wrong - please try again');
  } finally {
    setIsLoading(false);
  }
};
````

## 2. Client Makes Magic Link Request to Server

````typescript path=services/AuthService.ts mode=EDIT
/**
 * Request a magic link for authentication
 * @param email - User's email address
 * @param codeChallenge - PKCE code challenge
 * @param state - State parameter for CSRF protection
 * @returns Promise with response
 */
static async sendMagicLink(
  email: string, 
  codeChallenge: string, 
  state: string
): Promise<AuthResponse> {
  try {
    const input = {
      email,
      clientId: AUTH_CONFIG.CLIENT_ID,
      codeChallenge,
      codeChallengeMethod: 'S256',
      redirectUri: AUTH_CONFIG.getRedirectUri(),
      state
    };

    // Use GraphQL mutation to request magic link
    const result = await executeGraphQL<{ sendMagicLink: { success: boolean; message: string; code: string } }>(
      SEND_MAGIC_LINK_MUTATION,
      { input },
      true // isMutation = true
    );

    if (result.error) {
      return {
        success: false,
        message: 'Failed to send magic link',
        code: 'ERROR'
      };
    }

    return {
      success: result.data.sendMagicLink.success,
      message: result.data.sendMagicLink.message,
      code: result.data.sendMagicLink.code
    };
  } catch (error) {
    console.error('Send magic link error:', error);
    return {
      success: false,
      message: 'Network error occurred',
      code: 'NETWORK_ERROR'
    };
  }
}
````

## 3. Server Processes Request and Sends Email

The server should:
1. Verify the user exists
2. Generate a secure, short-lived magic link token
3. Store the token with the PKCE challenge and state
4. Send an email with the magic link

````php path=server-magic-link-handler.php mode=EDIT
// GraphQL resolver for sendMagicLink mutation
function sendMagicLink($email, $clientId, $codeChallenge, $codeChallengeMethod, $redirectUri, $state) {
    // Verify user exists
    $user = findUserByEmail($email);
    if (!$user) {
        // For security, don't reveal if email exists or not
        return [
            'success' => true,
            'message' => 'If your email is registered, you will receive a magic link shortly',
            'code' => 'SENT'
        ];
    }
    
    // Generate secure token (short-lived, single-use)
    $token = generateSecureToken();
    
    // Store token with PKCE data and expiration (15 minutes)
    storeAuthToken([
        'token' => $token,
        'user_id' => $user['id'],
        'email' => $email,
        'client_id' => $clientId,
        'code_challenge' => $codeChallenge,
        'code_challenge_method' => $codeChallengeMethod,
        'state' => $state,
        'redirect_uri' => $redirectUri,
        'expires_at' => time() + (15 * 60), // 15 minutes
        'used' => false
    ]);
    
    // Create magic link URL
    // The link should point to the server's verification endpoint, not directly to the app
    $magicLinkUrl = "https://api.example.com/magic-link/verify?token={$token}";
    
    // Send email with magic link
    sendEmail($email, 'Your Magic Link', [
        'magic_link' => $magicLinkUrl,
        'expires_in' => '15 minutes'
    ]);
    
    return [
        'success' => true,
        'message' => 'Magic link sent successfully',
        'code' => 'SENT'
    ];
}
````

## 4. User Clicks Magic Link in Email

The magic link should point to a server endpoint, not directly to the app:
```
https://api.example.com/magic-link/verify?token=abc123
```

## 5. Server Verifies Token and Redirects to App

````php path=server-verify-endpoint.php mode=EDIT
// Server endpoint that verifies the magic link token
function verifyMagicLink($token) {
    // Retrieve token data
    $tokenData = getStoredToken($token);
    
    // Validate token
    if (!$tokenData || $tokenData['expires_at'] < time() || $tokenData['used']) {
        // Token invalid, expired, or already used
        return redirectToError('Invalid or expired magic link');
    }
    
    // Mark token as used (prevent replay attacks)
    markTokenAsUsed($token);
    
    // Construct redirect URL to the app with parameters
    $redirectUrl = $tokenData['redirect_uri'];
    $params = [
        'magic_link_token' => $token,
        'state' => $tokenData['state']
    ];
    
    $redirectUrl .= (strpos($redirectUrl, '?') !== false ? '&' : '?') . http_build_query($params);
    
    // Redirect to app
    return redirect($redirectUrl);
}
````

## 6. App Receives Callback with Magic Link Token

````typescript path=app/auth/callback.tsx mode=EDIT
const handleMagicLinkCallback = async () => {
  const magicLinkToken = params.magic_link_token as string;
  const state = params.state as string;

  if (!magicLinkToken) {
    throw new Error('Magic link token not found');
  }

  // Validate state parameter for CSRF protection
  const storedState = await PKCEService.getStoredState();
  if (state !== storedState) {
    throw new Error('Invalid state parameter - possible CSRF attack');
  }

  setStatusMessage('Exchanging magic link token...');

  // Retrieve stored code verifier
  const pkceData = await PKCEService.retrievePKCE();
  if (!pkceData?.codeVerifier) {
    throw new Error('PKCE code verifier not found');
  }

  // Exchange magic link token for access tokens
  const result = await AuthService.exchangeMagicLinkToken(
    magicLinkToken,
    pkceData.codeVerifier
  );

  if (!result.success) {
    throw new Error(result.error || 'Magic link authentication failed');
  }

  setStatusMessage('Completing authentication...');

  // Login with the access token
  if (result.accessToken) {
    const expiresAt = result.expiresIn ? Date.now() + (result.expiresIn * 1000) : undefined;
    await login(result.accessToken, result.refreshToken, expiresAt);
  }

  setStatus('success');
  setStatusMessage('Authentication successful!');

  // Navigate to main app after a brief delay
  setTimeout(() => {
    router.replace('/');
  }, 1500);
};
````

## 7. App Exchanges Token for Access Tokens

````typescript path=services/AuthService.ts mode=EDIT
/**
 * Exchange magic link token for access tokens using PKCE
 * @param magicLinkToken - Magic link token from callback URL
 * @param codeVerifier - PKCE code verifier
 * @returns Promise with token data or error
 */
static async exchangeMagicLinkToken(
  magicLinkToken: string,
  codeVerifier: string
): Promise<{
  success: boolean;
  accessToken?: string;
  refreshToken?: string;
  expiresIn?: number;
  error?: string;
}> {
  try {
    // Exchange magic link token for access tokens
    const response = await fetch(AUTH_CONFIG.DISCOVERY.tokenEndpoint, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded',
        'X-CLIENT-ID': AUTH_CONFIG.CLIENT_ID,
      },
      body: new URLSearchParams({
        grant_type: 'magic_link',
        token: magicLinkToken,
        client_id: AUTH_CONFIG.CLIENT_ID,
        code_verifier: codeVerifier,
      }),
    });

    if (!response.ok) {
      const errorData = await response.json().catch(() => ({}));
      return {
        success: false,
        error: errorData.error || 'token_exchange_failed',
      };
    }

    const tokenData = await response.json();

    // Clear PKCE parameters after successful exchange
    await PKCEService.clearPKCE();

    return {
      success: true,
      accessToken: tokenData.access_token,
      refreshToken: tokenData.refresh_token,
      expiresIn: tokenData.expires_in,
    };
  } catch (error) {
    console.error('Magic link token exchange error:', error);
    return {
      success: false,
      error: 'network_error',
    };
  }
}
````

## 8. Server Validates Token Exchange and Issues Access Tokens

````php path=server-token-endpoint.php mode=EDIT
// OAuth token endpoint handling magic_link grant type
function handleTokenRequest($request) {
    $grantType = $request->post('grant_type');
    
    if ($grantType === 'magic_link') {
        $token = $request->post('token');
        $clientId = $request->post('client_id');
        $codeVerifier = $request->post('code_verifier');
        
        // Retrieve stored token data
        $tokenData = getStoredToken($token);
        
        // Validate token
        if (!$tokenData || $tokenData['expires_at'] < time()) {
            return jsonResponse([
                'error' => 'invalid_grant',
                'error_description' => 'Magic link token is invalid or expired'
            ], 400);
        }
        
        // Validate client ID
        if ($tokenData['client_id'] !== $clientId) {
            return jsonResponse([
                'error' => 'invalid_client',
                'error_description' => 'Client ID mismatch'
            ], 400);
        }
        
        // Verify PKCE
        if (isset($tokenData['code_challenge'])) {
            if (!$codeVerifier) {
                return jsonResponse([
                    'error' => 'invalid_request',
                    'error_description' => 'Code verifier is required'
                ], 400);
            }
            
            // Calculate challenge from received verifier
            $method = $tokenData['code_challenge_method'] ?? 'S256';
            $calculatedChallenge = calculatePKCEChallenge($codeVerifier, $method);
            
            // Compare with stored challenge
            if ($calculatedChallenge !== $tokenData['code_challenge']) {
                return jsonResponse([
                    'error' => 'invalid_grant',
                    'error_description' => 'Code verifier invalid'
                ], 400);
            }
        }
        
        // Generate tokens
        $userId = $tokenData['user_id'];
        $accessToken = generateAccessToken($userId, $clientId);
        $refreshToken = generateRefreshToken($userId, $clientId);
        
        // Store refresh token
        storeRefreshToken($refreshToken, $userId, $clientId);
        
        // Invalidate magic link token (already used)
        invalidateToken($token);
        
        // Return tokens
        return jsonResponse([
            'access_token' => $accessToken,
            'token_type' => 'Bearer',
            'expires_in' => 3600, // 1 hour
            'refresh_token' => $refreshToken
        ]);
    }
    
    // Handle other grant types...
}
````

## Key Points About This Flow

1. **Email Link Points to Server, Not App**:
   - The magic link in the email should point to a server endpoint (`/magic-link/verify`)
   - The server then redirects to the app with the token
   - This allows the server to validate and mark the token as used

2. **PKCE Parameters**:
   - Generated on the client when requesting the magic link
   - Code challenge sent to server, code verifier kept on client
   - Code verifier sent during token exchange for verification

3. **State Parameter**:
   - Used to prevent CSRF attacks
   - Generated on client, stored with token on server
   - Validated when app receives the callback

4. **Token Exchange**:
   - Uses OAuth2 token endpoint with custom `magic_link` grant type
   - Includes PKCE code verifier for security
   - Returns standard OAuth2 tokens (access token, refresh token)

5. **Security Measures**:
   - Magic link tokens are short-lived (15 minutes)
   - Tokens are single-use (marked as used after verification)
   - PKCE prevents authorization code interception attacks
   - State parameter prevents CSRF attacks

This flow provides a secure, seamless authentication experience while following OAuth2 and PKCE best practices.
