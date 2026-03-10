<?php

declare(strict_types=1);

namespace Modules\RealEstate\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Modules\RealEstate\Entities\PropertyInquiry;
use Modules\RealEstate\Entities\Template;

/**
 * TemplateService — F2.4 Canned Response Templates
 *
 * Handles admin CRUD and agent-facing variable resolution.
 *
 * Supported substitution variables:
 *   {{user.name}}        — inquiry user's name (or contact_name)
 *   {{property.title}}   — property title (via inquiry)
 *   {{property.price}}   — property price
 *   {{compound.name}}    — compound name (if applicable)
 *   {{agent.name}}       — assigned agent's name
 */
class TemplateService
{
    // =========================================================================
    // Admin CRUD
    // =========================================================================

    /**
     * Paginated list of all templates (admin).
     */
    public function getPaginated(array $filters = []): LengthAwarePaginator
    {
        $query = Template::query()->orderBy('name');

        if (!empty($filters['category'])) {
            $query->byCategory($filters['category']);
        }

        if (isset($filters['is_active'])) {
            $query->where('is_active', (bool) $filters['is_active']);
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('body_en', 'like', "%{$search}%")
                  ->orWhere('body_ar', 'like', "%{$search}%");
            });
        }

        $perPage = min((int) ($filters['per_page'] ?? 15), 50);

        return $query->paginate($perPage);
    }

    /**
     * Create a new template, auto-detecting variables.
     */
    public function create(array $data): Template
    {
        $template = new Template($data);
        $template->variables = $template->detectVariables();
        $template->save();

        return $template;
    }

    /**
     * Update template, refreshing detected variables.
     */
    public function update(Template $template, array $data): Template
    {
        $template->fill($data);
        $template->variables = $template->detectVariables();
        $template->save();

        return $template;
    }

    /**
     * Delete a template.
     */
    public function delete(Template $template): void
    {
        $template->delete();
    }

    // =========================================================================
    // Agent — active templates list
    // =========================================================================

    /**
     * Returns all active templates for agents (optionally filtered by category).
     */
    public function getActive(?string $category = null): Collection
    {
        $query = Template::active()->orderBy('name');

        if ($category !== null) {
            $query->byCategory($category);
        }

        return $query->get();
    }

    /**
     * Get distinct categories that have at least one active template.
     */
    public function getCategories(): array
    {
        return Template::active()
            ->whereNotNull('category')
            ->distinct()
            ->orderBy('category')
            ->pluck('category')
            ->toArray();
    }

    // =========================================================================
    // Variable resolution (preview)
    // =========================================================================

    /**
     * Resolve template variables against a PropertyInquiry and return
     * both language variants with substitutions applied.
     *
     * @return array{en: string, ar: string, variables_used: array}
     */
    public function resolveForInquiry(Template $template, PropertyInquiry $inquiry): array
    {
        $replacements = $this->buildReplacements($inquiry);

        $search  = array_keys($replacements);
        $replace = array_values($replacements);

        return [
            'en'             => str_replace($search, $replace, $template->body_en),
            'ar'             => str_replace($search, $replace, $template->body_ar),
            'variables_used' => $replacements,
        ];
    }

    /**
     * Build the token → value map for a given inquiry.
     */
    private function buildReplacements(PropertyInquiry $inquiry): array
    {
        // Eager-load if not already loaded
        if (!$inquiry->relationLoaded('user')) {
            $inquiry->load('user');
        }
        if (!$inquiry->relationLoaded('property') && $inquiry->property_id) {
            $inquiry->load('property');
        }
        if (!$inquiry->relationLoaded('compound') && $inquiry->compound_id) {
            $inquiry->load('compound');
        }
        if (!$inquiry->relationLoaded('agent') && $inquiry->agent_id) {
            $inquiry->load('agent');
        }

        $userName    = $inquiry->user?->name ?? $inquiry->contact_name ?? 'Customer';
        $propTitle   = $inquiry->property?->title ?? 'N/A';
        $propPrice   = $inquiry->property?->price
            ? number_format((float) $inquiry->property->price)
            : 'N/A';
        $compoundName = $inquiry->compound?->name ?? 'N/A';
        $agentName   = $inquiry->agent?->name ?? 'Our team';

        return [
            '{{user.name}}'      => $userName,
            '{{property.title}}' => $propTitle,
            '{{property.price}}' => $propPrice,
            '{{compound.name}}'  => $compoundName,
            '{{agent.name}}'     => $agentName,
        ];
    }
}
