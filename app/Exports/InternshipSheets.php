<?php

namespace App\Exports;

/**
 * Builds each table sheet from the data returned by InternshipReportService::build().
 * Used by the Excel dashboard (all sheets) and by the CSV downloads (one sheet).
 */
class InternshipSheets
{
    public const TYPES = ['progress', 'marks', 'weekly', 'attention', 'lecturers', 'supervisors', 'companies'];

    public static function make(string $type, array $report, bool $csv = false): ReportArraySheet
    {
        return match ($type) {
            'progress' => self::progress($report, $csv),
            'marks' => self::marks($report, $csv),
            'weekly' => self::weekly($report, $csv),
            'attention' => self::attention($report, $csv),
            'lecturers' => self::lecturers($report, $csv),
            'supervisors' => self::supervisors($report, $csv),
            'companies' => self::companies($report, $csv),
        };
    }

    protected static function statusRules(string $column): array
    {
        return [
            ['col' => $column, 'op' => 'equal', 'value' => 'On track', 'fill' => 'C6EFCE', 'font' => '006100'],
            ['col' => $column, 'op' => 'equal', 'value' => 'Completed', 'fill' => 'C6EFCE', 'font' => '006100'],
            ['col' => $column, 'op' => 'equal', 'value' => 'Validated', 'fill' => 'C6EFCE', 'font' => '006100'],
            ['col' => $column, 'op' => 'equal', 'value' => 'Behind', 'fill' => 'FFEB9C', 'font' => '9C5700'],
            ['col' => $column, 'op' => 'equal', 'value' => 'Incomplete', 'fill' => 'FFEB9C', 'font' => '9C5700'],
            ['col' => $column, 'op' => 'equal', 'value' => 'Waiting for review', 'fill' => 'FFEB9C', 'font' => '9C5700'],
            ['col' => $column, 'op' => 'equal', 'value' => 'In progress', 'fill' => 'DDEBF7', 'font' => '1F4E79'],
            ['col' => $column, 'op' => 'equal', 'value' => 'At risk', 'fill' => 'FFC7CE', 'font' => '9C0006'],
            ['col' => $column, 'op' => 'equal', 'value' => 'Revision requested', 'fill' => 'FFC7CE', 'font' => '9C0006'],
            ['col' => $column, 'op' => 'equal', 'value' => 'Not started', 'fill' => 'EDEDED', 'font' => '595959'],
            ['col' => $column, 'op' => 'equal', 'value' => 'Dates not set', 'fill' => 'EDEDED', 'font' => '595959'],
        ];
    }

    public static function progress(array $report, bool $csv = false): ReportArraySheet
    {
        $headings = [
            'Matric No', 'Student Name', 'Course', 'University', 'Student Email', 'Student Phone',
            'Lecturer', 'Company', 'Company Address', 'Department', 'Company Supervisor', 'Supervisor Phone',
            'Start Date', 'End Date', 'Total Weeks', 'Current Week', 'Status',
            'Weeks Sent', 'Weeks Validated', 'Weeks Need Revision', 'Weeks Waiting Review',
            'Working Days Due', 'Days With Entry', 'Missing Entries', 'Logbook Completion', 'Attendance',
            'MC Days', 'Leave Days', 'Public Holiday Days', 'WFH Days',
            'Last Activity', 'Days Without Update',
            'Tasks Done', 'Tasks Total', 'Overdue Tasks',
            'Entries Waiting Review', 'Reviews Overdue (7+ days)',
            'Evaluation Mark', 'Evaluation Weight', 'Evaluation Mark %', 'Evaluation Status', 'Evaluation Submitted', 'Main Issue',
        ];

        $rows = array_map(fn ($r) => [
            $r['matric'], $r['name'], $r['course'], $r['university'], $r['email'], $r['phone'],
            $r['lecturer'], $r['company'], $r['company_address'], $r['department'], $r['supervisor'], $r['supervisor_phone'],
            $r['start'], $r['end'], $r['total_weeks'], $r['current_week'], $r['status'],
            $r['weeks_sent'], $r['weeks_validated'], $r['weeks_revision'], $r['weeks_waiting'],
            $r['expected'], $r['entries_done'], $r['missing_entries'], $r['logbook_rate'], $r['attendance_rate'],
            $r['mc_days'], $r['leave_days'], $r['holiday_days'], $r['wfh_days'],
            $r['last_at'], $r['quiet_days'],
            $r['tasks_done'], $r['tasks_total'], $r['tasks_overdue'],
            $r['pending_entries'], $r['overdue_reviews'],
            $r['eval_mark'], $r['eval_weight'], $r['eval_percentage'], $r['eval_status'], $r['eval_submitted'], $r['main_issue'],
        ], $report['students']);

        return new ReportArraySheet('Student Progress', $headings, $rows, [
            'formats' => [
                'Start Date' => 'date', 'End Date' => 'date', 'Last Activity' => 'date', 'Evaluation Submitted' => 'date',
                'Logbook Completion' => 'percent', 'Attendance' => 'percent',
                'Evaluation Mark %' => 'percent',
            ],
            'freeze' => 'C2',
            'rules' => array_merge(self::statusRules('Status'), [
                ['col' => 'Attendance', 'op' => 'lt', 'value' => '0.75', 'fill' => 'FFC7CE', 'font' => '9C0006'],
                ['col' => 'Logbook Completion', 'op' => 'lt', 'value' => '0.75', 'fill' => 'FFC7CE', 'font' => '9C0006'],
                ['col' => 'Missing Entries', 'op' => 'gte', 'value' => '3', 'fill' => 'FFC7CE', 'font' => '9C0006'],
                ['col' => 'Days Without Update', 'op' => 'gte', 'value' => '3', 'fill' => 'FFEB9C', 'font' => '9C5700'],
                ['col' => 'Reviews Overdue (7+ days)', 'op' => 'gt', 'value' => '0', 'fill' => 'FFEB9C', 'font' => '9C5700'],
            ]),
        ], $csv);
    }

