@if (config('app.recaptcha_enabled'))

    <div wire:ignore>
        <input type="text" wire:model="recaptcha" x-on:captcha-success.window="$wire.recaptcha = $event.detail.token" hidden>
        <div class="my-4">
            {!! htmlFormSnippet([
                "callback" => "onRecaptchaSuccess"
            ]) !!}
        </div>
    </div>

    @error('g-recaptcha-response')
        <span class="text-xs text-red-500" role="alert">
            {{ $message }}
        </span>
    @enderror

@endif

@push('tail')
    <script>

        function onRecaptchaSuccess(token) {
            Livewire.dispatch('captcha-success', { token: token });
        }

        document.addEventListener('livewire:initialized', () => {
            Livewire.on('reset-recaptcha', (event) => {
                // wait .5 seconds before resetting the recaptcha
                setTimeout(() => {
                    grecaptcha.reset();
                }, 500);
            });
        });

    </script>
@endpush

@push('tail')
    {!! htmlScriptTagJsApi() !!}
@endpush
