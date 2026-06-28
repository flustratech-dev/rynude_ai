<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'rynude') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Lora:ital,wght@0,400;0,500;0,600;0,700;1,400;1,500;1,600;1,700&family=Playfair+Display:ital,wght@0,400;0,500;0,600;1,400;1,500&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <!-- Highlight.js -->
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/styles/github-dark.min.css">
        <script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/highlight.min.js"></script>

        <!-- Livewire -->
        @livewireStyles

        <!-- Theme Initialization -->
        <script>
            // Force dark mode on /code route (Claude Code terminal page)
            var _isCodePage = window.location.pathname.startsWith('/code');

            function getTheme() {
                if (_isCodePage) return 'dark';
                if (localStorage.getItem('theme') === 'dark') return 'dark';
                if (localStorage.getItem('theme') === 'light') return 'light';
                return 'system';
            }

            function applyTheme(theme) {
                if (_isCodePage || theme === 'dark' || (theme === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                    document.documentElement.classList.add('dark');
                } else {
                    document.documentElement.classList.remove('dark');
                }
            }
            
            applyTheme(getTheme());
            
            window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', () => {
                if (getTheme() === 'system') applyTheme('system');
            });
            
            document.addEventListener('alpine:init', () => {
                Alpine.data('themeManager', () => ({
                    theme: getTheme(),
                    init() {
                        this.$watch('theme', value => {
                            if (!_isCodePage) {
                                localStorage.setItem('theme', value);
                                applyTheme(value);
                            }
                        });
                        
                        window.addEventListener('theme-changed', (e) => {
                            if (!_isCodePage) {
                                let newTheme = typeof e.detail === 'string' ? e.detail 
                                    : (e.detail && e.detail.theme ? e.detail.theme 
                                    : (Array.isArray(e.detail) ? e.detail[0] : e.detail));
                                if (typeof newTheme === 'string') {
                                    this.theme = newTheme;
                                }
                            }
                        });
                    },
                    setTheme(newTheme) {
                        if (!_isCodePage) this.theme = newTheme;
                    }
                }));

                // Global PDF Viewer Component for Artifacts
                Alpine.data('pdfViewer', (url) => ({
                    url: url,
                    loading: true,
                    error: false,
                    errorMsg: '',
                    
                    init() {
                        this.loadPdfJs(() => {
                            this.renderPdf();
                        });
                    },

                    loadPdfJs(callback) {
                        if (typeof window.pdfjsLib !== 'undefined') {
                            callback();
                            return;
                        }
                        if (window.pdfjsLoading) {
                            document.addEventListener('pdfjs-loaded', callback, { once: true });
                            return;
                        }
                        window.pdfjsLoading = true;
                        const script = document.createElement('script');
                        script.src = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js';
                        script.onload = () => {
                            window.pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';
                            window.pdfjsLib.GlobalWorkerOptions.standardFontDataUrl = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/standard_fonts/';
                            window.pdfjsLib.GlobalWorkerOptions.cMapUrl = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/cmaps/';
                            window.pdfjsLib.GlobalWorkerOptions.cMapPacked = true;
                            document.dispatchEvent(new Event('pdfjs-loaded'));
                            callback();
                        };
                        document.head.appendChild(script);
                    },
                    
                    async renderPdf() {
                        try {
                            const loadingTask = window.pdfjsLib.getDocument(this.url);
                            const pdf = await loadingTask.promise;
                            
                            for (let pageNum = 1; pageNum <= pdf.numPages; pageNum++) {
                                const page = await pdf.getPage(pageNum);
                                
                                // Calculate scale to match ~210mm width nicely on high DPI
                                const viewport = page.getViewport({scale: 1.5});
                                
                                const wrapper = document.createElement('div');
                                wrapper.className = 'w-full max-w-[210mm] mx-auto shadow-md bg-white border border-[#E5E5E5] dark:border-stone-700 relative overflow-hidden';
                                
                                const canvas = document.createElement('canvas');
                                const context = canvas.getContext('2d');
                                canvas.height = viewport.height;
                                canvas.width = viewport.width;
                                canvas.className = 'w-full h-auto block';
                                
                                wrapper.appendChild(canvas);
                                this.$refs.container.appendChild(wrapper);
                                
                                const renderContext = {
                                    canvasContext: context,
                                    viewport: viewport
                                };
                                await page.render(renderContext).promise;
                                
                                // Hide loading spinner as soon as the first page is fully rendered
                                if (pageNum === 1) {
                                    this.loading = false;
                                }
                            }
                            // Fallback if no pages (shouldn't happen)
                            this.loading = false;
                        } catch (err) {
                            this.loading = false;
                            this.error = true;
                            this.errorMsg = 'Gagal memuat pratinjau: ' + err.message;
                            console.error(err);
                        }
                    }
                }));
            });
        </script>
    </head>
    <body class="font-sans antialiased text-stone-900 dark:text-stone-200 bg-[#F9F8F6] dark:bg-stone-900" x-data="themeManager()">
        <div class="min-h-screen">
            @isset($header)
                <header class="bg-white shadow">
                    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                        {{ $header }}
                    </div>
                </header>
            @endisset

            <!-- Page Content -->
            <main>
                {{ $slot }}
            </main>
        </div>

        @livewire('quota-warning-modal')
        @livewire('system-update-modal')
        @livewireScripts

        <!-- Markdown & Code Block Setup -->
        <script>
            function initCodeBlocks() {
                document.querySelectorAll('pre code').forEach((block) => {
                    if (block.parentElement.classList.contains('code-styled')) return;
                    
                    const pre = block.parentElement;
                    pre.classList.add('code-styled', 'relative', 'rounded-xl', 'overflow-hidden', 'bg-[#1E1E1E]', 'my-4', 'border', 'border-stone-700', 'flex', 'flex-col');
                    
                    const lang = block.className.replace('language-', '').replace('hljs', '').trim() || 'text';
                    
                    const header = document.createElement('div');
                    header.className = 'flex items-center justify-between px-4 py-2 bg-[#2D2D2D] border-b border-stone-700 text-xs text-stone-400 font-sans';
                    
                    const langSpan = document.createElement('span');
                    langSpan.textContent = lang;
                    header.appendChild(langSpan);
                    
                    const copyBtn = document.createElement('button');
                    copyBtn.className = 'flex items-center gap-1.5 hover:text-stone-200 transition-colors';
                    copyBtn.innerHTML = `<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path></svg> Copy code`;
                    
                    copyBtn.addEventListener('click', () => {
                        navigator.clipboard.writeText(block.textContent);
                        const originalHtml = copyBtn.innerHTML;
                        copyBtn.innerHTML = `<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg> Copied!`;
                        setTimeout(() => { copyBtn.innerHTML = originalHtml; }, 2000);
                    });
                    
                    header.appendChild(copyBtn);
                    pre.insertBefore(header, block);
                    
                    block.classList.add('p-4', 'text-[13px]', 'overflow-x-auto', 'text-stone-200', 'bg-transparent');
                    hljs.highlightElement(block);
                });
            }

            document.addEventListener('DOMContentLoaded', initCodeBlocks);
            document.addEventListener('livewire:navigated', initCodeBlocks);
            document.addEventListener('livewire:init', () => {
                Livewire.hook('morph.updated', ({ el, component }) => {
                    initCodeBlocks();
                });

                // Copy content dispatched from server components (share links, artifacts)
                Livewire.on('copyToClipboard', (event) => {
                    const content = Array.isArray(event) ? event[0]?.content : event?.content;
                    if (content && navigator.clipboard) {
                        navigator.clipboard.writeText(content).catch(() => {});
                    }
                });

                // Add Ngrok bypass header to all Livewire requests to prevent iframe warning interception
                Livewire.hook('request', ({ options }) => {
                    if (!options.headers) options.headers = {};
                    options.headers['ngrok-skip-browser-warning'] = 'true';
                });

                // Prevent Livewire error modal (black box bug) on connection issues/ngrok interceptions
                Livewire.hook('commit', ({ fail }) => {
                    fail(({ status, preventDefault }) => {
                        preventDefault();
                        console.error('Livewire request failed with status:', status);
                        // Show a native alert instead of a broken black iframe modal
                        if (status === 500) {
                            alert("Server error occurred. Please check your database connection or backend logs.");
                        } else if (status === 504) {
                            alert("Request timeout. The AI might be taking too long to respond.");
                        } else {
                            alert("An error occurred during communication with the server (Status: " + status + "). Please try again.");
                        }
                    });
                });
            });
        </script>
    </body>
</html>
