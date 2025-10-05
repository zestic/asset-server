<?php

declare(strict_types=1);

use Phinx\Seed\AbstractSeed;

class VerificationCommunicationSeed extends AbstractSeed
{
    /**
     * Run Method.
     *
     * Write your database seeder using this method.
     *
     * More information on writing seeders is available here:
     * https://book.cakephp.org/phinx/0/en/seeding.html
     */
    public function run(): void
    {
        // Create an email verification communication definition
        $communicationDefinitions = $this->table('communication_definitions');
        $communicationDefinitions->insert([
            'identifier' => 'auth.email-verification',
            'name' => 'Email Address Verification',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ])->save();

        // Create a channel definition for email
        $channelDefinitions = $this->table('channel_definitions');
        $channelDefinitions->insert([
            'communication_identifier' => 'auth.email-verification',
            'channel' => 'email',
            'template' => 'email-verification',
            'context_schema' => json_encode([
                'type' => 'object',
                'required' => ['name', 'link'],
                'properties' => [
                    'name' => ['type' => 'string'],
                    'link' => ['type' => 'string'],
                    'email' => ['type' => 'string'],
                    'additionalData' => ['type' => 'object']
                ]
            ]),
            'subject_schema' => json_encode([
                'type' => 'object',
                'required' => ['subject'],
                'properties' => [
                    'subject' => ['type' => 'string']
                ]
            ]),
            'channel_config' => json_encode([
                'from_address' => 'noreply@example.com',
                'reply_to' => 'support@example.com'
            ]),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ])->save();

        // Create a template for the email verification
        $templateId = $this->generateUlid();
        $communicationTemplates = $this->table('communication_templates');
        $communicationTemplates->insert([
            'id' => $templateId,
            'name' => 'email-verification',
            'channel' => 'email',
            'subject' => 'Please Verify Your Email Address',
            'content' => $this->getEmailVerificationTemplate(),
            'content_type' => 'text/html',
            'metadata' => json_encode([
                'description' => 'Updated email verification template for v2.0 with verification endpoint support',
                'version' => '2.0',
                'supports_pkce' => true,
                'verification_endpoint' => '/magic-link/verify'
            ]),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ])->save();
    }

    /**
     * Generate a ULID (Universally Unique Lexicographically Sortable Identifier)
     * This is a simplified implementation for demonstration purposes
     */
    private function generateUlid(): string
    {
        $time = (int)(microtime(true) * 1000);
        $timestamp = str_pad(base_convert((string) $time, 10, 32), 10, '0', STR_PAD_LEFT);
        $randomness = bin2hex(random_bytes(8));

        // Convert to Crockford's base32 (using only uppercase letters and digits 0-9, excluding I, L, O, U)
        $base32Chars = '0123456789ABCDEFGHJKMNPQRSTVWXYZ';
        $ulid = '';

        // Convert timestamp
        for ($i = 0; $i < strlen($timestamp); $i++) {
            $char = $timestamp[$i];
            $index = hexdec($char);
            $ulid .= $base32Chars[$index];
        }

        // Convert randomness
        for ($i = 0; $i < strlen($randomness); $i++) {
            $char = $randomness[$i];
            $index = hexdec($char) % 32;
            $ulid .= $base32Chars[$index];
        }

        return $ulid;
    }

    /**
     * Get the HTML template for the email verification email
     *
     * This returns a Twig template that handles the name and link variables
     */
    private function getEmailVerificationTemplate(): string
    {
        return <<<TWIG
{% block subject %}{{ subject|default('Please Verify Your Email Address') }}{% endblock %}

{% block body_html %}
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ subject|default('Please Verify Your Email Address') }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .content {
            background-color: #f9f9f9;
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 20px;
            margin-top: 20px;
        }
        .button {
            display: inline-block;
            background-color: #4285F4;
            color: white;
            text-decoration: none;
            padding: 12px 24px;
            border-radius: 4px;
            margin: 20px 0;
            font-weight: bold;
        }
        .footer {
            margin-top: 20px;
            font-size: 12px;
            color: #777;
            text-align: center;
            border-top: 1px solid #eee;
            padding-top: 15px;
        }
        .info-note {
            font-size: 13px;
            color: #666;
            margin-top: 15px;
            padding: 10px;
            border-left: 3px solid #4285F4;
            background-color: #f0f0f0;
        }
    </style>
</head>
<body>
    <div class="content">
        <h2>Hello{% if name %}, {{ name }}{% endif %}!</h2>
        
        <p>Thank you for registering. To complete your account setup and verify your email address, please click the button below:</p>
        
        <div style="text-align: center;">
            <a href="{{ link }}" class="button">Verify Email Address</a>
        </div>
        
        <p>If the button doesn't work, you can copy and paste this link into your browser:</p>
        <p style="word-break: break-all; background-color: #f0f0f0; padding: 10px; border-radius: 4px;">{{ link }}</p>
        
        <div class="info-note">
            <p><strong>Note:</strong> This verification link will expire in 24 hours. If you didn't create an account with us, you can safely ignore this email.</p>
            <p><strong>For Mobile Apps:</strong> This link supports secure PKCE authentication for mobile applications.</p>
        </div>
    </div>

    <div class="footer">
        <p>This is an automated message. Please do not reply to this email.</p>
        <p>If you need assistance, please contact our support team.</p>
        <p>© {{ "now"|date("Y") }} Your Company. All rights reserved.</p>
    </div>
</body>
</html>
{% endblock %}

{% block body_text %}
Hello{% if name %}, {{ name }}{% endif %}!

Thank you for registering. To complete your account setup and verify your email address, please use the link below:

{{ link }}

This verification link will expire in 24 hours.
This link supports secure PKCE authentication for mobile applications.

If you didn't create an account with us, you can safely ignore this email.

This is an automated message. Please do not reply to this email.
If you need assistance, please contact our support team.

© {{ "now"|date("Y") }} Your Company. All rights reserved.
{% endblock %}
TWIG;
    }
}