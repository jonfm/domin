<?php
namespace rtens\domin\web\fields;

use rtens\domin\delivery\FieldRegistry;
use rtens\domin\Parameter;
use rtens\domin\reflection\IdentifiersProvider;
use rtens\domin\reflection\IdentifierType;
use rtens\domin\web\Element;
use rtens\domin\web\WebField;
use watoki\reflect\TypeFactory;

class IdentifierField implements WebField {

    /** @var TypeFactory */
    private $types;

    /** @var FieldRegistry */
    private $fields;

    /** @var IdentifiersProvider */
    private $identifiers;

    /**
     * @param TypeFactory $types
     * @param FieldRegistry $fields
     * @param IdentifiersProvider $identifiers
     */
    public function __construct(TypeFactory $types, FieldRegistry $fields, IdentifiersProvider $identifiers) {
        $this->types = $types;
        $this->fields = $fields;
        $this->identifiers = $identifiers;
    }

    /**
     * @param Parameter $parameter
     * @return bool
     */
    public function handles(Parameter $parameter) {
        return $parameter->getType() instanceof IdentifierType;
    }

    /**
     * @param Parameter $parameter
     * @param string $serialized
     * @return mixed
     */
    public function inflate(Parameter $parameter, $serialized) {
        $primitiveParameter = new Parameter($parameter->getName(), $this->getType($parameter)->getPrimitive());
        return $this->fields->getField($primitiveParameter)->inflate($primitiveParameter, $serialized);
    }

    /**
     * @param Parameter $parameter
     * @param mixed $value
     * @return string
     */
    public function render(Parameter $parameter, $value) {
        return (string)new Element('select', [
            'name' => $parameter->getName(),
            'class' => 'form-control'
        ], $this->getOptions($parameter, $value));
    }

    private function getOptions(Parameter $parameter, $value) {
        $options = [];
        foreach ($this->identifiers->getIdentifiers($this->getType($parameter)->getTarget()) as $key => $caption) {
            $options[] = new Element('option', array_merge([
                'value' => $key
            ], $key == $value ? [
                'selected' => 'selected'
            ] : []), [
                (string)$caption
            ]);
        }
        return $options;
    }

    /**
     * @param Parameter $parameter
     * @return array|\rtens\domin\web\Element[]
     */
    public function headElements(Parameter $parameter) {
        return [];
    }

    /**
     * @param Parameter $parameter
     * @return IdentifierType
     */
    private function getType(Parameter $parameter) {
        $type = $parameter->getType();
        if (!($type instanceof IdentifierType)) {
            throw new \InvalidArgumentException("[$type] must be an IdentifierType");
        }
        return $type;
    }
}