<?php
namespace rtens\domin\delivery\web\fields;

use rtens\domin\delivery\FieldRegistry;
use rtens\domin\delivery\web\Element;
use rtens\domin\delivery\web\WebField;
use rtens\domin\Parameter;
use watoki\factory\Factory;
use watoki\factory\Injector;
use watoki\reflect\Property;
use watoki\reflect\PropertyReader;
use watoki\reflect\type\ClassType;
use watoki\reflect\TypeFactory;

class ObjectField implements WebField {

    /** @var TypeFactory */
    private $types;

    /** @var FieldRegistry */
    private $fields;

    /**
     * @param TypeFactory $types
     * @param FieldRegistry $fields
     */
    public function __construct(TypeFactory $types, FieldRegistry $fields) {
        $this->types = $types;
        $this->fields = $fields;
    }

    /**
     * @param Parameter $parameter
     * @return bool
     */
    public function handles(Parameter $parameter) {
        return $parameter->getType() instanceof ClassType;
    }

    /**
     * @param Parameter $parameter
     * @param \watoki\collections\Map $serialized
     * @return object
     */
    public function inflate(Parameter $parameter, $serialized) {
        $reader = new PropertyReader($this->types, $this->getClass($parameter));

        $properties = [];
        foreach ($reader->readInterface() as $property) {
            if ($serialized->has($property->name())) {
                $param = $this->makePropertyParameter($parameter, $property);
                $properties[$property->name()] = $this->getField($param)->inflate($param, $serialized[$property->name()]);
            }
        }

        $injector = new Injector(new Factory());
        $instance = $injector->injectConstructor($this->getClass($parameter), $properties, function () {
            return false;
        });

        foreach ($reader->readInterface() as $property) {
            if ($property->canSet()) {
                $property->set($instance, $properties[$property->name()]);
            }
        }

        return $instance;
    }

    /**
     * @param Parameter $parameter
     * @return array|\rtens\domin\delivery\web\Element[]
     */
    public function headElements(Parameter $parameter) {
        $reader = new PropertyReader($this->types, $this->getClass($parameter));

        $headElements = [];
        foreach ($reader->readInterface() as $property) {
            $param = $this->makePropertyParameter($parameter, $property);
            $headElements = array_merge($headElements, $this->getField($param)->headElements($param));
        }
        return $headElements;
    }

    /**
     * @param Parameter $parameter
     * @param mixed $value
     * @return string
     */
    public function render(Parameter $parameter, $value) {
        return (string)new Element('div', ['class' => 'panel panel-default'], [
            new Element('div', ['class' => 'panel-heading clearfix'], [
                new Element('h3', ['class' => 'panel-title'], [
                    htmlentities((new \ReflectionClass($this->getClass($parameter)))->getShortName()),
                ])
            ]),
            new Element('div', ['class' => 'panel-body'], $this->renderPropertyFields($parameter, $value))
        ]);
    }

    private function renderPropertyFields(Parameter $parameter, $object) {
        $reader = new PropertyReader($this->types, $this->getClass($parameter));

        $fields = [];
        foreach ($reader->readInterface($object) as $property) {
            if (!$property->canSet()) {
                continue;
            }

            $param = $this->makePropertyParameter($parameter, $property);
            $fields[] = new Element('div', ['class' => 'form-group'], [
                new Element('label', [], [ucfirst($property->name()) . ($property->isRequired() ? '*' : '')]),
                $this->getField($param)->render($param, $object ? $property->get($object) : null)
            ]);
        }
        return $fields;
    }

    private function getClass(Parameter $parameter) {
        $type = $parameter->getType();
        if (!($type instanceof ClassType)) {
            throw new \InvalidArgumentException("[$type] is not a ClassType");
        }
        return $type->getClass();
    }

    /**
     * @param Parameter $param
     * @return WebField
     */
    private function getField(Parameter $param) {
        return $this->fields->getField($param);
    }

    private function makePropertyParameter(Parameter $parameter, Property $property) {
        return new Parameter($parameter->getName() . '[' . $property->name() . ']', $property->type(), $property->isRequired());
    }
}