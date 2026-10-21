<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Logbook extends Model
{
    use HasFactory;

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

    public function student()
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    public function attachments()
    {
        return $this->hasMany(Attachment::class, 'logbook_id');
    }

    public function feedbacks()
    {
        return $this->hasMany(Feedback::class, 'logbook_id');
    }

    /** Only the company supervisor reviews entries now. */
    public function needsRevision(): bool
    {
        return $this->supervisor_status === 'Revision Requested';
    }

    /** An entry stays editable until the company supervisor validates it. */
    public function isEditableByStudent(): bool
    {
        return $this->supervisor_status !== 'Validated';
    }
}