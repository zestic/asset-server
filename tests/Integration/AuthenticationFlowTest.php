<?php

declare(strict_types=1);

namespace Test\Integration;

use AdrienGras\PKCE\PKCEUtils;
use Exception;
use GuzzleHttp\Client;
use PDO;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;

use function bin2hex;
use function chr;
use function explode;
use function json_decode;
use function ord;
use function parse_str;
use function parse_url;
use function preg_match;
use function quoted_printable_decode;
use function random_bytes;
use function sleep;
use function str_split;
use function strpos;
use function time;
use function urldecode;
use function usleep;
use function vsprintf;

/**
 * End-to-end authentication flow test
 *
 * Tests the complete authentication flow:
 * 1. Magic link request with PKCE parameters
 * 2. Magic link verification and OAuth2 code generation
 * 3. OAuth2 token exchange
 * 4. Database state validation at each step
 */
class AuthenticationFlowTest extends TestCase
{
    private Client $httpClient;
    private PDO $pdo;
    private string $accessToken;
    private string $clientId;
    /** @var array<string, mixed>|null */
    private ?array $message = null;
    /** @var array<string, mixed> */
    private array $pkceData;
    private string $refreshToken;
    private string $testEmail;
    /** @var array<string, mixed> */
    private array $testUser;

    protected function setUp(): void
    {
        parent::setUp();

        // Initialize HTTP client for API calls (from inside Docker container)
        $this->httpClient = new Client([
            'base_uri'    => 'http://localhost:80', // Internal container port
            'timeout'     => 300, // Increased timeout for slower operations
            'http_errors' => false, // Don't throw exceptions on 4xx/5xx responses
        ]);

        // Initialize database connection (from inside Docker container)
        $this->pdo = new PDO(
            'pgsql:host=postgres;port=5432;dbname=zestic_api',
            'zestic',
            'password1'
        );
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Use fixed mobile client ID (consistent across database rebuilds)
        $this->clientId = '0266d5f0-3054-439e-8f72-3cdc9c1a35d8';

        $this->testEmail = 'test-auth-flow-' . time() . '@example.com';

        // Clean up any existing test data
        $this->cleanupTestData();
    }

    protected function tearDown(): void
    {
        $this->cleanupTestData();
        parent::tearDown();
    }

    /**
     * Test complete authentication flow for a new user
     */
    public function testHappyPathForNewUser(): void
    {
        $this->setCleanEnvironment();

        // Step 1: Register thie test user
        $this->registerTestUser();

        // Step 2: Check for verification email
        $this->checkVerificationEmail();

        // Step 3: Follow verification link (simulate clicking email link)
        $this->followVerificationLink();

        // Step 5: Send magic link for login
        $this->requestMagicLinkForExistingUser();

        // Step 6: Check for login email and get token from database
        $this->checkLoginEmail();

        // Step 7: Follow login link (simulate clicking email link)
        $this->followLoginLink();

        // Step 8: Exchange magic link token for access token
        $tokenResponse = $this->exchangeMagicLinkTokenForAccessToken();

        // Verify token exchange was successful with PKCE support
        $this->assertEquals(200, $tokenResponse['status'], 'Magic link token exchange should succeed with PKCE');
        $this->assertArrayHasKey('access_token', $tokenResponse['body'], 'Response should contain access_token');
        $this->assertArrayHasKey('token_type', $tokenResponse['body'], 'Response should contain token_type');
        $this->assertEquals('Bearer', $tokenResponse['body']['token_type'], 'Token type should be Bearer');
        $this->assertArrayHasKey('expires_in', $tokenResponse['body'], 'Response should contain expires_in');

        // Verify the access token is a valid JWT
        $this->assertNotEmpty($tokenResponse['body']['access_token'], 'Access token should not be empty');
        $this->assertStringContainsString(
            '.',
            $tokenResponse['body']['access_token'],
            'Access token should be a JWT (contains dots)'
        );
    }

