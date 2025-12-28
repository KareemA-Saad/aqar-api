<?php

namespace Modules\Product\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProductRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $productId = $this->route('product') ?? $this->route('id');

        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('products', 'slug')->ignore($productId)],
            'summary' => ['nullable', 'string', 'max:1000'],
            'description' => ['nullable', 'string'],
            'brand_id' => ['nullable', 'integer', 'exists:brands,id'],
            'status_id' => ['nullable', 'integer'],
            'cost' => ['nullable', 'numeric', 'min:0'],
            'price' => ['sometimes', 'numeric', 'min:0'],
            'sale_price' => ['nullable', 'numeric', 'min:0'],
            'image_id' => ['nullable', 'integer'],
            'badge_id' => ['nullable', 'integer', 'exists:badges,id'],
            'min_purchase' => ['nullable', 'integer', 'min:1'],
            'max_purchase' => ['nullable', 'integer', 'min:1'],
            'is_refundable' => ['nullable', 'boolean'],
            'is_inventory_warn_able' => ['nullable', 'boolean'],
            'is_in_house' => ['nullable', 'boolean'],

            // Category relationships
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'sub_category_id' => ['nullable', 'integer', 'exists:sub_categories,id'],
            'child_category_ids' => ['nullable', 'array'],
            'child_category_ids.*' => ['integer', 'exists:child_categories,id'],

            // Inventory
            'sku' => ['nullable', 'string', 'max:50'],
            'stock_count' => ['nullable', 'integer', 'min:0'],

            // Gallery
            'gallery_images' => ['nullable', 'array'],
            'gallery_images.*' => ['integer'],

            // Tags
            'tags' => ['nullable', 'array'],
            'tags.*' => ['string', 'max:100'],

            // UOM
            'uom_id' => ['nullable', 'integer', 'exists:units,id'],
            'uom_quantity' => ['nullable', 'numeric', 'min:0'],

            // Delivery options
            'delivery_option_ids' => ['nullable', 'array'],
            'delivery_option_ids.*' => ['integer', 'exists:delivery_options,id'],

            // Return policy
            'return_policy' => ['nullable', 'array'],
            'return_policy.return_days' => ['nullable', 'integer', 'min:0'],
            'return_policy.policy' => ['nullable', 'string'],
            'return_policy.description' => ['nullable', 'string'],

            // Meta info
            'meta' => ['nullable', 'array'],
            'meta.title' => ['nullable', 'string', 'max:255'],
            'meta.description' => ['nullable', 'string', 'max:500'],
            'meta.keywords' => ['nullable', 'string', 'max:255'],
            'meta.og_image' => ['nullable', 'integer'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'price.min' => __('Price cannot be negative'),
            'sale_price.lt' => __('Sale price must be less than regular price'),
        ];
    }
}
