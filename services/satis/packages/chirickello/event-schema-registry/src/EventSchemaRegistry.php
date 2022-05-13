<?php

declare(strict_types=1);

namespace Chirickello\Package\EventSchemaRegistry;

use Chirickello\Package\EventSchemaRegistry\Exception\InvalidData;
use Chirickello\Package\EventSchemaRegistry\Exception\InvalidSchema;
use Chirickello\Package\EventSchemaRegistry\Exception\SchemaNotFound;
use JsonSchema\Constraints\Constraint;
use JsonSchema\Validator;

class EventSchemaRegistry
{
    private array $dirs = [];

    public function __construct()
    {
        $this->dirs[] = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'schemas';
    }

    public function addDirectory(string $dir): void
    {
        $this->dirs[] = $dir;
    }

    /**
     * @param object $data
     * @return void
     * @throws InvalidData
     * @throws InvalidSchema
     * @throws SchemaNotFound
     */
    public function check(object $data): void
    {
        $validator = new Validator();
        $event = property_exists($data, 'event') ? $data->event : null;
        if (!is_string($event)) {
            throw new InvalidData('event property must be string');
        }
        $parts = explode('.', $event, 2);
        if (count($parts) !== 2) {
            throw new InvalidData('event has not scope');
        }
        $version = property_exists($data, 'version') ? $data->version : 1;
        if (!is_int($version)) {
            throw new InvalidData('version property must be int');
        }
        [$scope, $name] = $parts;
        $this->validate($validator, $data, $this->getSchema($scope, $name, $version));
        $this->validate($validator, $data, $this->getGeneralSchema());
    }

    private function validate(Validator $validator, object $data, object $schema): void
    {
        $validator->validate($data, $schema, Constraint::CHECK_MODE_APPLY_DEFAULTS);
        if (!$validator->isValid()) {
            $message = 'invalid schema:' . PHP_EOL;
            foreach ($validator->getErrors() as $error) {
                $message .= sprintf(' - %s: %s%s', $error['property'], $error['message'], PHP_EOL);
            }
            throw new InvalidSchema($message);
        }
    }

    /**
     * @param string $scope
     * @param string $name
     * @param int $version
     * @return object
     * @throws SchemaNotFound
     */
    private function getSchema(string $scope, string $name, int $version): object
    {
        $filename = $this->getSchemaFilename($scope, $name, $version);
        $json = file_get_contents($filename);
        return json_decode($json, false);
    }

    /**
     * @return object
     */
    private function getGeneralSchema(): object
    {
        $filename = $this->dirs[0] . DIRECTORY_SEPARATOR . 'general.json';
        $json = file_get_contents($filename);
        return json_decode($json, false);
    }

    /**
     * @param string $scope
     * @param string $name
     * @param int $version
     * @return string
     * @throws SchemaNotFound
     */
    private function getSchemaFilename(string $scope, string $name, int $version): string
    {
        foreach ($this->dirs as $dir) {
            $filename = implode(DIRECTORY_SEPARATOR, [$dir, $scope, $name, sprintf('v%d.json', $version)]);
            if (file_exists($filename)) {
                return $filename;
            }
        }
        throw new SchemaNotFound(sprintf('schema %s.%s v%d not found', $scope, $name, $version));
    }
}
