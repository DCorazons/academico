<x-layouts.research-admin title="Laporan course">
    <h1 class="mb-4 text-2xl font-semibold">Laporan course periode #{{ $periodId }}</h1>
    <div class="overflow-x-auto rounded-lg border border-gray-200 bg-white">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50 text-left text-gray-600">
                <tr>
                    <th class="px-4 py-2">#</th>
                    <th class="px-4 py-2">Course</th>
                    <th class="px-4 py-2 text-right">Enrollment</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach ($courses as $course)
                    <tr>
                        <td class="px-4 py-2">{{ $course['id'] }}</td>
                        <td class="px-4 py-2">{{ $course['name'] }}</td>
                        <td class="px-4 py-2 text-right">{{ $course['enrollments_count'] }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot class="bg-gray-50 font-medium">
                <tr>
                    <td class="px-4 py-2" colspan="2">Total</td>
                    <td class="px-4 py-2 text-right">{{ array_sum(array_column($courses, 'enrollments_count')) }}</td>
                </tr>
            </tfoot>
        </table>
    </div>
</x-layouts.research-admin>
