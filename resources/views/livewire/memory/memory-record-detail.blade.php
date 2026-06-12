<div class="mx-auto flex w-full max-w-5xl flex-col gap-6 px-4 py-6 sm:px-6 lg:px-8">
    <div class="flex flex-col gap-3 border-b border-slate-200 pb-5">
        <p class="text-sm font-medium text-primary-700">{{ $tenant->name }}</p>
        <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div class="flex flex-col gap-1">
                <p class="text-xs font-semibold uppercase text-slate-500">
                    {{ $record->type === \App\Models\MemoryRecord::TYPE_PERIOD ? __('memory.record_detail.period_eyebrow') : __('memory.record_detail.eyebrow') }}
                </p>
                <h1 class="text-2xl font-semibold tracking-normal text-slate-950">
                    {{ $record->type === \App\Models\MemoryRecord::TYPE_PERIOD ? ($record->title ?? __('memory.record_detail.period_heading')) : __('memory.record_detail.heading') }}
                </h1>
                <p class="max-w-2xl text-sm leading-6 text-slate-600">
                    {{ $record->type === \App\Models\MemoryRecord::TYPE_PERIOD ? __('memory.record_detail.period_intro') : __('memory.record_detail.intro') }}
                </p>
            </div>

            @if ($record->type === \App\Models\MemoryRecord::TYPE_DIARY)
                @can('update', $record)
                <a
                    href="{{ route('memories.records.edit', ['tenant' => $tenant, 'record' => $record]) }}"
                    class="inline-flex min-h-10 items-center justify-center rounded-lg border border-slate-300 px-4 text-sm font-medium text-slate-700 transition hover:border-primary-400 hover:text-primary-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-500"
                >
                    {{ __('memory.record_detail.edit') }}
                </a>
                @endcan
            @endif
        </div>
    </div>

    <section class="grid gap-4 lg:grid-cols-[minmax(0,2fr)_minmax(16rem,1fr)]">
        <article class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex flex-col gap-5">
                <div class="flex flex-wrap items-center gap-2 text-sm text-slate-600">
                    @if ($record->type === \App\Models\MemoryRecord::TYPE_PERIOD)
                        <time datetime="{{ $record->period_start_date?->toDateString() }}">
                            {{ $record->period_start_date?->toFormattedDateString() }}
                        </time>
                        @if ($record->period_end_date)
                            <span>{{ __('memory.timeline.period_range_separator') }}</span>
                            <time datetime="{{ $record->period_end_date->toDateString() }}">
                                {{ $record->period_end_date->toFormattedDateString() }}
                            </time>
                        @endif
                    @else
                        <time datetime="{{ $record->experience_date?->toDateString() }}">
                            {{ $record->experience_date?->toFormattedDateString() }}
                        </time>
                    @endif

                    @if ($record->location_name)
                        <span aria-hidden="true">/</span>
                        <span>{{ $record->location_name }}</span>
                    @endif
                </div>

                @if ($record->type === \App\Models\MemoryRecord::TYPE_PERIOD)
                    @if (! empty($record->people))
                        <div class="flex flex-wrap gap-2" aria-label="{{ __('memory.record_detail.people') }}">
                            @foreach ($record->people as $person)
                                <span wire:key="record-person-{{ $loop->index }}-{{ md5($person) }}" class="rounded-full bg-slate-100 px-3 py-1 text-xs font-medium text-slate-700">
                                    {{ $person }}
                                </span>
                            @endforeach
                        </div>
                    @endif

                    @if ($record->notes)
                        <div class="whitespace-pre-line text-base leading-8 text-slate-950">{{ $record->notes }}</div>
                    @endif
                @else
                    <div class="whitespace-pre-line text-base leading-8 text-slate-950">{{ $record->body }}</div>
                @endif

                @if ($record->tags->isNotEmpty())
                    <div class="flex flex-wrap gap-2" aria-label="{{ __('memory.record_detail.tags') }}">
                        @foreach ($record->tags as $tag)
                            <span wire:key="record-tag-{{ $tag->id }}" class="rounded-full bg-slate-100 px-3 py-1 text-xs font-medium text-slate-700">
                                {{ $tag->name }}
                            </span>
                        @endforeach
                    </div>
                @endif

                @if ($record->getMedia($record->mediaCollectionName())->isNotEmpty())
                    <section class="grid gap-3 border-t border-slate-200 pt-5" aria-label="{{ __('memory.record_detail.photos') }}">
                        <h2 class="text-base font-semibold tracking-normal text-slate-950">{{ __('memory.record_detail.photos') }}</h2>
                        <div class="grid gap-3 sm:grid-cols-2">
                            @foreach ($record->getMedia($record->mediaCollectionName()) as $media)
                                <figure wire:key="record-photo-{{ $media->uuid }}" class="grid gap-2 rounded-lg border border-slate-200 bg-slate-50 p-2">
                                    <img
                                        src="{{ $record->mediaRoute($media) }}"
                                        alt="{{ __('memory.record_detail.photo_alt', ['number' => $loop->iteration]) }}"
                                        class="aspect-video w-full rounded-md object-cover"
                                    >
                                    <figcaption class="text-xs text-slate-500">
                                        {{ __('memory.record_detail.photo_private_state') }}
                                    </figcaption>
                                </figure>
                            @endforeach
                        </div>
                    </section>
                @endif
            </div>
        </article>

        <aside class="rounded-lg border border-slate-200 bg-slate-50 p-5">
            <div class="flex flex-col gap-5">
                <div class="flex flex-col gap-2">
                    <p class="text-xs font-semibold uppercase text-slate-500">{{ __('memory.record_detail.metadata') }}</p>
                    <p class="text-sm leading-6 text-slate-600">
                        {{ __('memory.record_detail.author', ['name' => $record->author?->name ?? __('memory.record_detail.unknown_author')]) }}
                    </p>
                    <p class="text-sm leading-6 text-slate-600">
                        {{ __('memory.record_detail.last_editor', ['name' => $record->lastEditor?->name ?? __('memory.record_detail.unknown_author')]) }}
                    </p>
                </div>

                @if ($record->highlights->isNotEmpty())
                    <div class="flex flex-col gap-3">
                        <h2 class="text-base font-semibold tracking-normal text-slate-950">{{ __('memory.record_detail.highlights') }}</h2>
                        <ul class="flex flex-col gap-2">
                            @foreach ($record->highlights as $highlight)
                                <li wire:key="record-highlight-{{ $highlight->id }}" class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm leading-6 text-slate-700">
                                    {{ $highlight->text }}
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>
        </aside>
    </section>
</div>
