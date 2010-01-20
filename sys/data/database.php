<?
/**
*
* Copyright (c) 2009, Jon Gilkison and Trunk Archive Inc.
* All rights reserved.
*
* Redistribution and use in source and binary forms, with or without
* modification, are permitted provided that the following conditions are met:
*
* - Redistributions of source code must retain the above copyright notice,
*   this list of conditions and the following disclaimer.
* - Redistributions in binary form must reproduce the above copyright
*   notice, this list of conditions and the following disclaimer in the
*   documentation and/or other materials provided with the distribution.
*
* THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS"
* AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
* IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE
* ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE
* LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR
* CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
* SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
* INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
* CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
* ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
* POSSIBILITY OF SUCH DAMAGE.
*
* This is a modified BSD license (the third clause has been removed).
* The BSD license may be found here:
* 
* http://www.opensource.org/licenses/bsd-license.php
*
*/

uses('system.app.config');

/**
 * Database Exception
 */
class DatabaseException extends Exception {}

/**
 * Database abstraction layer.
 */
abstract class Database
{
	const FEATURE_DELETE_VIA_QUERY	=	0;
	const FEATURE_PREFIX_COLUMNS	=	1;
	const FEATURE_JOINS				=	2;
	const FEATURE_AGGREGATES		=	3;
	const FEATURE_OFFSETLIMIT		=	4;
	const FEATURE_ORDERBY			=	5;
	const FEATURE_BULKDELETE		=	6;
	const FEATURE_TABLE_ALIAS       =   7;
	
	/** List of initialized databases */
	private static $_databases=array();

	/**
	 * Fetches a named database from the connection pool.
	 *
	 * @param string $name Name of the database to fetch from the connection pool.
	 * @return Database The named database.
	 */
	public static function Get($name)
	{
		if (isset(self::$_databases[$name]))
			return self::$_databases[$name];
		else
		{
			$conf=Config::Get('db');
			if (isset($conf->items[$name]))
			{
				$dsn=$conf->items[$name]->dsn;
				
				$matches=array();
				if (preg_match_all('#([a-z]*)://(.*)#',$dsn,$matches))
				{
					$driver=$matches[1][0];
					
					uses("system.data.database.$driver.$driver");
					
					$class=$driver."Database";
					$db=new $class($dsn);
					
					self::$_databases[$name]=$db;
					return $db;
				}
			}

			throw new Exception("Cannot find database named '$name' in Config.");
		}
	}

    /**
     * Constructor 
     * 
     * @param $dsn string DSN for the connection
     */
    public function __construct($dsn)
    {
    }


    /**
     * Determines if the driver supports a specific feature.
     *
     * @param string $feature
     */
    abstract public function supports($feature);

    /**
     * Performs an insert
     *
     * @param string $table_name Name of the table to update
     * @param array $fields Name/value pair of fields to update/insert
     */
    abstract function insert($table_name,$key,$fields);

    /**
     * Performs an update
     *
     * @param string $table_name Name of the table to update
     * @param string $key The name of the primary key
     * @param mixed $id The value of the key
     * @param array $fields Name/value pair of fields to update/insert
     */
    abstract function update($table_name,$key,$id,$fields);

    /**
     * Performs a delete
     *
     * @param string $table_name Name of the table to update
     * @param string $key The name of the primary key
     * @param mixed $id The value of the key
     */
    abstract function delete($table_name,$key,$id);

    /**
     * Executes a query statement
     *
     * @param string $query Query to execute.
     * @param int $offset Offset into the records to fetch
     * @param int $limit The number of records to fetch
     */
    abstract function execute($query,$offset=null,$limit=null);
    
	/**
	 * Executes a sql file
	 */
	public function execute_file($file_name)
	{
		$sql=file_get_contents($file_name);
		return $this->execute($sql);
	}
    

	/**
	 * Fetches the row count for a given query
	 *
	 * @param string $key
	 * @param string $table_name
	 * @param string $where
	 */
	abstract function count($table_name,$key,$where=null,$distinct=false);


    /**
     * Executes an sql statement and returns the first value
     *
     * @param string $sql SQL to execute.
     */
    abstract function get_one($query);

    /**
     * Executes an sql statement and returns the first row
     *
     * @param string $query Query to execute.
     */
    abstract function get_row($query);

    /**
     * Fetches a single row by the table's primary key
     *
     * @param unknown_type $table_name
     * @param unknown_type $key
     * @param unknown_type $id
     */
    abstract function fetch_row($table_name,$key,$id);


    /**
     * Executes an sql statement and returns the results as an array
     *
     * @param string $query Query to execute.
     */
    abstract function get_rows($query);

    /**
     * starts a transaction
     */
    abstract function begin();

    /**
     * Ends a transaction.
     */
    abstract function commit();

	/**
	 * Escapes a value for a sql statement
	 * 
	 * @param mixed $value The value to escape
	 * @return string The escaped value
	 */
	abstract function escape_value($type,$value);

	/**
	 * Parses a database array type into a php array
	 * 
	 * @param string $value
	 * @return array
	 */
	abstract function parse_array($value);

	/**
	 * Collapses a php array into a database array type
	 * 
	 * @param array $value
	 * @return string
	 */
	abstract function collapse_array($value);


	/**
	 * Returns the schemas in the database.
	 * 
	 * @return DatabaseResult
	 */
	abstract function schemas();

	/**
	 * Returns the list of tables and views for a given schema.
	 * 
	 * @param string $schema
	 * @return DatabaseResult
	 */
	abstract function tables($schema);
	
	/**
	 * Returns the schema for the table.
	 * 
	 * @param $tablename
	 * @param $related
	 * @param $restricted_to_schema
	 * @return unknown_type
	 */
	abstract function table($schema, $tablename, $related=false, $restricted_to_schema=false);
}