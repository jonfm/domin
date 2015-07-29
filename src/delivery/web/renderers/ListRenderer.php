<?php
namespace rtens\domin\delivery\web\renderers;

use rtens\domin\delivery\Renderer;
use rtens\domin\delivery\RendererRegistry;
use rtens\domin\delivery\web\Element;

class ListRenderer implements Renderer {

    /** @var RendererRegistry */
    private $renderers;

    public function __construct(RendererRegistry $renderers) {
        $this->renderers = $renderers;
    }

    /**
     * @param mixed $value
     * @return bool
     */
    public function handles($value) {
        return is_array($value) && $this->areNumerical(array_keys($value));
    }

    private function areNumerical($keys) {
        foreach ($keys as $key) {
            if (!is_numeric($key)) {
                return false;
            }
        }
        return true;
    }

    /**
     * @param array $value
     * @return mixed
     */
    public function render($value) {
        $items = [];
        foreach ($value as $item) {
            $items[] = new Element('li', [], [
                $this->renderers->getRenderer($item)->render($item)
            ]);
        }
        return (string)new Element('ul', ['class' => 'list-unstyled'], $items);
    }
}