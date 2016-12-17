<?php namespace unit;

require_once(__DIR__ . '/../../stub/DummyValidator.php');

use Aedart\Testing\Laravel\Traits\TestHelperTrait;
use Illuminate\Validation\ValidationException;

/**
 * This file is part of the GZERO CMS package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * Class ValidatorTest
 *
 * @author     Adrian Skierniewski <adrian.skierniewski@gmail.com>
 * @copyright  Copyright (c) 2014, Adrian Skierniewski
 */
class ValidatorTest extends \Codeception\Test\Unit {

    use TestHelperTrait;

    /**
     * @var Array
     */
    protected $input;

    /**
     * @var DummyValidator
     */
    protected $validator;

    protected function _before()
    {
        // Start the Laravel application
        $this->startApplication();
        $this->input     = $this->initData();
        $this->validator = new \DummyValidator(\App::make('validator'));
    }


    protected function _after()
    {
        // Stop the Laravel application
        $this->stopApplication();
    }

    /**
     * @test
     * @expectedException \Illuminate\Validation\ValidationException
     */
    public function it_throws_exceptions_with_errors()
    {
        try {
            $this->input['type'] = 'product';
            $this->validator->validate('list', $this->input);
        } catch (ValidationException $e) {
            $this->assertEquals('The selected type is invalid.', $e->validator->getMessageBag()->first('type'));
            throw $e;
        }
    }

    /**
     * @test
     * @expectedException \Illuminate\Validation\ValidationException
     */
    public function can_bind_rules()
    {
        try {
            $this->validator->bind('lang', ['required' => 'numeric'])->validate('update', $this->input);
        } catch (ValidationException $e) {
            $this->assertEquals('The lang must be a number.', $e->validator->getMessageBag()->first('lang'));
            throw $e;
        }
    }

    /**
     * @test
     * @expectedException \Gzero\Core\Exception
     */
    public function it_checks_validation_context()
    {
        $this->validator->validate('fakeContext', []);
    }

    /**
     * @test
     */
    public function only_fields_in_rules_are_returned()
    {
        $fakeInput = [
            'testAttribute1' => 'dummyValue1',
            'testAttribute2' => 'dummyValue2',
            'testAttribute3' => [
                'test' => 'dummyValue3'
            ],
        ];
        $input     = array_merge($this->input, $fakeInput);
        $data      = $this->validator->validate('list', $input);
        $this->assertContains('pl', $data);
        $this->assertArrayHasKey('level', $data);
        $this->assertEquals(0, $data['level']);
        $this->assertContains(['test1' => 'Before trim', 'test2' => 2], $data); // Testing nested array & trim filter
    }

    /**
     * @test
     */
    public function it_apply_filters()
    {
        $this->input['title'] = 'Lorem Ipsum        ';
        $this->assertNotEquals($this->input, $this->validator->validate('list', $this->input));
    }

    /**
     * @return array
     */
    protected function initData()
    {
        return [
            'title'       => 'Lorem Ipsum',
            'type'        => 'content',
            'lang'        => 'pl',
            'parent_id'   => null,
            'translation' => [
                'test1' => 'Before trim       ',
                'test2' => 2
            ],
            'level'       => 0
        ];

    }

}