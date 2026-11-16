<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class University extends Model
{
    protected $primaryKey = 'university_id';

    protected $fillable = [
        'name',
        'logo_url',
        'logo_public_id',
        'is_verified',
        'created_by_student_id',
    ];

    protected $casts = [
        'is_verified' => 'boolean',
    ];

    public function students()
    {
        return $this->hasMany(Student::class, 'university_id', 'university_id');
    }

    public function creator()
    {
        return $this->belongsTo(Student::class, 'created_by_student_id', 'student_id');
    }

    /** Verified universities, plus any unverified one this student added themselves. */
    public function scopeVisibleTo($query, int $studentId)
    {
        return $query->where(function ($where) use ($studentId) {
            $where->where('is_verified', true)
                ->orWhere('created_by_student_id', $studentId);
        });
    }

    /** Trim and collapse repeated spaces so "UiTM  Shah Alam " and "UiTM Shah Alam" match. */
    public static function normalizeName(string $name): string
    {
        return trim((string) preg_replace('/\s+/u', ' ', $name));
    }

    /** Case-insensitive lookup (works on MySQL and PostgreSQL). */
    public static function findByName(string $name, ?int $ignoreId = null): ?self
    {
        $query = static::whereRaw('lower(name) = ?', [mb_strtolower(static::normalizeName($name))]);

        if ($ignoreId !== null) {
            $query->where('university_id', '!=', $ignoreId);
        }

        return $query->first();
    }
}
