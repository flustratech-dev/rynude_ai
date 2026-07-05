# Implementation Plan — RTK Native + Token Usage UI

---

## BAGIAN 1: RTK Native Backend Integration

### ✅ Phase 1 — Core Engine (SELESAI)
| File | Status | Keterangan |
|---|---|---|
| `app/Services/AI/OutputCompressor.php` | **✅ Done** | Class PHP murni 330 baris, 6 kategori command |
| `app/Services/AI/AgentTools.php` | **✅ Done** | Injeksi di `runBash()` + `runGitOperation()` + `summarize()` |

**Hasil:** Kompresi otomatis aktif untuk semua user tanpa install apapun. Estimasi hemat 60–90% token pada command verbose (npm, phpunit, dll).

---

## BAGIAN 2: Token Usage UI (FITUR BARU)

### Analisis Kondisi Saat Ini

**Yang sudah ada:**
- ✅ Model `TokenUsage` — menyimpan `user_id`, `model`, `provider`, `input_tokens`, `output_tokens`, `usage_date` (per hari)
- ✅ `TokenUsage::record()` — sudah dipanggil di semua provider (Anthropic, Google, Mistral, OpenAI)
- ✅ `CostTracker` — pricing data per model (USD/1M token)
- ✅ Tab "Billing" di settings modal — **ada tapi sangat basic** (hanya progress bar + daftar per model tanpa detail)
- ✅ `tokensUsed`, `tokensLimit`, `tokenBreakdown` — variabel Alpine.js sudah ada di `settingsState()`

**Yang perlu dibangun:**
- ❌ API endpoint `/api/token-usage` — belum ada, data billing belum di-feed ke frontend
- ❌ Visualisasi chart trend 7/30 hari
- ❌ Breakdown per provider
- ❌ Estimasi biaya dalam USD per model
- ❌ Statistik penghematan RTK (saved tokens)
- ❌ Selector range waktu (Today / 7 hari / 30 hari / All time)

---

### Phase 2 — API Backend (BARU)

#### [MODIFY] `app/Http/Controllers/SettingsController.php`

Tambah method baru `tokenUsage()` yang mengembalikan data analytics:

```php
GET /api/token-usage?range=7d   →  {
  summary: { total_input, total_output, total_tokens, estimated_cost_usd, rtk_saved_pct },
  by_model: [
    { model, provider, input_tokens, output_tokens, total_tokens, cost_usd, days_active }
  ],
  daily_trend: [
    { date: "2026-07-01", total_tokens: 12000, cost_usd: 0.045 }
  ]
}
```

**Parameter:** `range` = `today` | `7d` | `30d` | `all`

#### [MODIFY] `routes/api.php` (atau `web.php`)
Tambah 1 route:
```
GET /api/token-usage  →  SettingsController@tokenUsage
```

---

### Phase 3 — UI Enhancement (BARU)

#### [MODIFY] `resources/views/livewire/settings-modal.blade.php`

Upgrade tab **"Billing"** (yang sudah ada, id: `billing`) dari tampilan sederhana menjadi **analytics dashboard lengkap**:

**Layout Tab Billing Baru:**

```
┌─────────────────────────────────────────────────────────────────┐
│ 💡 Token Usage & Cost Analytics                 [7D ▾] [Refresh]│
├─────────────────────────────────────────────────────────────────┤
│ ┌───────────────┐  ┌───────────────┐  ┌───────────────────────┐ │
│ │ Total Tokens  │  │ Est. Cost     │  │ RTK Savings           │ │
│ │ 1,247,382     │  │ $3.84 USD     │  │ ~480K tokens saved    │ │
│ │ last 7 days   │  │ last 7 days   │  │ ≈ $1.44 saved         │ │
│ └───────────────┘  └───────────────┘  └───────────────────────┘ │
├─────────────────────────────────────────────────────────────────┤
│  Daily Trend (bar chart — pure CSS, no lib dependency)          │
│                                                                 │
│  Jul 1    Jul 2    Jul 3    Jul 4    Jul 5    Jul 6    Jul 7    │
│  ████     ██       █████    ███      ████     ██████   ███      │
│  120K     45K      180K     90K      130K     200K     80K      │
├─────────────────────────────────────────────────────────────────┤
│  Per Model Breakdown                                            │
│                                                                 │
│  Model              Provider    Input      Output    Cost USD   │
│  ─────────────────────────────────────────────────────────────  │
│  claude-sonnet-4-6  Anthropic   842,000    180,000   $5.38      │
│  gpt-4o             OpenAI      120,000    34,000    $0.96      │
│  gemini-2.0-flash   Google      85,000     40,000    $0.34      │
│                                                                 │
│  [+ bar visualization per model]                                │
├─────────────────────────────────────────────────────────────────┤
│  Token Quota Progress                                           │
│  ████████████████████░░░░░░░░░░  62% — 1.2M of 2M used         │
└─────────────────────────────────────────────────────────────────┘
```

**Fitur UI:**
- **Range selector** — Today / 7 hari / 30 hari / Semua waktu
- **3 stat cards** — Total tokens, Estimated cost, RTK savings (% hemat)
- **Bar chart trend harian** — Pure CSS bars (tidak perlu Chart.js), animasi saat load
- **Tabel per model** — Sortable, dengan mini progress bar per baris
- **Quota progress bar** — Existing feature, dipertahankan

**Tech:** Alpine.js (sudah ada), pure CSS bars, fetch ke `/api/token-usage`

---

### Phase 4 — RTK Savings Tracking 

Agar angka "RTK Savings" bisa ditampilkan secara akurat, kita perlu menyimpan data kompresi.

#### [MODIFY] `database/migrations/` — Tambah kolom ke `token_usages`

```php
// Migration baru: add_compression_stats_to_token_usages_table
$table->integer('rtk_saved_chars')->default(0);   // chars yg dibuang
$table->integer('rtk_original_chars')->default(0); // chars sebelum kompresi
```

#### [MODIFY] `app/Models/TokenUsage.php`
Tambah kolom baru ke `$fillable`.


### Phase 5 — RTK Savings Tracking 

update README.md 
beritahu kepada user bahwa sistem ini ada RTK nya lalu berikan gambaran diagram/flowchart mekanisme dari digaram 
jelaskan fitur RTK ini secara detail yang di gunkan di sitem ini 
---

## Ringkasan Semua Phase

| Phase | Deskripsi | Status | Prioritas |
|---|---|---|---|
| 1 | OutputCompressor.php + AgentTools.php | ✅ Selesai | — |
| 2 | API endpoint `/api/token-usage` | 🔲 Belum | Tinggi |
| 3 | UI upgrade tab Billing di settings modal | 🔲 Belum | Tinggi |
| 4 | RTK savings tracking di DB | 🔲 Belum | Opsional |

---

## Verification Plan

### Backend
```bash
php artisan route:list | grep token-usage
php -l app/Http/Controllers/SettingsController.php
```

### Frontend
- Buka Settings → Tab Billing
- Ganti range selector → data berubah
- Semua model yang pernah dipakai muncul di tabel

---

## Open Questions

> [!IMPORTANT]
> **Apakah ingin Phase 4 (RTK savings tracking ke DB)?**
> Ya, data harus akurat per sesi 

> [!NOTE]
> **Range default saat tab Billing dibuka** — Mau default ke "7 hari terakhir" atau "Today"? jawaban saya:7 hari terakhir 

