<?php

declare(strict_types=1);

namespace Iniznet\Mahout\Kernel;

use Iniznet\Mahout\Kernel\Contracts\QuerySource;
use Iniznet\Mahout\Kernel\Exception\SpanNotStarted;

/**
 * The facts of one request, and the one place that writes to the server log.
 *
 * @phpstan-type ContextValue string|int|float|bool|\Throwable|\UnitEnum|null
 * @phpstan-type Context array<string, ContextValue>
 */
final class Diagnostics
{
    /** @var list<Record> */
    private array $records = [];

    /** @var array<string, array{at: float, queries: int}> */
    private array $spans = [];

    /** @var array<string, int> */
    private array $spanQueries = [];

    /** @var array<string, true> */
    private array $knownHooks = [];

    private int $sequence = 0;

    public function __construct(
        private readonly Environment $environment,
        private readonly QuerySource $queries,
    ) {
    }

    public function queryCount(): int
    {
        return $this->queries->count();
    }

    public function queryCountSince(string $span): int
    {
        return $this->spanQueries[$span] ?? 0;
    }

    /**
     * The query text, when the site enabled SAVEQUERIES and the environment is
     * a development one. Empty otherwise, so a production request never keeps
     * query text in memory.
     *
     * @return list<string>
     */
    public function queries(): array
    {
        if (!$this->environment->exposesErrors()) {
            return [];
        }

        return $this->queries->statements();
    }

    public function start(string $span): void
    {
        $this->spans[$span] = [
            'at' => \microtime(true),
            'queries' => $this->queries->count(),
        ];
    }

    public function stop(string $span): void
    {
        $started = $this->spans[$span] ?? null;

        if (null === $started) {
            throw SpanNotStarted::forName($span);
        }

        unset($this->spans[$span]);

        $seconds = \microtime(true) - $started['at'];

        $queries = $this->queries->count() - $started['queries'];
        $this->spanQueries[$span] = $queries;

        $this->log(
            level: Level::Debug,
            message: 'span '.$span,
            context: ['span' => $span, 'seconds' => $seconds, 'queries' => $queries],
        );
    }

    /** @return list<Record> */
    public function records(): array
    {
        return $this->records;
    }

    /**
     * The records after a cursor, so a test or a tail can read only what is new.
     *
     * @return list<Record>
     */
    public function since(int $cursor): array
    {
        return \array_slice($this->records, $cursor);
    }

    /**
     * Attach the development-only validator to core's all hook.
     *
     * Nothing attaches when wp_is_development_mode( 'theme' ) is false; the
     * all listener fires on every hook and is measurable overhead with no
     * production benefit.
     *
     * @param list<string> $known every hook name the site declares
     */
    public function watchHooks(array $known): void
    {
        if (!$this->environment->developmentMode) {
            return;
        }

        $this->knownHooks = \array_fill_keys($known, true);

        add_action(Hooks::ALL, $this->observe(...), accepted_args: 1);
    }

    /**
     * Validate one observed hook name. Unknown names are warnings, never
     * exceptions; a plugin may legitimately fire a name we do not know.
     */
    public function hook(string $name, HookKind $kind): void
    {
        if (!$this->environment->developmentMode) {
            return;
        }

        if (\array_key_exists($name, $this->knownHooks)) {
            return;
        }

        /** @var array<string, string|int|float|bool|\Throwable|\UnitEnum|null> $context */
        $context = ['hook' => $name, 'kind' => $kind];

        $file = $this->caller();
        if (null !== $file) {
            $context['file'] = $file;
        }

        $this->log(level: Level::Warning, message: 'unknown hook', context: $context);
    }

    /**
     * Record one entry and return its support reference.
     *
     * The reference is what the log line, the Error Surface, an admin notice
     * and a CLI failure all print, so one identifier ties what a user saw to
     * one record. It is opaque, per-request and unrelated to any user. This
     * method never throws: it is called from the error boundary that must render.
     *
     * @param Context $context
     */
    public function log(Level $level, string $message, array $context = []): string
    {
        $reference = $this->nextReference();

        $this->records[] = new Record(
            level: $level,
            message: $message,
            context: $context,
            reference: $reference,
        );

        if ($level->atLeast($this->environment->logThreshold())) {
            error_log(\sprintf(
                '[mahout:%s] %s%s (%s)',
                $level->name,
                $message,
                $this->renderContext($context),
                $reference,
            ));
        }

        return $reference;
    }

    /**
     * Clear every per-request buffer so one test cannot see another's records.
     */
    public function reset(): void
    {
        $this->records = [];
        $this->spans = [];
        $this->spanQueries = [];
        $this->sequence = 0;
    }

    private function observe(string $name): void
    {
        $this->hook($name, HookKind::Action);
    }

    private function nextReference(): string
    {
        ++$this->sequence;

        return 'MH-'.\str_pad((string) $this->sequence, 4, '0', STR_PAD_LEFT);
    }

    /**
     * @param Context $context
     */
    private function renderContext(array $context): string
    {
        $parts = [];
        foreach ($context as $key => $value) {
            $parts[] = $key.'='.$this->renderValue($value);
        }

        return [] === $parts ? '' : ' '.\implode(' ', $parts);
    }

    private function renderValue(string|int|float|bool|\Throwable|\UnitEnum|null $value): string
    {
        if ($value instanceof \Throwable) {
            return $value::class;
        }

        if ($value instanceof \UnitEnum) {
            return $value->name;
        }

        if (null === $value) {
            return 'null';
        }

        if (\is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        return (string) $value;
    }

    /**
     * Best effort: the emitter's file is not part of the all-hook arguments,
     * so it is recovered from the trace, skipping this file and WordPress core.
     */
    private function caller(): ?string
    {
        $root = \defined('ABSPATH') ? \constant('ABSPATH') : null;

        foreach (debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS) as $frame) {
            $file = $frame['file'] ?? null;

            if (!\is_string($file) || __FILE__ === $file) {
                continue;
            }

            if (\is_string($root) && str_starts_with($file, $root)) {
                continue;
            }

            return $file;
        }

        return null;
    }
}
