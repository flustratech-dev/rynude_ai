Paket 1 — Melengkapi dukungan file (kecil, 1-2 jam kerja)
Backend — 2 file diubah:

MistralProvider.php — salin blok penanganan attachment dari OpenAIProvider.php:213-252 (Mistral API kompatibel format image_url OpenAI; untuk model non-vision, kirim hanya hasil ekstraksi teks).
OpenAIProvider.php:238, AnthropicProvider, GoogleProvider — tambah cabang untuk text/plain, text/csv, text/markdown: baca langsung file_get_contents (batasi ~100KB) dan bungkus dengan penanda [Isi Dokumen lampiran: …] yang sama. Lebih rapi lagi: pindahkan seluruh logika "attachment → array content-parts" ke satu trait App\Services\AI\Concerns\ResolvesAttachments supaya empat provider tidak duplikat.
Frontend — 1 file diubah:

chat-interface.blade.php:273-279 — riwayat pesan hanya menampilkan file_name. Ubah: jika att.file_type diawali image/, render <img> thumbnail (perlu file_path ikut dikirim di payload show/send — sudah tersedia di ChatApiController.php:168-172, tinggal tambah url hasil Storage::url()).
Paket 2 — Regenerate + edit-branching + badge model + rating persist
Migration — 1 file baru database/migrations/xxxx_add_versioning_to_messages.php:

Schema::table('messages', function (Blueprint $table) {
    $table->string('model')->nullable();          // badge model per pesan
    $table->unsignedBigInteger('parent_id')->nullable()->index(); // branching
    $table->boolean('is_active_branch')->default(true)->index();
});
Konsep branching paling sederhana yang dipakai Claude: setiap pesan punya parent_id (pesan sebelumnya). Edit/regenerate membuat sibling (parent sama). Riwayat aktif = jalur dari root mengikuti anak dengan is_active_branch = true.

Backend — 3 file diubah, 0 file baru:

Message.php — tambah model, parent_id, is_active_branch ke $fillable; relasi siblings() (pesan lain dengan parent_id sama); helper statis activeThread(Conversation $c): array yang menelusuri jalur aktif.
ChatApiController.php:
send(): set parent_id pesan user = pesan aktif terakhir; terima parameter opsional edit_of (ID pesan user yang diedit) → nonaktifkan branch lama (is_active_branch=false untuk pesan itu dan turunannya), buat sibling baru.
Endpoint baru regenerate(Request $r, Conversation $c): terima message_id (pesan assistant) + model opsional → nonaktifkan pesan assistant itu, panggil ulang ChatStreamingService::stream() dengan history sampai pesan user induknya, model boleh berbeda. Kembalikan SSE yang sama dengan send.
Endpoint baru switchBranch(): terima message_id → set sibling tersebut aktif, sibling lain nonaktif (berikut turunannya), kembalikan thread aktif baru.
show() dan pembangunan $messages: ganti $conversation->messages → Message::activeThread($conversation), dan sertakan sibling_index/sibling_count per pesan untuk UI < 1/2 >.
ChatStreamingService.php:102-106 — saat Message::create assistant, isi model dan parent_id (ID pesan user terakhir). stream() perlu parameter tambahan ?int $parentUserMessageId.
routes/api.php — dua route baru: POST chats/{conversation}/regenerate, POST chats/{conversation}/switch-branch.
Rating persist: endpoint PATCH messages/{message}/rating (controller kecil atau method di ChatApiController), body {rating: 1|-1|null}.
Frontend — chat-interface.blade.php:

Baris aksi pesan assistant (~baris 327): tambah tombol Regenerate (ikon refresh) + dropdown model — panggil endpoint regenerate lalu jalankan pumpStream yang sama dengan sendMessage.
editMessage (baris 1046): ubah dari "salin ke input" menjadi inline edit — textarea menggantikan bubble, tombol Save → kirim ke send dengan edit_of, potong this.messages setelah index itu, stream jawaban baru.
Navigasi < 1/2 >: render jika msg.sibling_count > 1; klik panah → switchBranch → ganti this.messages dengan thread hasil respons.
rateMessage (baris 1042): tambah fetch PATCH ke endpoint rating.
Badge model: tampilkan msg.model kecil di bawah bubble assistant saat hover.
Paket 3 — Ketahanan streaming (background generation, resume, continue, retry)
Ini paket paling struktural. Intinya: generasi pindah dari "hidup di koneksi HTTP" ke "hidup di cache, dibaca oleh koneksi".

