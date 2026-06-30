<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $conversation->title ?? 'Shared chat' }} · {{ config('app.name', 'rynude') }}</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Lora:ital,wght@0,400;0,500;0,600;0,700;1,400;1,500;1,600;1,700&family=Playfair+Display:ital,wght@0,400;0,500;0,600;1,400;1,500&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">

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
<body class="font-sans antialiased text-stone-900 dark:text-stone-200 bg-[#F9F8F6] dark:bg-[#2C2C2A]">
    <header class="border-b border-[#E5E5E5] dark:border-stone-700 bg-white/80 dark:bg-[#2C2C2A]/80 backdrop-blur sticky top-0 z-10">
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
                        <div class="max-w-[85%] bg-[#F0EEE6] dark:bg-[#3A3A38] rounded-2xl px-4 py-3 text-[15px] whitespace-pre-wrap">{{ $msg->content }}</div>
                    </div>
                @else
                    <div class="flex gap-3">
                        <div class="w-7 h-7 shrink-0 rounded-md bg-[#D97757] flex items-center justify-center text-white font-semibold text-xs mt-0.5">R</div>
                        <div class="prose prose-stone dark:prose-invert max-w-none w-full text-[16px] leading-[1.6] font-claude-response prose-p:mt-0 prose-p:mb-3 prose-p:pl-2 prose-p:pr-8 [&_li>p]:my-0 [&_ul]:mt-0 [&_ol]:mt-0 [&_ul]:mb-3 [&_ol]:mb-3 prose-headings:font-sans prose-headings:font-semibold prose-headings:text-[#0B0B0B] dark:prose-headings:text-stone-100 prose-headings:mt-6 prose-headings:mb-3 prose-h1:text-2xl prose-h2:text-xl prose-h3:text-lg prose-ul:list-disc prose-ol:list-decimal prose-li:my-0 prose-li:pl-2 prose-ul:pl-5 prose-ol:pl-5 prose-pre:bg-[#1E1E1E] prose-pre:text-stone-200 prose-pre:rounded-xl prose-pre:shadow-sm prose-pre:border prose-pre:border-stone-700/50 prose-pre:p-4 prose-pre:my-4 prose-pre:overflow-x-auto prose-code:px-1.5 prose-code:py-0.5 prose-code:bg-stone-100 dark:prose-code:bg-stone-800 prose-code:text-[#0B0B0B] dark:prose-code:text-stone-200 prose-code:rounded-md prose-code:font-mono prose-code:text-[14px] prose-code:font-medium prose-code:before:content-none prose-code:after:content-none prose-a:text-[#D97757] hover:prose-a:text-[#c96646] prose-a:no-underline hover:prose-a:underline transition-colors prose-strong:font-semibold prose-strong:text-[#0B0B0B] dark:prose-strong:text-stone-100 prose-blockquote:border-l-4 prose-blockquote:border-stone-300 dark:prose-blockquote:border-stone-700 prose-blockquote:pl-4 prose-blockquote:italic prose-blockquote:text-stone-600 dark:prose-blockquote:text-stone-400 prose-table:w-full prose-table:border-collapse prose-table:my-4 prose-th:border prose-th:border-stone-300 dark:prose-th:border-stone-700 prose-th:px-4 prose-th:py-2 prose-th:bg-stone-100 dark:prose-th:bg-stone-800 prose-th:font-semibold prose-td:border prose-td:border-stone-300 dark:prose-td:border-stone-700 prose-td:px-4 prose-td:py-2 text-[#0B0B0B] dark:text-stone-200" style="font-family: 'Anthropic Serif', 'Lora', Georgia, serif;">
                            {!! \Illuminate\Support\Str::markdown($msg->content ?? '', ['html_input' => 'strip']) !!}
                        </div>
                    </div>
                @endif
            @empty
                <p class="text-stone-500">This conversation is empty.</p>
            @endforelse
        </div>

        <footer class="mt-12 pt-6 border-t border-[#E5E5E5] dark:border-stone-700 text-center text-xs text-stone-400">
            This is a read-only snapshot shared from {{ config('app.name', 'rynude') }}.
        </footer>
    </main>

    <script>
        document.querySelectorAll('pre code').forEach((b) => hljs.highlightElement(b));
    </script>
</body>
</html>
