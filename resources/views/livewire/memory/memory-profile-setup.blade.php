<div class="mx-auto flex w-full max-w-5xl flex-col gap-6 px-4 py-6 sm:px-6 lg:px-8">
    <div class="flex flex-col gap-2 border-b border-slate-200 pb-5">
        <p class="text-sm font-medium text-primary-700">{{ $tenant->name }}</p>
        <div class="flex flex-col gap-1">
            <p class="text-xs font-semibold uppercase text-slate-500">{{ __('memory.profile_setup.eyebrow') }}</p>
            <h1 class="text-2xl font-semibold tracking-normal text-slate-950">{{ __('memory.profile_setup.heading') }}</h1>
            <p class="max-w-2xl text-sm leading-6 text-slate-600">
                {{ __('memory.profile_setup.intro') }}
            </p>
        </div>
    </div>

    <form wire:submit="save" class="grid gap-4 lg:grid-cols-[minmax(0,2fr)_minmax(16rem,1fr)]">
        <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
            <div class="grid gap-5">
                <label class="grid gap-2">
                    <span class="text-sm font-medium text-slate-900">{{ __('memory.profile_setup.display_name') }}</span>
                    <input
                        type="text"
                        wire:model="displayName"
                        autocomplete="name"
                        class="min-h-11 rounded-lg border border-slate-300 bg-white px-3 text-sm text-slate-950 shadow-sm transition focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20"
                    >
                    @error('displayName')
                        <span class="text-sm text-red-600">{{ $message }}</span>
                    @enderror
                </label>

                <label class="grid gap-2">
                    <span class="text-sm font-medium text-slate-900">{{ __('memory.profile_setup.preferred_locale') }}</span>
                    <select
                        wire:model="preferredLocale"
                        class="min-h-11 rounded-lg border border-slate-300 bg-white px-3 text-sm text-slate-950 shadow-sm transition focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20"
                    >
                        @foreach ($localeOptions as $locale => $label)
                            <option value="{{ $locale }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('preferredLocale')
                        <span class="text-sm text-red-600">{{ $message }}</span>
                    @enderror
                </label>

                <label class="grid gap-2">
                    <span class="flex flex-wrap items-center gap-2 text-sm font-medium text-slate-900">
                        {{ __('memory.profile_setup.mission_context') }}
                        <span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-600">
                            {{ __('memory.profile_setup.optional') }}
                        </span>
                    </span>
                    <textarea
                        wire:model="missionContext"
                        rows="4"
                        class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm leading-6 text-slate-950 shadow-sm transition focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20"
                    ></textarea>
                    @error('missionContext')
                        <span class="text-sm text-red-600">{{ $message }}</span>
                    @enderror
                </label>

                <div class="flex flex-wrap items-center gap-3 pt-1">
                    <button
                        type="submit"
                        wire:loading.attr="disabled"
                        wire:target="save"
                        class="inline-flex min-h-11 items-center justify-center rounded-lg bg-primary-600 px-4 text-sm font-semibold text-white transition hover:bg-primary-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-500 disabled:cursor-not-allowed disabled:opacity-70"
                    >
                        <span wire:loading.remove wire:target="save">{{ __('memory.profile_setup.save') }}</span>
                        <span wire:loading wire:target="save">{{ __('memory.profile_setup.saving') }}</span>
                    </button>

                    <a
                        href="{{ route('filament.dashboard.pages.my-profile', ['tenant' => $tenant]) }}"
                        class="inline-flex min-h-11 items-center justify-center rounded-lg border border-slate-300 px-4 text-sm font-medium text-slate-700 transition hover:border-primary-400 hover:text-primary-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-500"
                    >
                        {{ __('memory.profile_setup.account_profile') }}
                    </a>
                </div>
            </div>
        </div>

        <aside class="rounded-lg border border-slate-200 bg-slate-50 p-5">
            <div class="flex flex-col gap-3">
                <p class="text-xs font-semibold uppercase text-slate-500">{{ __('memory.layout.workspace') }}</p>
                <p class="text-sm font-medium text-slate-950">{{ $tenant->name }}</p>
                <p class="text-sm leading-6 text-slate-600">
                    {{ __('memory.profile_setup.sidebar') }}
                </p>
            </div>
        </aside>
    </form>
</div>