    /**
     * Test magic link request for unregistered user
     */
    public function testMagicLinkRequestForUnregisteredUser(): void
    {
        $unregisteredEmail = 'unregistered-' . time() . '@example.com';

        $response = $this->sendMagicLinkRequest($unregisteredEmail);

        $this->assertEquals(200, $response->getStatusCode());
        $responseData = json_decode($response->getBody()->getContents(), true);

        $this->assertArrayHasKey('data', $responseData);
        $this->assertArrayHasKey('sendMagicLink', $responseData['data']);
        $this->assertEquals('MAGIC_LINK_REGISTRATION', $responseData['data']['sendMagicLink']['code']);
        $this->assertStringContainsString('not registered', $responseData['data']['sendMagicLink']['message']);
    }

    /**
     * Test invalid magic link token
     */
    public function testInvalidMagicLinkToken(): void
    {
        $response = $this->verifyMagicLink('invalid-token-12345');

        // Should return error page or redirect to error
        $this->assertContains($response['status'], [400, 404, 302]);
    }

    protected function setCleanEnvironment(): void
    {
        // Clear MailHog emails to ensure clean test environment
        $this->clearMailHogEmails();

        // Clean up any existing test data
        $this->cleanupTestData();
    }

    protected function checkLoginEmail(): void
    {
        $this->waitForNextEmail();
        $this->assertEmailWasSent('Secure Login Link');
        $databaseToken = $this->getLatestTokenFromDatabase('login');
        $this->assertNotNull($databaseToken, 'Verification token should be created');
        $emailToken = $this->getMagicLinkTokenFromMessage();
        $this->assertEquals($databaseToken, $emailToken, 'Email token should match database token');
    }

    protected function checkVerificationEmail(): void
    {
        $this->waitForNextEmail();
        $this->assertEmailWasSent('Verify Your Email');
        $databaseToken = $this->getLatestTokenFromDatabase('registration');
        $this->assertNotNull($databaseToken, 'Verification token should be created');
        $emailToken = $this->getMagicLinkTokenFromMessage();
        $this->assertEquals($databaseToken, $emailToken, 'Email token should match database token');
    }

    protected function followLoginLink(): void
    {
        // Get the verification token from the email
        $verificationToken = $this->getMagicLinkTokenFromMessage();
        $this->assertNotNull($verificationToken, 'Verification token should be found in email');

        $tokenResponse = $this->verifyMagicLink($verificationToken);
        $this->assertEquals(302, $tokenResponse['status'], 'Login should redirect');

              // Verify redirect URL contains correct parameters
        $redirectUrl = $tokenResponse['location'];
        $this->assertNotNull($redirectUrl, 'Redirect URL should be present');

        // Parse the redirect URL to extract query parameters
        $parsedUrl = parse_url($redirectUrl);
        $this->assertNotFalse($parsedUrl, 'Redirect URL should be valid');

        parse_str($parsedUrl['query'] ?? '', $queryParams);

        // Verify required parameters are present
        $this->assertArrayHasKey('flow', $queryParams, 'Redirect URL should contain flow parameter');
        $this->assertArrayHasKey('success', $queryParams, 'Redirect URL should contain success parameter');
        $this->assertArrayHasKey('message', $queryParams, 'Redirect URL should contain message parameter');
        $this->assertArrayHasKey('state', $queryParams, 'Redirect URL should contain state parameter');

        // Verify parameter values
        $this->assertEquals('login', $queryParams['flow'], 'Flow should be registration');
        $this->assertEquals('true', $queryParams['success'], 'Success should be true');
        $this->assertIsString($queryParams['message'], 'Message parameter should be a string');
        $this->assertStringContainsString(
            'Authentication successful',
            urldecode($queryParams['message']),
            'Message should indicate successful authentication'
        );
    }

