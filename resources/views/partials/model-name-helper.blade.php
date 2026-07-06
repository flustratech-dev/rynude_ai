{{--
    Shared model display-name mapping. Defines a single global helper so every
    view renders the branded "rynude" names consistently without duplicating the
    logic. Display-only — internal model codes are never changed.

    Resolution order:
      1. DB-backed model list (from localStorage cache, or a list passed in) → name
      2. Static fallback for the 6 local GGUF codes
      3. Raw code as a last resort (never blank)

    Usage in any Alpine expression:  x-text="rynudeModelName(row.model)"
    Include once per page:           @include('partials.model-name-helper')
--}}
<script>
    window.rynudeModelName = window.rynudeModelName || function (code, models) {
        if (!code) return '';
        if (!models) {
            try {
                models = JSON.parse(localStorage.getItem('rynude_models_cache') || '[]')
                    .concat(JSON.parse(localStorage.getItem('rynude_more_models_cache') || '[]'));
            } catch (e) {
                models = [];
            }
        }
        var m = (models || []).find(function (x) { return x && x.code === code; });
        if (m && m.name) return m.name;
        var fallback = {
            'qwen-2.5-0.5b': 'rynude Vignette',
            'qwen-2.5-1.5b': 'rynude Lyric',
            'llama-3.2-3b': 'rynude Stanza',
            'mistral-7b-v0.3': 'rynude Canto',
            'llama-3.1-8b': 'rynude Symphony',
            'qwen-2.5-14b': 'rynude Magnum',
        };
        return fallback[code] || code;
    };
</script>
