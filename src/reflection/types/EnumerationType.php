<?php
namespace rtens\domin\reflection\types;

use watoki\reflect\Type;

class EnumerationType implements Type {

    private $options;

    private $optionType;

    /**
     * @param array $options
     * @param Type $optionType
     */
    public function __construct(array $options, Type $optionType) {
        $this->options = $options;
        $this->optionType = $optionType;
    }

    /**
     * @return array key/value pairs
     */
    public function getOptions() {
        return $this->options;
    }

    public function is($value) {
        return array_key_exists($value, $this->getOptions());
    }

    public function __toString() {
        return $this->optionType . '[]';
    }

    /**
     * @return Type
     */
    public function getOptionType() {
        return $this->optionType;
    }
}