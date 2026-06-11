<div class="mx-auto flex w-full max-w-5xl flex-col gap-6 px-4 py-6 sm:px-6 lg:px-8">
    <div class="flex flex-col gap-2 border-b border-slate-200 pb-5">
        <p class="text-sm font-medium text-primary-700">{{ $tenant->name }}</p>
        <div class="flex flex-col gap-1">
            <p class="text-xs font-semibold uppercase text-slate-500">
                {{ $type === \App\Models\MemoryRecord::TYPE_PERIOD && ! $isEditing ? __('memory.record_editor.period_eyebrow') : __('memory.record_editor.eyebrow') }}
            </p>
            <h1 class="text-2xl font-semibold tracking-normal text-slate-950">
                @if ($isEditing)
                    {{ __('memory.record_editor.edit_heading') }}
                @elseif ($type === \App\Models\MemoryRecord::TYPE_PERIOD)
                    {{ __('memory.record_editor.period_heading') }}
                @else
                    {{ __('memory.record_editor.heading') }}
                @endif
            </h1>
            <p class="max-w-2xl text-sm leading-6 text-slate-600">
                @if ($isEditing)
                    {{ __('memory.record_editor.edit_intro') }}
                @elseif ($type === \App\Models\MemoryRecord::TYPE_PERIOD)
                    {{ __('memory.record_editor.period_intro') }}
                @else
                    {{ __('memory.record_editor.intro') }}
                @endif
            </p>
        </div>
    </div>

    <form wire:submit="save" class="grid gap-4 lg:grid-cols-[minmax(0,2fr)_minmax(16rem,1fr)]">
        <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
            <div class="grid gap-5">
                @if ($type === \App\Models\MemoryRecord::TYPE_PERIOD && ! $isEditing)
                    <label class="grid gap-2">
                        <span class="text-sm font-medium text-slate-900">{{ __('memory.record_editor.period_title_label') }}</span>
                        <input
                            type="text"
                            wire:model="title"
                            autofocus
                            class="min-h-11 rounded-lg border border-slate-300 bg-white px-3 text-sm text-slate-950 shadow-sm transition focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20"
                        >
                        @error('title')
                            <span class="text-sm text-red-600">{{ $message }}</span>
                        @enderror
                    </label>

                    <div class="grid gap-4 md:grid-cols-2">
                        <label class="grid gap-2">
                            <span class="text-sm font-medium text-slate-900">{{ __('memory.record_editor.period_start_date') }}</span>
                            <input
                                type="date"
                                wire:model="periodStartDate"
                                class="min-h-11 rounded-lg border border-slate-300 bg-white px-3 text-sm text-slate-950 shadow-sm transition focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20"
                            >
                            @error('periodStartDate')
                                <span class="text-sm text-red-600">{{ $message }}</span>
                            @enderror
                        </label>

                        <label class="grid gap-2">
                            <span class="text-sm font-medium text-slate-900">{{ __('memory.record_editor.period_end_date') }}</span>
                            <input
                                type="date"
                                wire:model="periodEndDate"
                                class="min-h-11 rounded-lg border border-slate-300 bg-white px-3 text-sm text-slate-950 shadow-sm transition focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20"
                            >
                            @error('periodEndDate')
                                <span class="text-sm text-red-600">{{ $message }}</span>
                            @enderror
                        </label>
                    </div>

                    <label class="grid gap-2">
                        <span class="flex flex-wrap items-center gap-2 text-sm font-medium text-slate-900">
                            {{ __('memory.record_editor.location_name') }}
                            <span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-600">
                                {{ __('memory.record_editor.optional') }}
                            </span>
                        </span>
                        <input
                            type="text"
                            wire:model="locationName"
                            class="min-h-11 rounded-lg border border-slate-300 bg-white px-3 text-sm text-slate-950 shadow-sm transition focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20"
                        >
                        @error('locationName')
                            <span class="text-sm text-red-600">{{ $message }}</span>
                        @enderror
                    </label>

                    <label class="grid gap-2">
                        <span class="flex flex-wrap items-center gap-2 text-sm font-medium text-slate-900">
                            {{ __('memory.record_editor.people') }}
                            <span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-600">
                                {{ __('memory.record_editor.optional') }}
                            </span>
                        </span>
                        <input
                            type="text"
                            wire:model="peopleInput"
                            class="min-h-11 rounded-lg border border-slate-300 bg-white px-3 text-sm text-slate-950 shadow-sm transition focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20"
                        >
                        <p class="text-xs leading-5 text-slate-500">{{ __('memory.record_editor.people_help') }}</p>
                        @error('peopleInput')
                            <span class="text-sm text-red-600">{{ $message }}</span>
                        @enderror
                    </label>

                    <label class="grid gap-2">
                        <span class="flex flex-wrap items-center gap-2 text-sm font-medium text-slate-900">
                            {{ __('memory.record_editor.notes') }}
                            <span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-600">
                                {{ __('memory.record_editor.optional') }}
                            </span>
                        </span>
                        <textarea
                            wire:model="notes"
                            rows="5"
                            class="rounded-lg border border-slate-300 bg-white px-3 py-3 text-base leading-7 text-slate-950 shadow-sm transition focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20"
                        ></textarea>
                        @error('notes')
                            <span class="text-sm text-red-600">{{ $message }}</span>
                        @enderror
                    </label>
                @else
                    <label class="grid gap-2">
                        <span class="text-sm font-medium text-slate-900">{{ __('memory.record_editor.body') }}</span>
                        <textarea
                            wire:model="body"
                            rows="10"
                            autofocus
                            class="rounded-lg border border-slate-300 bg-white px-3 py-3 text-base leading-7 text-slate-950 shadow-sm transition focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20"
                        ></textarea>
                        @error('body')
                            <span class="text-sm text-red-600">{{ $message }}</span>
                        @enderror
                    </label>

                    <div class="grid gap-4 md:grid-cols-2">
                        <label class="grid gap-2">
                            <span class="text-sm font-medium text-slate-900">{{ __('memory.record_editor.experience_date') }}</span>
                            <input
                                type="date"
                                wire:model="experienceDate"
                                class="min-h-11 rounded-lg border border-slate-300 bg-white px-3 text-sm text-slate-950 shadow-sm transition focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20"
                            >
                            @error('experienceDate')
                                <span class="text-sm text-red-600">{{ $message }}</span>
                            @enderror
                        </label>

                        <label class="grid gap-2">
                            <span class="flex flex-wrap items-center gap-2 text-sm font-medium text-slate-900">
                                {{ __('memory.record_editor.location_name') }}
                                <span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-600">
                                    {{ __('memory.record_editor.optional') }}
                                </span>
                            </span>
                            <input
                                type="text"
                                wire:model="locationName"
                                class="min-h-11 rounded-lg border border-slate-300 bg-white px-3 text-sm text-slate-950 shadow-sm transition focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20"
                            >
                            @error('locationName')
                                <span class="text-sm text-red-600">{{ $message }}</span>
                            @enderror
                        </label>
                    </div>
                @endif

                <label class="grid gap-2">
                    <span class="flex flex-wrap items-center gap-2 text-sm font-medium text-slate-900">
                        {{ __('memory.record_editor.tags') }}
                        <span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-600">
                            {{ __('memory.record_editor.optional') }}
                        </span>
                    </span>
                    <input
                        type="text"
                        wire:model="tagInput"
                        class="min-h-11 rounded-lg border border-slate-300 bg-white px-3 text-sm text-slate-950 shadow-sm transition focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20"
                    >
                    <p class="text-xs leading-5 text-slate-500">{{ __('memory.record_editor.tags_help') }}</p>
                    @error('tagInput')
                        <span class="text-sm text-red-600">{{ $message }}</span>
                    @enderror
                </label>

                <div class="grid gap-3">
                    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h2 class="text-base font-semibold tracking-normal text-slate-950">{{ __('memory.record_editor.highlights') }}</h2>
                            <p class="text-sm leading-6 text-slate-600">{{ __('memory.record_editor.highlights_help') }}</p>
                        </div>

                        <button
                            type="button"
                            wire:click="addHighlight"
                            class="inline-flex min-h-10 items-center justify-center rounded-lg border border-slate-300 px-3 text-sm font-medium text-slate-700 transition hover:border-primary-400 hover:text-primary-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-500"
                        >
                            {{ __('memory.record_editor.add_highlight') }}
                        </button>
                    </div>

                    <div class="grid gap-3">
                        @foreach ($highlights as $index => $highlight)
                            <div wire:key="highlight-{{ $highlight['uid'] }}" class="grid gap-2 rounded-lg border border-slate-200 bg-slate-50 p-3">
                                <label class="grid gap-2">
                                    <span class="sr-only">{{ __('memory.record_editor.highlight_number', ['number' => $index + 1]) }}</span>
                                    <textarea
                                        wire:model="highlights.{{ $index }}.text"
                                        rows="2"
                                        class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm leading-6 text-slate-950 shadow-sm transition focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20"
                                    ></textarea>
                                </label>

                                <div class="flex flex-wrap items-center gap-2">
                                    <button
                                        type="button"
                                        wire:click="moveHighlightUp({{ $index }})"
                                        class="inline-flex min-h-9 items-center justify-center rounded-lg border border-slate-300 px-3 text-xs font-medium text-slate-700 transition hover:border-primary-400 hover:text-primary-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-500"
                                    >
                                        {{ __('memory.record_editor.move_up') }}
                                    </button>
                                    <button
                                        type="button"
                                        wire:click="moveHighlightDown({{ $index }})"
                                        class="inline-flex min-h-9 items-center justify-center rounded-lg border border-slate-300 px-3 text-xs font-medium text-slate-700 transition hover:border-primary-400 hover:text-primary-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-500"
                                    >
                                        {{ __('memory.record_editor.move_down') }}
                                    </button>
                                    <button
                                        type="button"
                                        wire:click="removeHighlight({{ $index }})"
                                        class="inline-flex min-h-9 items-center justify-center rounded-lg border border-slate-300 px-3 text-xs font-medium text-slate-700 transition hover:border-red-300 hover:text-red-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-red-500"
                                    >
                                        {{ __('memory.record_editor.remove_highlight') }}
                                    </button>
                                </div>

                                @error('highlights.'.$index.'.text')
                                    <span class="text-sm text-red-600">{{ $message }}</span>
                                @enderror
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-3 pt-1">
                    <button
                        type="submit"
                        wire:loading.attr="disabled"
                        wire:target="save"
                        class="inline-flex min-h-11 items-center justify-center rounded-lg bg-primary-600 px-4 text-sm font-semibold text-white transition hover:bg-primary-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-500 disabled:cursor-not-allowed disabled:opacity-70"
                    >
                        <span wire:loading.remove wire:target="save">
                            @if ($isEditing)
                                {{ __('memory.record_editor.save_changes') }}
                            @elseif ($type === \App\Models\MemoryRecord::TYPE_PERIOD)
                                {{ __('memory.record_editor.save_period') }}
                            @else
                                {{ __('memory.record_editor.save') }}
                            @endif
                        </span>
                        <span wire:loading wire:target="save">{{ __('memory.record_editor.saving') }}</span>
                    </button>

                    <a
                        href="{{ $isEditing && $record !== null ? route('memories.records.show', ['tenant' => $tenant, 'record' => $record]) : route('memories.timeline', ['tenant' => $tenant]) }}"
                        class="inline-flex min-h-11 items-center justify-center rounded-lg border border-slate-300 px-4 text-sm font-medium text-slate-700 transition hover:border-primary-400 hover:text-primary-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-500"
                    >
                        {{ __('memory.record_editor.cancel') }}
                    </a>
                </div>
            </div>
        </div>

        <aside class="rounded-lg border border-slate-200 bg-slate-50 p-5">
            <div class="flex flex-col gap-3">
                <p class="text-xs font-semibold uppercase text-slate-500">{{ __('memory.layout.workspace') }}</p>
                <p class="text-sm font-medium text-slate-950">{{ $tenant->name }}</p>
                <p class="text-sm leading-6 text-slate-600">
                    @if ($isEditing)
                        {{ __('memory.record_editor.edit_sidebar') }}
                    @elseif ($type === \App\Models\MemoryRecord::TYPE_PERIOD)
                        {{ __('memory.record_editor.period_sidebar') }}
                    @else
                        {{ __('memory.record_editor.sidebar') }}
                    @endif
                </p>
            </div>
        </aside>
    </form>
</div>
