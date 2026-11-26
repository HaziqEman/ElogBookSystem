<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class GuestViteAssetsTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_page_uses_vite_assets_and_keeps_its_form(): void
    {
        $response = $this->get('/');

        $response->assertOk()
            ->assertSee('UiTM E-Logbook Portal')
            ->assertSee('for="email"', false)
            ->assertSee('id="email"', false)
            ->assertSee('name="email"', false)
            ->assertSee('for="password"', false)
            ->assertSee('id="password"', false)
            ->assertSee('name="password"', false);

        $this->assertGuestViteAssets($response);
    }

    public function test_registration_page_uses_vite_assets_and_keeps_its_form(): void
    {
        $response = $this->get('/register');

        $response->assertOk()
            ->assertSee('Create your')
        ->assertSee("I'm registering", false)
            ->assertSee('name="role"', false)
            ->assertSee('Full name')
            ->assertSee('name="name"', false)
            ->assertSee('Email address')
            ->assertSee('name="email"', false)
            ->assertSee('Password')
            ->assertSee('name="password"', false)
            ->assertSee('Matric number')
            ->assertSee('Phone number')
            ->assertSee('Course')
            ->assertSee('Assign lecturer')
            ->assertSee('Faculty')
            ->assertSee('Create account');

        $this->assertGuestViteAssets($response);
    }

    private function assertGuestViteAssets(TestResponse $response): void
    {
        $html = $response->getContent();

        $this->assertMatchesRegularExpression(
            '/<link\b[^>]*href="[^"]*\/build\/assets\/app-[^"]+\.css"/',
            $html
        );
        $this->assertMatchesRegularExpression(
            '/<script\b[^>]*src="[^"]*\/build\/assets\/app-[^"]+\.js"/',
            $html
        );
        $this->assertStringNotContainsString(
            'cdn.jsdelivr.net/npm/@tailwindcss/browser@4',
            $html
        );
        $this->assertStringContainsString(
            'cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css',
            $html
        );
    }
}