Backend — 1 file baru, 3 diubah:

Baru app/Services/StreamBuffer.php — kelas kecil di atas Cache (pakai file/redis store):
append(int $conversationId, array $event) — push event ke list stream_buf_{id}, TTL 10 menit.
read(int $conversationId, int $fromIndex): array — baca event dari offset.
status(int $conversationId): 'running'|'done'|'idle'.
ChatApiController.php send() — di dalam closure response()->stream(), tambahkan ignore_user_abort(true) dan tulis setiap event ke StreamBuffer sebelum echo. Dengan begitu walau echo gagal karena client putus, loop generator jalan terus sampai selesai dan pesan tetap tersimpan (penyimpanan DB memang sudah di dalam generator ChatStreamingService.php:102, jadi separuh pekerjaan sudah ada).
Endpoint baru GET chats/{conversation}/stream-resume?from={index} — SSE yang membaca StreamBuffer dari offset, polling tiap 200ms sampai event done. Route baru di routes/api.php.
Continue saat terpotong: di provider (OpenAIProvider, AnthropicProvider, GoogleProvider, MistralProvider), saat stream selesai baca finish_reason/stop_reason; jika length/max_tokens, yield array ['type' => 'truncated']. Di ChatStreamingService.php:77-82 guard is_string sudah ada — tambah cabang meneruskan event truncated dan ikutkan flag truncated: true di event done.
Retry/backoff: bungkus pembukaan koneksi HTTP di tiap provider (atau lebih rapi: satu method openStreamWithRetry() di trait bersama) — retry 3x dengan backoff 1s/2s/4s untuk status 429/500/502/503/529, yield event ['type' => 'status', 'data' => 'Model sibuk, mencoba lagi…'] supaya user melihat prosesnya.
Frontend:

sendMessage/pumpStream: simpan conversation_id + counter event yang sudah diterima ke sessionStorage; onerror/catch pada fetch stream → panggil stream-resume?from=N alih-alih menyerah.
Saat halaman dimuat (init): jika sessionStorage menandai stream aktif untuk conversation ini → langsung sambung ke stream-resume (jawaban "muncul lagi" setelah refresh, persis Claude).
Event truncated/done.truncated → tampilkan tombol "Lanjutkan" di bawah pesan → kirim send dengan pesan user Continue exactly where you left off. Do not repeat anything. (atau endpoint khusus yang menyembunyikan pesan sintetis ini dari UI).
Event status → tampilkan sebagai baris status kecil di bubble loading.
Paket 4 — Kepintaran: adaptasi prompt per model, tool-loop search + sitasi, simulated thinking, styles
4a. Adaptasi prompt per model — 2 file diubah:

ChatStreamingService.php:170 buildSystemPrompt() — di akhir, lewatkan hasilnya ke ModelAdapterRegistry::for($model)->adaptSystemPrompt($prompt).
Adapter yang sudah ada (Adapters/OpenAIAdapter.php, GoogleAdapter.php, MistralAdapter.php, AnthropicAdapter.php) — tambah method adaptSystemPrompt(): untuk model kecil/proxy (deteksi dari nama model) prepend blok "STRICT OUTPUT RULES" + 1 contoh few-shot format artifact; untuk model reasoning, ringkas instruksi gaya (mereka mengabaikan basa-basi). Kuncinya satu pintu: semua perbedaan per-model masuk adapter, ChatStreamingService tetap netral.
4b. Simulated thinking + toggle — 2 file diubah:

buildSystemPrompt(): jika thinking diaktifkan dan model bukan model reasoning natively → tambah instruksi "Sebelum menjawab, tulis penalaranmu di dalam <sim_thinking>…</sim_thinking> lalu jawab normal."
ChatStreamingService.php:67-91 loop stream: state-machine kecil yang mendeteksi tag <sim_thinking> pada aliran teks → potong dari $fullResponse dan yield sebagai ['type' => 'thinking'] — event yang sudah didukung frontend Anda, jadi UI tidak berubah sama sekali.
Frontend: toggle "Extended thinking" di dekat toggle web search (param thinking: true di body send); send() di controller meneruskannya ke stream().
4c. Tool-loop web search + sitasi:

