<?php

namespace App\Models;

use Database\Factories\ComplaintAttachmentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ComplaintAttachment extends Model
{
    /** @use HasFactory<ComplaintAttachmentFactory> */
    use HasFactory;

    protected $fillable = [
        'complaint_id',
        'user_id',
        'disk',
        'path',
        'original_name',
        'mime_type',
        'size',
    ];

    public function complaint(): BelongsTo
    {
        return $this->belongsTo(Complaint::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
