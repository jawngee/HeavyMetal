<?php
/**
 * [[
 * before:
 *   class: {}
 * after:
 *   class: {}
 * ]]
 */
class ScreenController extends Controller
{
	/**
	 * [[
	 * before:
	 *   before:
	 *     string: "A string"
	 *   class:
	 *     ignore: true
	 * ]]
	 */
	public function before()
	{
		return array('message'=>'hello world');
	}

	/**
	 * [[
	 * after:
	 *   after: {}
	 * ]]
	 */
	public function after()
	{
		return array('message'=>'hello world');
	}

	/**
	 * [[
	 * before:
	 *   both: {}
	 *   class:
	 *     ignore: true
	 * after:
	 *   both: {}
	 *   class: { ignore: true }
	 * ]]
	 */
	public function both()
	{
		return array('message'=>'hello world');
	}
}