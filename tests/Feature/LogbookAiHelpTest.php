<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class LogbookAiHelpTest extends TestCase
{
    public function test_ai_help_returns_generated_text(): void
    {
        putenv('GEMINI_API_KEY=test-key');

        Http::fake([
            'https://generativelanguage.googleapis.com/v1/models/gemini-2.5-flash:generateContent*' => Http::response([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [
                                ['text' => 'A polished logbook entry draft.'],
                            ],
                        ],
                    ],
                ],
            ], 200),
        ]);

        $response = $this->post('/student/logbook/ai-help', [
            'week_no' => 3,
            'activity_date' => '2026-06-10',
            'description' => 'I worked on the student dashboard UI.',
        ]);

        $response->assertOk();
        $response->assertJson([
            'text' => 'A polished logbook entry draft.',
        ]);
    }

    public function test_ai_help_uses_lightweight_draft_settings(): void
    {
        putenv('GEMINI_API_KEY=test-key');

        Http::fake(function ($request) {
            // Mock the v1 endpoint for gemini-2.5-flash
            if (strpos((string) $request->url(), 'gemini-2.5-flash') === false) {
                return Http::response(['error' => 'Wrong model'], 400);
            }

            $body = $request->data();
            $prompt = $body['contents'][0]['parts'][0]['text'];

            $this->assertStringContainsString('short draft', strtolower($prompt));
            $this->assertSame(80, $body['generationConfig']['maxOutputTokens']);
            $this->assertSame(0.2, $body['generationConfig']['temperature']);

            return Http::response([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [
                                ['text' => 'A concise note draft.'],
                            ],
                        ],
                    ],
                ],
            ], 200);
        });

        $response = $this->post('/student/logbook/ai-help', [
            'description' => 'I observed the workflow and updated the form.',
        ]);

        $response->assertOk();
    }

    public function test_ai_help_falls_back_when_gemini_returns_quota_error(): void
    {
        putenv('GEMINI_API_KEY=test-key');

        Http::fake([
            'https://generativelanguage.googleapis.com/v1/models/gemini-2.5-flash:generateContent*' => Http::response([
                'error' => [
                    'code' => 429,
                    'message' => 'You exceeded your current quota.',
                ],
            ], 429),
        ]);

        $response = $this->post('/student/logbook/ai-help', [
            'week_no' => 5,
            'activity_date' => '2026-06-15',
            'description' => 'I tested the fallback flow.',
        ]);

        $response->assertOk();
        $response->assertJson([
            'fallback' => true,
        ]);
        $this->assertStringContainsString('Gemini is currently unavailable, so a local draft was created instead.', $response->json('message'));
        $this->assertStringContainsString('During week 5, on 2026-06-15', $response->json('text'));
    }
}
