<?php
namespace rtens\domin\reflection;

use rtens\domin\ActionRegistry;

class ObjectActionGenerator {

    private $actions;
    private $types;

    public function __construct(ActionRegistry $actions, TypeFactory $types) {
        $this->actions = $actions;
        $this->types = $types;
    }

    public function fromFolder($path, callable $execute) {
        $before = get_declared_classes();

        foreach (glob($path . '/*.php') as $file) {
            include_once($file);
        }

        $newClasses = array_diff(get_declared_classes(), $before);

        foreach ($newClasses as $class) {
            $this->actions->add($this->getId($class),
                new GenericObjectAction($class, $this->types, $execute));
        }

        return $this;
    }

    /**
     * @param string $class
     * @return GenericObjectAction
     * @throws \Exception
     */
    public function get($class) {
        return $this->actions->getAction($this->getId($class));
    }

    /**
     * @param string $class
     * @return string
     */
    protected function getId($class) {
        $reflection = new \ReflectionClass($class);
        return lcfirst($reflection->getShortName());
    }

    public function configure($class, callable $callback) {
        $callback($this->get($class));
        return $this;
    }
}