<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Landlord\Admin;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Http\Requests\Settings\EmailTemplateRequest;
use App\Http\Requests\Settings\SmtpSettingsRequest;
use App\Http\Requests\Settings\TestEmailRequest;
use App\Http\Resources\EmailTemplateResource;
use App\Http\Resources\SmtpConfigResource;
use App\Services\SettingsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use OpenApi\Attributes as OA;

/**
 * Email Settings Controller
 *
 * Handles email and SMTP configuration management.
 *
 * @package App\Http\Controllers\Api\V1\Landlord\Admin
 */
#[OA\Tag(
    name: 'Email Settings',
    description: 'Email and SMTP configuration management endpoints'
)]
final class EmailSettingsController extends BaseApiController
{
    /**
     * Available email templates.
     *
     * @var array<string, array<string, mixed>>
     */
    private const EMAIL_TEMPLATES = [
        'welcome_email' => [
            'name' => 'Welcome Email',
            'description' => 'Sent when a new user registers',
            'variables' => ['{{site_name}}', '{{user_name}}', '{{user_email}}', '{{login_url}}'],
        ],
        'password_reset' => [
            'name' => 'Password Reset',
            'description' => 'Sent when a user requests a password reset',
            'variables' => ['{{site_name}}', '{{user_name}}', '{{reset_link}}', '{{expiry_time}}'],
        ],
        'email_verification' => [
            'name' => 'Email Verification',
            'description' => 'Sent to verify user email address',
            'variables' => ['{{site_name}}', '{{user_name}}', '{{verification_link}}'],
        ],
        'subscription_confirmation' => [
            'name' => 'Subscription Confirmation',
            'description' => 'Sent when a user subscribes to a plan',
            'variables' => ['{{site_name}}', '{{user_name}}', '{{plan_name}}', '{{amount}}', '{{expiry_date}}'],
        ],
        'subscription_renewal' => [
            'name' => 'Subscription Renewal Reminder',
            'description' => 'Sent before subscription expires',
            'variables' => ['{{site_name}}', '{{user_name}}', '{{plan_name}}', '{{expiry_date}}', '{{renewal_link}}'],
        ],
        'tenant_created' => [
            'name' => 'Tenant Created',
            'description' => 'Sent when a new tenant/website is created',
            'variables' => ['{{site_name}}', '{{user_name}}', '{{tenant_name}}', '{{tenant_url}}'],
        ],
        'support_ticket_created' => [
            'name' => 'Support Ticket Created',
            'description' => 'Sent when a support ticket is created',
            'variables' => ['{{site_name}}', '{{user_name}}', '{{ticket_id}}', '{{ticket_subject}}', '{{ticket_url}}'],
        ],
        'support_ticket_reply' => [
            'name' => 'Support Ticket Reply',
            'description' => 'Sent when a support ticket receives a reply',
            'variables' => ['{{site_name}}', '{{user_name}}', '{{ticket_id}}', '{{ticket_subject}}', '{{reply_content}}', '{{ticket_url}}'],
        ],
        'invoice' => [
            'name' => 'Invoice',
            'description' => 'Sent as payment invoice',
            'variables' => ['{{site_name}}', '{{user_name}}', '{{invoice_number}}', '{{amount}}', '{{payment_date}}', '{{invoice_url}}'],
        ],
    ];

    /**
     * Create a new controller instance.
     *
     * @param SettingsService $settingsService The settings service
     */
    public function __construct(
        private readonly SettingsService $settingsService
    ) {
        $this->middleware('permission:general-settings-smtp-settings');
    }

