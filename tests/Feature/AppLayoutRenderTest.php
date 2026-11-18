<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Lecturer;
use App\Models\Student;
use App\Models\Supervisor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AppLayoutRenderTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_layout_renders_navigation_profile_and_javascript_hooks(): void
    {
        $admin = Admin::create([
            'name' => 'Layout Test Admin',
            'email' => 'layout-admin@example.test',
            'password' => Hash::make('password'),
        ]);

        $response = $this->actingAs($admin, 'admin')->get('/admin/dashboard');

        $response->assertOk()
            ->assertSee('Layout Test Admin')
            ->assertSee('System Administrator');

        $html = $response->getContent();
        $this->assertNavigationLink($html, '/admin/dashboard', 'System Overview', true);
        $this->assertNavigationLink($html, '/admin/students', 'Manage Students');
        $this->assertStringNotContainsString('id="notif-wrap"', $html);
        $this->assertLayoutJavascriptElements($html, false);
    }

    public function test_student_layout_renders_navigation_notifications_profile_and_relationships(): void
    {
        $lecturer = Lecturer::create([
            'name' => 'Layout Test Lecturer',
            'email' => 'layout-lecturer@example.test',
            'password' => Hash::make('password'),
            'faculty' => 'Test Faculty',
        ]);
        $supervisor = Supervisor::create([
            'name' => 'Layout Test Supervisor',
            'email' => 'layout-supervisor@example.test',
            'password' => Hash::make('password'),
            'must_change_password' => false,
        ]);
        $student = Student::create([
            'lecturer_id' => $lecturer->lecturer_id,
            'supervisor_id' => $supervisor->supervisor_id,
            'matric_no' => 'LAYOUTRENDER001',
            'name' => 'Layout Test Student',
            'email' => 'layout-student@example.test',
            'password' => Hash::make('password'),
            'phone_no' => '0000000000',
            'course' => 'Test Course',
        ]);

        $response = $this->actingAs($student, 'student')->get('/student/dashboard');

        $response->assertOk()
            ->assertSee('Layout Test Student')
            ->assertSee('Logged in as Student')
            ->assertSee('Layout Test Lecturer')
            ->assertSee('Layout Test Supervisor');

        $html = $response->getContent();
        $this->assertNavigationLink($html, '/student/dashboard', 'Dashboard', true);
        $this->assertNavigationLink($html, '/student/logbooks', 'Logbook');
        $this->assertNotificationMarkup($html, 'student');
        $this->assertLayoutJavascriptElements($html, true);
    }

    public function test_lecturer_layout_renders_navigation_notifications_profile_and_javascript_hooks(): void
    {
        $lecturer = Lecturer::create([
            'name' => 'Layout Test Lecturer',
            'email' => 'layout-lecturer-render@example.test',
            'password' => Hash::make('password'),
            'faculty' => 'Test Faculty',
        ]);

        $response = $this->actingAs($lecturer, 'lecturer')->get('/lecturer/dashboard');

        $response->assertOk()
            ->assertSee('Layout Test Lecturer')
            ->assertSee('Faculty Evaluator');

        $html = $response->getContent();
        $this->assertNavigationLink($html, '/lecturer/dashboard', 'Dashboard', true);
        $this->assertNavigationLink($html, '/lecturer/messages', 'Messages');
        $this->assertNotificationMarkup($html, 'lecturer');
        $this->assertLayoutJavascriptElements($html, true);
    }

    public function test_supervisor_layout_renders_navigation_notifications_profile_and_javascript_hooks(): void
    {
        $supervisor = Supervisor::create([
            'name' => 'Layout Test Supervisor',
            'email' => 'layout-supervisor-render@example.test',
            'password' => Hash::make('password'),
            'must_change_password' => false,
        ]);

        $response = $this->actingAs($supervisor, 'supervisor')->get('/supervisor/dashboard');

        $response->assertOk()
            ->assertSee('Layout Test Supervisor')
            ->assertSee('Industry Supervisor');

        $html = $response->getContent();
        $this->assertNavigationLink($html, '/supervisor/dashboard', 'Supervisor Dashboard', true);
        $this->assertNavigationLink($html, '/supervisor/tasks', 'Tasks');
        $this->assertNotificationMarkup($html, 'supervisor');
        $this->assertLayoutJavascriptElements($html, true);
    }

    private function assertNavigationLink(string $html, string $href, string $label, bool $active = false): void
    {
        $nav = $this->sidebarNavigation($html);
        $this->assertStringContainsString('href="'.$href.'"', $nav);
        $this->assertStringContainsString($label, $nav);

        if (! $active) {
            return;
        }

        $pattern = '/<a\b(?=[^>]*href="'.preg_quote($href, '/').'")[^>]*aria-current="page"[^>]*>(.*?)<\/a>/s';
        $matched = preg_match($pattern, $nav, $matches);

        $this->assertSame(1, $matched, "Expected {$href} to be the active navigation link.");
        $this->assertStringContainsString('bg-white/15', $matches[0] ?? '');
        $this->assertStringContainsString($label, strip_tags($matches[1] ?? ''));
    }

    private function sidebarNavigation(string $html): string
    {
        $matched = preg_match('/<nav\b(?=[^>]*id="sidebar-nav")[^>]*>(.*?)<\/nav>/s', $html, $matches);

        $this->assertSame(1, $matched, 'Expected the layout sidebar navigation.');

        return $matches[1] ?? '';
    }

    private function assertNotificationMarkup(string $html, string $role): void
    {
        foreach ([
            'id="notif-wrap"',
            'id="notif-bell"',
            'id="notif-badge"',
            'id="notif-panel"',
            'id="notif-panel-sub"',
            'id="notif-panel-readall"',
            'id="notif-panel-list"',
            'aria-haspopup="true"',
            'aria-controls="notif-panel"',
            'data-count-url="/'.$role.'/notifications/unread-count"',
            'data-list-url="/'.$role.'/notifications"',
            'data-read-all-url="/'.$role.'/notifications/read-all"',
            'href="/'.$role.'/notifications"',
            'Mark all as read',
            'View all notifications',
        ] as $expected) {
            $this->assertStringContainsString($expected, $html);
        }

        $badgeMatched = preg_match('/<span\b[^>]*id="notif-badge"[^>]*>0<\/span>/s', $html, $badge);
        $panelMatched = preg_match('/<div\b[^>]*id="notif-panel"[^>]*>/s', $html, $panel);

        $this->assertSame(1, $badgeMatched, 'Expected the notification badge markup.');
        $this->assertStringContainsString('hidden', $badge[0] ?? '');
        $this->assertSame(1, $panelMatched, 'Expected the notification dropdown markup.');
        $this->assertStringContainsString('hidden', $panel[0] ?? '');
    }

    private function assertLayoutJavascriptElements(string $html, bool $hasNotifications): void
    {
        $ids = [
            'sidebar-overlay',
            'sidebar',
            'sidebar-close',
            'sidebar-nav',
            'sidebar-toggle',
            'page-title',
            'profile-wrap',
            'profile-btn',
            'profile-menu',
            'page-content',
            'nav-progress',
        ];

        if ($hasNotifications) {
            $ids = array_merge($ids, [
                'notif-wrap',
                'notif-bell',
                'notif-badge',
                'notif-panel',
                'notif-panel-list',
                'notif-panel-sub',
                'notif-panel-readall',
            ]);
        }

        foreach ($ids as $id) {
            $this->assertStringContainsString('id="'.$id.'"', $html, "Expected JavaScript hook #{$id}.");
        }

        $this->assertStringContainsString("overlay.id = 'nav-spinner'", $html);
        $this->assertStringContainsString("getElementById('cm-root')", $html);
    }
}
