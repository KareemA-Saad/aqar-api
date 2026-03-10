<?php

declare(strict_types=1);

namespace Modules\RealEstate\Entities;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Viewing Model — F2.5 Viewing Scheduler
 *
 * Represents a property or compound viewing appointment booked by a user or admin.
 * Agents confirm/decline/complete viewings.
 *
 * @property int         $id
 * @property int|null    $property_id
 * @property int|null    $compound_id
 * @property int|null    $agent_id
 * @property int|null    $user_id
 * @property string      $contact_name
 * @property string|null $contact_phone
 * @property string|null $contact_email
 * @property Carbon      $scheduled_at
 * @property string      $status     pending|confirmed|declined|rescheduled|completed|no_show
 * @property string|null $notes
 * @property string|null $result_notes
 * @property int         $reschedule_count
 * @property Carbon|null $rescheduled_to
 * @property Carbon      $created_at
 * @property Carbon      $updated_at
 * @property Carbon|null $deleted_at
 */
class Viewing extends Model
{
    use HasFactory, SoftDeletes;

    // -------------------------------------------------------------------------
    // Status constants
    // -------------------------------------------------------------------------
    public const STATUS_PENDING    = 'pending';
    public const STATUS_CONFIRMED  = 'confirmed';
    public const STATUS_DECLINED   = 'declined';
    public const STATUS_RESCHEDULED = 'rescheduled';
    public const STATUS_COMPLETED  = 'completed';
    public const STATUS_NO_SHOW    = 'no_show';

    public const STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_CONFIRMED,
        self::STATUS_DECLINED,
        self::STATUS_RESCHEDULED,
        self::STATUS_COMPLETED,
        self::STATUS_NO_SHOW,
    ];

    protected $table = 're_viewings';

    protected $fillable = [
        'property_id',
        'compound_id',
        'agent_id',
        'user_id',
        'contact_name',
        'contact_phone',
        'contact_email',
        'scheduled_at',
        'status',
        'notes',
        'result_notes',
        'reschedule_count',
        'rescheduled_to',
    ];

    protected $casts = [
        'scheduled_at'    => 'datetime',
        'rescheduled_to'  => 'datetime',
        'reschedule_count' => 'integer',
    ];

    // -------------------------------------------------------------------------
    // Relations
    // -------------------------------------------------------------------------

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class, 'property_id');
    }

    public function compound(): BelongsTo
    {
        return $this->belongsTo(Compound::class, 'compound_id');
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'agent_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // -------------------------------------------------------------------------
    // Scopes
    // -------------------------------------------------------------------------

    public function scopeForAgent(Builder $query, int $agentId): Builder
    {
        return $query->where('agent_id', $agentId);
    }

    public function scopeToday(Builder $query): Builder
    {
        return $query->whereDate('scheduled_at', Carbon::today());
    }

    public function scopeUpcoming(Builder $query): Builder
    {
        return $query->where('scheduled_at', '>=', Carbon::now());
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeConfirmed(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_CONFIRMED);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereIn('status', [self::STATUS_PENDING, self::STATUS_CONFIRMED]);
    }
}
