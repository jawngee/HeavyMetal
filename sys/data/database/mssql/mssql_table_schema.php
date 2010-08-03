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
class MSSQLTableSchema extends TableSchema
{
	private $db=null;
	
	public function __construct($db, $schema, $tablename, $related=false, $restricted=false)
	{
		$this->db=$db;
		
		$this->schema=$schema;
		$this->tablename=$tablename;
		
		$this->view=false;

	    $result=$db->execute("exec sp_columns '$tablename';");
	    if ($result->count==0)
	    	throw new Exception("No schema information for $tablename found.");
	    
	    foreach ($result as $key => $val) 
	    {
	    	$length=$val['PRECISION'];
	    	
	        $valtype=0;
	        $isprimary=false;
	        switch($val['TYPE_NAME'])
	        {
	        	case 'int identity':
	        		$valtype=Field::NUMBER;
	        		$isprimary=true;
	        		break;
	        	case 'uniqueidentifier':
	        	case 'varchar':
	        	case 'char':
	        		$valtype=Field::STRING;
	        		break;
	        	case 'int':
	        	case 'tinyint':
	        	case 'decimal':
	        	case 'float':
	        	case 'double':
	        	case 'money':
	        		$valtype=Field::NUMBER;
	        		break;
	        	case 'datetime':
	        		$valtype=Field::TIMESTAMP;
	        		break;
	        	case 'bool':
	        		$valtype=Field::BOOLEAN;
	        		break;
	        }
	
	        $field=new Field($val['COLUMN_NAME'],$valtype,$length,$val['REMARKS'],($val['IS_NULLABLE'] == 'YES'));
	        $field->db_type=$val['TYPE_NAME'];
	        
			if ($isprimary)           
				$this->primarykey=$field;
			else
				$this->columns[]=$field;
		}
		
		if ($related==true)
		{
			$this->alltables=array( $tablename => $this);
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
			$key=$fkey['referenced_object'];
			if (isset($alltables[$key]))
				$this->related[$key]=&$alltables[$key];
			else
			{
				$schema=new MSSQLTableSchema($this->db,null,$fkey['referenced_object'],false);
				$schema->related_key=$fkey['foreign_key_name'];

				$schema->related_column=$fkey['constraint_column_name'];

//				if ($this->has_constraint($fkey['source_schema'],$fkey['source'],$fkey['source_column'])!=false)
//				{
//					$schema->type=Relation::RELATION_SINGLE;
//					//$schema->related_field=$fkey['dest_column'];
//				}
//				else
//					$schema->type=Relation::RELATION_MANY;
//				
//				if (($restricted==false) || ($this->schema==$schema->schema))
//				{
					$alltables[$key]=$schema;
					$this->related[$key]=$schema;
					$schema->get_related($alltables,$restricted);
//				}
			}
		}

//		/** 1..1 Forward **/
//		$fkeys=$this->get_foreign_keys();
//		foreach($fkeys as $fkey)
//		{
//			$key=$fkey['dest_schema'].'.'.$fkey['dest'];
//			if (isset($alltables[$key]))
//				$this->related[$key]=&$alltables[$key];
//			else
//			{
//				$schema=new PGSQLTableSchema($this->db,$fkey['dest_schema'],$fkey['dest'],false);
//				$schema->related_key=$fkey['name'];
//				$schema->type=Relation::RELATION_SINGLE;
//				$schema->related_field=$fkey['source_column'];
//				$schema->related_column=$fkey['dest_column'];
//
//				if (($restricted==false) || ($this->schema==$schema->schema))
//				{
//					$alltables[$key]=$schema;
//					$this->related[$key]=$schema;
//					$schema->get_related($alltables,$restricted);
//				}
//			}
//		}
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
		
		$sql=<<<EOD
		SELECT 
		    f.name AS foreign_key_name
		   ,OBJECT_NAME(f.parent_object_id) AS table_name
		   ,COL_NAME(fc.parent_object_id, fc.parent_column_id) AS constraint_column_name
		   ,OBJECT_NAME (f.referenced_object_id) AS referenced_object
		   ,COL_NAME(fc.referenced_object_id, fc.referenced_column_id) AS referenced_column_name
		   ,is_disabled
		   ,delete_referential_action_desc
		   ,update_referential_action_desc
		FROM sys.foreign_keys AS f
		INNER JOIN sys.foreign_key_columns AS fc 
   			ON f.object_id = fc.constraint_object_id 
		WHERE f.parent_object_id = OBJECT_ID('{$this->tablename}');
EOD;
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