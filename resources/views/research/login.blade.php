<x-layouts.student title="Masuk">
    <div class="mx-auto max-w-sm rounded-lg border border-gray-200 bg-white p-6">
        <h1 class="mb-4 text-lg font-semibold">Masuk</h1>
        <form method="POST" action="{{ route('research.login.store') }}" class="space-y-4">
            @csrf
            <div>
                <label for="username" class="block text-sm text-gray-700">Username</label>
                <input id="username" name="username" type="text" value="{{ old('username') }}" required autofocus class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2">
                @error('username')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="password" class="block text-sm text-gray-700">Password</label>
                <input id="password" name="password" type="password" required class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2">
            </div>
            <button type="submit" class="w-full rounded-md bg-blue-600 px-3 py-2 text-sm font-medium text-white hover:bg-blue-700">Masuk</button>
        </form>
    </div>
</x-layouts.student>
