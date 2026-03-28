<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class ExampleTest extends TestCase
{
    /**
     * Verify basic PHP features work correctly.
     */
    public function test_string_manipulation_works(): void
    {
        $this->assertEquals('hello-world', strtolower(str_replace(' ', '-', 'Hello World')));
    }
}
