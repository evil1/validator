<?php
namespace Yiisoft\Validator\Tests\Rule;

use PHPUnit\Framework\TestCase;
use Yiisoft\Validator\Rule\Each;

/**
 * @group validators
 */
class EachTest extends TestCase
{
    public function testArrayFormat()
    {
        $validator = new Each(['rule' => ['required']]);

        $this->assertFalse($validator->validate('not array')->isValid());
        $this->assertTrue($validator->validate(['value'])->isValid());
    }

    /**
     * @depends testArrayFormat
     */
    public function testValidate()
    {
        $validator = new Each(['rule' => ['integer']]);

        $this->assertTrue($validator->validate([1, 3, 8]));
        $this->assertFalse($validator->validate([1, 'text', 8]));
    }

    /**
     * @depends testArrayFormat
     */
    public function testFilter()
    {
        $model = FakedValidationModel::createWithAttributes([
            'attr_one' => [
                '  to be trimmed  ',
            ],
        ]);
        $validator = new Each(['rule' => ['trim']]);
        $validator->validateAttribute($model, 'attr_one');
        $this->assertEquals('to be trimmed', $model->attr_one[0]);
    }

    /**
     * @depends testValidate
     */
    public function testAllowMessageFromRule()
    {
        $model = FakedValidationModel::createWithAttributes([
            'attr_one' => [
                'text',
            ],
        ]);
        $validator = new Each(['rule' => ['integer']]);

        $validator->allowMessageFromRule = true;
        $validator->validateAttribute($model, 'attr_one');
        $this->assertContains('integer', $model->getFirstError('attr_one'));

        $model->clearErrors();
        $validator->allowMessageFromRule = false;
        $validator->validateAttribute($model, 'attr_one');
        $this->assertNotContains('integer', $model->getFirstError('attr_one'));
    }

    /**
     * @depends testValidate
     */
    public function testCustomMessageValue()
    {
        $model = FakedValidationModel::createWithAttributes([
            'attr_one' => [
                'TEXT',
            ],
        ]);
        $validator = new Each(['rule' => ['integer', 'message' => '{value} is not an integer']]);

        $validator->validateAttribute($model, 'attr_one');
        $this->assertSame('TEXT is not an integer', $model->getFirstError('attr_one'));

        $model->clearErrors();
        $validator->allowMessageFromRule = false;
        $validator->message = '{value} is invalid';
        $validator->validateAttribute($model, 'attr_one');
        $this->assertEquals('TEXT is invalid', $model->getFirstError('attr_one'));
    }

    /**
     * @see https://github.com/yiisoft/yii2/issues/10825
     *
     * @depends testValidate
     */
    public function testSkipOnEmpty()
    {
        $validator = new Each(['rule' => ['integer', 'skipOnEmpty' => true]]);
        $this->assertTrue($validator->validate(['']));

        $validator = new Each(['rule' => ['integer', 'skipOnEmpty' => false]]);
        $this->assertFalse($validator->validate(['']));

        $model = FakedValidationModel::createWithAttributes([
            'attr_one' => [
                '',
            ],
        ]);
        $validator = new Each(['rule' => ['integer', 'skipOnEmpty' => true]]);
        $validator->validateAttribute($model, 'attr_one');
        $this->assertFalse($model->hasErrors('attr_one'));

        $model->clearErrors();
        $validator = new Each(['rule' => ['integer', 'skipOnEmpty' => false]]);
        $validator->validateAttribute($model, 'attr_one');
        $this->assertTrue($model->hasErrors('attr_one'));
    }

    /**
     * @see https://github.com/yiisoft/yii2/issues/9935
     *
     * @depends testValidate
     */
    public function testCompare()
    {
        $model = FakedValidationModel::createWithAttributes([
            'attr_one' => [
                'value1',
                'value2',
                'value3',
            ],
            'attr_two' => 'value2',
        ]);
        $validator = new Each(['rule' => ['compare', 'compareAttribute' => 'attr_two']]);
        $validator->validateAttribute($model, 'attr_one');
        $this->assertNotEmpty($model->getErrors('attr_one'));
        $this->assertCount(3, $model->attr_one);

        $model = FakedValidationModel::createWithAttributes([
            'attr_one' => [
                'value1',
                'value2',
                'value3',
            ],
            'attr_two' => 'value4',
        ]);
        $validator = new Each(['rule' => ['compare', 'compareAttribute' => 'attr_two', 'operator' => '!=']]);
        $validator->validateAttribute($model, 'attr_one');
        $this->assertEmpty($model->getErrors('attr_one'));
    }

    /**
     * @depends testValidate
     */
    public function testStopOnFirstError()
    {
        $model = FakedValidationModel::createWithAttributes([
            'attr_one' => [
                'one', 2, 'three',
            ],
        ]);
        $validator = new Each(['rule' => ['integer']]);

        $validator->stopOnFirstError = true;
        $validator->validateAttribute($model, 'attr_one');
        $this->assertCount(1, $model->getErrors('attr_one'));

        $model->clearErrors();
        $validator->stopOnFirstError = false;
        $validator->validateAttribute($model, 'attr_one');
        $this->assertCount(2, $model->getErrors('attr_one'));
    }

    public function testValidateArrayAccess()
    {
        $model = FakedValidationModel::createWithAttributes([
            'attr_array' => new ArrayAccessObject([1,2,3]),
        ]);

        $validator = new Each(['rule' => ['integer']]);
        $validator->validateAttribute($model, 'attr_array');
        $this->assertFalse($model->hasErrors('array'));

        $this->assertTrue($validator->validate($model->attr_array));
    }
}
