<x-layouts.student :title="$exam['title']">
    <a href="{{ route('research.dashboard') }}" class="text-sm text-blue-600 hover:text-blue-800">&larr; Dashboard</a>
    <h1 class="mb-1 mt-2 text-2xl font-semibold">{{ $exam['title'] }}</h1>
    <p class="mb-4 text-sm text-gray-600">{{ count($exam['questions']) }} soal pilihan ganda. Jawab semua soal sebelum mengirim.</p>

    @if ($errors->any())
        <div class="mb-4 rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{{ $errors->first() }}</div>
    @endif

    <form method="POST" action="{{ route('research.exams.submit', $exam['id']) }}" class="space-y-4">
        @csrf
        @foreach ($exam['questions'] as $index => $question)
            <fieldset class="rounded-lg border border-gray-200 bg-white p-4">
                <legend class="px-1 text-sm font-medium">{{ $question['number'] }}. {{ $question['question'] }}</legend>
                <input type="hidden" name="answers[{{ $index }}][question_id]" value="{{ $question['id'] }}">
                <div class="mt-2 space-y-1 text-sm">
                    @foreach ($question['options'] as $key => $text)
                        <label class="flex items-start gap-2">
                            <input type="radio" name="answers[{{ $index }}][option]" value="{{ $key }}" required>
                            <span><strong>{{ $key }}.</strong> {{ $text }}</span>
                        </label>
                    @endforeach
                </div>
            </fieldset>
        @endforeach
        <button type="submit" class="rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">Kirim jawaban</button>
    </form>
</x-layouts.student>
