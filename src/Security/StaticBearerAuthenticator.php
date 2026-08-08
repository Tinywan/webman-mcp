<?php

declare(strict_types=1);

namespace Tinywan\Mcp\Security;

use Throwable;
use Tinywan\Mcp\Contracts\AuthenticatorInterface;
use Tinywan\Mcp\Contracts\BearerTokenVerifierInterface;
use Tinywan\Mcp\Transport\HttpRequestContext;

final readonly class StaticBearerAuthenticator implements AuthenticatorInterface
{
    public function __construct(
        private BearerTokenVerifierInterface $verifier,
        private BearerTokenParser $parser = new BearerTokenParser(),
    ) {}

    public function authenticate(HttpRequestContext $request): Principal
    {
        try {
            $token = $this->parser->parse($request->header('authorization'));
            $principal = $this->verifier->verify($token);
        } catch (Throwable) {
            throw new AuthenticationException('Bearer authentication failed.');
        }

        if ($principal === null) {
            throw new AuthenticationException('Bearer authentication failed.');
        }

        return $principal;
    }
}
