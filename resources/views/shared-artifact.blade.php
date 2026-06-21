<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $artifact->title ?? 'Artifact' }} · {{ config('app.name', 'rynude') }}</title>

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
    @php
        $lang = strtolower($artifact->language ?? '');
        $isHtml = $lang === 'html';
        $isMarkdown = in_array($lang, ['markdown', 'md']) || $artifact->type !== 'code';
    @endphp

    <header class="border-b border-[#E5E5E5] dark:border-stone-800 bg-white/80 dark:bg-stone-900/80 backdrop-blur sticky top-0 z-10">
        <div class="max-w-4xl mx-auto px-5 py-3 flex items-center justify-between">
            <div class="flex items-center gap-2.5 min-w-0">
                <div class="w-7 h-7 rounded-md bg-[#D97757] flex items-center justify-center text-white font-semibold text-sm shrink-0">R</div>
                <span class="font-semibold truncate">{{ $artifact->title ?? 'Artifact' }}</span>
            </div>
            <a href="{{ route('home') }}" class="text-sm px-3.5 py-1.5 rounded-lg bg-[#D97757] text-white hover:bg-[#c96544] transition-colors shrink-0">Try {{ config('app.name', 'rynude') }}</a>
        </div>
    </header>

    <main class="max-w-4xl mx-auto px-5 py-8">
        @if ($isHtml)
            <iframe sandbox="allow-scripts allow-forms allow-popups"
                    class="w-full rounded-xl border border-[#E5E5E5] dark:border-stone-800 bg-white"
                    style="height: 80vh;"
                    srcdoc="{{ $artifact->content }}"></iframe>
        @elseif ($isMarkdown)
            <article class="prose prose-stone dark:prose-invert max-w-none">
                {!! \Illuminate\Support\Str::markdown($artifact->content ?? '', ['html_input' => 'strip']) !!}
            </article>
        @else
            <pre class="rounded-xl overflow-x-auto bg-[#1E1E1E] border border-stone-700 p-4 text-[13px]"><code class="language-{{ $lang ?: 'text' }}">{{ $artifact->content }}</code></pre>
        @endif

        <footer class="mt-10 pt-6 border-t border-[#E5E5E5] dark:border-stone-800 text-center text-xs text-stone-400">
            Published with {{ config('app.name', 'rynude') }}.
        </footer>
    </main>

    <script>
        document.querySelectorAll('pre code').forEach((b) => hljs.highlightElement(b));
    </script>
</body>
</html>
