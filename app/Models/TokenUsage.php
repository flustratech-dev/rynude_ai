<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TokenUsage extends Model
{
    protected $fillable = [
        'user_id',
        'model',
        'provider',
        'input_tokens',
        'output_tokens',
        'usage_date',
        'rtk_saved_chars',
        'rtk_original_chars',
    ];

    protected $casts = [
        'usage_date'          => 'date',
        'input_tokens'        => 'integer',
        'output_tokens'       => 'integer',
        'rtk_saved_chars'     => 'integer',
        'rtk_original_chars'  => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Record token usage for a user, aggregating into a single daily row per model.
     *
     * @param int    $userId
     * @param string $model
     * @param string|null $provider
     * @param int    $inputTokens
     * @param int    $outputTokens
     * @param int    $rtkSavedChars     Characters removed by RTK OutputCompressor (default 0)
     * @param int    $rtkOriginalChars  Original characters before RTK compression (default 0)
     */
    public static function record(
        int $userId,
        string $model,
        ?string $provider,
        int $inputTokens,
        int $outputTokens,
        int $rtkSavedChars = 0,
        int $rtkOriginalChars = 0
    ): void {
        if ($inputTokens <= 0 && $outputTokens <= 0) {
            return;
        }

        $row = static::firstOrNew([
            'user_id'    => $userId,
            'model'      => $model,
            'usage_date' => now()->toDateString(),
        ]);

        $row->provider           = $provider;
        $row->input_tokens       = ($row->input_tokens ?? 0) + $inputTokens;
        $row->output_tokens      = ($row->output_tokens ?? 0) + $outputTokens;
        $row->rtk_saved_chars    = ($row->rtk_saved_chars ?? 0) + $rtkSavedChars;
        $row->rtk_original_chars = ($row->rtk_original_chars ?? 0) + $rtkOriginalChars;
        $row->save();
    }
}
