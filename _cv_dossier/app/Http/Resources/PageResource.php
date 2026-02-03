<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\Page
 */
class PageResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'page_content' => $this->page_content,
            'visibility' => $this->visibility,
            'page_builder' => $this->page_builder,
            'status' => $this->status,
            'breadcrumb' => $this->breadcrumb,
            'navbar_variant' => $this->navbar_variant,
            'footer_variant' => $this->footer_variant,
            'meta_info' => new MetaInfoResource($this->whenLoaded('metaInfo')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}