    public static function marks(array $report, bool $csv = false): ReportArraySheet
    {
        $headings = [
            'Matric No', 'Student Name', 'Course', 'University', 'Lecturer', 'Company',
            'Company Supervisor', 'Evaluation Status', 'Mark', 'Out Of', 'Mark %', 'Submitted On',
        ];

        $rows = array_map(fn ($r) => [
            $r['matric'], $r['name'], $r['course'], $r['university'], $r['lecturer'], $r['company'],
            $r['supervisor'], $r['eval_status'], $r['eval_mark'], $r['eval_weight'],
            $r['eval_percentage'], $r['eval_submitted'],
        ], $report['students']);

        return new ReportArraySheet('Student Marks', $headings, $rows, [
            'formats' => ['Mark %' => 'percent', 'Submitted On' => 'date'],
            'freeze' => 'C2',
        ], $csv);
    }

    public static function weekly(array $report, bool $csv = false): ReportArraySheet
    {
        $headings = [
            'Matric No', 'Student Name', 'Lecturer', 'Company', 'Company Supervisor', 'Week',
            'Week Start', 'Week End', 'Working Days Due', 'Days With Entry', 'Missing Days', 'Entries',
            'Completion', 'Status', 'Sent On', 'Reviewed On', 'Days Waiting',
        ];

        $rows = array_map(fn ($w) => [
            $w['matric'], $w['name'], $w['lecturer'], $w['company'], $w['supervisor'], $w['week'],
            $w['week_start'], $w['week_end'], $w['required'], $w['done'], $w['missing'], $w['entries'],
            $w['rate'], $w['status'], $w['sent_at'], $w['reviewed_at'], $w['days_waiting'],
        ], $report['weekly']);

        return new ReportArraySheet('Weekly Review', $headings, $rows, [
            'formats' => [
                'Week Start' => 'date', 'Week End' => 'date', 'Sent On' => 'date', 'Reviewed On' => 'date',
                'Completion' => 'percent',
            ],
            'freeze' => 'C2',
            'rules' => array_merge(self::statusRules('Status'), [
                ['col' => 'Days Waiting', 'op' => 'gt', 'value' => '7', 'fill' => 'FFC7CE', 'font' => '9C0006'],
                ['col' => 'Days Waiting', 'op' => 'gt', 'value' => '3', 'fill' => 'FFEB9C', 'font' => '9C5700'],
            ]),
        ], $csv);
    }

