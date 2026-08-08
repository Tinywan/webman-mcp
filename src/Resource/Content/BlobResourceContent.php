<?php

declare(strict_types=1);

namespace Tinywan\Mcp\Resource\Content;

use InvalidArgumentException;

final readonly class BlobResourceContent implements ResourceContentInterface
{
    public function __construct(
        public string $uri,
        public string $blob,
        public ?string $mimeType = null,
    ) {
        if (base64_decode($blob, strict: true) === false) {
            throw new InvalidArgumentException('Resource blob content must be valid Base64.');
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $data = ['uri' => $this->uri, 'blob' => $this->blob];
        if ($this->mimeType !== null) {
            $data['mimeType'] = $this->mimeType;
        }

        return $data;
    }
}
