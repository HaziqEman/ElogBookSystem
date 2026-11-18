<?php

namespace App\View;

use Illuminate\Http\Request;

final class LayoutNavigation
{
    /**
     * @return list<array{href: string, icon: string, label: string, match: list<string>}>
     */
    public static function itemsFor(Request $request): array
    {
        if ($request->is('student*')) {
            return [
                [
                    'href' => '/student/dashboard',
                    'icon' => 'fa-house',
                    'label' => 'Dashboard',
                    'match' => ['student/dashboard'],
                ],
                [
                    'href' => '/student/logbooks',
                    'icon' => 'fa-book',
                    'label' => 'Logbook',
                    'match' => ['student/logbooks', 'student/logbook/*'],
                ],
                [
                    'href' => '/student/calendar',
                    'icon' => 'fa-calendar-days',
                    'label' => 'Calendar',
                    'match' => ['student/calendar'],
                ],
                [
                    'href' => '/student/attendance',
                    'icon' => 'fa-user-clock',
                    'label' => 'Attendance',
                    'match' => ['student/attendance', 'student/attendance/*'],
                ],
                [
                    'href' => '/student/messages',
                    'icon' => 'fa-comments',
                    'label' => 'Messages',
                    'match' => ['student/messages', 'student/messages/*'],
                ],
            ];
        }

        if ($request->is('lecturer*')) {
            return [
                [
                    'href' => '/lecturer/dashboard',
                    'icon' => 'fa-gauge-high',
                    'label' => 'Dashboard',
                    'match' => [
                        'lecturer/dashboard',
                        'lecturer/students/*',
                        'lecturer/logbook',
                        'lecturer/logbook/*',
                    ],
                ],
                [
                    'href' => '/lecturer/messages',
                    'icon' => 'fa-comments',
                    'label' => 'Messages',
                    'match' => ['lecturer/messages', 'lecturer/messages/*'],
                ],
                [
                    'href' => '/lecturer/evaluations',
                    'icon' => 'fa-clipboard-check',
                    'label' => 'Supervisor Marks',
                    'match' => ['lecturer/evaluations', 'lecturer/evaluations/*'],
                ],
            ];
        }

        if ($request->is('supervisor*')) {
            return [
                [
                    'href' => '/supervisor/dashboard',
                    'icon' => 'fa-gauge-high',
                    'label' => 'Supervisor Dashboard',
                    'match' => [
                        'supervisor/dashboard',
                        'supervisor/logbook',
                        'supervisor/logbook/*',
                        'supervisor/students/*',
                        'supervisor/weeks/*',
                    ],
                ],
                [
                    'href' => '/supervisor/tasks',
                    'icon' => 'fa-list-check',
                    'label' => 'Tasks',
                    'match' => ['supervisor/tasks', 'supervisor/tasks/*'],
                ],
                [
                    'href' => '/supervisor/messages',
                    'icon' => 'fa-comments',
                    'label' => 'Messages',
                    'match' => ['supervisor/messages', 'supervisor/messages/*'],
                ],
                [
                    'href' => '/supervisor/password',
                    'icon' => 'fa-key',
                    'label' => 'Change Password',
                    'match' => ['supervisor/password'],
                ],
            ];
        }

        return [
            [
                'href' => '/admin/dashboard',
                'icon' => 'fa-sliders',
                'label' => 'System Overview',
                'match' => ['admin/dashboard'],
            ],
            [
                'href' => '/admin/students',
                'icon' => 'fa-file-invoice',
                'label' => 'Manage Students',
                'match' => ['admin/students', 'admin/students/*'],
            ],
            [
                'href' => '/admin/lecturers',
                'icon' => 'fa-user-tie',
                'label' => 'Manage Lecturers',
                'match' => ['admin/lecturers', 'admin/lecturers/*'],
            ],
            [
                'href' => '/admin/supervisors',
                'icon' => 'fa-building-user',
                'label' => 'Manage Supervisors',
                'match' => ['admin/supervisors', 'admin/supervisors/*'],
            ],
            [
                'href' => '/admin/reports',
                'icon' => 'fa-chart-line',
                'label' => 'Reports',
                'match' => ['admin/reports', 'admin/reports/*'],
            ],
            [
                'href' => '/admin/universities',
                'icon' => 'fa-building-columns',
                'label' => 'Universities',
                'match' => ['admin/universities', 'admin/universities/*'],
            ],
            [
                'href' => '/admin/evaluations',
                'icon' => 'fa-clipboard-check',
                'label' => 'Supervisor Marks',
                'match' => ['admin/evaluations', 'admin/evaluations/*'],
            ],
            [
                'href' => '/admin/supervisor-rubric',
                'icon' => 'fa-list-check',
                'label' => 'Supervisor Rubric',
                'match' => ['admin/supervisor-rubric', 'admin/supervisor-rubric/*'],
            ],
        ];
    }
}
