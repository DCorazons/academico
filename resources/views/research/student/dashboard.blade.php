<x-layouts.student title="Dashboard">
    @if (session('status'))
        <div class="mb-4 rounded-md border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">{{ session('status') }}</div>
    @endif
    <h1 class="mb-4 text-2xl font-semibold">Course saya</h1>
    <div class="grid gap-4 md:grid-cols-2">
        @foreach ($courses as $course)
            <div class="rounded-lg border border-gray-200 bg-white p-4">
                <h2 class="font-semibold">{{ $course['name'] }}</h2>
                <a href="{{ route('research.materials', $course['id']) }}" class="mt-1 inline-block text-sm text-blue-600 hover:text-blue-800">{{ $course['materials_count'] }} materi</a>
                <ul class="mt-3 space-y-2 text-sm">
                    @foreach ($course['exams'] as $exam)
                        <li class="flex items-center justify-between">
                            <span>{{ $exam['title'] }}</span>
                            @if ($exam['submitted'])
                                <span class="rounded bg-green-50 px-2 py-0.5 text-green-700">Sudah dikerjakan</span>
                            @else
                                <a href="{{ route('research.exams.show', $exam['id']) }}" class="rounded bg-blue-600 px-2 py-0.5 text-white hover:bg-blue-700">Kerjakan</a>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </div>
        @endforeach
    </div>
</x-layouts.student>
