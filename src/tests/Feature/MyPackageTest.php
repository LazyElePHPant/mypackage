<?php

namespace LazyElePHPant\MyPackage\Tests;

use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MyPackageTest extends TestCase
{
	use WithFaker, RefreshDatabase;

	public function test_that_it_is_true()
	{
		$this->assertTrue(true);
	}
}
