<?php

declare(strict_types=1);

use Phinx\Seed\AbstractSeed;

class MagicLinkCommunicationSeed extends AbstractSeed
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
        // Create a magic link communication definition
        $communicationDefinitions = $this->table('communication_definitions');
        $communicationDefinitions->insert([
            'identifier' => 'auth.magic-link',
            'name' => 'Magic Link Authentication Email',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ])->save();

        // Create a channel definition for email
        $channelDefinitions = $this->table('channel_definitions');
        $channelDefinitions->insert([
            'communication_identifier' => 'auth.magic-link',
            'channel' => 'email',
            'template' => 'magic-link',
            'context_schema' => json_encode([
                'type' => 'object',
                'required' => ['name', 'link'],
                'properties' => [
                    'name' => ['type' => 'string'],
                    'link' => ['type' => 'string'],
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

        // Create a template for the magic link email
        $templateId = $this->generateUlid();
        $communicationTemplates = $this->table('communication_templates');
        $communicationTemplates->insert([
            'id' => $templateId,
            'name' => 'magic-link',
            'channel' => 'email',
            'subject' => 'Your Secure Login Link',
            'content' => $this->getMagicLinkEmailTemplate(),
            'content_type' => 'text/html',
            'metadata' => json_encode([
                'description' => 'Updated magic link template for v2.0 with verification endpoint support',
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
     * Get the HTML template for the magic link email
     *
     * This returns a Twig template that handles the name and link variables
     */
    private function getMagicLinkEmailTemplate(): string
    {
        return <<<TWIG
{% block subject %}{{ subject|default('Your Secure Login Link') }}{% endblock %}

{% block body_html %}
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ subject|default('Your Secure Login Link') }}</title>
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
            background-color: #4CAF50;
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
        .security-note {
            font-size: 13px;
            color: #666;
            margin-top: 15px;
            padding: 10px;
            border-left: 3px solid #4CAF50;
            background-color: #f0f0f0;
        }
    </style>
</head>
<body>
    <div class="content">
        <h2>Hello{% if name %}, {{ name }}{% endif %}!</h2>
        
        <p>You recently requested a secure login link to access your account. Click the button below to securely sign in:</p>
        
        <div style="text-align: center;">
            <a href="{{ link }}" class="button">Sign In Securely</a>
        </div>
        
        <p>If the button doesn't work, you can copy and paste this link into your browser:</p>
        <p style="word-break: break-all; background-color: #f0f0f0; padding: 10px; border-radius: 4px;">{{ link }}</p>
        
        <div class="security-note">
            <p><strong>Security Note:</strong> This link will expire in 10 minutes and can only be used once. If you didn't request this link, please ignore this email or contact support if you have concerns about your account security.</p>
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

You recently requested a secure login link to access your account. Please use the link below to securely sign in:

{{ link }}

This link will expire in 10 minutes and can only be used once.
This link supports secure PKCE authentication for mobile applications.

If you didn't request this link, please ignore this email or contact support if you have concerns about your account security.

This is an automated message. Please do not reply to this email.
If you need assistance, please contact our support team.

© {{ "now"|date("Y") }} Your Company. All rights reserved.
{% endblock %}
TWIG;
    }
}