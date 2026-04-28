<?php

declare(strict_types=1);

namespace Modules\RealEstate\Entities;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Reminder Model
 *
 * Follow-up reminders created by agents for their assigned inquiries.
 *
 * @property int $id
 * @property int $agent_id
 * @property int $inquiry_id
 * @property \Carbon\Carbon $remind_at
 * @property string|null $message
 * @property bool $is_completed
 * @property \Carbon\Carbon|null $completed_at
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * @property-read \App\Models\User $agent
 * @property-read \Modules\RealEstate\Entities\PropertyInquiry $inquiry
 * @property-read bool $is_overdue
 * @property-read string $status_label
 *
 * @package Modules\RealEstate\Entities
 */
class Reminder extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     */
    protected $table = 're_reminders';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'agent_id',
        'inquiry_id',
        'remind_at',
        'message',
        'is_completed',
        'completed_at',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'remind_at'    => 'datetime',
        'completed_at' => 'datetime',
        'is_completed' => 'boolean',
    ];

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    /**
     * The agent who owns this reminder.
     */
    public function agent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'agent_id');
    }

    /**
     * The inquiry this reminder is attached to.
     */
    public function inquiry(): BelongsTo
    {
        return $this->belongsTo(PropertyInquiry::class, 'inquiry_id');
    }

    // -------------------------------------------------------------------------
    // Computed Attributes
    // -------------------------------------------------------------------------

    /**
     * Whether the reminder is past its due time and not yet completed.
     */
    public function getIsOverdueAttribute(): bool
    {
        return !$this->is_completed && $this->remind_at->isPast();
    }

    /**
     * Human-readable status label.
     */
    public function getStatusLabelAttribute(): string
    {
        if ($this->is_completed) {
            return 'completed';
        }

        if ($this->remind_at->isPast()) {
            return 'overdue';
        }

        return 'pending';
    }

    // -------------------------------------------------------------------------
    // Scopes
    // -------------------------------------------------------------------------

    /**
     * Only active (not completed) reminders.
     */
    public function scopeActive($query)
    {
        return $query->where('is_completed', false);
    }

    /**
     * Reminders that are due (remind_at <= now and not completed).
     * Used by the scheduler command.
     */
    public function scopeDue($query)
    {
        return $query->where('is_completed', false)
                     ->where('remind_at', '<=', Carbon::now());
    }

    /**
     * Reminders for a given agent.
     */
    public function scopeForAgent($query, int $agentId)
    {
        return $query->where('agent_id', $agentId);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Mark this reminder as completed.
     */
    public function markCompleted(): bool
    {
        return $this->update([
            'is_completed'  => true,
            'completed_at'  => Carbon::now(),
        ]);
    }

    /**
     * Snooze the reminder by the given offset.
     *
     * @param string $snooze  One of: '+1 hour', '+4 hours', '+1 day'
     */
    public function snooze(string $snooze): bool
    {
        $allowed = ['+1 hour', '+4 hours', '+1 day'];

        if (!in_array($snooze, $allowed, true)) {
            return false;
        }

        return $this->update([
            'remind_at' => Carbon::now()->modify($snooze),
        ]);
    }
}
