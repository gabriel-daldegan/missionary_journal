<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full scroll-smooth bg-slate-50">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <meta name="csrf-token" content="{{ csrf_token() }}">
    @include('components.layouts.partials.head')
</head>
<body class="min-h-screen bg-slate-50 text-slate-950 antialiased" x-data data-memory-layout="true">
    <div id="app" class="flex min-h-screen flex-col">
        <header class="border-b border-slate-200 bg-white">
            <div class="mx-auto flex w-full max-w-7xl flex-col gap-4 px-4 py-4 sm:px-6 lg:px-8">
                <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                    <a
                        href="{{ route('memories.timeline', ['tenant' => $tenant]) }}"
                        class="flex items-center gap-3 rounded-lg focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-primary-500"
                        aria-label="{{ __('memory.layout.home_aria') }}"
                    >
                        <img src="{{ asset(config('app.logo.dark')) }}" class="h-7" alt="{{ config('app.name') }}" />
                        <span class="hidden text-sm font-semibold text-slate-900 sm:inline">{{ __('memory.layout.brand') }}</span>
                    </a>

                    <div class="flex flex-wrap items-center gap-2 text-sm">
                        <a
                            href="{{ route('filament.dashboard.pages.my-profile', ['tenant' => $tenant]) }}"
                            class="inline-flex h-10 items-center justify-center rounded-lg px-3 font-medium text-slate-700 transition hover:bg-slate-100 hover:text-slate-950 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-500"
                        >
                            {{ __('memory.layout.account') }}
                        </a>
                        <a
                            href="{{ route('filament.dashboard.pages.dashboard', ['tenant' => $tenant]) }}"
                            class="inline-flex h-10 items-center justify-center rounded-lg border border-slate-300 px-3 font-medium text-slate-700 transition hover:border-primary-400 hover:text-primary-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-500"
                        >
                            {{ __('memory.layout.dashboard') }}
                        </a>
                    </div>
                </div>

                <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                    <div class="flex flex-col gap-1">
                        <p class="text-xs font-semibold uppercase text-slate-500">{{ __('memory.layout.workspace') }}</p>
                        <p class="text-base font-semibold text-slate-950">{{ $tenant->name }}</p>
                    </div>

                    <nav class="flex flex-wrap gap-2" aria-label="{{ __('memory.layout.navigation_aria') }}">
                        <a
                            href="{{ route('memories.timeline', ['tenant' => $tenant]) }}"
                            @class([
                                'inline-flex h-10 items-center rounded-lg px-3 text-sm font-medium transition focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-500',
                                'bg-primary-600 text-white' => request()->routeIs('memories.timeline'),
                                'text-slate-700 hover:bg-slate-100 hover:text-slate-950' => ! request()->routeIs('memories.timeline'),
                            ])
                        >
                            {{ __('memory.layout.timeline') }}
                        </a>
                        <a
                            href="{{ route('memories.profile.setup', ['tenant' => $tenant]) }}"
                            @class([
                                'inline-flex h-10 items-center rounded-lg px-3 text-sm font-medium transition focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-500',
                                'bg-primary-600 text-white' => request()->routeIs('memories.profile.setup'),
                                'text-slate-700 hover:bg-slate-100 hover:text-slate-950' => ! request()->routeIs('memories.profile.setup'),
                            ])
                        >
                            {{ __('memory.layout.memory_profile') }}
                        </a>
                    </nav>
                </div>
            </div>
        </header>

        <main class="grow">
            {{ $slot }}
        </main>

        @include('components.layouts.partials.tail', ['skipCookieContentBar' => true])
    </div>
    <x-impersonate::banner/>
</body>
</html>
