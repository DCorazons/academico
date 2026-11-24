<x-layouts.research-admin title="Course">
    <h1 class="mb-4 text-2xl font-semibold">Course</h1>
    <div class="overflow-x-auto rounded-lg border border-gray-200 bg-white">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50 text-left text-gray-600">
                <tr>
                    <th class="px-4 py-2">#</th>
                    <th class="px-4 py-2">Nama</th>
                    <th class="px-4 py-2">Periode</th>
                    <th class="px-4 py-2">Pengajar</th>
                    <th class="px-4 py-2">Jadwal</th>
                    <th class="px-4 py-2">Kuota</th>
                    <th class="px-4 py-2"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach ($courses as $course)
                    <tr>
                        <td class="px-4 py-2">{{ $course['id'] }}</td>
                        <td class="px-4 py-2">{{ $course['name'] }}</td>
                        <td class="px-4 py-2">{{ $course['period']['name'] }}</td>
                        <td class="px-4 py-2">{{ $course['teacher']['firstname'] }} {{ $course['teacher']['lastname'] }}</td>
                        <td class="px-4 py-2">{{ $course['start_date'] }} s.d. {{ $course['end_date'] }}</td>
                        <td class="px-4 py-2">{{ $course['spots'] }} ({{ $course['volume'] }} jam)</td>
                        <td class="px-4 py-2"><a href="{{ route('research.enrollments', $course['id']) }}" class="text-blue-600 hover:text-blue-800">Enrollment</a></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $page->links() }}</div>
</x-layouts.research-admin>