Baru app/Services/AI/ChatToolLoop.php — loop maksimal 3 iterasi: definisikan tool web_search(query) dan fetch_url(url); model yang mendukung native tools (cek SupportsToolUse) pakai tool-call API, sisanya pakai pola ReAct teks yang sudah terbukti di RynudeCode (AgentRunner). Setiap iterasi yield event ['type' => 'tool', 'data' => ['name' => 'web_search', 'query' => …]] supaya UI bisa menampilkan "Mencari: …".
WebSearchService.php — tambah fetchUrl(string $url): string (fetch + strip tag + potong 8000 kata). Ini sekaligus menutup item "auto-fetch URL yang di-paste" — deteksi URL di pesan user, panggil method yang sama.
ChatStreamingService.php:236-246 — ganti blok pre-fetch: jika webSearch, jangan suntik hasil ke prompt; jalankan ChatToolLoop dulu, hasil akhirnya (konteks + daftar sumber) baru masuk ke generasi utama. Kumpulkan sumber jadi CitationDto (kelas sudah ada) dan yield ['type' => 'citations', 'data' => [...]] sebelum done.
Frontend: event tool → baris status "🔍 Mencari…"; event citations → render deretan chip sumber bernomor di bawah pesan, klik membuka URL; simpan di kolom baru messages.citations (JSON, masuk migration Paket 2 sekalian).
4d. Styles per-chat — kecil:

Kolom conversations.style (string, masuk migration mana pun), dropdown di samping model picker (Normal/Concise/Explanatory/Formal/Custom), dan satu blok di buildSystemPrompt() yang memetakan style → instruksi. 3 file disentuh: migration, controller update(), blade.
Paket 5 — Polish (independen satu sama lain, bisa dicicil)
Item	Perubahan
KaTeX	app.blade.php: tambah CDN KaTeX + auto-render; di renderContent() panggil renderMathInElement setelah parse markdown (lindungi $$…$$ dari marked dengan placeholder, pola yang sama dengan trik mermaid di baris 1060-1070)
Artifact versioning + update targeted	Migration: message_artifacts.version (int) + root_identifier; parseArtifact() deteksi atribut command="update" pada <antArtifact> → buat baris versi baru dengan konten = versi lama + find/replace blok <antOldContent>/<antNewContent>; instruksi format update masuk getBaseArtifactInstructions(); panel artifact tambah selector versi (dropdown, fetch artifacts/{id}?version=n)
Auto-scroll pintar	Di blade: flag userScrolledUp (event scroll container, threshold 80px dari bawah); typewriter hanya auto-scroll jika !userScrolledUp; tombol melayang "↓" muncul saat userScrolledUp && streaming
Quote-reply	Listener mouseup pada kontainer pesan → window.getSelection() → tombol mengambang "Tanyakan" → prepend > kutipan\n\n ke this.prompt
Sidebar	Rename inline (PATCH chats/{id} sudah ada di route!), star: kolom conversations.is_starred + section "Starred"; grouping waktu: murni client-side dari updated_at; full-text search: endpoint GET chats?q= dengan whereHas('messages', like)
Multiple artifacts	parseArtifact() → parseArtifacts() (loop regex, return array); loop MessageArtifact::create; panel: dropdown daftar artifact percakapan (endpoint artifacts?conversation_id= sudah bisa lewat index)
Sliding window berbasis token	ChatStreamingService.php:279: ganti historySize=200 dengan budget intdiv(strlen(json) , 4) vs limit per model (map kecil model => context_window di config)
User memory lintas chat	Tabel user_memories; job RefreshUserMemory meniru pola RefreshConversationMemory yang sudah ada; satu blok baru di buildSystemPrompt()
Keyboard shortcuts	Satu listener keydown global di layout: Ctrl+K → new chat, Esc → stop
Analysis tool	Terakhir & terbesar: sandbox iframe sandbox="allow-scripts" + Web Worker di frontend; tool run_javascript di ChatToolLoop; hasil eksekusi dikirim balik via endpoint kecil → masuk konteks iterasi berikutnya
Ringkasan urutan & dependensi
Paket 1 (kecil) → langsung bisa.
Paket 2 butuh migration messages — kerjakan migration-nya sekaligus dengan kolom citations (4c) dan conversations.style/is_starred supaya migrate sekali.
Paket 3 independen dari Paket 2, tapi tombol "Lanjutkan" enak digabung setelah regenerate ada.
Paket 4 bergantung Paket 3 untuk event status/tool di frontend (pola render yang sama).
Paket 5 bebas dicicil kapan saja.
Dua catatan sesuai kondisi proyek: setiap perubahan route/config perlu php artisan optimize:clear (startup menjalankan optimize), dan semua class CSS baru di blade harus memakai utility yang sudah ada di build Tailwind — kalau butuh class baru, pakai inline style atau rebuild Vite.