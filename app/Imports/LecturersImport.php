<?php

namespace App\Imports;

use App\Models\Lecturer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

/**
 * Imports many lecturers from one sheet.
 * Columns (heading row): name, email, faculty (optional), password (optional).
 */
class LecturersImport implements ToCollection, WithHeadingRow, SkipsEmptyRows
{
    public int $created = 0;

    /** @var array<int, array{row:int, matric:string, message:string}> */
    public array $failures = [];

    /** @var array<int, array{matric:string, name:string, password:string}> */
    public array $generated = [];

    public function collection($rows)
    {
        $seenEmail = [];

        foreach ($rows as $index => $row) {
            $line = $index + 2; // heading row is line 1
            $r = $row->map(fn ($v) => is_string($v) ? trim($v) : $v)->all();
            $email = strtolower((string) ($r['email'] ?? ''));

            $validator = Validator::make([
                'name' => $r['name'] ?? null,
                'email' => $email,
                'faculty' => $r['faculty'] ?? null,
            ], [
                'name' => 'required|string|max:255',
                'email' => 'required|email|max:255',
                'faculty' => 'nullable|max:255',
            ]);

            $plain = filled($r['password'] ?? null) ? (string) $r['password'] : null;
            $error = null;

            if ($validator->fails()) {
                $error = $validator->errors()->first();
            } elseif ($plain !== null && strlen($plain) < 6) {
                $error = 'Password must be at least 6 characters.';
            } elseif (isset($seenEmail[$email]) || $this->emailTaken($email)) {
                $error = 'Email is already used by another account.';
            }

            if ($error) {
                // "matric" key is reused so the shared results table can show the identifier (email here).
                $this->failures[] = ['row' => $line, 'matric' => $email, 'message' => $error];

                continue;
            }

            $seenEmail[$email] = true;
            $generated = $plain === null;
            $plain ??= Str::random(10);

            try {
                Lecturer::create([
                    'name' => $r['name'],
                    'email' => $email,
                    'password' => Hash::make($plain),
                    'faculty' => filled($r['faculty'] ?? null) ? $r['faculty'] : 'Unknown',
                ]);

                $this->created++;

                if ($generated) {
                    $this->generated[] = ['matric' => $email, 'name' => $r['name'], 'password' => $plain];
                }
            } catch (\Throwable $e) {
                $this->failures[] = ['row' => $line, 'matric' => $email, 'message' => 'Could not save this row.'];
            }
        }
    }

    protected function emailTaken(string $email): bool
    {
        foreach (['students', 'lecturers', 'admins', 'supervisors'] as $table) {
            if (Schema::hasTable($table) && DB::table($table)->whereRaw('lower(email) = ?', [$email])->exists()) {
                return true;
            }
        }

        return false;
    }
}
