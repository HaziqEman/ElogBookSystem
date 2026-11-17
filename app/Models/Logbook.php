<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Logbook extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'Pending';

    public const STATUS_APPROVED = 'Approved';

    public const STATUS_REJECTED = 'Rejected';

    public const SUPERVISOR_STATUS_PENDING = 'Pending';

    public const SUPERVISOR_STATUS_VALIDATED = 'Validated';

    public const SUPERVISOR_STATUS_REVISION_REQUESTED = 'Revision Requested';

    protected $primaryKey = 'logbook_id';

    protected $fillable = [
        'student_id',
        'week_no',
        'title',
        'description',
        'activity_date',
        'status',
        'supervisor_status',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(Attachment::class, 'logbook_id');
    }

    public function feedbacks(): HasMany
    {
        return $this->hasMany(Feedback::class, 'logbook_id');
    }

    /** Only the company supervisor reviews entries now. */
    public function needsRevision(): bool
    {
        return $this->supervisor_status === self::SUPERVISOR_STATUS_REVISION_REQUESTED;
    }

    /** An entry stays editable until the company supervisor validates it. */
    public function isEditableByStudent(): bool
    {
        return $this->supervisor_status !== self::SUPERVISOR_STATUS_VALIDATED;
    }
}
