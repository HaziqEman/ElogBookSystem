@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <div class="bg-gradient-to-r from-blue-700 to-indigo-800 text-white p-6 rounded-2xl shadow-md">
        <h3 class="text-xl font-bold">Welcome Back to UITM E-LOGBOOK PORTAL, {{ optional($student)->name ?? 'Student' }}!</h3>
        <p class="text-blue-100 text-xs mt-1">Keep track of your internship deliverables and ensure your metrics stay aligned with your evaluation timeline.</p>
    </div>

    @if(session('success'))
        <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('success') }}</div>
    @endif

    @if(session('error'))
        <div class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">{{ session('error') }}</div>
    @endif

    @if($errors->any())
        <div class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
            <ul class="list-disc pl-5">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="grid grid-cols-1 md:grid-cols-4 gap-5">
        <div class="bg-white p-5 rounded-xl border border-slate-200/80 shadow-xs flex items-center gap-4">
            <div class="p-3 bg-blue-50 text-blue-600 rounded-xl"><i class="fa-solid fa-signature text-xl"></i></div>
            <div><p class="text-xs font-medium text-slate-400 uppercase tracking-wider">Logbook Entries</p><h3 class="text-2xl font-bold mt-0.5">{{ $totalLogs }}</h3></div>
        </div>
        <div class="bg-white p-5 rounded-xl border border-slate-200/80 shadow-xs flex items-center gap-4">
            <div class="p-3 bg-emerald-50 text-emerald-600 rounded-xl"><i class="fa-solid fa-paperclip text-xl"></i></div>
            <div><p class="text-xs font-medium text-slate-400 uppercase tracking-wider">Attachments</p><h3 class="text-2xl font-bold mt-0.5">{{ $totalAttachments }} Files</h3></div>
        </div>
        <div class="bg-white p-5 rounded-xl border border-slate-200/80 shadow-xs flex items-center gap-4">
            <div class="p-3 bg-indigo-50 text-indigo-600 rounded-xl"><i class="fa-solid fa-comment-dots text-xl"></i></div>
            <div><p class="text-xs font-medium text-slate-400 uppercase tracking-wider">Lecturer Reviews</p><h3 class="text-2xl font-bold mt-0.5">{{ $totalReviews }} Received</h3></div>
        </div>
        <a href="/student/messages" class="bg-white p-5 rounded-xl border border-slate-200/80 shadow-xs flex items-center gap-4 transition hover:border-blue-300">
            <div class="p-3 bg-slate-50 text-slate-700 rounded-xl"><i class="fa-solid fa-comments text-xl"></i></div>
            <div><p class="text-xs font-medium text-slate-400 uppercase tracking-wider">Messages</p><h3 class="text-2xl font-bold mt-0.5">Chat with Lecturer</h3></div>
        </a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">
        <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-xs space-y-4">
            <h3 class="font-bold text-base text-slate-800 border-b border-slate-100 pb-3 flex items-center gap-2"><i class="fa-solid fa-circle-plus text-blue-600"></i> New Logbook Entry</h3>
            <form action="/student/logbook/store" method="POST" enctype="multipart/form-data" class="space-y-3">
                @csrf
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Date of Training</label>
                    <input type="date" name="activity_date" class="w-full text-sm border border-slate-200 rounded-lg p-2.5 focus:outline-none focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Week Number</label>
                    <input type="number" name="week_no" class="w-full text-sm border border-slate-200 rounded-lg p-2.5 focus:outline-none focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Activity Description</label>
                    <div class="mb-2 flex items-center justify-between">
                        <span class="text-[11px] text-slate-400">Need help drafting a polished entry?</span>
                        <button type="button" id="ai-help-btn" class="rounded-lg border border-blue-200 bg-blue-50 px-3 py-1.5 text-xs font-semibold text-blue-700 transition hover:bg-blue-100">
                            ✨ AI Help
                        </button>
                    </div>
                    <textarea id="description-field" rows="5" name="description" placeholder="Detail tasks performed..." class="w-full text-sm border border-slate-200 rounded-lg p-2.5 focus:outline-none focus:border-blue-500"></textarea>
                    <p id="ai-help-status" class="mt-2 text-xs text-slate-400"></p>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Upload Attachments</label>
                    <input type="file" name="attachment" class="w-full text-sm border border-slate-200 rounded-lg p-2.5 file:mr-3 file:rounded file:border-0 file:bg-blue-50 file:px-3 file:py-2 file:text-sm file:font-semibold file:text-blue-700">
                </div>
                <button class="w-full text-sm font-semibold text-white bg-blue-600 hover:bg-blue-700 p-2.5 rounded-lg shadow-sm transition">Submit Entry</button>
            </form>
        </div>

        <div class="bg-white rounded-xl border border-slate-200 shadow-xs lg:col-span-2 overflow-hidden">
            <div class="p-4 bg-slate-50 font-bold text-sm text-slate-700 border-b border-slate-100">Log History Logs</div>
            <table class="w-full text-left border-collapse text-sm">
                <tbody class="divide-y divide-slate-100">
                    @forelse($logbooks as $logbook)
                        <tr>
                            <td class="p-4 font-bold">Week {{ $logbook->week_no }}</td>
                            <td class="p-4 text-slate-600">{{ $logbook->description }}</td>
                            <td class="p-4">
                                @if($logbook->attachments->count())
                                    <a href="/{{ $logbook->attachments[0]->file_path }}" target="_blank" class="text-blue-600 underline">{{ $logbook->attachments[0]->file_name }}</a>
                                @else
                                    <span class="px-2 py-0.5 text-xs rounded bg-slate-100 text-slate-600">No file</span>
                                @endif
                            </td>
                            <td class="p-4"><span class="px-2 py-0.5 text-xs rounded {{ $logbook->status === 'Approved' ? 'bg-emerald-100 text-emerald-800' : ($logbook->status === 'Rejected' ? 'bg-rose-100 text-rose-800' : 'bg-amber-100 text-amber-800') }}">{{ $logbook->status }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="p-4 text-slate-500">No logbook entries yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    // Ensure script runs after DOM is fully loaded
    function initAiHelp() {
        const aiHelpBtn = document.getElementById('ai-help-btn');
        const descriptionField = document.getElementById('description-field');
        const aiHelpStatus = document.getElementById('ai-help-status');

        console.log('Initializing AI Help...', { aiHelpBtn, descriptionField, aiHelpStatus });

        if (!aiHelpBtn || !descriptionField || !aiHelpStatus) {
            console.error('AI Help elements not found', { aiHelpBtn, descriptionField, aiHelpStatus });
            return;
        }

        aiHelpBtn.addEventListener('click', async (e) => {
            e.preventDefault();
            console.log('AI Help button clicked');
            
            const weekNo = document.querySelector('input[name="week_no"]').value;
            const activityDate = document.querySelector('input[name="activity_date"]').value;
            const description = descriptionField.value;
            const tokenInput = document.querySelector('input[name="_token"]');
            const token = tokenInput ? tokenInput.value : '';

            console.log('Form data:', { weekNo, activityDate, description, token });

            aiHelpStatus.textContent = 'Generating a draft...';
            aiHelpStatus.style.color = '#64748b';
            aiHelpBtn.disabled = true;

            try {
                const payload = {
                    week_no: weekNo,
                    activity_date: activityDate,
                    description: description,
                };
                
                console.log('Sending request to /student/logbook/ai-help', payload);
                
                const response = await fetch('/student/logbook/ai-help', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': token,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify(payload),
                });

                console.log('Response received:', { status: response.status, ok: response.ok });

                if (!response.ok) {
                    const errorData = await response.json();
                    console.error('API error response:', errorData);
                    throw new Error(errorData.message || `HTTP ${response.status}`);
                }

                const data = await response.json();
                console.log('API success response:', data);

                if (!data.text) {
                    throw new Error('No text field in response');
                }

                descriptionField.value = data.text;
                descriptionField.focus();
                aiHelpStatus.textContent = data.message || 'Draft inserted successfully.';
                aiHelpStatus.style.color = '#10b981';
                console.log('Success! Draft inserted.');
            } catch (error) {
                console.error('AI Help error:', error.message, error);
                aiHelpStatus.textContent = 'Error: ' + (error.message || 'Unable to generate text.');
                aiHelpStatus.style.color = '#ef4444';
            } finally {
                aiHelpBtn.disabled = false;
            }
        });
    }

    // Run when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initAiHelp);
    } else {
        initAiHelp();
    }
