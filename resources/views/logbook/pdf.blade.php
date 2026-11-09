<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Logbook - {{ $student->name }}</title>
    <style>
        @page { margin: 40px 45px; }
        body { font-family: Helvetica, Arial, sans-serif; font-size: 11px; color: #000; line-height: 1.5; }
        .title { text-align: center; font-size: 15px; font-weight: bold; text-transform: uppercase; margin: 0 0 14px; }
        .info { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
        .info td { padding: 2px 0; vertical-align: top; }
        .info td.label { width: 110px; font-weight: bold; }
        table.log { width: 100%; border-collapse: collapse; table-layout: fixed; }
        table.log th, table.log td { border: 1.5px solid #000; padding: 8px 10px; vertical-align: top; }
        table.log th { text-align: center; font-size: 11px; height: 38px; vertical-align: middle; }
        .col-date { width: 15%; text-align: center; }
        .col-desc { width: 63%; }
        .col-sv { width: 22%; text-align: center; }
        .entry-row { page-break-inside: avoid; }
        .label { font-weight: bold; }
        .desc { white-space: pre-wrap; margin-top: 8px; }
        .ref-img { max-width: 100%; max-height: 260px; margin: 4px 0 8px; }
        .ref-file { margin: 2px 0; }
        .feedback { margin-top: 8px; font-size: 10px; }
        .status { font-size: 10px; margin-top: 6px; }
        .sv-name { font-size: 10px; margin-top: 8px; }
        .empty { text-align: center; padding: 24px; }
        .footer { margin-top: 16px; font-size: 9px; color: #444; text-align: center; }

        .cover { page-break-after: always; }
        .cover .appx-top { text-align: right; font-size: 10px; margin-bottom: 14px; }
        .cover .appx-title { text-align: center; font-family: "Times New Roman", serif; font-size: 15px; font-weight: bold; text-transform: uppercase; margin: 0 0 18px; }
        .cover .center { text-align: center; }
        .cover .insert-box { margin: 0 auto 14px; border: 1px dashed #777; color: #777; text-align: center; font-style: italic; }
        .cover .logo-box { width: 230px; height: 110px; line-height: 110px; }
        .cover .uni-box { width: 330px; height: 46px; line-height: 46px; }
        .cover .period { font-size: 13px; font-weight: bold; line-height: 1.6; margin: 10px 0 14px; }
        .cover .note { font-size: 10px; font-style: italic; margin-bottom: 18px; }
        table.cover-t { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
        table.cover-t td { border: 1px solid #000; padding: 7px 8px; vertical-align: top; font-size: 11px; }
        table.cover-t td.k { width: 26%; text-transform: uppercase; font-size: 10px; }
        table.cover-t td.v { font-family: "Times New Roman", serif; font-size: 12px; text-transform: uppercase; }
    </style>
</head>
<body>
    <div class="cover">
        <div class="appx-top">Appendix A</div>
        <div class="appx-title">Appendix A: Practical Training Logbook</div>

        {{-- University logo + name come from the student's saved university. The dashed boxes stay as the fallback. --}}
        @if(! empty($universityLogo))
            <div class="center" style="margin: 0 auto 14px;">
                <img src="{{ $universityLogo }}" alt="" style="height: 100px; width: auto;">
            </div>
        @else
            <div class="insert-box logo-box">[ Insert logo here ]</div>
        @endif

        @if(! empty($university))
            <div class="center" style="font-family: 'Times New Roman', serif; font-size: 16px; font-weight: bold; text-transform: uppercase; margin: 0 0 14px;">{{ $university->name }}</div>
        @else
            <div class="insert-box uni-box">[ Insert university name here ]</div>
        @endif

        <div class="center period">
            PRACTICAL TRAINING LOGBOOK<br>
            {{ $period }}
        </div>

        <div class="center note">
            *Note: This document contains activities performed by student of<br>
            {{ $student->course }} for Practical Training course.
        </div>

        <table class="cover-t">
            <tr><td class="k">Student name</td><td class="v">{{ $student->name }}</td></tr>
            <tr><td class="k">Student ID</td><td class="v">{{ $student->matric_no }}</td></tr>
        </table>

        <table class="cover-t">
            <tr><td class="k">Name of the organization</td><td class="v">{{ $student->company_name ?? $supervisor->company_name ?? '' }}</td></tr>
            <tr><td class="k">Address of the organization</td><td class="v" style="height:70px">{!! nl2br(e($student->organization_address ?: ($student->company_address ?? $supervisor->company_address ?? ''))) !!}</td></tr>
            <tr><td class="k">Department<br>(if applicable)</td><td class="v" style="height:40px">{{ $student->department ?? '' }}</td></tr>
            <tr><td class="k">Name of industry supervisor</td><td class="v">{{ $supervisor->name ?? '' }}</td></tr>
        </table>

        <table class="cover-t">
            <tr><td class="k">Date</td><td class="v">{{ $period }}</td></tr>
        </table>

        <table class="cover-t">
            <tr><td class="k">Name of academic supervisor</td><td class="v">{{ $lecturer->name ?? '' }}</td></tr>
        </table>
    </div>

    <div class="title">Student Industrial Training Logbook</div>

    <table class="info">
        <tr><td class="label">Student</td><td>: {{ $student->name }}</td></tr>
        <tr><td class="label">Matric number</td><td>: {{ $student->matric_no }}</td></tr>
        <tr><td class="label">Programme</td><td>: {{ $student->course ?: 'N/A' }}</td></tr>
    </table>

    <table class="log">
        <thead>
            <tr>
                <th class="col-date">Date</th>
                <th class="col-desc">Description of Work</th>
                <th class="col-sv">Verification by<br>Employer's SV</th>
            </tr>
        </thead>
        <tbody>
        @forelse($logbooks as $logbook)
            @php
                $date = $logbook->activity_date ? \Carbon\Carbon::parse($logbook->activity_date) : null;
                $images = $logbook->pdf_images ?? [];
                $files = $logbook->pdf_files ?? [];
                $svFeedback = $logbook->feedbacks->filter(fn ($f) => ! empty($f->supervisor_id))->sortBy('feedback_date');
                $lecFeedback = $logbook->feedbacks->filter(fn ($f) => empty($f->supervisor_id))->sortBy('feedback_date');
            @endphp
            <tr class="entry-row">
                <td class="col-date">
                    {{ $date ? $date->format('d-m-Y') : 'N/A' }}<br>
                    ( {{ $date ? $date->format('l') : '-' }} )<br><br>
                    <span class="label">Week {{ $logbook->week_no }}</span>
                </td>
                <td class="col-desc">
                    <span class="label">Explanation :</span>
                    <div class="desc">{{ $logbook->description }}</div>

                    @if(count($images) || count($files))
                        <div style="margin-top:10px"><span class="label">References :</span></div>
                        @foreach($images as $src)
                            <img class="ref-img" src="{{ $src }}" alt="">
                        @endforeach
                        @foreach($files as $name)
                            <div class="ref-file">&bull; {{ $name }}</div>
                        @endforeach
                    @endif

                    @if($lecFeedback->isNotEmpty())
                        <div class="feedback">
                            <span class="label">Lecturer comment :</span>
                            @foreach($lecFeedback as $fb)
                                <div>{{ optional($fb->lecturer)->name ?? 'Lecturer' }}
                                    @if($fb->feedback_date) ({{ \Carbon\Carbon::parse($fb->feedback_date)->format('d-m-Y') }}) @endif
                                    : {{ $fb->comment }}</div>
                            @endforeach
                        </div>
                    @endif
                </td>
                <td class="col-sv">
                    @if(($logbook->supervisor_status ?? '') === 'Validated')
                        <strong>VERIFIED</strong>
                        @if($svFeedback->isNotEmpty())
                            @php $last = $svFeedback->last(); @endphp
                            <div class="sv-name">{{ optional($last->supervisor)->name ?? 'Supervisor' }}</div>
                            @if($last->feedback_date)
                                <div class="sv-name">{{ \Carbon\Carbon::parse($last->feedback_date)->format('d-m-Y') }}</div>
                            @endif
                        @endif
                    @else
                        <div class="status">{{ $logbook->supervisor_status ?: 'Pending' }}</div>
                    @endif
                </td>
            </tr>
        @empty
            <tr><td colspan="3" class="empty">No logbook entries have been submitted.</td></tr>
        @endforelse
        </tbody>
    </table>

    <div class="footer">Generated {{ now()->format('d-m-Y H:i') }} &mdash; Smart eLogBook System</div>
</body>
</html>
