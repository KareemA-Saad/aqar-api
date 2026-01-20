<?php

declare(strict_types=1);

namespace Modules\RealEstate\Transformers;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'PropertyInquiryResource',
    properties: [
        new OA\Property(property: 'id', type: 'integer'),
        new OA\Property(property: 'name', type: 'string'),
        new OA\Property(property: 'email', type: 'string'),
        new OA\Property(property: 'phone', type: 'string'),
        new OA\Property(property: 'message', type: 'string'),
        new OA\Property(property: 'status', type: 'string', enum: ['new', 'contacted', 'qualified', 'converted', 'closed']),
        new OA\Property(property: 'property', ref: '#/components/schemas/PropertyResource'),
        new OA\Property(property: 'compound', ref: '#/components/schemas/CompoundResource'),
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
            
            // Admin fields (only for admin routes)
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
