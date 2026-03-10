<?php

declare(strict_types=1);

namespace Modules\RealEstate\Entities;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * InquiryNote Model
 *
 * A structured note written by an agent on a PropertyInquiry.
 * Replaces the fragile admin_notes text-append pattern.
 *
 * @property int          $id
 * @property int          $inquiry_id
 * @property int          $agent_id
 * @property string       $content       Max 2000 chars (enforced in service)
 * @property bool         $is_internal   Internal notes hidden from non-agents
 * @property \Carbon\Carbon|null $deleted_at
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * @property-read \Modules\RealEstate\Entities\PropertyInquiry $inquiry
 * @property-read \App\Models\User $agent
 *
 * @package Modules\RealEstate\Entities
 */
class InquiryNote extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 're_inquiry_notes';

    protected $fillable = [
        'inquiry_id',
        'agent_id',
        'content',
        'is_internal',
    ];

    protected $casts = [
        'is_internal' => 'boolean',
    ];

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    public function inquiry(): BelongsTo
    {
        return $this->belongsTo(PropertyInquiry::class, 'inquiry_id');
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'agent_id');
    }

    // -------------------------------------------------------------------------
    // Scopes
    // -------------------------------------------------------------------------

    /**
     * Exclude internal notes when the caller is not an agent/admin.
     */
    public function scopeVisible($query)
    {
        return $query->where('is_internal', false);
    }

    /**
     * Notes for a specific inquiry, including soft-deleted ones (for timeline).
     */
    public function scopeForInquiry($query, int $inquiryId)
    {
        return $query->where('inquiry_id', $inquiryId);
    }
}
