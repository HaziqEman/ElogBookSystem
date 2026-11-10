{{-- Edit entry pop-up --}}
<div id="edit-modal" class="fixed inset-0 z-[90] hidden items-start justify-center overflow-y-auto bg-slate-900/50 p-4" role="dialog" aria-modal="true" aria-labelledby="edit-modal-title">
    <div class="my-8 w-full max-w-2xl rounded-[22px] bg-white p-5 shadow-xl md:p-6">
        <div class="mb-3 flex items-center justify-between gap-3">
            <h2 id="edit-modal-title" class="text-lg font-extrabold">Edit logbook entry</h2>
            <button type="button" id="edit-modal-close" aria-label="Close" class="flex h-9 w-9 items-center justify-center rounded-lg text-slate-500 transition hover:bg-slate-100"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <div id="edit-modal-body"></div>
    </div>
</div>

<div id="entry-dates" data-live data-dates="{{ json_encode($entryDates->values()->all()) }}" class="hidden"></div>

{{-- University modal. Kept OUTSIDE every data-live block: the live refresh replaces those blocks'
     HTML, which would wipe this form (and its tab buttons) while the student is using it. --}}
<div id="universityModal" onclick="if (event.target === this) closeUniversityModal()" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 p-4" role="dialog" aria-modal="true" aria-labelledby="universityModalTitle">
    <div class="max-h-[90vh] w-full max-w-lg overflow-y-auto rounded-2xl bg-white p-6 shadow-2xl">
        <div class="mb-5 flex items-start justify-between gap-3">
            <div>
                <h2 id="universityModalTitle" class="text-xl font-extrabold text-gray-900">University Information</h2>
                <p class="mt-1 text-sm text-gray-500">Confirm your university before exporting. It will appear on your logbook PDF.</p>
            </div>
            <button type="button" onclick="closeUniversityModal()" aria-label="Close" class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg text-slate-500 transition hover:bg-slate-100">
                <i class="fa-solid fa-xmark text-xl"></i>
            </button>
        </div>

        @include('student.partials.university-form')
    </div>
</div>
