@props(['action', 'inputClass', 'noun', 'total'])

<form id="bulk-form" method="POST" action="{{ $action }}" data-total="{{ $total }}" data-noun="{{ $noun }}">
    @csrf
    <div id="bulk-bar" class="sticky top-2 z-20 hidden rounded-xl border border-blue-200 bg-blue-50 p-4 shadow-lg">
        <div class="flex flex-wrap items-center gap-x-5 gap-y-2">
            <p class="text-sm font-semibold text-blue-900"><span id="selected-count">0</span> selected</p>
            <div id="apply-all-wrap" class="hidden">
                <label class="flex items-center gap-2 text-xs text-blue-900">
                    <input type="checkbox" id="apply-all" name="apply_to_all" value="1" class="h-4 w-4 rounded border-blue-300">
                    Apply to all {{ $total }} {{ $noun }} matching the current filters, not just this page
                </label>
            </div>
            <button type="button" id="bulk-clear" class="ml-auto text-xs font-semibold text-blue-700 hover:underline">Clear selection</button>
        </div>

        <div class="mt-3 flex flex-col gap-3 lg:flex-row lg:items-end">
            <div class="lg:w-72">
                <label for="bulk-action" class="mb-1 block text-[11px] font-semibold uppercase tracking-wider text-slate-500">Action</label>
                <select id="bulk-action" name="action" class="{{ $inputClass }}">
                    <option value="">Choose an action...</option>
                    {{ $actions }}
                </select>
            </div>

            <div class="min-w-0 flex-1">
                {{ $fields }}
            </div>

            <button type="submit" id="bulk-submit" disabled class="cursor-not-allowed rounded-lg bg-blue-600 px-5 py-2 text-sm font-semibold text-white opacity-50 transition hover:bg-blue-700">Apply</button>
        </div>
    </div>
</form>
