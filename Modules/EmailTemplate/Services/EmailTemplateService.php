<?php

declare(strict_types=1);

namespace Modules\EmailTemplate\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class EmailTemplateService
{
    /**
     * Get all email templates with pagination
     */
    public function getTemplates(array $filters = []): LengthAwarePaginator
    {
        $query = DB::table('email_templates');

        // Search by name or subject
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('subject', 'like', "%{$search}%");
            });
        }

        // Filter by template type
        if (!empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        // Filter by status
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // Sorting
        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortOrder = $filters['sort_order'] ?? 'desc';
        $query->orderBy($sortBy, $sortOrder);

        return $query->paginate($filters['per_page'] ?? 15);
    }

    /**
     * Get template by ID
     */
    public function getTemplateById(int $id): ?object
    {
        return DB::table('email_templates')->where('id', $id)->first();
    }

    /**
     * Get template by type (e.g., 'user_registration', 'password_reset')
     */
    public function getTemplateByType(string $type): ?object
    {
        return DB::table('email_templates')
            ->where('type', $type)
            ->where('status', 1)
            ->first();
    }

    /**
     * Create email template
     */
    public function createTemplate(array $data): int
    {
        return DB::table('email_templates')->insertGetId([
            'name' => $data['name'],
            'type' => $data['type'],
            'subject' => $data['subject'],
            'body' => $data['body'],
            'variables' => $data['variables'] ?? null,
            'status' => $data['status'] ?? 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Update email template
     */
    public function updateTemplate(int $id, array $data): bool
    {
        $updateData = array_filter([
            'name' => $data['name'] ?? null,
            'type' => $data['type'] ?? null,
            'subject' => $data['subject'] ?? null,
            'body' => $data['body'] ?? null,
            'variables' => $data['variables'] ?? null,
            'status' => $data['status'] ?? null,
            'updated_at' => now(),
        ], fn($value) => $value !== null);

        return DB::table('email_templates')
            ->where('id', $id)
            ->update($updateData) > 0;
    }

    /**
     * Delete email template
     */
    public function deleteTemplate(int $id): bool
    {
        return DB::table('email_templates')->where('id', $id)->delete() > 0;
    }

    /**
     * Bulk delete templates
     */
    public function bulkDelete(array $templateIds): int
    {
        return DB::table('email_templates')->whereIn('id', $templateIds)->delete();
    }

    /**
     * Bulk activate templates
     */
    public function bulkActivate(array $templateIds): int
    {
        return DB::table('email_templates')
            ->whereIn('id', $templateIds)
            ->update(['status' => 1, 'updated_at' => now()]);
    }

    /**
     * Bulk deactivate templates
     */
    public function bulkDeactivate(array $templateIds): int
    {
        return DB::table('email_templates')
            ->whereIn('id', $templateIds)
            ->update(['status' => 0, 'updated_at' => now()]);
    }

    /**
     * Parse template variables
     */
    public function parseTemplate(string $body, array $variables): string
    {
        foreach ($variables as $key => $value) {
            $body = str_replace("{{" . $key . "}}", $value, $body);
        }
        
        return $body;
    }

    /**
     * Get available template types
     */
    public function getTemplateTypes(): array
    {
        return [
            'user_registration' => 'User Registration',
            'user_email_verify' => 'User Email Verification',
            'password_reset' => 'Password Reset',
            'order_confirmation' => 'Order Confirmation',
            'subscription_activated' => 'Subscription Activated',
            'subscription_expired' => 'Subscription Expired',
            'payment_success' => 'Payment Success',
            'payment_failed' => 'Payment Failed',
            'contact_form' => 'Contact Form Submission',
            'newsletter' => 'Newsletter',
            'custom' => 'Custom Template',
        ];
    }

    /**
     * Get common template variables
     */
    public function getCommonVariables(): array
    {
        return [
            '{{user_name}}' => 'User full name',
            '{{user_email}}' => 'User email address',
            '{{site_name}}' => 'Website name',
            '{{site_url}}' => 'Website URL',
            '{{reset_link}}' => 'Password reset link',
            '{{verify_link}}' => 'Email verification link',
            '{{order_id}}' => 'Order ID',
            '{{amount}}' => 'Payment amount',
            '{{date}}' => 'Current date',
            '{{year}}' => 'Current year',
        ];
    }

    /**
     * Get template statistics
     */
    public function getStatistics(): array
    {
        $total = DB::table('email_templates')->count();
        $active = DB::table('email_templates')->where('status', 1)->count();
        
        return [
            'total_templates' => $total,
            'active_templates' => $active,
            'inactive_templates' => $total - $active,
        ];
    }

    /**
     * Duplicate template
     */
    public function duplicateTemplate(int $id): ?int
    {
        $template = $this->getTemplateById($id);
        
        if (!$template) {
            return null;
        }

        return DB::table('email_templates')->insertGetId([
            'name' => $template->name . ' (Copy)',
            'type' => $template->type,
            'subject' => $template->subject,
            'body' => $template->body,
            'variables' => $template->variables,
            'status' => 0, // Disabled by default for copies
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Preview template with sample data
     */
    public function previewTemplate(int $id, array $sampleData = []): ?array
    {
        $template = $this->getTemplateById($id);
        
        if (!$template) {
            return null;
        }

        $defaultSampleData = [
            'user_name' => 'John Doe',
            'user_email' => 'john@example.com',
            'site_name' => config('app.name'),
            'site_url' => config('app.url'),
            'date' => now()->format('Y-m-d'),
            'year' => now()->year,
            'amount' => '$99.99',
            'order_id' => '#12345',
        ];

        $mergedData = array_merge($defaultSampleData, $sampleData);
        
        return [
            'subject' => $this->parseTemplate($template->subject, $mergedData),
            'body' => $this->parseTemplate($template->body, $mergedData),
        ];
    }
}
