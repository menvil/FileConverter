<?php

namespace App\Support\Converters\DTO;

final readonly class OptionsSchemaField
{
    public function __construct(
        public string $key,
        public string $type,
        public string $label,
        public mixed $default = null,
        public array $options = [],
        public bool $required = false,
        public ?string $help = null,
    ) {}

    public function toArray(): array
    {
        return [
            'key' => $this->key,
            'type' => $this->type,
            'label' => $this->label,
            'default' => $this->default,
            'options' => $this->options,
            'required' => $this->required,
            'help' => $this->help,
        ];
    }
}
