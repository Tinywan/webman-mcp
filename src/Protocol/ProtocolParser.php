<?php

declare(strict_types=1);

namespace Tinywan\Mcp\Protocol;

use InvalidArgumentException;
use JsonException;
use stdClass;
use Tinywan\Mcp\Protocol\Error\ProtocolErrors;
use Tinywan\Mcp\Protocol\Error\ProtocolException;

final class ProtocolParser
{
    private const VERSION_KEY = 'io.modelcontextprotocol/protocolVersion';

    private const CAPABILITIES_KEY = 'io.modelcontextprotocol/clientCapabilities';

    private const CLIENT_INFO_KEY = 'io.modelcontextprotocol/clientInfo';

    public function parse(string $json): ProtocolRequest
    {
        try {
            $message = $this->messageObject(json_decode(
                json: $json,
                associative: false,
                depth: 512,
                flags: JSON_THROW_ON_ERROR,
            ));
        } catch (JsonException) {
            throw new ProtocolException(ProtocolErrors::parse());
        }

        $id = $this->parseId($message);
        if (($message->jsonrpc ?? null) !== '2.0') {
            throw new ProtocolException(ProtocolErrors::invalidRequest($id, 'Invalid Request: jsonrpc must be "2.0".'));
        }

        $method = $this->stringProperty($message, 'method');
        if (!is_string($method) || $method === '') {
            throw new ProtocolException(ProtocolErrors::invalidRequest(
                $id,
                'Invalid Request: method must be a string.',
            ));
        }

        $paramsObject = $this->objectProperty($message, 'params');
        if ($paramsObject === null) {
            throw new ProtocolException(ProtocolErrors::invalidParams($id, 'Request params must be an object.'));
        }

        [$params, $protocolVersion, $capabilities, $clientInfo] = $this->parseParams($paramsObject, $id);

        return new ProtocolRequest($id, $method, $params, $protocolVersion, $capabilities, $clientInfo);
    }

    private function parseId(stdClass $message): ?RequestId
    {
        if (!property_exists($message, 'id')) {
            return null;
        }

        try {
            return RequestId::from($message->id);
        } catch (InvalidArgumentException) {
            throw new ProtocolException(ProtocolErrors::invalidRequest(
                null,
                'Invalid Request: id must be a string or integer.',
            ));
        }
    }

    /**
     * @return array{array<string, mixed>, string, ClientCapabilities, ClientInfo|null}
     */
    private function parseParams(stdClass $paramsObject, ?RequestId $id): array
    {
        $meta = $this->objectProperty($paramsObject, '_meta');
        if ($meta === null) {
            throw new ProtocolException(ProtocolErrors::invalidParams($id, 'Request params._meta must be an object.'));
        }

        $protocolVersion = $this->stringProperty($meta, self::VERSION_KEY);
        if ($protocolVersion === null || $protocolVersion === '') {
            throw new ProtocolException(ProtocolErrors::invalidParams($id, 'Request protocol version is required.'));
        }

        $capabilities = $this->objectProperty($meta, self::CAPABILITIES_KEY);
        if ($capabilities === null) {
            throw new ProtocolException(ProtocolErrors::invalidParams(
                $id,
                'Request client capabilities must be an object.',
            ));
        }

        $clientInfo = $this->parseClientInfo($meta, $id);

        return [
            $this->objectToArray($paramsObject),
            $protocolVersion,
            new ClientCapabilities($this->objectToArray($capabilities)),
            $clientInfo,
        ];
    }

    private function parseClientInfo(stdClass $meta, ?RequestId $id): ?ClientInfo
    {
        if (!property_exists($meta, self::CLIENT_INFO_KEY)) {
            return null;
        }

        $value = $this->objectProperty($meta, self::CLIENT_INFO_KEY);
        if ($value === null) {
            throw new ProtocolException(ProtocolErrors::invalidParams($id, 'Request clientInfo is invalid.'));
        }

        $name = $this->stringProperty($value, 'name');
        $version = $this->stringProperty($value, 'version');
        if ($name === null || $version === null) {
            throw new ProtocolException(ProtocolErrors::invalidParams(
                $id,
                'Request clientInfo name and version must be strings.',
            ));
        }

        if (property_exists($value, 'title') && $value->title !== null && !is_string($value->title)) {
            throw new ProtocolException(ProtocolErrors::invalidParams(
                $id,
                'Request clientInfo title must be a string.',
            ));
        }

        return new ClientInfo($name, $version, $this->stringProperty($value, 'title'));
    }

    /**
     * @return array<string, mixed>
     */
    private function objectToArray(stdClass $value): array
    {
        try {
            $encoded = json_encode($value, JSON_THROW_ON_ERROR);

            return $this->decodedObjectArray(json_decode(
                json: $encoded,
                associative: true,
                depth: 512,
                flags: JSON_THROW_ON_ERROR,
            ));
        } catch (JsonException) {
            throw new ProtocolException(ProtocolErrors::invalidRequest());
        }
    }

    private function messageObject(mixed $value): stdClass
    {
        if (!$value instanceof stdClass) {
            throw new ProtocolException(ProtocolErrors::invalidRequest());
        }

        return $value;
    }

    private function objectProperty(stdClass $object, string $property): ?stdClass
    {
        $properties = get_object_vars($object);
        if (!array_key_exists($property, $properties) || !$properties[$property] instanceof stdClass) {
            return null;
        }

        return $properties[$property];
    }

    private function stringProperty(stdClass $object, string $property): ?string
    {
        $properties = get_object_vars($object);
        if (!array_key_exists($property, $properties) || !is_string($properties[$property])) {
            return null;
        }

        return $properties[$property];
    }

    /**
     * @return array<string, mixed>
     */
    private function decodedObjectArray(mixed $value): array
    {
        if (!is_array($value)) {
            throw new ProtocolException(ProtocolErrors::invalidRequest());
        }

        /** @var array<string, mixed> $value */
        return $value;
    }
}
