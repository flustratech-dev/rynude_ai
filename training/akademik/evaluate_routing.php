<?php
require __DIR__.'/../../vendor/autoload.php';
$app = require_once __DIR__.'/../../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\ChatStreamingService;

$datasetPath = __DIR__ . '/skripsi_routing_dataset_super_massive.json';
$jsonStr = file_get_contents($datasetPath);
if (strpos($jsonStr, "\x00") !== false) {
    $jsonStr = mb_convert_encoding($jsonStr, 'UTF-8', 'UTF-16LE');
}
// Strip BOM if present
$jsonStr = preg_replace('/^[\xef\xbb\xbf]+/', '', $jsonStr);
$jsonStr = trim($jsonStr);
$dataset = json_decode($jsonStr, true);
if (!$dataset) {
    die("JSON ERROR: " . json_last_error_msg());
}

$tester = new class extends ChatStreamingService {
    public function __construct() {}
    
    // Override recentUserRequestText to just return the prompt for simple testing
    protected function recentUserRequestText(array $messages, int $take = 3, int $cap = 1500): string {
        return $messages[0]['content'];
    }

    public function evaluate(string $prompt) {
        $messages = [ ['role' => 'user', 'content' => $prompt] ];
        $recent = $prompt;
        
        if ($this->isDocumentQuestion($recent)) {
             return "PLAIN_CHAT";
        }
        
        if ($this->isSkripsiPipelineRequest($messages)) {
             if ($this->needsSkripsiClarification($messages)) {
                 return "PIPELINE_NEEDS_METHOD";
             } else {
                 if (preg_match('/\b(lanjut(?:kan)?|teruskan|sambung|berdasarkan|tadi|diskusi)\b/i', $recent)) {
                      return "PIPELINE_CONTINUATION";
                 }
                 return "PIPELINE_READY";
             }
        }
        
        if ($this->isTitleSuggestionRequest($recent)) {
             return "TITLE_SUGGESTION";
        }
        
        if (preg_match('/\b(buat(?:kan)?|bikin(?:kan)?|susun(?:kan)?|rancang(?:kan)?|minta|beri(?:kan)?|kasih|contoh|butuh|tulis(?:kan)?)\s+(?:saya\s+|tolong\s+)*(?:berikan\s+)?(outline|kerangka|struktur|draft outline)\b/i', $recent) 
            && !preg_match('/\b(lanjut(?:kan)?|teruskan|berdasarkan|dari)\b/i', $recent)) {
             return "OUTLINE_REQUEST";
        }

        if ($this->wantsDocumentCreation($recent)) {
             if (preg_match('/\brevisi|perbaiki|tambahkan|ganti|perdalam|perpanjang|lengkapi|ubah\b/i', $recent)) {
                 return "DOCUMENT_REVISION";
             }
             if (preg_match('/\blanjutkan\b/i', $recent) && preg_match('/\bbab|selanjutnya|file|lampirkan|pdf|tulisan|upload\b/i', $recent)) {
                 return "UPLOAD_CONTINUATION";
             }
             return "DOCUMENT_CREATION_OTHER";
        }
        
        // Fallback default
        if (preg_match('/\b(lanjutkan)\b/i', $recent)) return "UPLOAD_CONTINUATION";
        return "UNKNOWN";
    }
};

$passed = 0;
$failed = 0;
$failedDetails = [];

foreach ($dataset as $idx => $case) {
    $result = $tester->evaluate($case['prompt']);
    if ($result === $case['expected_intent']) {
        $passed++;
    } else {
        $failed++;
        // Limit failed details to first 50 to avoid massive output
        if ($failed <= 50) {
            $failedDetails[] = "Prompt: '{$case['prompt']}'\n   Expected: {$case['expected_intent']}\n   Actual:   {$result}";
        }
    }
}

echo "TOTAL DATASET: " . count($dataset) . "\n";
echo "PASSED: $passed\n";
echo "FAILED: $failed\n";

if ($failed > 0) {
    echo "\nCONTOH KEGAGALAN (Top 50):\n";
    foreach ($failedDetails as $fail) {
        echo $fail . "\n\n";
    }
}
