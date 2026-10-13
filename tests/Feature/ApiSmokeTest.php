<?php

namespace Tests\Feature;

use Tests\TestCase;

class ApiSmokeTest extends TestCase
{
    public function test_game_catalog_lists_all_eighteen_games(): void
    {
        $response = $this->postJson('/api/data/games');

        $response->assertOk();
        $this->assertCount(18, $response->json());
    }

    public function test_unknown_whisper_event_is_rejected(): void
    {
        $this->postJson('/api/whisper', ['event' => 'NoSuchEvent'])->assertJson(['code' => 1]);
    }
}
