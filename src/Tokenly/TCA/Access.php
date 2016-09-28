<?php

namespace Tokenly\TCA;
use Exception;
use Config;
use Tokenly\XChainClient\Client;

class Access
{
	static $balances = array();
	protected $client = false;
	
	function __construct($load_client = false)
	{
		if ($load_client) {
			if (function_exists('app') AND class_exists('Illuminate\Foundation\Application')) {
				$this->client = app('Tokenly\XChainClient\Client');
			} else {
	            $this->client = new Client(env('XCHAIN_CONNECTION_URL'), env('XCHAIN_API_TOKEN'), env('XCHAIN_API_KEY'));
			}
		}
	}
	
	/**
	 * Checks if one or more addresses meet the defined Token-Access conditions. Returns true or false
	 * 
	 * @param $conditions array
	 * @param $balances array - array of token balances (e.g from a cached table)
	 * @param $address string|array - can be a single BTC address or a string of addresses
	 * @example $conditions = array(array('asset' => 'ASSET', 'amount' => 5000, 'op' => '=', 'stackOp' => 'AND'));
	 * 
	 * @return bool
	 */
	public function checkAccess($conditions = array(), $balances = false, $address = false)
	{
		if($balances === false){
			$getBalances = $this->getAddressBalances($address);
		}
		else{
			$getBalances = $balances;
		}
		$stack = array();
		foreach($conditions as $lock){
			$hasReq = $this->parseLock($getBalances, $lock);
			$stack[] = array('hasReq' => $hasReq, 'stackOp' => $lock['stackOp']);
		}
		$doCheck = $this->parseStack($stack);
		return $doCheck;
	}
	
	public function getAddressBalances($address, $grouped = true)
	{
		$balanceList = array();
		if(!is_array($address)){
			$address = array($address);
		}
		foreach($address as $addr){
			if(!isset(self::$balances[$addr])){
				self::$balances[$addr] = $this->client->getBalances($addr);
			}
			$balanceList[$addr] = self::$balances[$addr];
		}
		if($grouped){
			$groupList = array();
			foreach($balanceList as $addr => $bal){
				foreach($bal as $asset => $quantity){
					$quantity = intval(round($quantity * 100000000));
					if(!isset($groupList[$asset])){
						$groupList[$asset] = $quantity;
					}
					else{
						$groupList[$asset] += $quantity;
					}
				}
			}
			$balanceList = $groupList;
		}
		return $balanceList;
	}
	
	/**
	* Checks specific condition against list of address balances based on defined conditional operator.
	*
	* @param $balances Array
	* Must be an array of (grouped) address token balances. $asset => $amount
	* @param $lock Array
	* 
	* @return bool
	*/
	protected function parseLock($balances, $lock)
	{
		$hasReq = false;
		if(!isset($balances[$lock['asset']])){
			$assetAmnt = 0;
		}
		else{
			$assetAmnt = $balances[$lock['asset']];
		}
		switch($lock['op']){
			case '==':
			case '=':
				if($assetAmnt == $lock['amount']){
					$hasReq = true;
				}
				break;
			case '!=':
			case '!':
				if($assetAmnt != $lock['amount']){
					$hasReq = true;
				}
				break;
			case '>':
				if($assetAmnt > $lock['amount']){
					$hasReq = true;
				}
				break;
			case '>=':
				if($assetAmnt >= $lock['amount']){
					$hasReq = true;
				}
				break;
			case '<':
				if($assetAmnt < $lock['amount'] AND $assetAmnt > 0){
					$hasReq = true;
				}
				break;
			case '<=':
				if($assetAmnt <= $lock['amount'] AND $assetAmnt > 0){
					$hasReq = true;
				}
				break;
		}
		return $hasReq;
	}
	
	/**
	* Parses a stack order array (e.g from checkAccess) to determine if all conditions are properly met.
	* 
	* The stack order is split up into "OR" groups. Each time stackOp == "OR" is encountered, a new stack group is created.
	* At least one "OR" group must be fully true to return true, otherwise returns false. 
	* 
	* @example 
	* e.g stack order: AND, OR, AND, AND, OR,AND
	* would result in three groups, (AND), (OR,AND,AND), (OR,AND).
	* If all conditions in at least one of those three groups are met, returns true.
	* 
	* @param $stack Array Stack order array looks like Array(hasReq => (bool), stackOp => string("AND"|"OR"))
	* @return bool
	*/ 
	protected function parseStack($stack)
	{
		$groups = array();
		$gnum = -1;
		foreach($stack as $k => $item){
			if($k == 0 OR $item['stackOp'] == 'OR'){
				$gnum++;
				$groups[$gnum] = array($item);
			}
			else{
				$groups[$gnum][] = $item;
			}
		}
		foreach($groups as $group){
			$groupMatch = true;
			foreach($group as $item){
				if(!$item['hasReq']){
					$groupMatch = false;
				}
			}
			if($groupMatch){
				return true;
			}
		}
		return false;
	}
}