    /**
     * Get SMTP configuration.
     *
     * Retrieves current SMTP settings (password masked).
     */
    #[OA\Get(
        path: '/api/v1/admin/email-settings/smtp',
        summary: 'Get SMTP configuration',
        description: 'Retrieves current SMTP settings. Password is always masked.',
        security: [['sanctum_admin' => []]],
        tags: ['Email Settings'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'SMTP configuration retrieved successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'SMTP configuration retrieved successfully'),
                        new OA\Property(
                            property: 'data',
                            ref: '#/components/schemas/SmtpConfigResource'
                        ),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
        ]
    )]
    public function getSmtpConfig(): JsonResponse
    {
        $config = $this->settingsService->getSmtpConfig();

        return $this->successResponse(
            (new SmtpConfigResource($config))->toArray(request()),
            'SMTP configuration retrieved successfully'
        );
    }

    /**
     * Update SMTP configuration.
     *
     * Updates SMTP settings for sending emails.
     */
    #[OA\Put(
        path: '/api/v1/admin/email-settings/smtp',
        summary: 'Update SMTP configuration',
        description: 'Updates SMTP settings for sending emails. Leave password empty to keep existing password.',
        security: [['sanctum_admin' => []]],
        tags: ['Email Settings'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/SmtpSettingsRequest')
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'SMTP configuration updated successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'SMTP configuration updated successfully'),
                        new OA\Property(
                            property: 'data',
                            ref: '#/components/schemas/SmtpConfigResource'
                        ),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function updateSmtpConfig(SmtpSettingsRequest $request): JsonResponse
    {
        $config = $request->validatedConfig();

        $success = $this->settingsService->updateSmtpConfig($config);

        if (!$success) {
            return $this->errorResponse('Failed to update SMTP configuration', 500);
        }

        // Update .env file for mail settings (if running in central/landlord context)
        $this->updateEnvMailSettings($config);

        // Return updated config
        $updatedConfig = $this->settingsService->getSmtpConfig();

        return $this->successResponse(
            (new SmtpConfigResource($updatedConfig))->toArray(request()),
            'SMTP configuration updated successfully'
        );
    }

    /**
     * Send test email.
     *
     * Sends a test email to verify SMTP configuration.
     */
    #[OA\Post(
        path: '/api/v1/admin/email-settings/test',
        summary: 'Send test email',
        description: 'Sends a test email to verify SMTP configuration is working correctly',
        security: [['sanctum_admin' => []]],
        tags: ['Email Settings'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/TestEmailRequest')
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Test email sent successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Test email sent successfully'),
                    ]
                )
            ),
            new OA\Response(
                response: 500,
                description: 'Failed to send test email',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'message', type: 'string', example: 'Failed to send test email'),
                        new OA\Property(property: 'error', type: 'string', example: 'Connection refused'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function testEmail(TestEmailRequest $request): JsonResponse
    {
        $email = $request->getEmail();

        try {
            $siteName = $this->settingsService->get('site_title', config('app.name'));
            $message = "Hi,\n\nThis is a test email from {$siteName}.\n\nIf you received this email, your SMTP configuration is working correctly.\n\nBest regards,\n{$siteName}";

            Mail::raw($message, function ($mail) use ($email, $siteName) {
                $mail->to($email)
                    ->subject("SMTP Test Email from {$siteName}");
            });

            Log::info('Test email sent', ['to' => $email]);

            return $this->successResponse(null, 'Test email sent successfully');
        } catch (\Exception $e) {
            Log::error('Failed to send test email', [
                'to' => $email,
                'error' => $e->getMessage(),
            ]);

            return $this->errorResponse('Failed to send test email: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get all email templates.
     *
     * Retrieves a list of all available email templates.
     */
    #[OA\Get(
        path: '/api/v1/admin/email-settings/templates',
        summary: 'Get all email templates',
        description: 'Retrieves a list of all available email templates with their current configuration',
        security: [['sanctum_admin' => []]],
        tags: ['Email Settings'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Email templates retrieved successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Email templates retrieved successfully'),
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(ref: '#/components/schemas/EmailTemplateResource')
                        ),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
        ]
    )]
    public function getEmailTemplates(): JsonResponse
    {
        $templates = [];

        foreach (self::EMAIL_TEMPLATES as $id => $templateInfo) {
            // Get stored template data from settings
            $subject = $this->settingsService->get("email_template_{$id}_subject");
            $body = $this->settingsService->get("email_template_{$id}_body");
            $enabled = $this->settingsService->get("email_template_{$id}_enabled", 'true');

            $templates[] = [
                'id' => $id,
                'name' => $templateInfo['name'],
                'description' => $templateInfo['description'],
                'subject' => $subject,
                'body' => $body,
                'enabled' => $enabled === 'true' || $enabled === true,
                'variables' => $templateInfo['variables'],
            ];
        }

        $resources = array_map(
            fn ($template) => (new EmailTemplateResource($template))->toArray(request()),
            $templates
        );

        return $this->successResponse($resources, 'Email templates retrieved successfully');
    }

    /**
     * Get single email template.
     *
     * Retrieves a specific email template by ID.
     */
    #[OA\Get(
        path: '/api/v1/admin/email-settings/templates/{template}',
        summary: 'Get single email template',
        description: 'Retrieves a specific email template by its ID',
        security: [['sanctum_admin' => []]],
        tags: ['Email Settings'],
        parameters: [
            new OA\Parameter(
                name: 'template',
                in: 'path',
                required: true,
                description: 'Template ID',
                schema: new OA\Schema(type: 'string'),
                example: 'welcome_email'
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Email template retrieved successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Email template retrieved successfully'),
                        new OA\Property(
                            property: 'data',
                            ref: '#/components/schemas/EmailTemplateResource'
                        ),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 404, description: 'Template not found'),
        ]
    )]
    public function getEmailTemplate(string $template): JsonResponse
    {
        if (!isset(self::EMAIL_TEMPLATES[$template])) {
            return $this->errorResponse('Email template not found', 404);
        }

        $templateInfo = self::EMAIL_TEMPLATES[$template];
        $subject = $this->settingsService->get("email_template_{$template}_subject");
        $body = $this->settingsService->get("email_template_{$template}_body");
        $enabled = $this->settingsService->get("email_template_{$template}_enabled", 'true');

        $data = [
            'id' => $template,
            'name' => $templateInfo['name'],
            'description' => $templateInfo['description'],
            'subject' => $subject,
            'body' => $body,
            'enabled' => $enabled === 'true' || $enabled === true,
            'variables' => $templateInfo['variables'],
        ];

        return $this->successResponse(
            (new EmailTemplateResource($data))->toArray(request()),
            'Email template retrieved successfully'
        );
    }

    /**
     * Update email template.
     *
     * Updates a specific email template.
     */
    #[OA\Put(
        path: '/api/v1/admin/email-settings/templates/{template}',
        summary: 'Update email template',
        description: 'Updates a specific email template by its ID',
        security: [['sanctum_admin' => []]],
        tags: ['Email Settings'],
        parameters: [
            new OA\Parameter(
                name: 'template',
                in: 'path',
                required: true,
                description: 'Template ID',
                schema: new OA\Schema(type: 'string'),
                example: 'welcome_email'
            ),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/EmailTemplateRequest')
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Email template updated successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Email template updated successfully'),
                        new OA\Property(
                            property: 'data',
                            ref: '#/components/schemas/EmailTemplateResource'
                        ),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 404, description: 'Template not found'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function updateEmailTemplate(EmailTemplateRequest $request, string $template): JsonResponse
    {
        if (!isset(self::EMAIL_TEMPLATES[$template])) {
            return $this->errorResponse('Email template not found', 404);
        }

        $data = $request->validatedTemplate();

        // Save template settings
        $this->settingsService->set("email_template_{$template}_subject", $data['subject']);
        $this->settingsService->set("email_template_{$template}_body", $data['body']);
        $this->settingsService->set("email_template_{$template}_enabled", $data['enabled'] ? 'true' : 'false');

        // Return updated template
        $templateInfo = self::EMAIL_TEMPLATES[$template];
        $responseData = [
            'id' => $template,
            'name' => $templateInfo['name'],
            'description' => $templateInfo['description'],
            'subject' => $data['subject'],
            'body' => $data['body'],
            'enabled' => $data['enabled'],
            'variables' => $templateInfo['variables'],
        ];

        return $this->successResponse(
            (new EmailTemplateResource($responseData))->toArray(request()),
            'Email template updated successfully'
        );
    }

    /**
     * Update .env file with mail settings.
     *
     * @param array<string, mixed> $config SMTP configuration
     * @return void
     */
    private function updateEnvMailSettings(array $config): void
    {
        // Only update .env in central/landlord context
        if (function_exists('tenant') && tenant() !== null) {
            return;
        }

        try {
            $envValues = [
                'MAIL_MAILER' => $config['driver'] ?? 'smtp',
                'MAIL_HOST' => $config['host'],
                'MAIL_PORT' => $config['port'],
                'MAIL_USERNAME' => $config['username'],
                'MAIL_ENCRYPTION' => $config['encryption'] ?? 'tls',
                'MAIL_FROM_ADDRESS' => $config['from_email'],
            ];

            // Only update password if provided and not masked
            if (!empty($config['password']) && $config['password'] !== '********') {
                $envValues['MAIL_PASSWORD'] = $this->addQuotesIfNeeded($config['password']);
            }

            if (!empty($config['from_name'])) {
                $envValues['MAIL_FROM_NAME'] = $this->addQuotesIfNeeded($config['from_name']);
            }

            if (function_exists('setEnvValue')) {
                setEnvValue($envValues);
            }
        } catch (\Exception $e) {
            Log::warning('Failed to update .env mail settings', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Add quotes to value if it contains spaces.
     *
     * @param string $value The value
     * @return string
     */
    private function addQuotesIfNeeded(string $value): string
    {
        if (preg_match('/\s/', $value) && !preg_match('/^["\'].*["\']$/', $value)) {
            return "\"{$value}\"";
        }

        return $value;
    }
}
