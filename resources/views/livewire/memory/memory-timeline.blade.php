@php
    $formatMemoryDate = fn ($date): ?string => $date?->copy()->locale(app()->getLocale())->translatedFormat('M j, Y');
@endphp

<div class="mx-auto flex w-full max-w-5xl flex-col gap-6 px-4 py-6 sm:px-6 lg:px-8">
    <div class="flex flex-col gap-2 border-b border-slate-200 pb-5">
        <p class="text-sm font-medium text-primary-700">{{ $tenant->name }}</p>
        <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div class="flex flex-col gap-1">
                <h1 class="text-2xl font-semibold tracking-normal text-slate-950">{{ __('memory.timeline.heading') }}</h1>
                <p class="max-w-2xl text-sm leading-6 text-slate-600">
                    {{ __('memory.timeline.intro') }}
                </p>
            </div>

            <div class="flex flex-wrap gap-2">
                <a
                    href="{{ route('memories.records.create', ['tenant' => $tenant, 'type' => \App\Models\MemoryRecord::TYPE_DIARY]) }}"
                    class="inline-flex h-10 items-center justify-center rounded-lg bg-primary-600 px-4 text-sm font-semibold text-white transition hover:bg-primary-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-500"
                >
                    {{ __('memory.record_editor.new_diary') }}
                </a>
                <a
                    href="{{ route('memories.records.create', ['tenant' => $tenant, 'type' => \App\Models\MemoryRecord::TYPE_PERIOD]) }}"
                    class="inline-flex h-10 items-center justify-center rounded-lg border border-slate-300 px-4 text-sm font-semibold text-slate-700 transition hover:border-primary-400 hover:text-primary-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-500"
                >
                    {{ __('memory.record_editor.new_period') }}
                </a>
            </div>
        </div>
    </div>

    <section class="grid gap-4 md:grid-cols-[minmax(0,2fr)_minmax(16rem,1fr)]">
        <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
            <div class="mb-6 flex flex-col gap-4 rounded-lg border border-slate-200 bg-slate-50 p-4">
                <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-4">
                    <label class="flex min-w-0 flex-col gap-1 text-sm font-medium text-slate-700">
                        <span>{{ __('memory.timeline.filters.date_from') }}</span>
                        <input
                            type="date"
                            wire:model.live="dateFrom"
                            class="h-10 min-w-0 rounded-lg border border-slate-300 bg-white px-3 text-sm text-slate-950 shadow-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-200"
                        >
                    </label>

                    <label class="flex min-w-0 flex-col gap-1 text-sm font-medium text-slate-700">
                        <span>{{ __('memory.timeline.filters.date_to') }}</span>
                        <input
                            type="date"
                            wire:model.live="dateTo"
                            class="h-10 min-w-0 rounded-lg border border-slate-300 bg-white px-3 text-sm text-slate-950 shadow-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-200"
                        >
                    </label>

                    <label class="flex min-w-0 flex-col gap-1 text-sm font-medium text-slate-700">
                        <span>{{ __('memory.timeline.filters.tag') }}</span>
                        <select
                            wire:model.live="selectedTag"
                            class="h-10 min-w-0 rounded-lg border border-slate-300 bg-white px-3 text-sm text-slate-950 shadow-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-200"
                        >
                            <option value="">{{ __('memory.timeline.filters.all_tags') }}</option>
                            @foreach ($tagOptions as $tag)
                                <option wire:key="timeline-filter-tag-{{ $tag->id }}" value="{{ $tag->slug }}">{{ $tag->name }}</option>
                            @endforeach
                        </select>
                    </label>

                    <label class="flex min-w-0 flex-col gap-1 text-sm font-medium text-slate-700">
                        <span>{{ __('memory.timeline.filters.location') }}</span>
                        <input
                            type="search"
                            wire:model.live.debounce.400ms="location"
                            class="h-10 min-w-0 rounded-lg border border-slate-300 bg-white px-3 text-sm text-slate-950 shadow-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-200"
                        >
                    </label>
                </div>

                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <div class="flex flex-wrap gap-2">
                        @foreach ($activeFilterLabels as $activeFilterLabel)
                            <span wire:key="timeline-active-filter-{{ $activeFilterLabel['key'] }}" class="inline-flex max-w-full items-center rounded-full bg-white px-3 py-1 text-xs font-medium text-slate-700 ring-1 ring-slate-200">
                                {{ $activeFilterLabel['label'] }}
                            </span>
                        @endforeach
                    </div>

                    @if ($hasActiveFilters)
                        <button
                            type="button"
                            wire:click="clearFilters"
                            class="inline-flex h-9 w-fit items-center justify-center rounded-lg border border-slate-300 bg-white px-3 text-sm font-semibold text-slate-700 transition hover:border-primary-400 hover:text-primary-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-500"
                        >
                            {{ __('memory.timeline.filters.clear') }}
                        </button>
                    @endif
                </div>
            </div>

            @if ($timelineGroups->isEmpty())
                @if ($hasActiveFilters)
                    <div class="flex flex-col gap-3">
                        <p class="text-xs font-semibold uppercase text-primary-700">{{ __('memory.layout.timeline') }}</p>
                        <h2 class="text-lg font-semibold tracking-normal text-slate-950">{{ __('memory.timeline.filters.no_results_heading') }}</h2>
                        <p class="text-sm leading-6 text-slate-600">
                            {{ __('memory.timeline.filters.no_results_body') }}
                        </p>
                        <button
                            type="button"
                            wire:click="clearFilters"
                            class="inline-flex h-10 w-fit items-center justify-center rounded-lg bg-primary-600 px-4 text-sm font-semibold text-white transition hover:bg-primary-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-500"
                        >
                            {{ __('memory.timeline.filters.clear') }}
                        </button>
                    </div>
                @else
                    <div class="flex flex-col gap-3">
                        <p class="text-xs font-semibold uppercase text-primary-700">{{ __('memory.layout.timeline') }}</p>
                        <h2 class="text-lg font-semibold tracking-normal text-slate-950">{{ __('memory.timeline.ready_heading') }}</h2>
                        <p class="text-sm leading-6 text-slate-600">
                            {{ __('memory.timeline.ready_body') }}
                        </p>
                        <div class="flex flex-wrap gap-2">
                            <a
                                href="{{ route('memories.records.create', ['tenant' => $tenant, 'type' => \App\Models\MemoryRecord::TYPE_DIARY]) }}"
                                class="inline-flex h-10 w-fit items-center justify-center rounded-lg bg-primary-600 px-4 text-sm font-semibold text-white transition hover:bg-primary-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-500"
                            >
                                {{ __('memory.record_editor.new_diary') }}
                            </a>
                            <a
                                href="{{ route('memories.records.create', ['tenant' => $tenant, 'type' => \App\Models\MemoryRecord::TYPE_PERIOD]) }}"
                                class="inline-flex h-10 w-fit items-center justify-center rounded-lg border border-slate-300 px-4 text-sm font-semibold text-slate-700 transition hover:border-primary-400 hover:text-primary-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-500"
                            >
                                {{ __('memory.record_editor.new_period') }}
                            </a>
                        </div>
                    </div>
                @endif
            @else
                <div class="flex flex-col gap-6">
                    @foreach ($timelineGroups as $group)
                        <section wire:key="timeline-group-{{ $group['month_key'] }}" class="flex flex-col gap-3">
                            <header>
                                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $group['month_label'] }}</p>
                            </header>
                            <div class="flex flex-col gap-3">
                                @foreach ($group['records'] as $record)
                                    @php
                                        $timelineDate = $record->timelineDate();
                                        $photoCount = $record->timelinePhotoCount();
                                    @endphp
                                    <article
                                        wire:key="timeline-record-{{ $record->id }}"
                                        class="grid gap-3 rounded-lg border border-slate-200 bg-slate-50 p-4 transition hover:border-primary-300 hover:bg-primary-50/40 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-500"
                                    >
                                        <div class="flex flex-wrap items-center gap-2 text-xs font-medium uppercase text-slate-500">
                                            @if ($timelineDate)
                                                <time datetime="{{ $timelineDate->toDateString() }}">
                                                    {{ $formatMemoryDate($timelineDate) }}
                                                    @if ($record->period_start_date !== null && $record->period_end_date !== null)
                                                        {{ __('memory.timeline.period_range_separator') }}
                                                        {{ $formatMemoryDate($record->period_end_date) }}
                                                    @endif
                                                </time>
                                            @endif

                                            @if ($record->location_name)
                                                <span aria-hidden="true">/</span>
                                                <span>{{ $record->location_name }}</span>
                                            @endif
                                        </div>

                                        <p class="text-sm leading-6 text-slate-800">{{ $record->timelineExcerpt() }}</p>

                                        @if ($record->tags->isNotEmpty())
                                            <div class="flex flex-wrap gap-2">
                                                @foreach ($record->tags as $tag)
                                                    <span wire:key="timeline-tag-{{ $record->id }}-{{ $tag->id }}" class="rounded-full bg-slate-100 px-3 py-1 text-xs font-medium text-slate-700">
                                                        {{ $tag->name }}
                                                    </span>
                                                @endforeach
                                            </div>
                                        @endif

                                        @if ($photoCount > 0)
                                            <p class="text-xs text-slate-500">
                                                {{ trans_choice('memory.timeline.photo_count', $photoCount, ['count' => $photoCount]) }}
                                            </p>
                                        @endif

                                        <a
                                            href="{{ route('memories.records.show', ['tenant' => $tenant, 'record' => $record]) }}"
                                            class="inline-flex h-9 min-h-9 w-fit items-center justify-center rounded-lg border border-primary-200 px-4 text-sm font-semibold text-primary-700 transition hover:bg-primary-50"
                                        >
                                            {{ __('memory.timeline.open_record') }}
                                        </a>
                                    </article>
                                @endforeach
                            </div>
                        </section>
                    @endforeach
                </div>
            @endif
        </div>

        <aside class="rounded-lg border border-slate-200 bg-slate-50 p-5">
            <div class="flex flex-col gap-3">
                <p class="text-xs font-semibold uppercase text-slate-500">{{ __('memory.layout.workspace') }}</p>
                <p class="text-sm font-medium text-slate-950">{{ $tenant->name }}</p>
                <p class="text-sm leading-6 text-slate-600">
                    {{ __('memory.timeline.dashboard_note') }}
                </p>
            </div>
        </aside>
    </section>
</div>
