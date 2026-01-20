<?php

declare(strict_types=1);

namespace Modules\RealEstate\Entities;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * PropertyInquiry Model
 *
 * Represents lead/inquiry submissions for properties.
 *
 * @package Modules\RealEstate\Entities
 *
 * @property int $id
 * @property int $property_id
 * @property int|null $agent_id
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
    use HasFactory, SoftDeletes;

    /**
     * The table associated with the model.
     */
    protected $table = 're_property_inquiries';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'property_id',
        'agent_id',
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
     * Get the assigned agent.
     */
    public function agent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'agent_id');
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
