<?

uses('system.data.field');
uses('system.data.relation');
uses('system.data.table_schema');
/**
 * Retrieves table schemas from a postgresql database
 * 
 * @author		user
 * @date		Jun 17, 2007
 * @time		11:57:32 PM
 * @file		schema.php
 * @copyright  Copyright (c) 2007 massify.com, all rights reserved.
 */
class PGSQLTableSchema extends TableSchema
{
	private $db=null;
	
	public function __construct($db, $schema, $tablename, $related=false, $restricted=false)
	{
		$this->db=$db;
		
		$this->schema=$schema;
		$this->tablename=$tablename;
		
		$this->view=(strpos($tablename,'_view')>0);
		
		$sql='';		
		$sql.="SELECT ";
		$sql.="	a.attnum, ";
		$sql.="	a.attname AS field, ";
		$sql.="	t.typname AS type, ";
		$sql.="	format_type(a.atttypid, a.atttypmod) AS complete_type, ";
		$sql.="	( ";
		$sql.="		SELECT ";
		$sql.="			't' ";
		$sql.="		FROM ";
		$sql.="			pg_index ";
		$sql.="		WHERE ";
		$sql.="			c.oid = pg_index.indrelid ";
		$sql.="			AND pg_index.indkey[0] = a.attnum ";
		$sql.="			AND pg_index.indisprimary = 't' ";
		$sql.="	) AS pri, ";
		$sql.="	( ";
		$sql.="		SELECT ";
		$sql.="			pg_attrdef.adsrc ";
		$sql.="		FROM "; 
		$sql.="			pg_attrdef ";
		$sql.="		WHERE ";
		$sql.="			c.oid = pg_attrdef.adrelid ";
		$sql.="			AND pg_attrdef.adnum=a.attnum ";
		$sql.="	) AS default, ";
		$sql.="	a.attnotnull AS isnotnull, ";
		$sql.="	d.description ";
		$sql.="FROM ";
		$sql.="	pg_attribute a ";
		$sql.="INNER JOIN ";
		$sql.="	pg_class c ";
		$sql.="ON ";
		$sql.="	a.attrelid = c.oid ";
		$sql.="INNER JOIN ";
		$sql.="	 pg_type t ";
		$sql.="ON ";
		$sql.="	a.atttypid = t.oid ";
		$sql.="LEFT OUTER JOIN ";
		$sql.="	pg_description d ";
		$sql.="ON ";
		$sql.="	d.objoid=a.attrelid ";
		$sql.="	AND d.objsubid=a.attnum ";
		$sql.="WHERE ";
		$sql.="	c.relname = '$tablename' ";
		$sql.="	AND a.attnum > 0 ";
		$sql.="ORDER BY ";
		$sql.="	a.attnum ";

	    $result=$db->execute($sql);
	    if ($result->count==0)
	    	throw new Exception("No schema information for $tablename found.");
	    
	    foreach ($result as $key => $val) 
	    {
	    	$length=0;
	    	
	        if ($val['type'] === 'varchar')
		{
	            $length = preg_replace('~.*\(([0-9]*)\).*~', '$1', $val['complete_type']);
		    if (!is_numeric($length))
			$length=0;
		}

	        $valtype=0;
	        switch($val['type'])
	        {
	        	case 'varchar':
	        	case 'char':
	        		$valtype=Field::STRING;
	        		break;
	        	case 'int4':
	        	case 'int8':
	        	case 'float':
	        	case 'double precision':
	        		$valtype=Field::NUMBER;
	        		break;
	        	case 'timestamp':
	        		$valtype=Field::TIMESTAMP;
	        		break;
	        	case 'bool':
	        		$valtype=Field::BOOLEAN;
	        		break;
	        }
	
	        $field=new Field($val['field'],$valtype,$length,$val['description'],($val['isnotnull'] == 't'));
	        $field->db_type=$val['type'];
	        
			if ($val['pri'] == 't')                
				$this->primarykey=$field;
			else
				$this->columns[]=$field;
		}
		
		if ($related==true)
		{
			$this->alltables=array( $schema.'.'.$tablename => $this);
			$this->get_related($this->alltables,$restricted);
			$this->alltables=array_slice($this->alltables,1);
		}
	}
	
	protected function get_related(&$alltables,$restricted)
	{
		/** Backwards links */
		$fkeys=$this->get_related_tables();
		foreach($fkeys as $fkey)
		{
			$key=$fkey['source_schema'].'.'.$fkey['source'];
			if (isset($alltables[$key]))
				$this->related[$key]=&$alltables[$key];
			else
			{
				$schema=new PGSQLTableSchema($this->db,$fkey['source_schema'],$fkey['source'],false);
				$schema->related_key=$fkey['name'];

				$schema->related_column=$fkey['source_column'];

				if ($this->has_constraint($fkey['source_schema'],$fkey['source'],$fkey['source_column'])!=false)
				{
					$schema->type=Relation::RELATION_SINGLE;
					//$schema->related_field=$fkey['dest_column'];
				}
				else
					$schema->type=Relation::RELATION_MANY;
				
				if (($restricted==false) || ($this->schema==$schema->schema))
				{
					$alltables[$key]=$schema;
					$this->related[$key]=$schema;
					$schema->get_related($alltables,$restricted);
				}
			}
		}

		/** 1..1 Forward **/
		$fkeys=$this->get_foreign_keys();
		foreach($fkeys as $fkey)
		{
			$key=$fkey['dest_schema'].'.'.$fkey['dest'];
			if (isset($alltables[$key]))
				$this->related[$key]=&$alltables[$key];
			else
			{
				$schema=new PGSQLTableSchema($this->db,$fkey['dest_schema'],$fkey['dest'],false);
				$schema->related_key=$fkey['name'];
				$schema->type=Relation::RELATION_SINGLE;
				$schema->related_field=$fkey['source_column'];
				$schema->related_column=$fkey['dest_column'];

				if (($restricted==false) || ($this->schema==$schema->schema))
				{
					$alltables[$key]=$schema;
					$this->related[$key]=$schema;
					$schema->get_related($alltables,$restricted);
				}
			}
		}
	}
	
