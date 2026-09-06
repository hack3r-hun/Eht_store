<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Tests\Concerns\CreatesTestProducts;

abstract class TestCase extends BaseTestCase
{
    use CreatesTestProducts;
}
