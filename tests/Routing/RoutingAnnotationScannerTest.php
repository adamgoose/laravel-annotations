<?php

use Adamgoose\Routing\Annotations\Scanner;

class RoutingAnnotationScannerTest extends PHPUnit_Framework_TestCase {

	public function testProperRouteDefinitionsAreGenerated()
	{
		require_once __DIR__.'/fixtures/annotations/BasicController.php';
		$scanner = Scanner::create(['App\Http\Controllers\BasicController']);

		$scanner->addAnnotationNamespace(
            'Adamgoose\Routing\Annotations\Annotations',
            realpath(__DIR__.'/../../src/Adamgoose/Routing/Annotations/Annotations')
        );

		$definition = str_replace(PHP_EOL, "\n", $scanner->getRouteDefinitions());

		$this->assertEquals(trim(file_get_contents(__DIR__.'/results/annotation-basic.php')), $definition);
	}

}
