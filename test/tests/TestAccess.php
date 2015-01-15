<?php
use Tokenly\TCA\Access;
use \PHPUnit_Framework_Assert as PHPUnit;

class TestAccess extends PHPUnit_Framework_TestCase
{
	public function testTokenAccess()
	{
		$tca = new Access;
		
		$address = '15fx1Gqe4KodZvyzN6VUSkEmhCssrM1yD7';
		$conditions = array(array('asset' => 'LTBCOIN', 'amount' => 50000, 'op' => '>', 'stackOp' => 'AND'));
		
		$this->assertEquals(true, $tca->checkAccess($address, $conditions));
	}
	
}
