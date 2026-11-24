<x-layouts.student title="Materi">
    <a href="{{ route('research.dashboard') }}" class="text-sm text-blue-600 hover:text-blue-800">&larr; Dashboard</a>
    <h1 class="mb-4 mt-2 text-2xl font-semibold">Materi course #{{ $courseId }}</h1>
    <div class="space-y-4">
        @foreach ($materials as $material)
            <article class="rounded-lg border border-gray-200 bg-white p-4">
                <p class="text-xs text-gray-500">Pertemuan {{ $material['meeting'] }}</p>
                <h2 class="font-semibold">{{ $material['title'] }}</h2>
                <div class="mt-2 text-sm leading-relaxed text-gray-700">{!! nl2br(e($material['content'])) !!}</div>
            </article>
        @endforeach
    </div>
</x-layouts.student>
