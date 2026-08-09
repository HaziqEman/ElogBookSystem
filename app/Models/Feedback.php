<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Logbook;
use App\Models\Lecturer;

class Feedback extends Model
{
    use HasFactory;
    protected $primaryKey = 'feedback_id';
    protected $table = 'feedback';

    protected $fillable = [
        'logbook_id',
        'lecturer_id',
        'comment',
        'feedback_date'
    ];

    public function logbook()
    {
        return $this->belongsTo(
            Logbook::class,
            'logbook_id'
        );
    }

        public function lecturer()
    {
        return $this->belongsTo(
            Lecturer::class,
            'lecturer_id'
        );
    }
}
