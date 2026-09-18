<?php

namespace Tests\Feature\Deployment;

use Tests\TestCase;

class SharedHostingReadinessTest extends TestCase
{
    public function test_public_htaccess_blocks_sensitive_files_and_directory_indexes(): void
    {
        $htaccess = file_get_contents(public_path('.htaccess'));

        $this->assertStringContainsString('Options -MultiViews -Indexes', $htaccess);
        $this->assertStringContainsString('RewriteRule ^\\.env - [F,L]', $htaccess);
        $this->assertStringContainsString('RewriteRule ^composer\\.(json|lock) - [F,L]', $htaccess);
        $this->assertStringContainsString('RewriteRule (^|/)\\.[^/]+ - [F,L]', $htaccess);
    }

    public function test_robots_txt_disallows_private_attachment_routes(): void
    {
        $robots = file_get_contents(public_path('robots.txt'));

        $this->assertStringContainsString('Disallow: /api/v1/attachments/', $robots);
        $this->assertStringContainsString('Disallow: /attachments/', $robots);
    }

    public function test_env_example_uses_shared_hosting_safe_defaults_without_real_secrets(): void
    {
        $env = file_get_contents(base_path('.env.example'));

        $this->assertStringContainsString('APP_ENV=production', $env);
        $this->assertStringContainsString('APP_DEBUG=false', $env);
        $this->assertStringContainsString('QUEUE_CONNECTION=sync', $env);
        $this->assertStringContainsString('SESSION_DRIVER=database', $env);
        $this->assertStringContainsString('CACHE_STORE=database', $env);
        $this->assertStringContainsString('DB_PASSWORD=', $env);
    }
}
