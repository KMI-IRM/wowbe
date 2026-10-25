<?php

namespace Tests\Unit\Rules;

use App\Rules\AuthKey;
use Tests\TestCase;

class AuthKeyTest extends TestCase
{
    public function test_validates_pincode(): void
    {
        $rule = new AuthKey;
        $fails = false;

        $rule->validate('auth_key', '123456', function () use (&$fails) {
            $fails = true;
        });

        $this->assertFalse($fails, 'Valid PIN code should pass validation');
    }

    public function test_validates_password(): void
    {
        $rule = new AuthKey;
        $fails = false;

        $rule->validate('auth_key', 'securepassword', function () use (&$fails) {
            $fails = true;
        });

        $this->assertFalse($fails, 'Valid password should pass validation');
    }

    public function test_fails_invalid_password(): void
    {
        $rule = new AuthKey;
        $fails = false;

        $rule->validate('auth_key', 'short', function () use (&$fails) {
            $fails = true;
        });

        $this->assertTrue($fails, 'Invalid password should fail validation');
    }
}
