<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $conversation->title ?? 'Shared chat' }} · {{ config('app.name', 'rynude') }}</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css'])
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/styles/github-dark.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/highlight.min.js"></script>
    <script>
        if (localStorage.getItem('theme') === 'dark' ||
            (!localStorage.getItem('theme') && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        }
    </script>
</head>
<body class="font-sans antialiased text-stone-900 dark:text-stone-200 bg-[#F9F8F6] dark:bg-stone-900">
    <header class="border-b border-[#E5E5E5] dark:border-stone-800 bg-white/80 dark:bg-stone-900/80 backdrop-blur sticky top-0 z-10">
        <div class="max-w-3xl mx-auto px-5 py-3 flex items-center justify-between">
            <div class="flex items-center gap-2.5">
                <div class="w-7 h-7 rounded-md bg-[#D97757] flex items-center justify-center text-white font-semibold text-sm">R</div>
                <span class="font-semibold">{{ config('app.name', 'rynude') }}</span>
            </div>
            <a href="{{ route('home') }}" class="text-sm px-3.5 py-1.5 rounded-lg bg-[#D97757] text-white hover:bg-[#c96544] transition-colors">Try {{ config('app.name', 'rynude') }}</a>
        </div>
    </header>

    <main class="max-w-3xl mx-auto px-5 py-8">
        <h1 class="text-2xl font-semibold mb-1">{{ $conversation->title ?? 'Shared chat' }}</h1>
        <p class="text-sm text-stone-500 mb-8">Shared conversation · read only</p>

        <div class="space-y-7">
            @forelse ($conversation->messages as $msg)
                @if ($msg->role === 'user')
                    <div class="flex justify-end">
                        <div class="max-w-[85%] bg-[#F0EEE6] dark:bg-stone-800 rounded-2xl px-4 py-3 text-[15px] whitespace-pre-wrap">{{ $msg->content }}</div>
                    </div>
                @else
                    <div class="flex gap-3">
                        <div class="w-7 h-7 shrink-0 rounded-md bg-[#D97757] flex items-center justify-center text-white font-semibold text-xs mt-0.5">R</div>
                        <div class="prose prose-stone dark:prose-invert max-w-none text-[15px] leading-relaxed">
                            {!! \Illuminate\Support\Str::markdown($msg->content ?? '') !!}
                        </div>
                    </div>
                @endif
            @empty
                <p class="text-stone-500">This conversation is empty.</p>
            @endforelse
        </div>

        <footer class="mt-12 pt-6 border-t border-[#E5E5E5] dark:border-stone-800 text-center text-xs text-stone-400">
            This is a read-only snapshot shared from {{ config('app.name', 'rynude') }}.
        </footer>
    </main>

    <script>
        document.querySelectorAll('pre code').forEach((b) => hljs.highlightElement(b));
    </script>
</body>
</html>
