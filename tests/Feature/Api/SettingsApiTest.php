<?php

namespace Tests\Feature\Api;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\TokenUsage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * API Layer Migration — Settings API.
 *
 * Covers the behaviour migrated out of App\Livewire\SettingsModal into
 * App\Http\Controllers\Api\SettingsApiController.
 */
class SettingsApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_show_returns_profile_preferences_api_key_presence_and_billing(): void
    {
        $user = User::factory()->create([
            'name' => 'Ada Lovelace',
            'custom_instructions' => 'Be concise.',
            'anthropic_api_key' => 'sk-ant-secretvalue1234567',
            'preferences' => [
                'nickname' => 'Ada',
                'theme' => 'dark',
                'accent_color' => '#5E72E4',
                'compact_mode' => true,
            ],
        ]);

        $response = $this->actingAs($user)->getJson('/api/settings');

        $response->assertOk()
            ->assertJsonPath('profile.name', 'Ada Lovelace')
            ->assertJsonPath('profile.nickname', 'Ada')
            ->assertJsonPath('profile.custom_instructions', 'Be concise.')
            ->assertJsonPath('preferences.theme', 'dark')
            ->assertJsonPath('preferences.accent_color', '#5E72E4')
            ->assertJsonPath('preferences.compact_mode', true)
            // Defaults are filled in for absent preference keys.
            ->assertJsonPath('preferences.language', 'en')
            ->assertJsonPath('preferences.cap_web_search', true)
            // API key presence is reported as a boolean; the secret is never exposed.
            ->assertJsonPath('api_keys.anthropic', true)
            ->assertJsonPath('api_keys.openai', false)
            ->assertJsonStructure([
                'billing' => ['plan', 'tokens_used', 'tokens_limit', 'tracked_tokens', 'token_breakdown'],
            ]);
    }

    public function test_show_never_leaks_raw_api_keys(): void
    {
        $user = User::factory()->create([
            'anthropic_api_key' => 'sk-ant-supersecret-value-001',
        ]);

        $response = $this->actingAs($user)->getJson('/api/settings');

        $response->assertOk();
        $this->assertStringNotContainsString('sk-ant-supersecret-value-001', $response->getContent());
    }

    public function test_update_saves_profile_direct_columns(): void
    {
        $user = User::factory()->create(['name' => 'Old Name']);

        $this->actingAs($user)->patchJson('/api/settings', [
            'name' => 'New Name',
            'custom_instructions' => 'Always cite sources.',
        ])->assertOk()
            ->assertJsonPath('profile.name', 'New Name')
            ->assertJsonPath('profile.custom_instructions', 'Always cite sources.');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'New Name',
            'custom_instructions' => 'Always cite sources.',
        ]);
    }

    public function test_update_saves_preferences_into_json_column(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->patchJson('/api/settings', [
            'theme' => 'dark',
            'language' => 'es',
            'compact_mode' => true,
            'accent_color' => '#11998E',
        ])->assertOk()
            ->assertJsonPath('preferences.theme', 'dark')
            ->assertJsonPath('preferences.language', 'es')
            ->assertJsonPath('preferences.compact_mode', true)
            ->assertJsonPath('preferences.accent_color', '#11998E');

        $user->refresh();
        $this->assertSame('dark', $user->preferences['theme']);
        $this->assertSame('es', $user->preferences['language']);
        $this->assertTrue($user->preferences['compact_mode']);
    }

    public function test_update_partial_payload_does_not_clobber_other_preferences(): void
    {
        $user = User::factory()->create([
            'preferences' => ['theme' => 'dark', 'language' => 'fr'],
        ]);

        $this->actingAs($user)->patchJson('/api/settings', [
            'theme' => 'light',
        ])->assertOk();

        $user->refresh();
        $this->assertSame('light', $user->preferences['theme']);
        // The untouched preference survives.
        $this->assertSame('fr', $user->preferences['language']);
    }

    public function test_update_stores_api_keys_and_reports_presence(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->patchJson('/api/settings', [
            'anthropic_api_key' => 'sk-ant-newkey1234567890',
            'use_proxy' => true,
            'proxy_base_url' => 'https://proxy.example.com/v1',
        ])->assertOk()
            ->assertJsonPath('api_keys.anthropic', true)
            ->assertJsonPath('api_keys.use_proxy', true)
            ->assertJsonPath('api_keys.proxy_base_url', 'https://proxy.example.com/v1');

        $user->refresh();
        // Stored (and decrypts back) correctly.
        $this->assertSame('sk-ant-newkey1234567890', $user->anthropic_api_key);
        $this->assertTrue((bool) $user->use_proxy);
    }

    public function test_update_validates_theme_enum(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->patchJson('/api/settings', [
            'theme' => 'neon',
        ])->assertStatus(422)
            ->assertJsonValidationErrors('theme');
    }

    public function test_update_validates_accent_color_format(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->patchJson('/api/settings', [
            'accent_color' => 'red',
        ])->assertStatus(422)
            ->assertJsonValidationErrors('accent_color');
    }

    public function test_update_validates_email_uniqueness(): void
    {
        $other = User::factory()->create(['email' => 'taken@example.com']);
        $user = User::factory()->create();

        $this->actingAs($user)->patchJson('/api/settings', [
            'email' => 'taken@example.com',
        ])->assertStatus(422)
            ->assertJsonValidationErrors('email');
    }

    public function test_update_allows_user_to_keep_their_own_email(): void
    {
        $user = User::factory()->create(['email' => 'me@example.com']);

        $this->actingAs($user)->patchJson('/api/settings', [
            'email' => 'me@example.com',
            'name' => 'Renamed',
        ])->assertOk();
    }

    public function test_billing_reflects_tracked_token_usage(): void
    {
        $user = User::factory()->create();

        TokenUsage::record($user->id, 'claude-haiku-4-5', 'anthropic', 100, 250);

        $this->actingAs($user)->getJson('/api/settings')
            ->assertOk()
            ->assertJsonPath('billing.tracked_tokens', 350)
            ->assertJsonPath('billing.tokens_used', 350)
            ->assertJsonPath('billing.token_breakdown.0.model', 'claude-haiku-4-5')
            ->assertJsonPath('billing.token_breakdown.0.total', 350);
    }

    public function test_billing_falls_back_to_character_estimate_when_no_tracked_usage(): void
    {
        $user = User::factory()->create();
        $conversation = Conversation::create(['user_id' => $user->id, 'title' => 'Chat']);
        Message::create([
            'conversation_id' => $conversation->id,
            'role' => 'assistant',
            'content' => str_repeat('a', 400), // 400 chars / 4 = 100 tokens
        ]);

        $this->actingAs($user)->getJson('/api/settings')
            ->assertOk()
            ->assertJsonPath('billing.tracked_tokens', 0)
            ->assertJsonPath('billing.tokens_used', 100);
    }

    public function test_validate_api_key_accepts_well_formed_anthropic_key(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->postJson('/api/settings/validate-api-key', [
            'provider' => 'anthropic',
            'key' => 'sk-ant-api03-abcdefghijklmnop',
        ])->assertOk()
            ->assertJsonPath('valid', true)
            ->assertJsonPath('provider', 'anthropic');
    }

    public function test_validate_api_key_rejects_malformed_key(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->postJson('/api/settings/validate-api-key', [
            'provider' => 'anthropic',
            'key' => 'not-a-real-key',
        ])->assertOk()
            ->assertJsonPath('valid', false);
    }

    public function test_validate_api_key_rejects_unknown_provider(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->postJson('/api/settings/validate-api-key', [
            'provider' => 'skynet',
            'key' => 'sk-whatever',
        ])->assertStatus(422)
            ->assertJsonValidationErrors('provider');
    }

    public function test_settings_endpoints_require_authentication(): void
    {
        $this->getJson('/api/settings')->assertUnauthorized();
        $this->patchJson('/api/settings', ['name' => 'x'])->assertUnauthorized();
        $this->postJson('/api/settings/validate-api-key', [])->assertUnauthorized();
    }
}
