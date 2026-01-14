<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class EmailTemplateService
{
    /**
     * Predefined email template types matching OLDARCHIVE patterns
     */
    private const TEMPLATE_TYPES = [
        'user_reset_password',
        'user_email_verify',
        'admin_email_verify',
        'newsletter_verify',
        'wallet_manual_payment_approved',
    ];

    /**
     * Get available template types
     *
     * @return array
     */
    public function getTemplateTypes(): array
    {
        return self::TEMPLATE_TYPES;
    }

    /**
     * Get email template for specific type and language
     *
     * @param string $type Template type (e.g., 'user_reset_password')
     * @param string $lang Language code (e.g., 'en', 'ar')
     * @return array{subject: string, message: string}
     */
    public function getTemplate(string $type, string $lang): array
    {
        if (!in_array($type, self::TEMPLATE_TYPES)) {
            Log::warning("Invalid email template type requested", [
                'type' => $type,
                'lang' => $lang
            ]);
            
            return [
                'subject' => '',
                'message' => '',
            ];
        }

        $subjectKey = "{$type}_{$lang}_subject";
        $messageKey = "{$type}_{$lang}_message";

        return [
            'subject' => get_static_option($subjectKey, ''),
            'message' => get_static_option($messageKey, ''),
        ];
    }

    /**
     * Update email template for specific type and language
     *
     * @param string $type Template type
     * @param string $lang Language code
     * @param string $subject Email subject
     * @param string $message Email message/body
     * @return bool
     */
    public function updateTemplate(string $type, string $lang, string $subject, string $message): bool
    {
        if (!in_array($type, self::TEMPLATE_TYPES)) {
            Log::error("Attempted to update invalid email template type", [
                'type' => $type,
                'lang' => $lang
            ]);
            
            return false;
        }

        try {
            $subjectKey = "{$type}_{$lang}_subject";
            $messageKey = "{$type}_{$lang}_message";

            update_static_option($subjectKey, $subject);
            update_static_option($messageKey, $message);

            Log::info("Email template updated successfully", [
                'type' => $type,
                'lang' => $lang,
                'subject_key' => $subjectKey,
                'message_key' => $messageKey,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error("Failed to update email template", [
                'type' => $type,
                'lang' => $lang,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Get all templates for a specific type across all languages
     *
     * @param string $type Template type
     * @param array $languages Array of language codes
     * @return array
     */
    public function getTemplateAllLanguages(string $type, array $languages): array
    {
        if (!in_array($type, self::TEMPLATE_TYPES)) {
            return [];
        }

        $templates = [];
        
        foreach ($languages as $lang) {
            $templates[$lang] = $this->getTemplate($type, $lang);
        }

        return $templates;
    }

    /**
     * Batch update templates for multiple languages
     *
     * @param string $type Template type
     * @param array $templates Associative array [lang => ['subject' => ..., 'message' => ...]]
     * @return array{success: bool, failed: array}
     */
    public function batchUpdateTemplates(string $type, array $templates): array
    {
        $failed = [];

        foreach ($templates as $lang => $content) {
            $subject = $content['subject'] ?? '';
            $message = $content['message'] ?? '';

            $success = $this->updateTemplate($type, $lang, $subject, $message);

            if (!$success) {
                $failed[] = $lang;
            }
        }

        return [
            'success' => empty($failed),
            'failed' => $failed,
        ];
    }

    /**
     * Check if template exists for given type and language
     *
     * @param string $type Template type
     * @param string $lang Language code
     * @return bool
     */
    public function templateExists(string $type, string $lang): bool
    {
        if (!in_array($type, self::TEMPLATE_TYPES)) {
            return false;
        }

        $template = $this->getTemplate($type, $lang);
        
        return !empty($template['subject']) || !empty($template['message']);
    }
}
