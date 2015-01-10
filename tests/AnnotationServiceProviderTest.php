<?php

use Mockery as m;
use Adamgoose\AnnotationsServiceProvider;

class AnnotationsServiceProviderTest extends PHPUnit_Framework_TestCase {

	public function setUp()
	{
		$this->app = m::mock('Illuminate\Contracts\Foundation\Application');
		$this->provider = new AnnotationsServiceProvider( $this->app );
	}

	public function testPrefixClasses()
	{
		$prefix = 'Prefix';
		$classes = ['Foo', 'Bar', 'Foo\\Bar'];

		$this->assertEquals(
			['Prefix\\Foo', 'Prefix\\Bar', 'Prefix\\Foo\\Bar'],
			$this->provider->prefixClasses($prefix, $classes)
		);
	}

	public function testPrefixClassesWithNoPrefix()
	{
		$prefix = '';
		$classes = ['Foo', 'Bar'];

		$this->assertEquals(
			['Foo', 'Bar'],
			$this->provider->prefixClasses($prefix, $classes)
		);
	}

	public function testPrefixClassesWithTrimsWhitespaceAndDereferencers()
	{
		$prefix = '\\Prefix ';
		$classes = ['\\ Foo  \\', ' \\Bar\\ '];

		$this->assertEquals(
			['Prefix\\Foo', 'Prefix\\Bar'],
			$this->provider->prefixClasses($prefix, $classes)
		);
	}

}
