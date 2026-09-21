<?php

declare(strict_types=1);

namespace Iniznet\Mahout\Kernel\Tests\Architecture;

use Iniznet\Mahout\Kernel\Tests\TestCase;

/**
 * The negative proofs. A fixture that resolves a collaborator without declaring
 * it must fail mahout.arch.noStaticServiceAccess; a fixture that calls
 * error_log() outside Diagnostics must fail mahout.arch.errorLogOnlyInDiagnostics;
 * the nearest legal neighbour must fail neither.
 *
 * The fixtures are analysed by no green gate. This test runs the shared rules
 * over them with PHPStan and asserts the identifiers exactly.
 *
 * @internal
 */
final class ArchitectureRuleProofTest extends TestCase
{
    /**
     * @return array<string, list<string>>
     */
    private const EXPECTED = [
        'violations/ResolvesCollaboratorWithoutDeclaringIt.php' => ['mahout.arch.noStaticServiceAccess'],
        'violations/ErrorLogOutsideDiagnostics.php' => ['mahout.arch.errorLogOnlyInDiagnostics'],
        'clean/DeclaredCollaborator.php' => [],
    ];

    public function testTheFixturesEmitExactlyTheExpectedIdentifiers(): void
    {
        $observed = $this->analyseFixtures();

        foreach (self::EXPECTED as $relative => $expected) {
            $path = $this->root().'/fixtures/architecture/'.$relative;
            $actual = $observed[$this->normalise($path)] ?? [];

            sort($expected);
            sort($actual);

            self::assertSame($expected, $actual, 'Unexpected identifiers in '.$relative);
        }
    }

    /**
     * @return array<string, list<string>>
     */
    private function analyseFixtures(): array
    {
        $root = $this->root();
        $phpstan = $root.'/vendor/phpstan/phpstan/phpstan';
        self::assertFileExists($phpstan, 'PHPStan is not installed');

        $this->clearDirectory($root.'/.phpstan-fixtures-cache');

        $process = proc_open(
            [
                PHP_BINARY,
                $phpstan,
                'analyse',
                '-c',
                'phpstan-arch-fixtures.neon',
                '--no-progress',
                '--error-format=json',
                '--memory-limit=1G',
            ],
            [1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
            $pipes,
            $root,
        );
        self::assertIsResource($process, 'Could not start PHPStan');

        $stdout = (string) stream_get_contents($pipes[1]);
        stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        proc_close($process);

        $decoded = json_decode($stdout, true);
        self::assertIsArray($decoded, 'PHPStan produced no JSON report: '.$stdout);

        $observed = [];
        /** @var array<string, array{messages?: list<array{identifier?: string|null}>}> $files */
        $files = is_array($decoded['files'] ?? null) ? $decoded['files'] : [];
        foreach ($files as $path => $report) {
            $identifiers = [];
            foreach ($report['messages'] ?? [] as $message) {
                $identifier = $message['identifier'] ?? null;
                if (is_string($identifier) && str_starts_with($identifier, 'mahout.arch.')) {
                    $identifiers[] = $identifier;
                }
            }

            $observed[$this->normalise((string) $path)] = array_values(array_unique($identifiers));
        }

        return $observed;
    }

    private function root(): string
    {
        return \dirname(__DIR__, 2);
    }

    private function normalise(string $path): string
    {
        $path = (string) preg_replace('/ \(in context of .*\)$/', '', $path);
        $real = realpath($path);

        return str_replace('\\', '/', false === $real ? $path : $real);
    }

    private function clearDirectory(string $directory): void
    {
        if (!is_dir($directory)) {
            return;
        }

        $items = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST,
        );
        foreach ($items as $item) {
            if ($item->isDir()) {
                rmdir($item->getPathname());
            } else {
                unlink($item->getPathname());
            }
        }

        rmdir($directory);
    }
}
