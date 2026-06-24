<x-filament-panels::page>
    @php
        $summary = $this->report['summary'] ?? [];
        $tenants = $this->report['tenants'] ?? [];
    @endphp

    <div class="space-y-6">
        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Active validation tenants</p>
                <p class="mt-2 text-3xl font-semibold text-gray-950 dark:text-white">
                    {{ $summary['active_validation_tenants'] ?? 0 }}
                </p>
            </div>

            <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Completed profiles</p>
                <p class="mt-2 text-3xl font-semibold text-gray-950 dark:text-white">
                    {{ $summary['tenants_with_completed_profiles'] ?? 0 }}
                </p>
            </div>

            <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Activity over two weeks</p>
                <p class="mt-2 text-3xl font-semibold text-gray-950 dark:text-white">
                    {{ $summary['tenants_spanning_two_weeks'] ?? 0 }}
                </p>
            </div>

            <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Collaborator engagement</p>
                <p class="mt-2 text-3xl font-semibold text-gray-950 dark:text-white">
                    {{ $summary['tenants_with_collaborator_engagement'] ?? 0 }}
                </p>
            </div>
        </div>

        <div class="rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <div class="border-b border-gray-200 px-4 py-4 dark:border-gray-800">
                <h2 class="text-base font-semibold text-gray-950 dark:text-white">Tenant validation signals</h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Generated at {{ $summary['generated_at'] ?? 'not available' }}.
                </p>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-gray-800">
                    <thead class="bg-gray-50 dark:bg-gray-950">
                        <tr>
                            <th class="px-4 py-3 text-left font-semibold text-gray-700 dark:text-gray-300">Tenant</th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-700 dark:text-gray-300">Members</th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-700 dark:text-gray-300">Profiles</th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-700 dark:text-gray-300">Records</th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-700 dark:text-gray-300">Span</th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-700 dark:text-gray-300">Collaborators</th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-700 dark:text-gray-300">Media</th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-700 dark:text-gray-300">Email source</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @forelse ($tenants as $tenant)
                            <tr>
                                <td class="px-4 py-3 text-gray-950 dark:text-white">
                                    <div class="font-medium">{{ $tenant['tenant_name'] }}</div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">ID {{ $tenant['tenant_id'] }}</div>
                                </td>
                                <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ $tenant['member_count'] }}</td>
                                <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ $tenant['completed_profile_count'] }}</td>
                                <td class="px-4 py-3 text-gray-700 dark:text-gray-300">
                                    <div>{{ $tenant['record_count'] }}</div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">
                                        First: {{ $tenant['first_record_created_at'] ?? 'none' }}
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-gray-700 dark:text-gray-300">
                                    {{ $tenant['activity_span_days'] }} days
                                </td>
                                <td class="px-4 py-3 text-gray-700 dark:text-gray-300">
                                    <div>{{ $tenant['collaborator_participant_count'] }} participants</div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">
                                        {{ $tenant['collaborator_edit_count'] }} cross-edits
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-gray-700 dark:text-gray-300">
                                    {{ $tenant['media_attachment_count'] }} attachments
                                </td>
                                <td class="px-4 py-3 text-gray-700 dark:text-gray-300">
                                    {{ $tenant['email_source_record_count'] }} records
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td class="px-4 py-6 text-center text-gray-500 dark:text-gray-400" colspan="8">
                                    No tenant validation signals are available.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-filament-panels::page>
