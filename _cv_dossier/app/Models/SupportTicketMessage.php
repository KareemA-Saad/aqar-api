<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupportTicketMessage extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'support_ticket_messages';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'message',
        'notify',
        'attachment',
        'support_ticket_id',
        'type',
        'user_id',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'notify' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the support ticket this message belongs to.
     */
    public function supportTicket(): BelongsTo
    {
        return $this->belongsTo(SupportTicket::class, 'support_ticket_id', 'id');
    }

    /**
     * Get the user who sent this message.
     * Returns Admin if type is 'admin', otherwise returns User.
     */
    public function sender(): BelongsTo
    {
        if ($this->type === 'admin') {
            return $this->belongsTo(Admin::class, 'user_id', 'id');
        }

        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    /**
     * Get the user (for user type messages).
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    /**
     * Get the admin (for admin type messages).
     */
    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'user_id', 'id');
    }

    /**
     * Check if message is from admin.
     */
    public function isFromAdmin(): bool
    {
        return $this->type === 'admin';
    }
}

