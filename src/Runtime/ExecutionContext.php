<?php

declare(strict_types=1);

namespace Tinywan\Mcp\Runtime;

use Tinywan\Mcp\Contracts\CancellationTokenInterface;
use Tinywan\Mcp\Contracts\ProgressReporterInterface;
use Tinywan\Mcp\Protocol\ClientCapabilities;
use Tinywan\Mcp\Protocol\ClientInfo;
use Tinywan\Mcp\Security\Principal;

final readonly class ExecutionContext
{
    public function __construct(
        public Principal $principal,
        public string $traceId,
        public string $protocolVersion,
        public ?ClientInfo $clientInfo,
        public ClientCapabilities $clientCapabilities,
        public Deadline $deadline,
        public CancellationTokenInterface $cancellation = new NullCancellationToken(),
        public ProgressReporterInterface $progress = new NullProgressReporter(),
    ) {}
}
