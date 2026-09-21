<?php

declare(strict_types=1);

namespace Fixture\Kernel\Violations;

error_log('booted'); // EXPECT: mahout.arch.errorLogOnlyInDiagnostics