	public function get_foreign_keys()
	{
		$sql='';		
		$sql.="select ";
		$sql.="	c.conname as name, ";
		$sql.="	sns.nspname as source_schema, ";
		$sql.="	s.relname as source, ";
		$sql.="	sa.attname as source_column, ";
		$sql.="	dns.nspname as dest_schema, ";
		$sql.="	d.relname as dest, ";
		$sql.="	da.attname as dest_column ";
		$sql.="from  ";
		$sql.="	pg_class s ";
		$sql.="inner join ";
		$sql.="	pg_namespace sns ";
		$sql.="on ";
		$sql.="	s.relnamespace=sns.oid ";
		$sql.="inner join  ";
		$sql.="	pg_constraint c ";
		$sql.="on  ";
		$sql.="	s.oid=c.conrelid ";
		$sql.="inner join ";
		$sql.="	pg_class d ";
		$sql.="on ";
		$sql.="	d.oid=c.confrelid ";
		$sql.="inner join ";
		$sql.="	pg_namespace dns ";
		$sql.="on ";
		$sql.="	d.relnamespace=dns.oid ";
		$sql.="inner join ";
		$sql.="	pg_attribute sa ";
		$sql.="on ";
		$sql.="	sa.attrelid=s.oid and sa.attnum = c.conkey[1] ";
		$sql.="inner join ";
		$sql.="	pg_attribute da ";
		$sql.="on ";
		$sql.="	da.attrelid=d.oid and da.attnum = c.confkey[1] ";
		$sql.="where ";
		$sql.="	s.relname='$this->tablename'; ";
		
		return $this->db->execute($sql);
	}
  
	public function get_related_tables()
	{
		$sql='';		
		$sql.="select ";
		$sql.="	c.conname as name, ";
		$sql.="	sns.nspname as source_schema, ";
		$sql.="	s.relname as source, ";
		$sql.="	sa.attname as source_column, ";
		$sql.="	dns.nspname as dest_schema, ";
		$sql.="	d.relname as dest, ";
		$sql.="	da.attname as dest_column ";
		$sql.="from  ";
		$sql.="	pg_class s ";
		$sql.="inner join ";
		$sql.="	pg_namespace sns ";
		$sql.="on ";
		$sql.="	s.relnamespace=sns.oid ";
		$sql.="inner join  ";
		$sql.="	pg_constraint c ";
		$sql.="on  ";
		$sql.="	s.oid=c.conrelid ";
		$sql.="inner join ";
		$sql.="	pg_class d ";
		$sql.="on ";
		$sql.="	d.oid=c.confrelid ";
		$sql.="inner join ";
		$sql.="	pg_namespace dns ";
		$sql.="on ";
		$sql.="	d.relnamespace=dns.oid ";
		$sql.="inner join ";
		$sql.="	pg_attribute sa ";
		$sql.="on ";
		$sql.="	sa.attrelid=s.oid and sa.attnum = c.conkey[1] ";
		$sql.="inner join ";
		$sql.="	pg_attribute da ";
		$sql.="on ";
		$sql.="	da.attrelid=d.oid and da.attnum = c.confkey[1] ";
		$sql.="where ";
		$sql.="	d.relname='$this->tablename'; ";
		
		return $this->db->execute($sql);
	}
	
	public function has_constraint($schema,$table,$column)
	{
		$sql='';
		$sql.="select  ";
		$sql.="     c.conname as name "; 
		$sql.="from   ";
		$sql.="     pg_class s "; 
		$sql.="inner join  ";
		$sql.="     pg_namespace sns "; 
		$sql.="on  ";
		$sql.="     sns.oid=s.relnamespace "; 
		$sql.="inner join   ";
		$sql.="     pg_constraint c "; 
		$sql.="on   ";
		$sql.="     s.oid=c.conrelid "; 
		$sql.="     and c.contype not in ('p','f') "; 
		$sql.="inner join  ";
		$sql.="     pg_attribute sa "; 
		$sql.="on  ";
		$sql.="     sa.attrelid=s.oid and sa.attnum = c.conkey[1] "; 
		$sql.="where  ";
		$sql.="     s.relname='$table' ";
		$sql.="     and sns.nspname='$schema' ";
		$sql.="     and sa.attname='$column' ";
		
		return ($this->db->get_one($sql)!=false);
     }
} 