</script>

<script>
console.log('🚀 AI Help Script Loading...');

function initAiHelp() {
    console.log('📍 initAiHelp() called');
    const aiHelpBtn = document.getElementById('ai-help-btn');
    const descriptionField = document.getElementById('description-field');
    const aiHelpStatus = document.getElementById('ai-help-status');

    console.log('✅ Elements found:', !!aiHelpBtn, !!descriptionField, !!aiHelpStatus);

    if (!aiHelpBtn || !descriptionField || !aiHelpStatus) {
        console.error('❌ AI Help elements not found');
        return;
    }

    aiHelpBtn.addEventListener('click', async (e) => {
        e.preventDefault();
        console.log('🔘 AI Help button clicked');
        
        const weekNo = document.querySelector('input[name="week_no"]').value;
        const activityDate = document.querySelector('input[name="activity_date"]').value;
        const description = descriptionField.value;
        const tokenInput = document.querySelector('input[name="_token"]');
        const token = tokenInput ? tokenInput.value : '';

        console.log('📝 Form data:', { weekNo, activityDate, description });

        aiHelpStatus.textContent = 'Generating a draft...';
        aiHelpStatus.style.color = '#64748b';
        aiHelpBtn.disabled = true;

        try {
            const payload = {
                week_no: weekNo,
                activity_date: activityDate,
                description: description,
            };
            
            console.log('🚀 Sending POST to /student/logbook/ai-help');
            
            const response = await fetch('/student/logbook/ai-help', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': token,
                    'Accept': 'application/json',
                },
                body: JSON.stringify(payload),
            });

            console.log('📡 Response status:', response.status);

            if (!response.ok) {
                const errorData = await response.json();
                console.error('❌ API error:', errorData);
                throw new Error(errorData.message || `HTTP ${response.status}`);
            }

            const data = await response.json();
            console.log('✅ API success:', data);

            if (!data.text) {
                throw new Error('No text field in response');
            }

            console.log('📄 Setting textarea value:', data.text.substring(0, 50) + '...');
            descriptionField.value = data.text;
            descriptionField.focus();
            aiHelpStatus.textContent = data.message || 'Draft inserted successfully.';
            aiHelpStatus.style.color = '#10b981';
        } catch (error) {
            console.error('❌ AI Help error:', error.message);
            aiHelpStatus.textContent = 'Error: ' + (error.message || 'Unable to generate text.');
            aiHelpStatus.style.color = '#ef4444';
        } finally {
            aiHelpBtn.disabled = false;
        }
    });
}

// Try to initialize immediately
setTimeout(initAiHelp, 100);

// Also initialize on DOMContentLoaded
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initAiHelp);
}
</script>
@endsection