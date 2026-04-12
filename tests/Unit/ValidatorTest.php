<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Helpers\Validator;

class ValidatorTest extends TestCase
{
    public function testRequiredRule()
    {
        $data = ['name' => ''];
        $rules = ['name' => ['required' => true]];
        $validator = new Validator($data);
        $validator->check($rules);
        
        $this->assertFalse($validator->passed());
        $this->assertArrayHasKey('name', $validator->errors());
    }

    public function testMinRule()
    {
        $data = ['username' => 'ab'];
        $rules = ['username' => ['min' => 3]];
        $validator = new Validator($data);
        $validator->check($rules);
        
        $this->assertFalse($validator->passed());
        $this->assertArrayHasKey('username', $validator->errors());
    }

    public function testEmailRule()
    {
        $data = ['email' => 'invalid-email'];
        $rules = ['email' => ['email' => true]];
        $validator = new Validator($data);
        $validator->check($rules);
        
        $this->assertFalse($validator->passed());
        $this->assertArrayHasKey('email', $validator->errors());
    }

    public function testValidationPassed()
    {
        $data = [
            'username' => 'johndoe',
            'email' => 'john@example.com'
        ];
        $rules = [
            'username' => ['required' => true, 'min' => 3],
            'email' => ['required' => true, 'email' => true]
        ];
        $validator = new Validator($data);
        $validator->check($rules);
        
        $this->assertTrue($validator->passed());
    }
}