    protected function followVerificationLink(): void
    {
        // Get the verification token from the email
        $verificationToken = $this->getMagicLinkTokenFromMessage();
        $this->assertNotNull($verificationToken, 'Verification token should be found in email');

        $tokenResponse = $this->verifyMagicLink($verificationToken);

        $this->assertEquals(302, $tokenResponse['status'], 'Magic link token redirect to app');

        // Verify redirect URL contains correct parameters
        $redirectUrl = $tokenResponse['location'];
        $this->assertNotNull($redirectUrl, 'Redirect URL should be present');

        // Parse the redirect URL to extract query parameters
        $parsedUrl = parse_url($redirectUrl);
        $this->assertNotFalse($parsedUrl, 'Redirect URL should be valid');

        parse_str($parsedUrl['query'] ?? '', $queryParams);

        // Verify required parameters are present
        $this->assertArrayHasKey('flow', $queryParams, 'Redirect URL should contain flow parameter');
        $this->assertArrayHasKey('success', $queryParams, 'Redirect URL should contain success parameter');
        $this->assertArrayHasKey('message', $queryParams, 'Redirect URL should contain message parameter');
        $this->assertArrayHasKey('codeChallenge', $queryParams, 'Redirect URL should contain state parameter');
        $this->assertArrayHasKey('state', $queryParams, 'Redirect URL should contain state parameter');

        // Verify parameter values
        $this->assertEquals('registration', $queryParams['flow'], 'Flow should be registration');
        $this->assertEquals('true', $queryParams['success'], 'Success should be true');
        $this->assertIsString($queryParams['message'], 'Message parameter should be a string');
        $this->assertStringContainsString(
            'Registration verified successfully',
            urldecode($queryParams['message']),
            'Message should indicate successful registration verification'
        );
    }

    protected function requestMagicLinkForExistingUser(): void
    {
        $response = $this->sendMagicLinkRequest($this->testEmail);

        $this->assertEquals(200, $response->getStatusCode());
        $responseData = json_decode($response->getBody()->getContents(), true);

        $this->assertArrayHasKey('data', $responseData);
        $this->assertArrayHasKey('sendMagicLink', $responseData['data']);

        $sendMagicLinkResponse = $responseData['data']['sendMagicLink'];
        $this->assertTrue($sendMagicLinkResponse['success']);
        $this->assertEquals('MAGIC_LINK_SUCCESS', $sendMagicLinkResponse['code']);
    }

    private function registerTestUser(): void
    {
        $mutation = '
            mutation RegisterUser($input: RegistrationInput!) {
                register(input: $input) {
                    success
                    message
                    code
                    data
                }
            }
        ';

        $variables = [
            'input' => [
                'email'          => $this->testEmail,
                'additionalData' => [
                    'displayName' => 'Test User',
                ],
            ],
        ];

        $response = $this->sendGraphqlMutation($mutation, $variables);

        $this->assertEquals(200, $response->getStatusCode());
        $responseData = json_decode($response->getBody()->getContents(), true);

        $this->assertArrayHasKey('data', $responseData);
        $this->assertArrayHasKey('register', $responseData['data']);

        $registerResponse = $responseData['data']['register'];
        $this->assertTrue($registerResponse['success']);
        $this->assertEquals('EMAIL_REGISTERED', $registerResponse['code']);

        $this->fetchTestUserFromDatabase();
    }

