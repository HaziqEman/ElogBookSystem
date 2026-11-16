<?php

namespace App\Services;

class LocalDraftBuilder
{
    /**
     * @param  array{role?: string, company?: string, notes?: string, week?: int|string|null, date?: \Carbon\Carbon|null}  $context
     */
    public function build(array $context): string
    {
        $date = $context['date'] ?? null;
        $week = $context['week'] ?? null;
        $role = trim((string) ($context['role'] ?? ''));
        $company = trim((string) ($context['company'] ?? ''));
        $notes = trim((string) ($context['notes'] ?? ''));

        $intro = $date ? 'On '.$date->format('l, d F Y') : 'Today';

        if ($week) {
            $intro .= " (Week {$week})";
        }

        if ($role !== '') {
            $article = preg_match('/^[aeiou]/i', $role) ? 'an' : 'a';
            $opening = "{$intro}, I carried out my internship duties as {$article} {$role}";
        } else {
            $opening = "{$intro}, I carried out my internship duties";
        }

        $opening .= ($company !== '' ? " at {$company}" : '').'.';

        $tasks = $this->tasks($notes);

        if (! $tasks) {
            return $opening.' I recorded my activities for the day in this logbook.';
        }

        if (count($tasks) === 1) {
            $work = 'The work for the day was: '.$tasks[0].'.';
        } else {
            $last = array_pop($tasks);
            $work = 'The work for the day included: '.implode('; ', $tasks).'; and '.$last.'.';
        }

        return $opening.' '.$work.' I have recorded these activities as part of my daily internship logbook.';
    }

    /** Split the student's notes into short tasks. Their own words are kept as they are. */
    protected function tasks(string $notes): array
    {
        if ($notes === '') {
            return [];
        }

        $pieces = preg_split('/[\r\n;•]+|(?<=[.!?])\s+/u', $notes) ?: [];
        $tasks = [];

        foreach ($pieces as $piece) {
            $piece = trim($piece);
            $piece = preg_replace('/^[\-\*\d\.\)\s]+/u', '', $piece) ?? $piece;
            $piece = rtrim(trim($piece), ".,;:!");

            if ($piece === '') {
                continue;
            }

            // "Fixed the bug" becomes "fixed the bug" inside a sentence, but acronyms like "API" stay.
            if (preg_match('/^\p{Lu}\p{Ll}/u', $piece)) {
                $piece = mb_strtolower(mb_substr($piece, 0, 1)).mb_substr($piece, 1);
            }

            $tasks[] = $piece;
        }

        return array_slice($tasks, 0, 6);
    }
}