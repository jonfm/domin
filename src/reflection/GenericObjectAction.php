<?php
namespace rtens\domin\reflection;

use rtens\domin\reflection\types\TypeFactory;

class GenericObjectAction extends ObjectAction {

    private $execute;
    private $fill;

    public function __construct($class, TypeFactory $types, callable $execute) {
        parent::__construct($class, $types);
        $this->execute = $execute;
    }

    public function caption() {
        return parent::caption();
    }

    /**
     * @return string|null
     */
    public function description() {
        return null;
    }

    public function setExecute(callable $execute) {
        $this->execute = $execute;
        return $this;
    }

    public function setFill(callable $fill) {
        $this->fill = $fill;
        return $this;
    }

    /**
     * @param callable $callback Filters the return value of execute
     */
    public function setAfterExecute(callable $callback) {
        $oldExecute = $this->execute;
        $this->execute = function ($object) use ($oldExecute, $callback) {
            return $callback(call_user_func($oldExecute, $object));
        };
    }

    /**
     * Called by execute() with the instantiated object
     *
     * @param object $object
     * @return mixed
     */
    protected function executeWith($object) {
        return call_user_func($this->execute, $object);
    }

    public function fill(array $parameters) {
        $parameters = parent::fill($parameters);
        if ($this->fill) {
            return call_user_func($this->fill, $parameters);
        }
        return $parameters;
    }
}