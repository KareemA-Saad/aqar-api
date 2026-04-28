<?php

declare(strict_types=1);

namespace Modules\RealEstate\Transformers;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'RE_PropertyInquiryResource',
    properties: [
        new OA\Property(property: 'id', type: 'integer'),
        new OA\Property(property: 'name', type: 'string'),
        new OA\Property(property: 'email', type: 'string'),
        new OA\Property(property: 'phone', type: 'string'),
        new OA\Property(property: 'message', type: 'string', nullable: true),
        new OA\Property(property: 'status', type: 'string', enum: ['new', 'contacted', 'qualified', 'converted', 'closed']),
        new OA\Property(property: 'status_label', type: 'string'),
        new OA\Property(property: 'property', ref: '#/components/schemas/RE_PropertyResource', nullable: true),
        new OA\Property(property: 'compound', ref: '#/components/schemas/RE_CompoundResource', nullable: true, description: 'For direct compound inquiries'),
        new OA\Property(property: 'user', type: 'object', nullable: true, description: 'Authenticated user who submitted inquiry', properties: [
            new OA\Property(property: 'id', type: 'integer'),
            new OA\Property(property: 'name', type: 'string'),
            new OA\Property(property: 'email', type: 'string'),
        ]),
        new OA\Property(property: 'agent', type: 'object', nullable: true, description: 'Assigned agent', properties: [
            new OA\Property(property: 'id', type: 'integer'),
            new OA\Property(property: 'name', type: 'string'),
            new OA\Property(property: 'email', type: 'string'),
        ]),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
    ]
)]
class PropertyInquiryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            
            // Contact Info
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'message' => $this->message,
            
            // Status
            'status' => $this->status,
            'status_label' => ucfirst($this->status),

            // F2.3: Lead score (always computed, shown to agents + admins)
            'lead_score'       => $this->when(
                $request->routeIs('agent.*') || $request->routeIs('admin.*'),
                fn () => $this->lead_score
            ),
            'lead_temperature' => $this->when(
                $request->routeIs('agent.*') || $request->routeIs('admin.*'),
                fn () => $this->lead_temperature
            ),

            // F2.1: SLA status (shown to agents)
            'sla_status'       => $this->when(
                $request->routeIs('agent.*'),
                fn () => $this->sla_status
            ),
            'sla_hours_elapsed' => $this->when(
                $request->routeIs('agent.*'),
                fn () => $this->sla_hours_elapsed
            ),
            'admin_notes' => $this->when($request->routeIs('admin.*'), $this->admin_notes),
            'ip_address' => $this->when($request->routeIs('admin.*'), $this->ip_address),
            'user_agent' => $this->when($request->routeIs('admin.*'), $this->user_agent),
            
            // Relations
            'property' => new PropertyResource($this->whenLoaded('property')),
            'compound' => new CompoundResource($this->whenLoaded('compound')),
            'user' => $this->when($this->relationLoaded('user'), function () {
                return $this->user ? [
                    'id' => $this->user->id,
                    'name' => $this->user->name,
                    'email' => $this->user->email,
                ] : null;
            }),
            'agent' => $this->when($this->relationLoaded('agent'), function () {
                return $this->agent ? [
                    'id' => $this->agent->id,
                    'name' => $this->agent->name,
                    'email' => $this->agent->email,
                ] : null;
            }),
            
            // Timestamps
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
