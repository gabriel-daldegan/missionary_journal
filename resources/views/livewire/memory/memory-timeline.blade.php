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
            <div class="flex flex-col gap-3">
                <p class="text-xs font-semibold uppercase text-primary-700">{{ __('memory.layout.timeline') }}</p>
                <h2 class="text-lg font-semibold tracking-normal text-slate-950">{{ __('memory.timeline.ready_heading') }}</h2>
                <p class="text-sm leading-6 text-slate-600">
                    {{ __('memory.timeline.ready_body') }}
                </p>
            </div>
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
