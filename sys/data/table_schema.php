<?
/**
 * Retrieves table schemas from a postgresql database
 * 
 * @author		user
 * @date		Jun 17, 2007
 * @time		11:57:32 PM
 * @file		schema.php
 * @copyright  Copyright (c) 2007 massify.com, all rights reserved.
 */
uses('system.data.relation');

abstract class TableSchema
{
	/**
	 * The relation of this table to the root table (the initial table queried for it's schema).
	 */
	public $type=Relation::RELATION_UNKNOWN;
	
	/**
	 * Name of the primary key
	 * @var Field
	 */
	public $primarykey=null;
	
	/**
	 * List of columns
	 * @var array of Field
	 */
  	public $fields=null;
  	
  	/**
  	 * Name of the table
  	 * @var string
  	 */
  	public $tablename='';
  	
  	/**
  	 * Name of the schema this table belongs to.
  	 * @var string
  	 */
  	public $schema='';
  	
  	/**
  	 * 
  	 * @var unknown_type
  	 */
  	public $related_column='';
  	
  	/**
  	 * 
  	 * @var unknown_type
  	 */
  	public $related_field='';
  	
  	/**
  	 * 
  	 * @var unknown_type
  	 */
  	public $related_key='';
  	
  	/**
  	 * 
  	 * @var unknown_type
  	 */
  	public $view=false;
  	
  	/**
  	 * List of related tables
  	 * @var unknown_type
  	 */
  	public $related=array();
  	
  	/**
  	 * All tables, regardless of relationships
  	 * @var unknown_type
  	 */
  	public $alltables=array();
	
  	public function __construct($db, $schema, $tablename, $related=false, $restricted=false)
	{
	}
	
	abstract function get_foreign_keys();

	abstract function get_related_tables();
	
	abstract function has_constraint($schema,$table,$column);
} 