<?php

declare(strict_types=1);

it('uses strict types in every maintained PHP file', function (): void {
    foreach (maintained_php_files() as $file) {
        $contents = file_get_contents($file);

        expect($contents)->not->toBeFalse()->and($contents)->toMatch('/\A<\?php\R\Rdeclare\(strict_types=1\);/');
    }
});
