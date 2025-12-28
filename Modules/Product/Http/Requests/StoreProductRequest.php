<?php

namespace Modules\Product\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductRequest extends FormRequest
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
        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:products,slug'],
            'summary' => ['nullable', 'string', 'max:1000'],
            'description' => ['nullable', 'string'],
            'brand_id' => ['nullable', 'integer', 'exists:brands,id'],
            'status_id' => ['nullable', 'integer'],
            'cost' => ['nullable', 'numeric', 'min:0'],
            'price' => ['required', 'numeric', 'min:0'],
            'sale_price' => ['nullable', 'numeric', 'min:0', 'lt:price'],
            'image_id' => ['nullable', 'integer'],
            'badge_id' => ['nullable', 'integer', 'exists:badges,id'],
            'min_purchase' => ['nullable', 'integer', 'min:1'],
            'max_purchase' => ['nullable', 'integer', 'min:1', 'gte:min_purchase'],
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

            // Variants
            'variants' => ['nullable', 'array'],
            'variants.*.color_id' => ['nullable', 'integer', 'exists:colors,id'],
            'variants.*.size_id' => ['nullable', 'integer', 'exists:sizes,id'],
            'variants.*.additional_price' => ['nullable', 'numeric', 'min:0'],
            'variants.*.add_cost' => ['nullable', 'numeric', 'min:0'],
            'variants.*.image_id' => ['nullable', 'integer'],
            'variants.*.stock_count' => ['nullable', 'integer', 'min:0'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.required' => __('Product name is required'),
            'price.required' => __('Product price is required'),
            'price.min' => __('Price cannot be negative'),
            'sale_price.lt' => __('Sale price must be less than regular price'),
            'max_purchase.gte' => __('Max purchase must be greater than or equal to min purchase'),
        ];
    }
}
