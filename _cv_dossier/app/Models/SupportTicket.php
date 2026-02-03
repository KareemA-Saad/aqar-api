<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SupportTicket extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'support_tickets';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'title',
        'via',
        'operating_system',
        'user_agent',
        'description',
        'subject',
        'status',
        'priority',
        'user_id',
        'admin_id',
        'department_id',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'status' => 'integer', // 0 = open, 1 = closed, 2 = pending
        'priority' => 'integer', // 0 = low, 1 = medium, 2 = high, 3 = urgent
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Priority labels.
     */
    public const PRIORITIES = [
        0 => 'low',
        1 => 'medium',
        2 => 'high',
        3 => 'urgent',
    ];

    /**
     * Status labels.
     */
    public const STATUSES = [
        0 => 'open',
        1 => 'closed',
        2 => 'pending',
    ];

    /**
     * Get the user who created this ticket.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    /**
     * Get the admin assigned to this ticket.
     */
    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'admin_id', 'id');
    }

    /**
     * Get the department for this ticket.
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(SupportDepartment::class, 'department_id', 'id');
    }

    /**
     * Get all messages for this ticket.
     */
    public function messages(): HasMany
    {
        return $this->hasMany(SupportTicketMessage::class, 'support_ticket_id', 'id');
    }

    /**
     * Get the priority label.
     */
    public function getPriorityLabelAttribute(): string
    {
        return self::PRIORITIES[$this->priority] ?? 'unknown';
    }

    /**
     * Get the status label.
     */
    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? 'unknown';
    }
}

