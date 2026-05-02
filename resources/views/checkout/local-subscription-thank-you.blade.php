<x-layouts.focus-center>

    <x-slot name="title">
        {{ __('Thank You') }}
    </x-slot>

    <div class="mx-4">
        <div class="card max-w-3xl bg-base-100 shadow-xl mx-auto text-center">
            <div class="card-body">
                @svg('party', 'w-24 h-24 mx-auto text-primary-500 stroke-primary-500')
                <x-heading.h3 class="text-primary-900">
                    {{ __('We are thrilled to have you on board!') }}
                </x-heading.h3>
                <p>
                    {{ __('Thanks for joining us! It might take a few moments for your subscription to be activated.') }}
                </p>

                <x-button-link.primary href="{{ route('home') }}" class="mt-4 mx-auto">
                    {{ __('Start Your Journey') }}
                </x-button-link.primary>

            </div>
        </div>
    </div>

</x-layouts.focus-center>
