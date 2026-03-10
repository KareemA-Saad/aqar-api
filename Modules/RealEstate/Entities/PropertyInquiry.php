<?php

declare(strict_types=1);

namespace Modules\RealEstate\Entities;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * PropertyInquiry Model
 *
 * Represents lead/inquiry submissions for properties.
 *
 * @package Modules\RealEstate\Entities
 *
 * @property int $id
 * @property int|null $property_id
 * @property int|null $compound_id
 * @property int|null $agent_id
 * @property int|null $user_id
 * @property string $name
 * @property string $email
 * @property string $phone
 * @property string|null $message
 * @property string $status (new, contacted, qualified, converted, closed)
 * @property string|null $admin_notes
 * @property string|null $source
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property string|null $referrer_url
 * @property \DateTime|null $contacted_at
 * @property \DateTime|null $qualified_at
 * @property \DateTime|null $converted_at
 * @property \DateTime|null $closed_at
 */
class PropertyInquiry extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    /**
     * The table associated with the model.
     */
    protected $table = 're_property_inquiries';

    // ==================== ACTIVITY LOG ====================

    /**
     * Configure Spatie Activitylog for this model.
     *
     * Logged events:
     *  - status changes  (status_changed)
     *  - agent assignment (agent_assigned)
     *  - contacted_at update (contacted)
     *
     * We use a named log 'inquiry' so we can query it separately.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['status', 'agent_id', 'contacted_at'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('inquiry');
    }

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'property_id',
        'compound_id',
        'agent_id',
        'user_id',
        'name',
        'email',
        'phone',
        'message',
        'status',
        'admin_notes',
        'source',
        'ip_address',
        'user_agent',
        'referrer_url',
        'contacted_at',
        'qualified_at',
        'converted_at',
        'closed_at',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'contacted_at' => 'datetime',
        'qualified_at' => 'datetime',
        'converted_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    /**
     * Status constants.
     */
    public const STATUS_NEW = 'new';
    public const STATUS_CONTACTED = 'contacted';
    public const STATUS_QUALIFIED = 'qualified';
    public const STATUS_CONVERTED = 'converted';
    public const STATUS_CLOSED = 'closed';

    /**
     * Available statuses.
     */
    public static array $statuses = [
        self::STATUS_NEW,
        self::STATUS_CONTACTED,
        self::STATUS_QUALIFIED,
        self::STATUS_CONVERTED,
        self::STATUS_CLOSED,
    ];

    // ==================== RELATIONSHIPS ====================

    /**
     * Get the property this inquiry is for.
     */
    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class, 'property_id');
    }

    /**
     * Get the compound this inquiry is for (for direct compound inquiries).
     */
    public function compound(): BelongsTo
    {
        return $this->belongsTo(Compound::class, 'compound_id');
    }

    /**
     * Get the user who submitted this inquiry (if authenticated).
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the assigned agent.
     */
    public function agent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'agent_id');
    }

    /**
     * Get the follow-up reminders for this inquiry.
     */
    public function reminders(): HasMany
    {
        return $this->hasMany(Reminder::class, 'inquiry_id');
    }

    /**
     * Get structured notes written by agents on this inquiry.
     */
    public function notes(): HasMany
    {
        return $this->hasMany(InquiryNote::class, 'inquiry_id');
    }

    // ==================== SCOPES ====================

    /**
     * Scope by status.
     */
    public function scopeStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope for new inquiries.
     */
    public function scopeNew($query)
    {
        return $query->where('status', self::STATUS_NEW);
    }

    /**
     * Scope for contacted inquiries.
     */
    public function scopeContacted($query)
    {
        return $query->where('status', self::STATUS_CONTACTED);
    }

    /**
     * Scope for qualified inquiries.
     */
    public function scopeQualified($query)
    {
        return $query->where('status', self::STATUS_QUALIFIED);
    }

    /**
     * Scope for converted inquiries.
     */
    public function scopeConverted($query)
    {
        return $query->where('status', self::STATUS_CONVERTED);
    }

    /**
     * Scope by agent.
     */
    public function scopeAssignedTo($query, int $agentId)
    {
        return $query->where('agent_id', $agentId);
    }

    /**
     * Scope by property.
     */
    public function scopeForProperty($query, int $propertyId)
    {
        return $query->where('property_id', $propertyId);
    }

    /**
     * Scope by compound.
     */
    public function scopeForCompound($query, int $compoundId)
    {
        return $query->where('compound_id', $compoundId);
    }

    /**
     * Scope unassigned.
     */
    public function scopeUnassigned($query)
    {
        return $query->whereNull('agent_id');
    }

    /**
     * Scope recent first.
     */
    public function scopeRecent($query)
    {
        return $query->orderBy('created_at', 'desc');
    }

    // ==================== LEAD SCORING ====================

    /**
     * Computed lead score 0–100.
     *
     * Signals (no stored value; computed fresh per request):
     *
     *  +20  Phone/mobile provided              — shows real intent
     *  +15  Message is detailed (> 50 chars)   — engaged, not just a click
     *  +15  Inquiry is from today              — hot recency
     *  + 8  Inquiry is from yesterday          — warm recency
     *  +15  User has verified email            — authenticated, lower ghost risk
     *  +15  User has mobile on file            — contactable
     *  +12  User made ≤ 3 previous inquiries   — focused buyer, not spam
     *
     * Total possible: 100 (capped).
     */
    public function getLeadScoreAttribute(): int
    {
        $score = 0;

        // Phone provided
        if (!empty($this->phone)) {
            $score += 20;
        }

        // Message detail
        if (mb_strlen($this->message ?? '') > 50) {
            $score += 15;
        }

        // Recency
        if ($this->created_at?->isToday()) {
            $score += 15;
        } elseif ($this->created_at?->isYesterday()) {
            $score += 8;
        }

        // Authenticated user quality signals
        $user = $this->relationLoaded('user') ? $this->user : null;

        if ($user) {
            // Verified email
            if ($user->email_verified) {
                $score += 15;
            }

            // Mobile on file
            if (!empty($user->mobile)) {
                $score += 15;
            }
        }

        // Focused buyer — count inquiries from this email in this tenant DB.
        // Works for both guests and authenticated users without cross-DB joins.
        $emailCount = static::where('email', $this->email)->count();
        if ($emailCount <= 3) {
            $score += 12;
        }

        return min($score, 100);
    }

    /**
     * Lead temperature bucket derived from lead_score.
     *
     * hot  ≥ 70   → needs immediate follow-up
     * warm 40–69  → engaged, worth nurturing
     * cold < 40   → low intent or unverified
     */
    public function getLeadTemperatureAttribute(): string
    {
        $score = $this->lead_score;

        if ($score >= 70) {
            return 'hot';
        }

        if ($score >= 40) {
            return 'warm';
        }

        return 'cold';
    }

    // ==================== SLA ====================

    /**
     * SLA threshold constants (hours).
     * Override per-tenant via config('realestate.sla_thresholds').
     */
    public const SLA_URGENT_HOURS  = 4;   // on_track → warning
    public const SLA_WARNING_HOURS = 24;  // warning → breached
    public const SLA_BREACH_HOURS  = 48;  // hard breach

    /**
     * Computed SLA status for this inquiry.
     *
     * on_track  — responded within 4 h, or created < 4 h ago
     * warning   — 4–24 h without contact
     * breached  — > 24 h without contact
     *
     * Once the inquiry is contacted/qualified/converted/closed, we return
     * the historical status at the time of first contact so the timeline
     * still makes sense.
     *
     * @return string  'on_track' | 'warning' | 'breached'
     */
    public function getSlaStatusAttribute(): string
    {
        $thresholds = config('realestate.sla_thresholds', [
            'urgent'  => self::SLA_URGENT_HOURS,
            'warning' => self::SLA_WARNING_HOURS,
            'breach'  => self::SLA_BREACH_HOURS,
        ]);

        // If already contacted, measure time-to-first-contact
        $referenceTime = $this->contacted_at ?? Carbon::now();
        $hoursElapsed  = $this->created_at->diffInHours($referenceTime);

        if ($hoursElapsed < $thresholds['urgent']) {
            return 'on_track';
        }

        if ($hoursElapsed < $thresholds['warning']) {
            return 'warning';
        }

        return 'breached';
    }

    /**
     * Hours elapsed since inquiry creation (float, 2dp).
     */
    public function getSlaHoursElapsedAttribute(): float
    {
        $referenceTime = $this->contacted_at ?? Carbon::now();
        return round($this->created_at->diffInMinutes($referenceTime) / 60, 2);
    }

    /**
     * Hours remaining before SLA warning threshold (negative = already past).
     */
    public function getSlaHoursRemainingAttribute(): float
    {
        if ($this->contacted_at) {
            return 0.0; // already contacted — SLA is closed
        }

        $thresholds = config('realestate.sla_thresholds', [
            'urgent'  => self::SLA_URGENT_HOURS,
            'warning' => self::SLA_WARNING_HOURS,
            'breach'  => self::SLA_BREACH_HOURS,
        ]);

        $warningHours  = $thresholds['warning'];
        $hoursElapsed  = $this->created_at->diffInMinutes(Carbon::now()) / 60;
        return round($warningHours - $hoursElapsed, 2);
    }

    // ==================== ACCESSORS ====================

    /**
     * Check if inquiry is new.
     */
    public function getIsNewAttribute(): bool
    {
        return $this->status === self::STATUS_NEW;
    }

    /**
     * Check if inquiry is converted.
     */
    public function getIsConvertedAttribute(): bool
    {
        return $this->status === self::STATUS_CONVERTED;
    }

    /**
     * Get status label.
     */
    public function getStatusLabelAttribute(): string
    {
        return ucfirst($this->status);
    }

    // ==================== METHODS ====================

    /**
     * Mark as contacted.
     */
    public function markAsContacted(?int $agentId = null): void
    {
        $this->update([
            'status' => self::STATUS_CONTACTED,
            'contacted_at' => now(),
            'agent_id' => $agentId ?? $this->agent_id,
        ]);
    }

    /**
     * Mark as qualified.
     */
    public function markAsQualified(): void
    {
        $this->update([
            'status' => self::STATUS_QUALIFIED,
            'qualified_at' => now(),
        ]);
    }

    /**
     * Mark as converted.
     */
    public function markAsConverted(): void
    {
        $this->update([
            'status' => self::STATUS_CONVERTED,
            'converted_at' => now(),
        ]);
    }

    /**
     * Mark as closed.
     */
    public function markAsClosed(): void
    {
        $this->update([
            'status' => self::STATUS_CLOSED,
            'closed_at' => now(),
        ]);
    }

    /**
     * Assign to agent.
     */
    public function assignToAgent(int $agentId): void
    {
        $this->update(['agent_id' => $agentId]);
    }

    /**
     * Add admin notes.
     */
    public function addNotes(string $notes): void
    {
        $existingNotes = $this->admin_notes ? $this->admin_notes . "\n" : '';
        $this->update([
            'admin_notes' => $existingNotes . '[' . now()->format('Y-m-d H:i') . '] ' . $notes,
        ]);
    }

    /**
     * Get statistics.
     */
    public static function getStatistics(): array
    {
        return [
            'total' => self::count(),
            'new' => self::new()->count(),
            'contacted' => self::contacted()->count(),
            'qualified' => self::qualified()->count(),
            'converted' => self::converted()->count(),
            'closed' => self::status(self::STATUS_CLOSED)->count(),
            'unassigned' => self::unassigned()->count(),
        ];
    }
}
