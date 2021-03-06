<?php
namespace spec\rtens\domin;

use rtens\domin\delivery\Field;
use rtens\domin\delivery\FieldRegistry;
use rtens\domin\delivery\ParameterReader;
use rtens\domin\delivery\Renderer;
use rtens\domin\delivery\RendererRegistry;
use rtens\domin\execution\FailedResult;
use rtens\domin\execution\MissingParametersResult;
use rtens\domin\execution\NoResult;
use rtens\domin\execution\RenderedResult;
use rtens\domin\Executor;
use rtens\domin\Parameter;
use rtens\mockster\arguments\Argument;
use rtens\mockster\Mockster;
use rtens\scrut\tests\statics\StaticTestSuite;
use watoki\reflect\type\UnknownType;

/**
 * @property \spec\rtens\domin\fixtures\ActionFixture action <-
 */
class ExecuteActionSpec extends StaticTestSuite {

    function unregisteredAction() {
        $this->whenIExecute('foo');
        $this->thenTheResultShouldBeTheError('Action [foo] is not registered.');
    }

    function emptyAction() {
        $this->action->givenTheAction('foo');

        $this->whenIExecute('foo');
        $this->thenThereShouldBeNoResult();
    }

    function passOnResult() {
        $this->action->givenTheAction_Returning('foo', new RenderedResult('hello'));

        $this->whenIExecute('foo');
        $this->thenTheResultShouldBe('hello');
    }

    function noMatchingRenderer() {
        $this->action->givenTheAction_Returning('foo', 'bar');
        $this->whenIExecute('foo');
        $this->thenTheResultShouldBeTheError("No Renderer found to handle 'bar'");
    }

    function renderResult() {
        $this->action->givenTheAction_Returning('foo', 'this is foo');
        $this->givenTheRenderer(function ($in) {
            return $in . ' rendered';
        });

        $this->whenIExecute('foo');
        $this->thenTheResultShouldBe('this is foo rendered');
    }

    function noMatchingField() {
        $this->action->givenTheAction('foo');
        $this->action->given_HasTheParameter('foo', 'one');
        $this->givenTheParameter_Is('one', 'uno');

        $this->whenIExecute('foo');
        $this->thenTheResultShouldBeTheError('No field found to handle [one:type of one]');
    }

    function inflateParameters() {
        $this->action->givenTheAction('foo');
        $this->action->given_ExecutesWith('foo', function ($params) {
            return implode(' ', $params);
        });
        $this->action->given_HasTheParameter('foo', 'one');
        $this->givenAFieldInflatingWith(function (Parameter $p, $s) {
            return $p->getName() . ':' . $s . '!';
        });
        $this->givenTheRenderer(function ($in) {
            return $in . ' rendered';
        });

        $this->givenTheParameter_Is('one', 'uno');

        $this->whenIExecute('foo');
        $this->thenTheResultShouldBe('one:uno! rendered');
    }

    function checkForMissingParameters() {
        $this->action->givenTheAction('foo');
        $this->action->given_HasTheParameter('foo', 'one');
        $this->action->given_HasTheRequiredParameter('foo', 'two');
        $this->action->given_HasTheRequiredParameter('foo', 'three');
        $this->action->given_HasTheRequiredParameter('foo', 'four');

        $this->givenTheParameter_Is('three', null);
        $this->givenAFieldHandling_InflatingWith('three', function ($s) {
            return $s . '!';
        });

        $this->whenIExecute('foo');
        $this->thenTheResultShouldBeThatParameters_AreMissing(['two', 'four']);
    }

    function parameterIsMissingIfValueDoesNotMatchType() {
        $this->action->givenTheAction('foo');
        $this->action->given_HasTheRequiredParameter_OfATypeMatching('foo', 'one', 'uno!');
        $this->action->given_HasTheRequiredParameter_OfATypeMatching('foo', 'two', 'uno!');
        $this->action->given_HasTheRequiredParameter_OfATypeMatching('foo', 'three', 'uno!');
        $this->action->given_HasTheParameter_OfATypeMatching('foo', 'four', 'uno!');

        $this->givenTheParameter_Is('one', 'uno');
        $this->givenTheParameter_Is('three', 'tres');
        $this->givenTheParameter_Is('four', 'cuatro');

        /** @noinspection PhpUnusedParameterInspection */
        $this->givenAFieldInflatingWith(function (Parameter $p, $s) {
            return $s . '!';
        });

        $this->whenIExecute('foo');
        $this->thenTheResultShouldBeThatParameters_AreMissing(['two', 'three']);
    }

