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

uses('system.data.database');
uses('system.data.database.sqlite3.sqlite3_result');
uses("system.app.dynamic_object");


/**
 * Driver for SQLite
 */
class SQLite3Database extends Database
{
    private $config=null;
    private $connection=null;

    /**
     * Constructor 
     * 
     * @param $dsn string DSN for the connection
     */
    public function __construct($dsn)
    {
    	
    	//sqlite://path/to/database/file.db
		$this->config=parse_url($dsn);
		if (!$this->config)
		    throw new DatabaseException("Invalid dsn '$dsn'.");
		
		$this->connection=sqlite_open(PATH_APP.'/data/'.$this->config['host']);
		if (!$this->connection)
			throw new DatabaseException("Invalid database settings.");
    }


    /**
     * Determines if the driver supports a specific feature.
     *
     * @param string $feature
     */
    public function supports($feature) { return true; }
    


    /**
     * Performs an insert
     *
     * @param string $table_name Name of the table to update
     * @param array $fields Name/value pair of fields to update/insert
     */
    public function insert($table_name,$key,$fields)
    {
    	// extract the keys and values so we can build a prepared statement.

		$keys=array_keys($fields);
    	$vals=array_values($fields);
    	
    	$sql="insert into $table_name (".implode(',',$keys).") values (";
    	for($i=1; $i<=count($vals); $i++)
    		$sql.="'".$vals[$i]."',";
    	$sql=trim($sql,',');

   		$sql.=");";

    	$res=sqlite_query($this->connection,$sql);
    	if (!$res)
    		throw new DatabaseException(sqlite_last_error($this->connection));
    		
    	return sqlite_last_insert_rowid($this->connection);    }

    /**
     * Performs an update
     *
     * @param string $table_name Name of the table to update
     * @param string $key The name of the primary key
     * @param mixed $id The value of the key
     * @param array $fields Name/value pair of fields to update/insert
     */
    public function update($table_name,$key,$id,$fields)
    {
    	// extract the keys and values so we can build a prepared statement.
    	$keys=array_keys($fields);
    	$vals=array_values($fields);
    	
    	$sql="update $table_name set ";
    	
    	for($i=1; $i<=count($keys); $i++)
    		$sql.=$keys[$i-1]."='".$vals[$i]."',";
    	$sql=trim($sql,',');

   		$sql.=" where $key=$id";
    	$res=sqlite_query($this->connection,$sql);
    	if (sqlite_changes($this->connection)<=0)
    		throw new DatabaseException(sqlite_last_error($this->connection));
    		
    	return true;
    }

    /**
     * Performs a delete
     *
     * @param string $table_name Name of the table to update
     * @param string $key The name of the primary key
     * @param mixed $id The value of the key
     */
    public function delete($table_name,$key,$id)
    {
		$res=sqlite_query($this->connection,"delete from $table_name where $key=$id");
    	return (sqlite_changes($this->connection)>0);
    }

    /**
     * Executes a query statement
     *
     * @param string $query Query to execute.
     * @param int $offset Offset into the records to fetch
     * @param int $limit The number of records to fetch
     */
    public function execute($query,$offset=null,$limit=null)
    {
    	if ($offset)
    		$query.=" OFFSET $offset";
    		
    	if ($limit)
    		$query.=" LIMIT $limit";
    	$res=sqlite_query($this->connection,$query);
    	
    	if (!$res)
    		throw new DatabaseException(sqlite_last_error($this->connection));
    	
    	return new SQLite3Result($res);
    }

	/**
	 * Fetches the row count for a given query
	 *
	 * @param string $key
	 * @param string $table_name
	 * @param string $where
	 */
	public function count($key,$table_name,$where=null,$distinct=false)
	{
		$d=($distinct) ? 'distinct' : '';
		
		$sql="select $d count($table_name.$key) from $table_name";
		if ($where)
			$sql.=" $where;";
   		
		return $this->get_one($sql);
	}


    /**
     * Executes an sql statement and returns the first value
     *
     * @param string $sql SQL to execute.
     */
    public function get_one($query)
    {
    	$res=sqlite_query($this->connection,$query);
    	$row=sqlite_fetch_array($res);
    	return $row[0];
    }

