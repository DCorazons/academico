<x-layouts.research-admin title="Enrollment">
    <h1 class="mb-4 text-2xl font-semibold">Enrollment course #{{ $courseId }}</h1>
    <p class="mb-4 text-sm text-gray-600">{{ count($enrollments) }} mahasiswa</p>
    <div class="overflow-x-auto rounded-lg border border-gray-200 bg-white">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50 text-left text-gray-600">
                <tr>
                    <th class="px-4 py-2">#</th>
                    <th class="px-4 py-2">NIM</th>
                    <th class="px-4 py-2">Nama</th>
                    <th class="px-4 py-2">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach ($enrollments as $enrollment)
                    <tr>
                        <td class="px-4 py-2">{{ $enrollment['id'] }}</td>
                        <td class="px-4 py-2">{{ $enrollment['student']['idnumber'] }}</td>
                        <td class="px-4 py-2">{{ $enrollment['student']['firstname'] }} {{ $enrollment['student']['lastname'] }}</td>
                        <td class="px-4 py-2"><span class="rounded bg-green-50 px-2 py-0.5 text-green-700">{{ $enrollment['status']['name'] }}</span></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</x-layouts.research-admin>
