@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-white p-6 rounded-[16px] border border-slate-200 shadow-sm shadow-slate-200/40 transition hover:-translate-y-0.5">
            <div class="flex items-start gap-4">
                <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-blue-100 text-blue-700">
                    <i class="fa-solid fa-users text-lg"></i>
                </div>
                <div>
                    <p class="text-xs font-semibold text-slate-400 uppercase tracking-[0.18em]">User Distribution</p>
                    <h3 class="mt-3 text-[46px] font-extrabold text-slate-900">{{ $students + $lecturers }}</h3>
                    <p class="text-sm text-slate-500 mt-1">Active profiles indexed</p>
                </div>
            </div>
        </div>

        <div class="bg-white p-6 rounded-[16px] border border-slate-200 shadow-sm shadow-slate-200/40 transition hover:-translate-y-0.5">
            <div class="flex items-start gap-4">
                <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-cyan-100 text-cyan-700">
                    <i class="fa-solid fa-user-graduate text-lg"></i>
                </div>
                <div>
                    <p class="text-xs font-semibold text-slate-400 uppercase tracking-[0.18em]">Total Students</p>
                    <h3 class="mt-3 text-[46px] font-extrabold text-slate-900">{{ $students }}</h3>
                    <p class="text-sm text-slate-500 mt-1">Enrolled learners</p>
                </div>
            </div>
        </div>

        <div class="bg-white p-6 rounded-[16px] border border-slate-200 shadow-sm shadow-slate-200/40 transition hover:-translate-y-0.5">
            <div class="flex items-start gap-4">
                <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-emerald-100 text-emerald-700">
                    <i class="fa-solid fa-chalkboard-teacher text-lg"></i>
                </div>
                <div>
                    <p class="text-xs font-semibold text-slate-400 uppercase tracking-[0.18em]">Total Lecturers</p>
                    <h3 class="mt-3 text-[46px] font-extrabold text-slate-900">{{ $lecturers }}</h3>
                    <p class="text-sm text-slate-500 mt-1">Faculty members</p>
                </div>
            </div>
        </div>

        <div class="bg-white p-6 rounded-[16px] border border-slate-200 shadow-sm shadow-slate-200/40 transition hover:-translate-y-0.5">
            <div class="flex items-start gap-4">
                <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-sky-100 text-sky-700">
                    <i class="fa-solid fa-book-open text-lg"></i>
                </div>
                <div>
                    <p class="text-xs font-semibold text-slate-400 uppercase tracking-[0.18em]">Total Logbooks</p>
                    <h3 class="mt-3 text-[46px] font-extrabold text-slate-900">{{ $logbooks }}</h3>
                    <p class="text-sm text-slate-500 mt-1">Internship records</p>
                </div>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-[16px] border border-slate-200 shadow-sm shadow-slate-200/40 overflow-hidden">
        <div class="px-6 py-5 bg-slate-50 border-b border-slate-100 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <span class="text-xs font-bold text-slate-500 uppercase tracking-[0.18em]">Quick Actions</span>
            </div>
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                <a href="/admin/students" class="inline-flex items-center justify-center text-xs font-bold bg-blue-600 text-white px-4 py-2 rounded-lg transition hover:bg-blue-700">Manage Students</a>
                <a href="/admin/lecturers" class="inline-flex items-center justify-center text-xs font-bold bg-blue-600 text-white px-4 py-2 rounded-lg transition hover:bg-blue-700">Manage Lecturers</a>
                <a href="/admin/reports" class="inline-flex items-center justify-center text-xs font-bold bg-emerald-600 text-white px-4 py-2 rounded-lg transition hover:bg-emerald-700">Generate Reports</a>
                <a href="/admin/reports/export/excel" class="inline-flex items-center justify-center text-xs font-bold bg-slate-700 text-white px-4 py-2 rounded-lg transition hover:bg-slate-800">Export Reports</a>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-4 p-6 sm:grid-cols-3">
            <div class="bg-slate-50 rounded-[14px] border border-slate-200 p-4 shadow-sm shadow-slate-200/30">
                <div class="flex items-center gap-3">
                    <div class="h-12 w-12 rounded-2xl bg-amber-100 text-amber-700 flex items-center justify-center">
                        <i class="fa-solid fa-clock text-lg"></i>
                    </div>
                    <div>
                        <p class="text-xs font-semibold text-slate-400 uppercase tracking-[0.18em]">Pending</p>
                        <h3 class="mt-3 text-[42px] font-extrabold text-amber-700">{{ $pending }}</h3>
                    </div>
                </div>
            </div>

            <div class="bg-slate-50 rounded-[14px] border border-slate-200 p-4 shadow-sm shadow-slate-200/30">
                <div class="flex items-center gap-3">
                    <div class="h-12 w-12 rounded-2xl bg-emerald-100 text-emerald-700 flex items-center justify-center">
                        <i class="fa-solid fa-check-circle text-lg"></i>
                    </div>
                    <div>
                        <p class="text-xs font-semibold text-slate-400 uppercase tracking-[0.18em]">Approved</p>
                        <h3 class="mt-3 text-[42px] font-extrabold text-emerald-700">{{ $approved }}</h3>
                    </div>
                </div>
            </div>

            <div class="bg-slate-50 rounded-[14px] border border-slate-200 p-4 shadow-sm shadow-slate-200/30">
                <div class="flex items-center gap-3">
                    <div class="h-12 w-12 rounded-2xl bg-rose-100 text-rose-700 flex items-center justify-center">
                        <i class="fa-solid fa-times-circle text-lg"></i>
                    </div>
                    <div>
                        <p class="text-xs font-semibold text-slate-400 uppercase tracking-[0.18em]">Rejected</p>
                        <h3 class="mt-3 text-[42px] font-extrabold text-rose-700">{{ $rejected }}</h3>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-[16px] border border-slate-200 shadow-sm shadow-slate-200/40 p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold text-slate-800">Logbook Analytics</h3>
            <span class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">Live Metrics</span>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
            <div class="rounded-xl border border-slate-200 p-4">
                <p class="text-xs uppercase tracking-[0.18em] text-slate-400">Submitted Today</p>
                <h4 class="mt-2 text-2xl font-bold text-slate-900">{{ $submittedToday }}</h4>
            </div>
            <div class="rounded-xl border border-slate-200 p-4">
                <p class="text-xs uppercase tracking-[0.18em] text-slate-400">Submitted This Week</p>
                <h4 class="mt-2 text-2xl font-bold text-slate-900">{{ $submittedThisWeek }}</h4>
            </div>
            <div class="rounded-xl border border-slate-200 p-4">
                <p class="text-xs uppercase tracking-[0.18em] text-slate-400">Average / Student</p>
                <h4 class="mt-2 text-2xl font-bold text-slate-900">{{ $averageLogbooksPerStudent }}</h4>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
        <div class="bg-white rounded-[16px] border border-slate-200 shadow-sm p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-slate-800">Recent Logbook Activity</h3>
                <span class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">Latest 10</span>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="text-left text-slate-500 border-b">
                            <th class="py-2">Student</th>
                            <th class="py-2">Lecturer</th>
                            <th class="py-2">Date</th>
                            <th class="py-2">Status</th>
                            <th class="py-2">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recentLogbooks as $logbook)
                            <tr class="border-b last:border-0">
                                <td class="py-2">{{ $logbook->student->name ?? 'Unknown' }}</td>
                                <td class="py-2">{{ $logbook->student->lecturer->name ?? 'Unassigned' }}</td>
                                <td class="py-2">{{ $logbook->created_at?->format('Y-m-d') }}</td>
                                <td class="py-2"><span class="px-2 py-1 rounded-full text-xs font-semibold {{ $logbook->status === 'Approved' ? 'bg-emerald-100 text-emerald-700' : ($logbook->status === 'Rejected' ? 'bg-rose-100 text-rose-700' : ($logbook->status === 'Revision Required' ? 'bg-amber-100 text-amber-700' : 'bg-sky-100 text-sky-700')) }}">{{ $logbook->status }}</span></td>
                                <td class="py-2"><a href="/lecturer/logbook/{{ $logbook->logbook_id }}" class="text-blue-600 hover:underline">View</a></td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="py-3 text-slate-500">No recent logbooks found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="bg-white rounded-[16px] border border-slate-200 shadow-sm p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-slate-800">Activity Feed</h3>
                <span class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">Latest 10</span>
            </div>
            <div class="space-y-3">
                @forelse($recentActivities as $activity)
                    <div class="flex items-start gap-3 rounded-xl border border-slate-200 p-3">
                        <div class="mt-0.5 h-9 w-9 rounded-full bg-blue-100 text-blue-700 flex items-center justify-center"><i class="fa-solid fa-bell"></i></div>
                        <div>
                            <p class="font-medium text-slate-800">{{ $activity['title'] }}</p>
                            <p class="text-sm text-slate-500">{{ $activity['detail'] }} • {{ $activity['time'] }}</p>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-slate-500">No activity yet.</p>
                @endforelse
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
        <div class="bg-white rounded-[16px] border border-slate-200 shadow-sm p-6">
            <h3 class="text-lg font-semibold text-slate-800 mb-4">Student Insights</h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="rounded-xl border border-slate-200 p-4"><p class="text-xs uppercase tracking-[0.18em] text-slate-400">Total Students</p><h4 class="mt-2 text-2xl font-bold text-slate-900">{{ $students }}</h4></div>
                <div class="rounded-xl border border-slate-200 p-4"><p class="text-xs uppercase tracking-[0.18em] text-slate-400">Active Students</p><h4 class="mt-2 text-2xl font-bold text-slate-900">{{ $activeStudents }}</h4></div>
                <div class="rounded-xl border border-slate-200 p-4"><p class="text-xs uppercase tracking-[0.18em] text-slate-400">No Submission This Week</p><h4 class="mt-2 text-2xl font-bold text-slate-900">{{ $studentsWithoutSubmissionThisWeek }}</h4></div>
                <div class="rounded-xl border border-slate-200 p-4"><p class="text-xs uppercase tracking-[0.18em] text-slate-400">Most Active Student</p><h4 class="mt-2 text-2xl font-bold text-slate-900">{{ $mostActiveStudent }} ({{ $mostActiveStudentCount }})</h4></div>
            </div>
        </div>

        <div class="bg-white rounded-[16px] border border-slate-200 shadow-sm p-6">
            <h3 class="text-lg font-semibold text-slate-800 mb-4">Lecturer Insights</h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="rounded-xl border border-slate-200 p-4"><p class="text-xs uppercase tracking-[0.18em] text-slate-400">Total Lecturers</p><h4 class="mt-2 text-2xl font-bold text-slate-900">{{ $lecturers }}</h4></div>
                <div class="rounded-xl border border-slate-200 p-4"><p class="text-xs uppercase tracking-[0.18em] text-slate-400">Students Supervised</p><h4 class="mt-2 text-2xl font-bold text-slate-900">{{ $supervisedStudents }}</h4></div>
                <div class="rounded-xl border border-slate-200 p-4"><p class="text-xs uppercase tracking-[0.18em] text-slate-400">Pending Reviews</p><h4 class="mt-2 text-2xl font-bold text-slate-900">{{ $pendingReviews }}</h4></div>
                <div class="rounded-xl border border-slate-200 p-4"><p class="text-xs uppercase tracking-[0.18em] text-slate-400">Reviewed</p><h4 class="mt-2 text-2xl font-bold text-slate-900">{{ $reviewedLogbooks }}</h4></div>
            </div>
            <p class="mt-4 text-sm text-slate-500">Top reviewer: <span class="font-semibold text-slate-800">{{ $topLecturerName }}</span></p>
        </div>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
        <div class="bg-white rounded-[16px] border border-slate-200 shadow-sm p-6 xl:col-span-2">
            <h3 class="text-lg font-semibold text-slate-800 mb-4">Monthly Logbook Submission Trend</h3>
            <canvas id="monthlyTrendChart" height="180"></canvas>
        </div>
        <div class="bg-white rounded-[16px] border border-slate-200 shadow-sm p-6">
            <h3 class="text-lg font-semibold text-slate-800 mb-4">User Distribution</h3>
            <canvas id="userDistributionChart" height="220"></canvas>
            <div id="userDistributionLegend" class="mt-4 flex flex-col gap-2"></div>
        </div>
    </div>

    <div class="bg-white rounded-[16px] border border-slate-200 shadow-sm p-6">
        <h3 class="text-lg font-semibold text-slate-800 mb-4">Weekly Logbook Status</h3>
        <canvas id="statusChart" height="140"></canvas>
    </div>

    <div class="bg-white rounded-[16px] border border-slate-200 shadow-sm p-6">
        <h3 class="text-lg font-semibold text-slate-800 mb-4">System Information</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">
            <div class="rounded-xl border border-slate-200 p-4"><p class="text-xs uppercase tracking-[0.18em] text-slate-400">Attachments</p><h4 class="mt-2 text-2xl font-bold text-slate-900">{{ $attachments }}</h4></div>
            <div class="rounded-xl border border-slate-200 p-4"><p class="text-xs uppercase tracking-[0.18em] text-slate-400">Database Status</p><h4 class="mt-2 text-2xl font-bold text-slate-900">{{ $databaseStatus }}</h4></div>
            <div class="rounded-xl border border-slate-200 p-4"><p class="text-xs uppercase tracking-[0.18em] text-slate-400">Storage Usage</p><h4 class="mt-2 text-2xl font-bold text-slate-900">{{ $storageUsage }} MB</h4></div>
            <div class="rounded-xl border border-slate-200 p-4"><p class="text-xs uppercase tracking-[0.18em] text-slate-400">App Version</p><h4 class="mt-2 text-2xl font-bold text-slate-900">{{ $appVersion }}</h4></div>
        </div>
        <p class="mt-4 text-sm text-slate-500">Last backup time: <span class="font-semibold text-slate-800">{{ $lastBackupTime }}</span></p>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const monthlyTrendData = @json($monthlyTrend->pluck('count', 'month'));
    const statusBreakdownData = @json($statusBreakdown->pluck('count', 'status'));
    const userDistributionData = @json($userDistribution);

    const monthlyLabels = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
    const monthlyValues = monthlyLabels.map((_, index) => monthlyTrendData[index + 1] || 0);

    new Chart(document.getElementById('monthlyTrendChart'), {
        type: 'line',
        data: {
            labels: monthlyLabels,
            datasets: [{
                label: 'Logbooks',
                data: monthlyValues,
                borderColor: '#2563eb',
                backgroundColor: 'rgba(37,99,235,0.15)',
                fill: true,
                tension: 0.3
            }]
        },
        options: { responsive: true, plugins: { legend: { display: false } } }
    });

    const userDistributionLabels = ['Students', 'Lecturers'];
    const userDistributionValues = [userDistributionData.students || 0, userDistributionData.lecturers || 0];
    const userDistributionTotal = userDistributionValues.reduce((sum, value) => sum + value, 0) || 1;
    const userDistributionColors = ['#2563eb', '#10b981'];

    const userDistributionLegend = document.getElementById('userDistributionLegend');
    userDistributionLegend.innerHTML = userDistributionLabels.map((label, index) => {
        const value = Number(userDistributionValues[index] || 0);
        const percentage = ((value / userDistributionTotal) * 100).toFixed(0);
        return `
            <div class="flex items-center justify-between rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm">
                <div class="flex items-center gap-2">
                    <span class="h-3 w-3 rounded-full" style="background-color: ${userDistributionColors[index]}"></span>
                    <span class="font-medium text-slate-700">${label}</span>
                </div>
                <span class="font-semibold text-slate-800">${percentage}%</span>
            </div>
        `;
    }).join('');

    new Chart(document.getElementById('userDistributionChart'), {
        type: 'pie',
        data: {
            labels: userDistributionLabels,
            datasets: [{
                data: userDistributionValues,
                backgroundColor: userDistributionColors,
                borderColor: '#ffffff',
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const value = Number(context.parsed) || 0;
                            const percentage = ((value / userDistributionTotal) * 100).toFixed(0);
                            return `${context.label}: ${value} (${percentage}%)`;
                        }
                    }
                }
            }
        }
    });

    const statusDisplayOrder = ['Pending', 'Approved', 'Rejected', 'Revision Required'];
    const visibleStatusData = statusDisplayOrder.filter((status) => {
        return Number(statusBreakdownData[status] || 0) > 0;
    });

    const statusColors = {
        Pending: '#f59e0b',
        Approved: '#10b981',
        Rejected: '#ef4444',
        'Revision Required': '#8b5cf6'
    };

    new Chart(document.getElementById('statusChart'), {
        type: 'bar',
        data: {
            labels: visibleStatusData,
            datasets: [{
                label: 'Count',
                data: visibleStatusData.map((status) => Number(statusBreakdownData[status] || 0)),
                backgroundColor: visibleStatusData.map((status) => statusColors[status] || '#64748b')
            }]
        },
        options: {
            responsive: true,
            scales: { y: { beginAtZero: true } }
        }
    });
</script>
@endsection