    private function fetchTestUserFromDatabase(): void
    {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$this->testEmail]);
        $this->testUser = $stmt->fetch(PDO::FETCH_ASSOC);
    }

    private function ensureTestUserExists(): string
    {
        // Check if test user exists
        $stmt = $this->pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$this->testEmail]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            return $user['id'];
        }

        // Create test user
        $userId = $this->generateUuid();
        $stmt   = $this->pdo->prepare("
            INSERT INTO users (id, display_name, email, verified_at, created_at, updated_at)
            VALUES (?, ?, ?, NOW(), NOW(), NOW())
        ");
        $stmt->execute([$userId, 'Test User', $this->testEmail]);

        return $userId;
    }

    private function cleanupTestData(): void
    {
        // Clean up test user and related data
        $this->pdo->exec(
            "DELETE FROM magic_link_tokens WHERE user_id IN "
            . "(SELECT id FROM users WHERE email LIKE 'test-%' OR email LIKE 'unregistered-%')"
        );
        $this->pdo->exec(
            "DELETE FROM oauth_auth_codes WHERE user_id IN "
            . "(SELECT id FROM users WHERE email LIKE 'test-%' OR email LIKE 'unregistered-%')"
        );
        $this->pdo->exec("DELETE FROM users WHERE email LIKE 'test-%' OR email LIKE 'unregistered-%'");
    }

    private function sendMagicLinkRequest(string $email): ResponseInterface
    {
        $this->generatePkceData();

        $mutation = '
            mutation MagicLink($email: String!) {
                sendMagicLink(email: $email) {
                    success
                    message
                    code
                }
            }
        ';

        $variables = [
            'email'               => $this->testEmail,
            'clientId'            => $this->clientId,
            'codeChallenge'       => $this->pkceData['codeChallenge'],
            'codeChallengeMethod' => $this->pkceData['codeChallengeMethod'],
            'redirectUri'         => $this->pkceData['redirectUri'],
            'state'               => $this->pkceData['state'],
        ];

        return $this->sendGraphqlMutation($mutation, $variables);
    }

    /** @param array<string, mixed> $variables */
    private function sendGraphqlMutation(string $mutation, array $variables): ResponseInterface
    {
        return $this->httpClient->post('/graphql', [
            'json'    => [
                'query'     => $mutation,
                'variables' => $variables,
            ],
            'headers' => [
                'X-CLIENT-ID' => $this->clientId,
            ],
        ]);
    }

    /**
     * Assert that an email with specific content was sent
     */
    private function assertEmailWasSent(string $expectedContent): void
    {
        $this->assertNotNull($this->message, 'Email message should be available');
        $this->assertArrayHasKey('Content', $this->message, 'Email should have Content');
        $this->assertArrayHasKey('Body', $this->message['Content'], 'Email Content should have Body');

        $found = false;
        if (strpos($this->message['Content']['Body'], $expectedContent) !== false) {
            $found = true;
        }

        $this->assertTrue($found, "Email containing '{$expectedContent}' should have been sent");
    }

    /**
     * Get the latest token from database by type
     */
    private function getLatestTokenFromDatabase(string $tokenType): ?string
    {
        $stmt = $this->pdo->prepare("
            SELECT token FROM magic_link_tokens
            WHERE token_type = ?
            ORDER BY created_at DESC
            LIMIT 1
        ");
        $stmt->execute([$tokenType]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return $result ? $result['token'] : null;
    }

    /**
     * Wait for the next email to arrive in MailHog and save it to $this->message
     * Then clear MailHog for the next attempt
     */
    private function waitForNextEmail(int $timeoutSeconds = 10): void
    {
        $startTime     = time();
        $this->message = null;

        while (time() - $startTime < $timeoutSeconds) {
            try {
                $response = $this->httpClient->get('http://mailhog:8025/api/v2/messages');
                $messages = json_decode($response->getBody()->getContents(), true);

                if (! empty($messages['items'])) {
                    // Save the most recent email
                    $this->message = $messages['items'][0];

                    // Clear MailHog for next attempt
                    $this->clearMailHogEmails();
                    return;
                }
            } catch (Exception $e) {
                // Continue waiting
            }

            // Wait 100ms before checking again
            usleep(100000);
        }

        throw new Exception("No email received within {$timeoutSeconds} seconds");
    }

    /**
     * Clear all emails from MailHog for clean test environment
     */
    private function clearMailHogEmails(): void
    {
        try {
            $this->httpClient->delete('http://mailhog:8025/api/v1/messages');
        } catch (Exception $e) {
            // Ignore errors - MailHog might be empty or unavailable
        }
    }

    /**
     * Get login token from MailHog email (login flow)
     */
    private function getLoginTokenFromMailHog(): ?string
    {
        // Wait a moment for email to be delivered
        sleep(1);

        // Get messages from MailHog API (using Docker service name)
        $response = $this->httpClient->get('http://mailhog:8025/api/v2/messages');
        $messages = json_decode($response->getBody()->getContents(), true);

        // Look for login email (most recent)
        $emailUser = explode('@', $this->testEmail)[0];

        foreach ($messages['items'] as $message) {
            $messageUser = $message['To'][0]['Mailbox'] ?? '';

            if ($messageUser === $emailUser) {
                $emailContent = $message['Content']['Body'];

                // Look for login email (contains "Secure Login Link")
                if (strpos($emailContent, 'Secure Login Link') !== false) {
                    // Decode quoted-printable content to handle line breaks
                    $decodedContent = quoted_printable_decode($emailContent);

                    // Extract token from login email
                    $pattern = '/http:\/\/localhost:8088\/magic-link\/verify\?token=(?:3D)?([a-f0-9]+)/';
                    if (preg_match($pattern, $decodedContent, $matches)) {
                        return $matches[1];
                    }
                }
            }
        }

        return null;
    }

    /**
     * Extract authorization code from redirect URL (standard OAuth2 flow)
     */
    private function extractAuthCodeFromRedirect(string $location): ?string
    {
        if (preg_match('/[?&]code=([a-f0-9]+)/', $location, $matches)) {
            return $matches[1];
        }
        return null;
    }

    /**
     * Extract token from redirect URL after magic link verification
     */
    private function extractTokenFromRedirect(string $location): ?string
    {
        if (preg_match('/[?&]token=([a-f0-9]+)/', $location, $matches)) {
            return $matches[1];
        }
        return null;
    }

    /**
     * Get magic link token from MailHog email (true end-to-end test)
     */
    private function getMagicLinkTokenFromMessage(): ?string
    {
        // Since we cleared emails at start, any email should be from current test
        $emailUser = explode('@', $this->testEmail)[0];

        $messageUser = $this->message['To'][0]['Mailbox'] ?? '';

        if ($messageUser === $emailUser) {
            // Extract magic link from email content
            $this->assertNotNull($this->message, 'Email message should be available');
            $this->assertArrayHasKey('Content', $this->message, 'Email should have Content');
            $this->assertArrayHasKey('Body', $this->message['Content'], 'Email Content should have Body');

            $emailContent = $this->message['Content']['Body'];

            // Decode quoted-printable content to handle line breaks
            $decodedContent = quoted_printable_decode($emailContent);

            // Look for magic link URL pattern (handle URL encoding)
            $pattern = '/http:\/\/localhost:8088\/magic-link\/verify\?token(?:%3D|=)([a-f0-9]+)/';
            if (preg_match($pattern, $decodedContent, $matches)) {
                return $matches[1]; // Return the token part
            }
        }

        return null;
    }

    /** @return array<string, mixed> */
    private function verifyMagicLink(string $token): array
    {
        $response = $this->httpClient->get("/magic-link/verify?token={$token}", [
            'allow_redirects' => false,
        ]);

        return [
            'status'   => $response->getStatusCode(),
            'location' => $response->getHeader('Location')[0] ?? null,
            'body'     => $response->getBody()->getContents(),
        ];
    }

    /** @return array<string, mixed> */
    private function exchangeMagicLinkTokenForAccessToken(): array
    {
        $magicLinkToken = $this->getMagicLinkTokenFromMessage();
        $this->assertNotNull($magicLinkToken, 'Magic link token should be found in email');

        $response = $this->httpClient->post('/oauth/token', [
            'form_params' => [
                'grant_type'    => 'magic_link',
                'client_id'     => $this->clientId,
                'token'         => $magicLinkToken,
                'redirect_uri'  => $this->pkceData['redirectUri'],
                'code_verifier' => $this->pkceData['codeVerifier'],
            ],
            'headers'     => [
                'X-CLIENT-ID' => $this->clientId,
            ],
        ]);

        return [
            'status' => $response->getStatusCode(),
            'body'   => json_decode($response->getBody()->getContents(), true),
        ];
    }

    private function generatePkceData(string $flow = 'login'): void
    {
        // Generate PKCE parameters using the PKCEUtils library
        $codeVerifier  = PKCEUtils::generateCodeVerifier();
        $codeChallenge = PKCEUtils::generateCodeChallenge($codeVerifier);

        $this->pkceData = [
            'clientId'      => $this->clientId,
            'codeChallenge' => $codeChallenge,
            'redirectUri'   => 'http://localhost:8081/auth/callback',
            'state'         => $flow . '-' . time() . '-' . bin2hex(random_bytes(8)),
        ];
    }

    private function generateUuid(): string
    {
        $data    = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }

    // MailHog helper methods

    /** @return array<string, mixed> */
    private function getEmailsFromMailHog(): array
    {
        $response = $this->httpClient->get('http://mailhog:8025/api/v1/messages');
        return json_decode($response->getBody()->getContents(), true);
    }

    /** @param array<string, mixed> $email */
    private function findMagicLinkInEmail(array $email): ?string
    {
        $body = $email['Content']['Body'] ?? '';

        // Look for magic link URL pattern
        if (preg_match('/http:\/\/localhost:8081\/auth\/callback\?token=([a-f0-9]+)/', $body, $matches)) {
            return $matches[1]; // Return just the token
        }

        return null;
    }
}