    public static function attention(array $report, bool $csv = false): ReportArraySheet
    {
        $headings = [
            'Priority', 'Matric No', 'Student Name', 'Lecturer', 'Company', 'Company Supervisor',
            'Main Issue', 'All Issues', 'Missing Entries', 'Days Without Update',
            'Last Activity', 'Last Activity Type', 'Student Phone', 'Supervisor Phone',
        ];

        $rows = array_map(fn ($r) => [
            $r['priority'], $r['matric'], $r['name'], $r['lecturer'], $r['company'], $r['supervisor'],
            $r['main_issue'], implode('; ', $r['issues']), $r['missing_entries'], $r['quiet_days'],
            $r['last_at'], $r['last_label'], $r['phone'], $r['supervisor_phone'],
        ], $report['attention']);

        return new ReportArraySheet('Attention List', $headings, $rows, [
            'formats' => ['Last Activity' => 'date'],
            'freeze' => 'D2',
            'rules' => [
                ['col' => 'Priority', 'op' => 'equal', 'value' => 'High', 'fill' => 'FFC7CE', 'font' => '9C0006'],
                ['col' => 'Priority', 'op' => 'equal', 'value' => 'Medium', 'fill' => 'FFEB9C', 'font' => '9C5700'],
            ],
        ], $csv);
    }

    public static function lecturers(array $report, bool $csv = false): ReportArraySheet
    {
        $headings = [
            'Lecturer', 'Faculty', 'Students', 'On Track / Completed', 'Behind / Incomplete', 'At Risk',
            'Not Started / No Dates', 'Avg Attendance', 'Avg Logbook Completion', 'Entries Waiting Review', 'Evaluations Submitted',
        ];

        $rows = array_map(fn ($r) => [
            $r['name'], $r['faculty'], $r['students'], $r['on_track'], $r['behind'], $r['at_risk'],
            $r['not_started'], $r['attendance_rate'], $r['logbook_rate'], $r['pending'], $r['evaluations'],
        ], $report['lecturers']);

        return new ReportArraySheet('Lecturers', $headings, $rows, [
            'formats' => ['Avg Attendance' => 'percent', 'Avg Logbook Completion' => 'percent'],
            'freeze' => 'B2',
            'rules' => [
                ['col' => 'At Risk', 'op' => 'gt', 'value' => '0', 'fill' => 'FFC7CE', 'font' => '9C0006'],
                ['col' => 'Avg Attendance', 'op' => 'lt', 'value' => '0.75', 'fill' => 'FFC7CE', 'font' => '9C0006'],
            ],
        ], $csv);
    }

    public static function supervisors(array $report, bool $csv = false): ReportArraySheet
    {
        $headings = [
            'Company Supervisor', 'Company', 'Position', 'Email', 'Phone', 'Students',
            'Entries Waiting Review', 'Reviews Overdue (7+ days)', 'Entries Need Revision',
            'Evaluations Submitted', 'Evaluations Missing',
        ];

        $rows = array_map(fn ($r) => [
            $r['name'], $r['company'], $r['position'], $r['email'], $r['phone'], $r['students'],
            $r['pending'], $r['overdue'], $r['revision'], $r['evaluated'], $r['not_evaluated'],
        ], $report['supervisors']);

        return new ReportArraySheet('Supervisors', $headings, $rows, [
            'freeze' => 'B2',
            'rules' => [
                ['col' => 'Reviews Overdue (7+ days)', 'op' => 'gt', 'value' => '0', 'fill' => 'FFC7CE', 'font' => '9C0006'],
                ['col' => 'Entries Waiting Review', 'op' => 'gt', 'value' => '0', 'fill' => 'FFEB9C', 'font' => '9C5700'],
            ],
        ], $csv);
    }

    public static function companies(array $report, bool $csv = false): ReportArraySheet
    {
        $headings = ['Company', 'Interns', 'Supervisors', 'Entries Waiting Review', 'Reviews Overdue (7+ days)', 'Avg Attendance'];

        $rows = array_map(fn ($r) => [
            $r['company'], $r['interns'], $r['supervisors'], $r['pending'], $r['overdue'], $r['attendance_rate'],
        ], $report['companies']);

        return new ReportArraySheet('Companies', $headings, $rows, [
            'formats' => ['Avg Attendance' => 'percent'],
            'freeze' => 'B2',
            'rules' => [
                ['col' => 'Reviews Overdue (7+ days)', 'op' => 'gt', 'value' => '0', 'fill' => 'FFC7CE', 'font' => '9C0006'],
            ],
        ], $csv);
    }
}
