<?php

namespace Tests\Feature;

use App\Models\Lecturer;
use App\Models\Student;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class LogbookAiHelpTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $lecturer = Lecturer::create([
            'name' => 'Test Lecturer',
            'email' => 'lecturer@example.test',
            'password' => Hash::make('password'),
            'faculty' => 'Computer Science',
        ]);
        $student = Student::create([
            'lecturer_id' => $lecturer->lecturer_id,
            'matric_no' => 'STU001',
            'name' => 'Test Student',
            'email' => 'student@example.test',
            'password' => Hash::make('password'),
            'phone_no' => '0123456789',
            'course' => 'Computer Science',
        ]);

        $this->actingAs($student, 'student');
        config([
            'services.gemini.key' => 'test-key',
            'services.gemini.api_key' => 'test-key',
            'services.gemini.model' => 'gemini-2.5-flash',
        ]);
    }

    public function test_ai_help_returns_generated_text(): void
    {
        Http::fake([
            'https://generativelanguage.googleapis.com/v1beta/models/*:generateContent*' => Http::response([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [
                                ['text' => "A polished logbook entry draft describing the student's completed work."],
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
            'text' => "A polished logbook entry draft describing the student's completed work.",
        ]);
    }

    public function test_ai_help_uses_current_draft_settings(): void
    {
        Http::fake(function ($request) {
            if (strpos((string) $request->url(), 'gemini-2.5-flash') === false) {
                return Http::response(['error' => 'Wrong model'], 400);
            }

            $body = $request->data();
            $prompt = $body['contents'][0]['parts'][0]['text'];

            $this->assertStringContainsString("Student's notes:", $prompt);
            $this->assertSame(450, $body['generationConfig']['maxOutputTokens']);
            $this->assertSame(0.5, $body['generationConfig']['temperature']);
            $this->assertSame(0, $body['generationConfig']['thinkingConfig']['thinkingBudget']);

            return Http::response([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [
                                ['text' => 'A concise note draft describing the work completed during the internship.'],
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
        Http::fake([
            'https://generativelanguage.googleapis.com/v1beta/models/*:generateContent*' => Http::response([
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
        $this->assertStringContainsString('AI quota is used up for now, so your notes were tidied into a draft instead.', $response->json('message'));
        $this->assertStringContainsString('Week 5', $response->json('text'));
        $this->assertStringContainsString('15 June 2026', $response->json('text'));
    }
}
