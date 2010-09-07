<?='<?'?>	
	uses('system.data.model');
	
	/**
 	 * <?=$classname?> Model
	 *
	 * Contains the following properties:
	 *
<?
	foreach($schema->columns as $column)
	{
?>
	 * <?=$column->name?> - <?=($column->description=='') ? 'Undocumented column' : str_replace("'","\\'",$column->description)?>

<?
	}
?>		
	 *
	 * @copyright  Copyright (c) 2007 massify.com, all rights reserved.
	 */
	class <?=$classname?> extends Model
	{
		public $table_name='<?=$schema->schema?>.<?=$schema->tablename?>';
		<? if ($schema->view): ?>
		
		public $primary_key='id';
		public $readonly=true;
		
		<? else: ?>
		
		public $primary_key='<?=$schema->primarykey->name?>';
		
		<? endif; ?>
		public $database='<?=$database?>';

		/**
		* Describes the schema and validation rules for this object.  This is auto-generated.
		*/
		protected function describe()
		{
			// describe the fields/columns
<? 
foreach($schema->columns as $column):
	$fieldtype='Field::STRING';
	switch($column->type)
	{
		case Field::NUMBER:
			$fieldtype='Field::NUMBER';
			break;
		case Field::TEXT:
			$fieldtype='Field::TEXT';
			break;
		
		case Field::TIMESTAMP:
			$fieldtype='Field::TIMESTAMP';
			break;
		
		case Field::BOOLEAN:
			$fieldtype='Field::BOOLEAN';
			break;
		
		case Field::BLOB:
			$fieldtype='Field::BLOB';
			break;
	}
?>		
			// <?=$column->name?> - <?=($column->description=='') ? 'TODO: DOCUMENT YOUR DATABASE DUDE' : str_replace("'","\\'",$column->description)?>
			
			$this->fields['<?=$column->name?>']=new Field('<?=$column->name?>',<?=$fieldtype?>,<?=$column->length?>,'<?=($column->description=='') ? 'Undocumented column' : str_replace("'","\\'",$column->description)?>',<?=(($column->notnull) ? "true" : "false")?>);
<? endforeach; ?>		

<?
	foreach($schema->columns as $column)
	if ($column->notnull)
	{
?>		
			$this->validators["<?=$column->name?>"]=array(
				RequiredValidator::Create("Required.")
			);
<?
	}
?>		
			
			// create relations for columns
<?
	foreach($schema->related as $key => $related)
	{
		$name=str_replace($id_suffix,'',(($related->related_field==null) ? str_replace($schema->tablename.'_','',$related->tablename) : $related->related_field));
//		if ($name==$schema->primarykey->name)
//			$name=$related->tablename;
			
		$reltype='Relation::RELATION_SINGLE';
		
		if ($related->type==Relation::RELATION_MANY)
		{
			$reltype='Relation::RELATION_MANY';
			$name=$name;
		}
			
?>			$this->related['<?=$name?>']=new Relation($this,'<?=$name?>',<?=$reltype?>,'<?=$related->schema?>/<?=$related->tablename?>','<?=$related->related_column?>'<?=(($related->related_field==null) ? '' : ",'$related->related_field'")?>);
<?
	}
?>
		}
<?
	foreach($schema->related as $key => $related)
		if ($related->type==Relation::RELATION_MANY)
		{ 
			$name=str_replace($id_suffix,'',(($related->related_field==null) ? str_replace($schema->tablename.'_','',$related->tablename) : $related->related_field));

			$rel_classname='';
			$names=explode('_',$related->tablename);
			foreach($names as $n)
				$rel_classname.=ucfirst($n);
?>

		/**
		 * Fetches a list of <?=$name?> related to this <?=$classname?>.  
		 *
		 * @param int $offset Offset into results
		 * @param int $limit Max. number of results to return
		 * @result mixed Array of items, note NOT an array of model classes, but an array of array of values 
		 */
		public function get_<?=$name?>($offset=0,$limit=null)
		{
			$filter=$this->filter_<?=$name?>();
			
			$filter->limit=$limit;
			$filter->offset=$offset;
			
			return $filter->execute();
		}
		
		/**
		 * Adds a <?=$rel_classname?> to this <?=$classname?>
		
		 *
		 * @param <?=$rel_classname?> $item <?=$rel_classname?> to add
		 */
		public function add_<?=$name?>(<?=$rel_classname?> $item)
		{
			$item-><?=$related->related_column?>=$this->primary_key_value;
			$item->save();
		}
		
		/**
		 * Creates a new <?=$rel_classname?> linked to this <?=$classname?>
		
		 *
		 * @result <?=$rel_classname?> A new <?=$rel_classname?>.  Note, you must save() this model for it to persist to the db 
		 */
		public function create_<?=$name?>()
		{
			$result=new <?=$rel_classname?>();
			$result-><?=$related->related_column?>=$this->primary_key_value;
			
			return $result;
		}
        

		/**
		 * Creates a Filter object to filter a list of <?=$name?> related to this <?=$classname?>
		
		 *
		 * @result Filter A Filter object 
		 */
		public function filter_<?=$name?>()
		{
			$filter=<?=$rel_classname?>::Filter();
			$filter-><?=$related->related_column?>=$this->primary_key_value;
			
			return $filter;
		}

<?		} ?>

		// MERGE
		
		<?= $merged ?>
		
		
		// END MERGE
	}
