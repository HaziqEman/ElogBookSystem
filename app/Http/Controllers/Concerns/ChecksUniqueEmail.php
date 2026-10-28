<?php

namespace App\Http\Controllers\Concerns;

use Closure;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

trait ChecksUniqueEmail
{
    /**
     * Login checks student, lecturer, admin and supervisor in turn and stops at the first match,
     * so the same email must never exist in two of these tables.
     *
     * When editing a record, pass its table, primary key column and id so it can keep its own email.
     */
    protected function uniqueEmailRule(?string $ignoreTable = null, ?string $ignoreKey = null, $ignoreId = null): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail) use ($ignoreTable, $ignoreKey, $ignoreId) {
            $email = strtolower(trim((string) $value));

            foreach (['students', 'lecturers', 'admins', 'supervisors'] as $table) {
                if (! Schema::hasTable($table)) {
                    continue;
                }

                $query = DB::table($table)->whereRaw('lower(email) = ?', [$email]);

                if ($ignoreTable === $table && $ignoreKey && $ignoreId) {
                    $query->where($ignoreKey, '!=', $ignoreId);
                }

                if ($query->exists()) {
                    $fail('This email is already used by another account.');

                    return;
                }
            }
        };
    }
}