    function chooseFieldForParameterType() {
        $this->action->givenTheAction('foo');
        $this->action->given_ExecutesWith('foo', function ($params) {
            return implode(' ', $params);
        });
        $this->action->given_HasTheParameter_OfType('foo', 'one', 'bar');
        $this->action->given_HasTheParameter_OfType('foo', 'two', 'bas');
        $this->givenAFieldHandling_InflatingWith('bar', function (Parameter $p, $s) {
            return $p->getName() . '_' . $s . '?';
        });
        $this->givenAFieldHandling_InflatingWith('bas', function (Parameter $p, $s) {
            return $p->getName() . '_' . $s . '!';
        });
        $this->givenTheRenderer(function ($in) {
            return $in . ' rendered';
        });

        $this->givenTheParameter_Is('one', 'uno');
        $this->givenTheParameter_Is('two', 'dos');

        $this->whenIExecute('foo');
        $this->thenTheResultShouldBe('one_uno? two_dos! rendered');
    }

    function chooseRendererForReturnedValue() {
        $this->action->givenTheAction_Returning('foo', 'this is foo');
        $this->action->givenTheAction_Returning('bar', 'this is bar');

        $this->givenARendererFor_RenderingWith('this is foo', function ($s) {
            return $s . ' with foo';
        });
        $this->givenARendererFor_RenderingWith('this is bar', function ($s) {
            return $s . ' with bar';
        });

        $this->whenIExecute('foo');
        $this->thenTheResultShouldBe('this is foo with foo');

        $this->whenIExecute('bar');
        $this->thenTheResultShouldBe('this is bar with bar');
    }

    /** @var Renderer[] */
    private $renderers = [];

    /** @var Field[] */
    private $fields = [];

    /** @var ParameterReader */
    private $reader;

    /** @var RenderedResult|FailedResult|MissingParametersResult */
    private $result;

    private $parameters = [];

    protected function before() {
        $this->reader = Mockster::of(ParameterReader::class);
        Mockster::stub($this->reader->read(Argument::any()))->will()->forwardTo(function (Parameter $parameter) {
            return $this->parameters[$parameter->getName()];
        });
        Mockster::stub($this->reader->has(Argument::any()))->will()->forwardTo(function (Parameter $parameter) {
            return array_key_exists($parameter->getName(), $this->parameters);
        });
    }

    private function givenAFieldInflatingWith($callback) {
        $this->givenAFieldHandling_InflatingWith(null, $callback);
    }

    private function givenAFieldHandling_InflatingWith($type, $callback) {
        $field = Mockster::of(Field::class);
        $this->fields[] = $field;

        Mockster::stub($field->handles(Argument::any()))->will()->forwardTo(function (Parameter $p) use ($type) {
            $pType = $p->getType();
            return $type == null || ($pType instanceof UnknownType && $pType->getHint() == $type);
        });
        Mockster::stub($field->inflate(Argument::any(), Argument::any()))->will()->forwardTo($callback);
    }

    private function givenTheParameter_Is($key, $value) {
        $this->parameters[$key] = $value;
    }

    private function givenTheRenderer($callback) {
        $this->givenARendererFor_RenderingWith(Argument::any(), $callback);
    }

    private function givenARendererFor_RenderingWith($value, $callback) {
        $renderer = Mockster::of(Renderer::class);
        $this->renderers[] = $renderer;

        Mockster::stub($renderer->handles($value))->will()->return_(true);
        Mockster::stub($renderer->render(Argument::any()))->will()->forwardTo($callback);
    }

    private function whenIExecute($id) {
        $fields = new FieldRegistry();
        foreach ($this->fields as $field) {
            $fields->add(Mockster::mock($field));
        }

        $renderers = new RendererRegistry();
        foreach ($this->renderers as $renderer) {
            $renderers->add(Mockster::mock($renderer));
        }

        $executor = new Executor($this->action->registry, $fields, $renderers, Mockster::mock($this->reader));
        $this->result = $executor->execute($id);
    }

    private function thenTheResultShouldBe($value) {
        $this->assert->isInstanceOf($this->result, RenderedResult::class);
        $this->assert($this->result->getOutput(), $value);
    }

    private function thenThereShouldBeNoResult() {
        $this->assert->isInstanceOf($this->result, NoResult::class);
    }

    private function thenTheResultShouldBeTheError($message) {
        $this->assert->isInstanceOf($this->result, FailedResult::class);
        $this->assert($this->result->getMessage(), $message);
    }

    private function thenTheResultShouldBeThatParameters_AreMissing($names) {
        $this->assert->isInstanceOf($this->result, MissingParametersResult::class);
        $this->assert($this->result->getParameters(), $names);

    }
}