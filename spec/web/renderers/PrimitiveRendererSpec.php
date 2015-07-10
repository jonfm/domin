<?php
namespace spec\rtens\domin\web\renderers;

use rtens\domin\web\renderers\PrimitiveRenderer;
use rtens\scrut\tests\statics\StaticTestSuite;

class PrimitiveRendererSpec extends StaticTestSuite {

    function handlesPrimitives() {
        $renderer = new PrimitiveRenderer();

        $this->assert($renderer->handles('foo'));
        $this->assert($renderer->handles(1));
        $this->assert($renderer->handles(true));
        $this->assert->not($renderer->handles(new \DateTime()));
        $this->assert->not($renderer->handles([]));
    }

    function castsToStrings() {
        $renderer = new PrimitiveRenderer();

        $this->assert($renderer->render(1) === '1');
        $this->assert($renderer->render([]), 'Array');
    }
} 