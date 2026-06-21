<div class="h-full bg-[#F9F8F6] dark:bg-stone-900 flex flex-col justify-center items-center px-6 py-6 overflow-y-auto">
    <div class="max-w-3xl w-full flex flex-col items-center justify-center">
        {{-- Video Container --}}
        <div class="w-full max-w-[560px] aspect-[1.6] rounded-3xl border border-stone-200 dark:border-stone-800 overflow-hidden shadow-[0_8px_30px_rgba(0,0,0,0.02)] bg-white dark:bg-stone-950 flex items-center justify-center relative">
            <video 
                class="w-full h-full object-cover" 
                src="{{ asset('video/desktop-cowork-tab.mp4') }}" 
                autoplay 
                muted 
                loop 
                playsinline
            ></video>
        </div>

        {{-- Heading --}}
        <h2 class="text-[26px] font-serif text-[#2D2825] dark:text-stone-100 font-medium tracking-tight text-center mt-6 mb-2 leading-tight">
            Hand off complex tasks
        </h2>

        {{-- Description --}}
        <p class="text-[14.5px] text-stone-500 dark:text-stone-400 max-w-[480px] text-center leading-relaxed mb-6">
            Describe what you need and come back to polished work. Rynude works across your files and connected tools, on the schedule you set.
        </p>

        {{-- Button --}}
        <button class="px-5 py-2.5 bg-[#191919] hover:bg-black dark:bg-stone-100 dark:text-stone-900 dark:hover:bg-white text-white rounded-xl text-[14px] font-medium transition-colors shadow-sm focus:outline-none">
            Open in desktop app
        </button>
    </div>
</div>
