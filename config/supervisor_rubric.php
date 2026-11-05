<?php

/*
 * Company supervisor marks rubric.
 *
 * CHANGE THIS FILE to match the evaluation form your faculty gave you.
 * - Each criterion is scored from 1 up to its "max" (default 5).
 * - "weight" is the percentage this evaluation is worth in the final grade.
 * - The final mark = (marks obtained / total possible marks) x weight.
 * - After editing, run: php artisan config:clear
 */

return [
    'title' => 'Industrial Training Supervisor Evaluation',

    // This evaluation is worth this many percent of the student's total.
    'weight' => 40,

    // false = the supervisor can submit only once and cannot change the marks afterwards.
    'allow_edit' => true,

    // Shown above the marks.
    'scale_hint' => '1 = Very Poor, 2 = Poor, 3 = Satisfactory, 4 = Good, 5 = Excellent',

    'sections' => [
        'work' => [
            'title' => 'A. Work performance',
            'criteria' => [
                'cooperation'    => ['label' => 'Co-operation', 'help' => 'Works well with colleagues and follows instructions.'],
                'punctuality'    => ['label' => 'Punctuality and attendance', 'help' => 'Comes on time and is present on working days.'],
                'initiative'     => ['label' => 'Initiative', 'help' => 'Starts tasks without being told and looks for ways to help.'],
                'responsibility' => ['label' => 'Responsibility', 'help' => 'Finishes tasks on time and owns their mistakes.'],
                'quality'        => ['label' => 'Quality of work', 'help' => 'Work is accurate, neat and complete.'],
            ],
        ],
        'skills' => [
            'title' => 'B. Skills and knowledge',
            'criteria' => [
                'technical'     => ['label' => 'Technical knowledge and skills', 'help' => 'Applies what they learned to the real tasks.'],
                'learning'      => ['label' => 'Ability to learn', 'help' => 'Picks up new tools and methods quickly.'],
                'problem'       => ['label' => 'Problem solving', 'help' => 'Finds solutions to problems at work.'],
                'communication' => ['label' => 'Communication skills', 'help' => 'Speaks and writes clearly, asks when unsure.'],
            ],
        ],
        'professional' => [
            'title' => 'C. Professional conduct',
            'criteria' => [
                'ethics'   => ['label' => 'Professional ethics and discipline', 'help' => 'Follows company rules and keeps information confidential.'],
                'teamwork' => ['label' => 'Teamwork', 'help' => 'Supports the team and shares the workload.'],
            ],
        ],
    ],
];
