<x-filament-widgets::widget>
    <x-filament::section>
        <div class="space-y-2" x-data="{
            referralLink: '{{ $this->getReferralLink() }}',
            copyToClipboard() {
                const input = this.$refs.referralInput;
                input.select();
                input.setSelectionRange(0, 99999);

                try {
                    if (navigator.clipboard && window.isSecureContext) {
                        navigator.clipboard.writeText(this.referralLink).then(() => {
                            this.showNotification();
                        });
                    } else {
                        document.execCommand('copy');
                        this.showNotification();
                    }
                } catch (err) {
                    document.execCommand('copy');
                    this.showNotification();
                }
            },
            showNotification() {
                new FilamentNotification()
                    .title('{{ __('Success!') }}')
                    .success()
                    .body('{{ __('Referral link copied to clipboard') }}')
                    .send();
            }
        }">
            <div>
                <label class="text-sm font-medium text-gray-700 dark:text-gray-300">
                    {{ __('Your Referral Link') }}
                </label>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                    {{ __('Share this link with your friends to earn rewards when they sign up') }}
                </p>
            </div>

            <div class="flex gap-2">
                <input
                    type="text"
                    x-model="referralLink"
                    readonly
                    x-ref="referralInput"
                    class="flex-1 block w-full rounded-lg border border-gray-200 px-3 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white sm:text-sm"
                />
                <x-filament::button
                    type="button"
                    color="primary"
                    x-on:click="copyToClipboard()"
                >
                    <x-filament::icon
                        icon="heroicon-o-clipboard"
                        class="h-5 w-5"
                    />
                    {{ __('Copy') }}
                </x-filament::button>
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
