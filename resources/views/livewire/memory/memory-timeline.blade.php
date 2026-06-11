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

            <a
                href="{{ route('memories.records.create', ['tenant' => $tenant, 'type' => \App\Models\MemoryRecord::TYPE_DIARY]) }}"
                class="inline-flex h-10 items-center justify-center rounded-lg bg-primary-600 px-4 text-sm font-semibold text-white transition hover:bg-primary-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-500"
            >
                {{ __('memory.record_editor.new_diary') }}
            </a>
        </div>
    </div>

    <section class="grid gap-4 md:grid-cols-[minmax(0,2fr)_minmax(16rem,1fr)]">
        <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
            @if ($timelineGroups->isEmpty())
                <div class="flex flex-col gap-3">
                    <p class="text-xs font-semibold uppercase text-primary-700">{{ __('memory.layout.timeline') }}</p>
                    <h2 class="text-lg font-semibold tracking-normal text-slate-950">{{ __('memory.timeline.ready_heading') }}</h2>
                    <p class="text-sm leading-6 text-slate-600">
                        {{ __('memory.timeline.ready_body') }}
                    </p>
                    <a
                        href="{{ route('memories.records.create', ['tenant' => $tenant, 'type' => \App\Models\MemoryRecord::TYPE_DIARY]) }}"
                        class="inline-flex h-10 w-fit items-center justify-center rounded-lg bg-primary-600 px-4 text-sm font-semibold text-white transition hover:bg-primary-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-500"
                    >
                        {{ __('memory.record_editor.new_diary') }}
                    </a>
                </div>
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
                                                    {{ $timelineDate->toFormattedDateString() }}
                                                    @if ($record->period_start_date !== null && $record->period_end_date !== null)
                                                        {{ __('memory.timeline.period_range_separator') }}
                                                        {{ $record->period_end_date->toFormattedDateString() }}
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
