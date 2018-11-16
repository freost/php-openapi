<?php

/**
 * @copyright Copyright (c) 2018 Carsten Brandt <mail@cebe.cc> and contributors
 * @license https://github.com/cebe/php-openapi/blob/master/LICENSE
 */

namespace cebe\openapi\spec;

use cebe\openapi\exceptions\ReadonlyPropertyException;
use cebe\openapi\SpecObjectInterface;

/**
 * A container for the expected responses of an operation.
 *
 * @link https://github.com/OAI/OpenAPI-Specification/blob/3.0.2/versions/3.0.2.md#responsesObject
 */
class Responses implements SpecObjectInterface, \ArrayAccess
{
    private $_responses = [];
    private $_errors = [];


    /**
     * Create an object from spec data.
     * @param array $data spec data read from YAML or JSON
     * @throws \cebe\openapi\exceptions\TypeErrorException in case invalid data is supplied.
     */
    public function __construct(array $data)
    {
        foreach ($data as $statusCode => $response) {
            // From Spec: This field MUST be enclosed in quotation marks (for example, "200") for compatibility between JSON and YAML.
            $statusCode = (string) $statusCode;
            if (preg_match('~^(?:default|[1-5](?:[0-9][0-9]|XX))$~', $statusCode)) {
                $this->_responses[$statusCode] = new Response($response);
            } else {
                $this->_errors[] = "Responses: $statusCode is not a valid HTTP status code.";
            }
        }
    }

    /**
     * @param string $statusCode HTTP status code
     * @return bool
     */
    public function hasResponse($statusCode): bool
    {
        return isset($this->_responses[$statusCode]);
    }

    /**
     * @param string $statusCode HTTP status code
     * @return PathItem
     */
    public function getResponse($statusCode): ?Response
    {
        return $this->_responses[$statusCode] ?? null;
    }

    /**
     * @return Response[]
     */
    public function getResponses(): array
    {
        return $this->_responses;
    }

    /**
     * Validate object data according to OpenAPI spec.
     * @return bool whether the loaded data is valid according to OpenAPI spec
     * @see getErrors()
     */
    public function validate(): bool
    {
        $valid = true;
        foreach ($this->_responses as $key => $response) {
            if ($response === null) {
                continue;
            }
            if (!$response->validate()) {
                $valid = false;
            }
        }
        return $valid && empty($this->_errors);
    }

    /**
     * @return string[] list of validation errors according to OpenAPI spec.
     * @see validate()
     */
    public function getErrors(): array
    {
        $errors = [$this->_errors];
        foreach ($this->_responses as $response) {
            if ($response === null) {
                continue;
            }
            $errors[] = $response->getErrors();
        }
        return array_merge(...$errors);
    }

    /**
     * Whether a offset exists
     * @link http://php.net/manual/en/arrayaccess.offsetexists.php
     * @param mixed $offset An offset to check for.
     * @return boolean true on success or false on failure.
     * The return value will be casted to boolean if non-boolean was returned.
     */
    public function offsetExists($offset)
    {
        return $this->hasResponse($offset);
    }

    /**
     * Offset to retrieve
     * @link http://php.net/manual/en/arrayaccess.offsetget.php
     * @param mixed $offset The offset to retrieve.
     * @return mixed Can return all value types.
     */
    public function offsetGet($offset)
    {
        return $this->getResponse($offset);
    }

    /**
     * Offset to set
     * @link http://php.net/manual/en/arrayaccess.offsetset.php
     * @param mixed $offset The offset to assign the value to.
     * @param mixed $value The value to set.
     * @throws ReadonlyPropertyException because spec objects are read-only.
     */
    public function offsetSet($offset, $value)
    {
        throw new ReadonlyPropertyException('Setting read-only property: ' . \get_class($this) . '::' . $offset);
    }

    /**
     * Offset to unset
     * @link http://php.net/manual/en/arrayaccess.offsetunset.php
     * @param mixed $offset The offset to unset.
     * @throws ReadonlyPropertyException because spec objects are read-only.
     */
    public function offsetUnset($offset)
    {
        throw new ReadonlyPropertyException('Unsetting read-only property: ' . \get_class($this) . '::' . $offset);
    }
}