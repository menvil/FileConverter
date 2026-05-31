<?php

declare(strict_types=1);

namespace App\Support\Conversions;

use App\Support\Conversions\Contracts\ConverterDriver;
use App\Support\Conversions\Exceptions\MissingConverterDriverException;
use InvalidArgumentException;

class ConverterDriverRegistry
{
    /**
     * @var array<string, ConverterDriver>
     */
    private array $drivers = [];

    /**
     * @param  iterable<ConverterDriver>  $drivers
     */
    public function __construct(iterable $drivers = [])
    {
        foreach ($drivers as $driver) {
            $key = $driver->key();

            if (isset($this->drivers[$key])) {
                throw new InvalidArgumentException(
                    "A converter driver is already registered for key [{$key}]."
                );
            }

            $this->drivers[$key] = $driver;
        }
    }

    /**
     * @return array<string, ConverterDriver>
     */
    public function all(): array
    {
        return $this->drivers;
    }

    public function find(string $key): ?ConverterDriver
    {
        return $this->drivers[$key] ?? null;
    }

    public function findOrFail(string $key): ConverterDriver
    {
        return $this->find($key) ?? throw MissingConverterDriverException::forKey($key);
    }
}
