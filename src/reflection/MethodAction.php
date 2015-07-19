<?php
namespace rtens\domin\reflection;

use rtens\domin\Action;
use rtens\domin\Parameter;
use watoki\reflect\MethodAnalyzer;

class MethodAction implements Action {

    /** @var object */
    private $object;

    /** @var \ReflectionMethod */
    private $method;

    /** @var TypeFactory */
    private $types;

    /**
     * @param object $object
     * @param string $method
     * @param TypeFactory $types
     */
    function __construct($object, $method, TypeFactory $types) {
        $this->object = $object;
        $this->method = new \ReflectionMethod(get_class($object), $method);
        $this->types = $types;
    }

    /**
     * @return string
     */
    public function caption() {
        return ucfirst(preg_replace('/(.)([A-Z0-9])/', '$1 $2', $this->method->getName()));
    }

    /**
     * @return Parameter[]
     */
    public function parameters() {
        $analyzer = new MethodAnalyzer($this->method);
        $parameters = [];
        foreach ($this->method->getParameters() as $parameter) {
            $type = $analyzer->getType($parameter, $this->types);
            $parameters[] = new Parameter($parameter->getName(), $type, !$parameter->isDefaultValueAvailable());
        }
        return $parameters;
    }

    /**
     * Fills out partially available parameters
     *
     * @param array $parameters Available values indexed by name
     * @return array Filled values indexed by name
     */
    public function fill(array $parameters) {
        foreach ($this->method->getParameters() as $parameter) {
            if ($parameter->isDefaultValueAvailable() && $this->notIn($parameter, $parameters)) {
                $parameters[$parameter->getName()] = $parameter->getDefaultValue();
            }
        }
        return $parameters;
    }

    /**
     * @param mixed[] $parameters Values indexed by name
     * @return mixed the result of the execution
     * @throws \Exception if Action cannot be executed
     */
    public function execute(array $parameters) {
        $injector = function () {
        };
        $filter = function () {
            return true;
        };

        $analyzer = new MethodAnalyzer($this->method);
        $arguments = $analyzer->fillParameters($parameters, $injector, $filter);

        return $this->method->invokeArgs($this->object, $arguments);
    }

    private function notIn(\ReflectionParameter $parameter, array $parameters) {
        return !array_key_exists($parameter->getName(), $parameters) || is_null($parameters[$parameter->getName()]);
    }
}