    /**
     * Executes an sql statement and returns the first row
     *
     * @param string $query Query to execute.
     */
    public function get_row($query)
    {
    	$res=sqlite_query($this->connection,$query);
    	return sqlite_fetch_array($res,SQLITE_ASSOC);
    }

    /**
     * Fetches a single row by the table's primary key
     *
     * @param unknown_type $table_name
     * @param unknown_type $key
     * @param unknown_type $id
     */
    public function fetch_row($table_name,$key,$id)
    {
		$res=sqlite_query($this->connection,"SELECT * FROM $table_name WHERE $key=$id");
		return sqlite_fetch_array($res,SQLITE_ASSOC);
    }


    /**
     * Executes an sql statement and returns the results as an array
     *
     * @param string $query Query to execute.
     */
    public function get_rows($query)
    {
    	$res=sqlite_query($this->connection,$query);
    	return sqlite_fetch_all($res);
    }

    /**
     * starts a transaction
     */
    public function begin()
    {
		throw new Exception("Not implemented.");
    }

    /**
     * Ends a transaction.
     */
    public function commit()
    {
		throw new Exception("Not implemented.");
    }

	/**
	 * Escapes a value for a sql statement
	 * 
	 * @param mixed $value The value to escape
	 * @return string The escaped value
	 */
	public function escape_value($type,$value)
	{
   		$value=str_replace("'","''",$value);
		
		switch($type)
		{
			case Field::STRING:
			case Field::TEXT:
				return "'$value'";
			case Field::BOOLEAN:
				return ($value) ? "true" : "false";
			case Field::OBJECT:
				return "'".(($value instanceof DynamicObject) ? $value->to_string() : serialize($value))."'";
			default:
				if (is_numeric($value))
					return $value;
				else
					return "'$value'";
		}
	}    

	/**
	 * Parses a database array type into a php array
	 * 
	 * @param string $value
	 * @return array
	 */
	public function parse_array($value)
	{
		if ($value==null)
			return array();
		
		if (is_array($value))
			return $value;
			
		$value=trim($value,'{}');
		$result=explode(',',$value);
		
		for($i=0; $i<count($result); $i++)
			if ($result[$i]=='NULL')
				$result[$i]=null;
				
		return $result;
	}

	/**
	 * Collapses a php array into a database array type
	 * 
	 * @param array $value
	 * @return string
	 */
	public function collapse_array($value)
	{
		if (count($value)==0)
			return null;
			
		$result='';
		foreach($value as $val)
			if ($val==null)
				$result.='NULL,';
			else
				$result.="$val,";
		
		return 'ARRAY['.trim($result,',').']';
	}

	/**
	 * Returns the schemas in the database.
	 * 
	 * @return DatabaseResult
	 */
	public function schemas()
	{
//		return $this->execute("SELECT nspname as schema FROM pg_namespace WHERE nspname NOT LIKE 'pg_%' AND nspname<>'information_schema'");
		throw new Exception("Not implemented.");
		
	}

	/**
	 * Returns the list of tables and views for a given schema.
	 * 
	 * @param string $schema
	 * @return DatabaseResult
	 */
	public function tables($schema)
	{
//	  	$sql="select tablename from pg_tables where tablename not like 'pg\_%' "
//			."and tablename not in ('sql_features', 'sql_implementation_info', 'sql_languages', "
//	 		."'sql_packages', 'sql_sizing', 'sql_sizing_profiles') and schemaname='$schema';";
//	
//		return $this->execute($sql);
		throw new Exception("Not implemented.");
	}
	
	/**
	 * Returns the schema for the table.
	 * 
	 * @param $tablename
	 * @param $related
	 * @param $restricted_to_schema
	 * @return unknown_type
	 */
	public function table($schema,$tablename, $related=false, $restricted_to_schema=false)
	{
//		uses('system.data.driver.database.pgsql_table_schema');
//		$fuck=new PGSQLTableSchema($this,$schema,$tablename,$related,$restricted_to_schema);
//		return $fuck;
		throw new Exception("Not implemented.");
	}
	
}