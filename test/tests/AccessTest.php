<?php
use PHPUnit\Framework\TestCase;
use Tokenly\TCA\Access;
use PHPUnit\Framework\Assert as PHPUnit;

class AccessTest extends TestCase
{
	public function testTokenAccess()
	{
		$tca = new Access();
		
		$address = '1AAAA1111xxxxxxxxxxxxxxxxxxy43CZ9j';
		$conditions = [
			['asset' => 'LTBCOIN', 'amount' => 50000, 'op' => '>', 'stackOp' => 'AND']
		];
		
		$balances = [
			'LTBCOIN' => 60000,
		];
		$this->assertEquals(true, $tca->checkAccess($conditions, $balances, $address));
	}
	
}
