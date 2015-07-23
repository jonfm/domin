<?php
namespace rtens\domin\delivery\web\fields;

use rtens\domin\Parameter;
use rtens\domin\delivery\web\Element;
use rtens\domin\delivery\web\WebField;
use watoki\reflect\type\BooleanType;

class BooleanField implements WebField {

    /**
     * @param Parameter $parameter
     * @return bool
     */
    public function handles(Parameter $parameter) {
        return $parameter->getType() == new BooleanType();
    }

    /**
     * @param Parameter $parameter
     * @param string $serialized
     * @return mixed
     */
    public function inflate(Parameter $parameter, $serialized) {
        return !!$serialized;
    }

    /**
     * @param Parameter $parameter
     * @param mixed $value
     * @return string
     */
    public function render(Parameter $parameter, $value) {
        $attributes = [
            'type' => 'checkbox',
            'name' => $parameter->getName(),
            'value' => 1,
        ];

        if ($value) {
            $attributes['checked'] = 'checked';
        }

        return implode('', [
            new Element('input', [
                'type' => 'hidden',
                'name' => $parameter->getName(),
                'value' => 0
            ]),
            new Element('input', $attributes)
        ]);
    }

    /**
     * @param Parameter $parameter
     * @return array|Element[]
     */
    public function headElements(Parameter $parameter) {
        return [];
    }
}