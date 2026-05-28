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
        $array = [
            'key' => $this->key,
            'type' => $this->type,
            'label' => $this->label,
        ];

        if ($this->default !== null) {
            $array['default'] = $this->default;
        }

        $array['options'] = $this->options;
        $array['required'] = $this->required;
        $array['help'] = $this->help;

        return $array;
    }
}
