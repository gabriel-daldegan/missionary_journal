<?php

namespace App\Services;

use App\Models\MemoryProfile;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class MemoryProfileService
{
    public const DEFAULT_LOCALE = 'en';

    /**
     * @var array<int, string>
     */
    public const SUPPORTED_LOCALES = [
        'en',
        'pt',
        'es',
    ];

    public function completeProfile(
        User $user,
        string $displayName,
        string $preferredLocale,
        ?string $missionContext = null,
    ): MemoryProfile {
        /** @var MemoryProfile $profile */
        $profile = $user->memoryProfile()->updateOrCreate([], [
            'display_name' => trim($displayName),
            'preferred_locale' => $this->normalizeLocale($preferredLocale),
            'mission_context' => $this->normalizeOptionalText($missionContext),
            'completed_at' => Carbon::now(),
        ]);

        $user->setRelation('memoryProfile', $profile);

        return $profile;
    }

    public function isComplete(User $user): bool
    {
        /** @var MemoryProfile|null $profile */
        $profile = $user->memoryProfile;

        if ($profile === null) {
            return false;
        }

        return $profile->completed_at !== null
            && filled($profile->display_name)
            && $this->isSupportedLocale($profile->preferred_locale);
    }

    public function preferredLocale(User $user): string
    {
        /** @var MemoryProfile|null $profile */
        $profile = $user->memoryProfile;

        if ($profile === null) {
            return self::DEFAULT_LOCALE;
        }

        return $this->normalizeLocale($profile->preferred_locale);
    }

    /**
     * @return array<int, string>
     */
    public function supportedLocales(): array
    {
        return self::SUPPORTED_LOCALES;
    }

    public function isSupportedLocale(?string $locale): bool
    {
        return in_array($locale, self::SUPPORTED_LOCALES, true);
    }

    public function normalizeLocale(?string $locale): string
    {
        if (! $this->isSupportedLocale($locale)) {
            return self::DEFAULT_LOCALE;
        }

        return $locale;
    }

    private function normalizeOptionalText(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim($value);

        if ($value === '') {
            return null;
        }

        return Str::limit($value, 500, '');
    }
}
