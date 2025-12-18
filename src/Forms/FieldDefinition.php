<?php
declare(strict_types=1);

namespace AFE\Forms;

final class FieldDefinition
{
    public string $id;
    public string $type;
    public string $label;
    public bool $required;
    public bool $sensitive;

    /** @var array<int,array{label:string,value:string}> */
    public array $options;

    public function __construct(string $id, string $type, string $label,
        bool $required = false, bool $sensitive = false, array $options = []) {
            
        $this->id = $id;
        $this->type = $type;
        $this->label = $label;
        $this->required = $required;
        $this->sensitive = $sensitive;
        $this->options = $options;
    }
}
