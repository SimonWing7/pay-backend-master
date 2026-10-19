<?php

namespace Tests\Unit;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

/**
 * `php artisan view:cache` only COMPILES templates; a template can compile and
 * still fail the moment it is rendered (e.g. an @if written straight after a
 * word is left uncompiled, so its @endif becomes a syntax error). That broke
 * the payment-link email silently — emails are sent inside try/catch — so every
 * template is compiled AND syntax-checked here.
 */
class BladeTemplatesTest extends TestCase
{
    public function test_every_blade_template_compiles_to_valid_php(): void
    {
        $problems = [];
        $checked = 0;

        $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(resource_path('views')));

        foreach ($files as $file) {
            if (!str_ends_with($file->getFilename(), '.blade.php')) {
                continue;
            }

            $checked++;
            $compiled = Blade::compileString(file_get_contents($file->getPathname()));

            $tmp = tempnam(sys_get_temp_dir(), 'blade') . '.php';
            file_put_contents($tmp, $compiled);
            $output = (string) shell_exec(escapeshellarg(PHP_BINARY) . ' -l ' . escapeshellarg($tmp) . ' 2>&1');
            unlink($tmp);

            // Directives left uncompiled show up as a bare @if/@endif etc. in the
            // output (ignoring scripts and comments, where @ is legitimate).
            $outsideScripts = preg_replace('/<script.*?<\/script>|\{\{--.*?--\}\}/s', '', $compiled);
            $leftover = preg_match('/(?<![@\w])@(if|foreach|forelse|endif|endforeach|endforelse|else|elseif|unless|endunless)\b/', $outsideScripts);

            if (!str_contains($output, 'No syntax errors') || $leftover) {
                $problems[] = str_replace(resource_path('views') . '/', '', $file->getPathname());
            }
        }

        $this->assertGreaterThan(50, $checked, 'Expected to find the project\'s templates.');
        $this->assertSame([], $problems, 'These templates do not compile to valid PHP: ' . implode(', ', $problems));
    }
}
