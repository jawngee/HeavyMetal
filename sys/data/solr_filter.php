<?
uses_system('data/field');
uses_system('data/filter');
uses_system('data/solr_filter_field');
uses_system('data/order_by');
uses_system('data/facets');
uses_system('data/highlights');
uses_system('data/join');

/**
 * Model filter
 * 
 * @author		user
 * @date		Jun 16, 2007
 * @time		10:30:32 PM
 * @file		filter.php
 * @copyright  Copyright (c) 2007 massify.com, all rights reserved.
 */


class SOLRFilter extends Filter
{
	protected $fields=array();		/** List of filtered fields */

	public  $model=null;			/** Reference to the Model being filtered */
	public $class=null;
	
	public $select='*'; // SOLR defaults to returning everything unless otherwise specified

	public $order_by=null;			/** OrderBy class for controlling sort order */
	
	public $limit=null;				/** Result limits */
	public $offset=null;			/** Result offset */
	
	private $grouplevel=0;
	
	public $result_format = 'php';
	
	public $facet=null;
	public $highlight=null;
	
	public $boost_function=null;


	
	/**
	 * Constructor
	 * 
	 * @param Model $model The model to filter
	 */
	public function __construct($model,$class,$set_defaults=true)
	{
		$this->model = $model;
		$this->class=$class;

		$this->order_by=new OrderBy($this,$model);
		$this->facet=new Facets($this,$model);
		$this->highlight=new Highlights($this,$model);
		
		if ($set_defaults)
			$model->set_filter_defaults($this);
	}
	
	public static function Model($model)
	{
		$model=str_replace('.','/',$model);
		
		uses_model($model);
		
		$parts=explode('/',$model);
		
		$class=str_replace('_','',$parts[1]);
		$instance=new $class();
		
		return new SOLRFilter($instance,$class);
	}
	
	
	/**
	* Callback method for getting a property
	*/
   	function __get($prop_name)
   	{
   	   	if ($prop_name=='or')
   		{
 			$this->grouplevel++;
   			return $this;
   		}
   		
   		if (isset($this->model->fields[$prop_name]))
   		{
   			if (isset($this->fields[$prop_name]))
   				$result=&$this->fields[$prop_name];
   			else
   			{
		   		$result=new SOLRFilterField($this,$this->model->fields[$prop_name]);
		   		$this->fields[$prop_name]=&$result;
   			}
   			
   		}
   		else if ($prop_name==$this->model->primary_key)
   		{
   			if (isset($this->fields[$prop_name]))
   				$result=&$this->fields[$prop_name];
   			else
   			{
   				$field=new Field($prop_name,Field::NUMBER);
		   		$result=new SOLRFilterField($this,$field);
		   		$this->fields[$prop_name]=&$result;
   			}
   		}
   		else
   			return null;
   			//throw new ModelException("Field '$prop_name' doesn't exist in this filter.");
   			
   		return $result;
   	}
   	
   	/**
  	*  Callback method for setting a property
   	*/
   	function __set($prop_name, $prop_value)
   	{
  		if (isset($this->model->fields[$prop_name]))
   		{
   			if (isset($this->fields[$prop_name]))
   				$this->fields[$prop_name]->equals($prop_value);
   			else
   			{
		   		$result=new SOLRFilterField($this,$this->model->fields[$prop_name]);
		   		$result->and=($this->grouplevel==0);
		   		$result->equals($prop_value);
		   		$this->fields[$prop_name]=&$result;
		   		
		   		if ($this->grouplevel>0)
   					$this->grouplevel--;
   			}

	   		return true;
   		}
   		else if ($prop_name==$this->model->primary_key)
   		{
   			if (isset($this->fields[$prop_name]))
   				$this->fields[$prop_name]->equals($prop_value);
   			else
   			{
   				$field=new Field($prop_name,Field::NUMBER);
		   		$result=new SOLRFilterField($this,$field);
		   		$result->equals($prop_value);
		   		$this->fields[$prop_name]=&$result;
   				$result->and=($this->grouplevel==0);
   				
		   		if ($this->grouplevel>0)
   					$this->grouplevel--;
     		}

	   		return true;
   		}
   		else
   			return false;//throw new ModelException("Field '$prop_name' doesn't exist in this filter.");
    }
   	
    
   	function join($column,$filter,$foreign_column, $kind='inner', $filter_in_join=false)
   	{
		// joins ignored -- SOLR is one big table
   	}
    
    /**
     * Builds solr query string
     *
     * @return string The solr query string
     */
   	function build_query($select=null /*defaults to everything*/)
   	{
   		$query = array();
		$fq = array();
		$q = array();
		$loc = array();

		// primary query string (deal with this optimization)

		// all filter fields specified
   		foreach($this->fields as $filter_field)
   		{ 
   			if (in_array($filter_field->field->name,array('lat','long','radius')))
   			{
   				if(count($loc)==0)
   					$loc[] = 'qt=geo';
   					
   				$loc[] = $filter_field->value;
   			}
			else
   			{	
   				// figure out if this field goes in the q parameter
				$raw_value = trim($filter_field->value,'"');

				if ($filter_field->q_param)
   				{ 
   					if (!empty($raw_value))
   						$q[] = preg_replace('/\+'.$filter_field->field->name.':"(.*)"/', '\\1', $filter_field->value);
   				}
   				elseif ($filter_field->value)
   				{
   					if (!empty($raw_value))
   						$fq[] = $filter_field->value;
   				}
   			}

   		}
	
   		$query[] = 'q=' . rawurlencode(implode($q, ' '));
   		$query = array_merge($query, $loc);

   		if (count($fq) > 0)
			foreach($fq as $filter_query)
				$query[] = 'fq='.rawurlencode($filter_query);
   		//   			$query[] = 'fq=' . urlencode(implode($fq, ' '));

				
   		// handle specific fields requested
   		if ($select)
   			$query[] = 'fl='.urlencode($select);
   		
   		
   		// handle offset / limit
   		if ($this->offset)
   			$query[] = 'start='.$this->offset;
   			
   		if ($this->limit)
   			$query[] = 'rows='.$this->limit;
		
   		
   		// handle ordering  (TODO: what happens to score when we order?)
		$sort = array();
		foreach ($this->order_by->orders as $order)
			$sort[] = rawurlencode($order->field . ' ' . strtolower($order->direction));

		if (count($sort) > 0)
			$query[] = 'sort='.implode($sort, ',');
			
			
   		// handle faceting
   		if (!empty($this->facet->fields))
   			$query[] = 'facet=true';
   		
   			
   		// TODO:  Factor this into Facet
   		foreach ($this->facet->fields as $facet)
   		{
   			if ($facet->multi && isset($this->fields[$facet->field_name]))
				$query[] = 'facet.field={!ex='.$facet->field_name.'}'.$facet->field_name;
   			else
				$query[] = 'facet.field='.$facet->field_name;
   			
   			if ($facet->min_count)
   				$query[] = 'f.'.$facet->field_name.'.facet.mincount='.$facet->min_count;

   			if ($facet->limit)
   				$query[] = 'f.'.$facet->field_name.'.facet.limit='.$facet->limit; 

   			if ($facet->sort && $facet->sort==true)
   				$query[] = 'f.'.$facet->field_name.'.facet.sort=true';  
   			else
   				$query[] = 'f.'.$facet->field_name.'.facet.sort=false';  
   				
   			
   		}
   		
   		
   		
   		foreach ($this->facet->config as $key => $value)
			$query[] = 'facet.' . $key .'='. $value;
   		
		
			
			
   		// handle highlighting
   		if (!empty($this->highlight->fields))
   		{
   			$query[] = 'hl=true';
   			//$query[] = 'hl.fragsize=0'; // include entire field in frag
   			$query[] = 'hl.fl=' . implode(',',$this->highlight->fields);
   		}
   		
   		foreach ($this->highlight->config as $key => $value)
			$query[] = 'hl.' . $key .'='. $value;
   		
		
		// handle boost function
		if (!empty($this->boost_function))
			$query[] = 'bf='.$this->boost_function;
			
   		// tack on the result_format
   		$query[] = 'wt='.$this->result_format;			

   		return SOLR_SERVER . '/select?' . implode($query, '&');
   	}

   	

	function show_query()
   	{
   		return $this->build_query();
   	}
   	
   	

   	
   	
   	
   	/**
   	 * Builds the filter and executes the sql
   	 */
   	function execute($select=null,$offset=null,$limit=null)
   	{
   		if ($offset)
   			$this->offset=$offset;
   			
   		if ($limit)
   			$this->limit=$limit;

   		$response = file_get_contents($this->build_query());

		eval("\$response_array = " . $response . ";");
   			
		$result = array();
		
		$result = $response_array['response']['docs'];
		$result_count = count($result);

		//  weave in facets and meta info	
		$result['count'] = $result_count;
		$result['total_count'] = $response_array['response']['numFound'];
		$result['facet_counts'] = $response_array['facet_counts']['facet_fields'];

		// overlay any highlit results into main result set
		foreach ($response_array['highlighting'] as $id => $hi_fields)
			for ($i=0; $i<$result_count; $i++)
				if ($result[$i][$this->model->primary_key]==$id)	
					foreach ($hi_fields as $field_name => $frags)
						if (!empty($frags[0]))	
							$result[$i][$field_name]=$frags[0];
		
   		return $result;	
   	}
   	
   	

   	
   	/**
   	 * Builds the filter and executes the sql, returning a single row
   	 */
   	function get_row($select=null)
   	{
		$result = $this->execute(null,0,1);
		
		return $result[0];
   	}
   	
   	/**
   	 * Fetches the result as an array versus a DatabaseResult
   	 *
   	 * @param string $select
   	 * @return array
   	 */
   	function get_rows($select=null)
   	{
		return $this->execute();
   	}

   	/**
   	 * Builds the filter and executes the sql, returning a single row
   	 */
   	function get_one($field)
   	{
		$result = $this->get_row();
		
		return $result[$field];
   	}
   	
   	/**
   	 * Builds the filter and executes the sql returning a single dimensional array of the specified result field
   	 */
   	function get_array($field)
   	{
   		$arr = array();

   		foreach($this->execute($field) as $row)
   			$arr[] = $row[$field];
   			
   		return $arr;
   	}
   	
   	

   	/**
   	 * Builds the filter and executes the sql, returning the total count of items.
   	 */
   	function get_count($field=null, $distinct=false)
   	{
   		if (!$field)
   			$field = $this->model->primary_key;
   			
		$results = $this->get_array($field);
		
		return ($results['total_count']) ? $results['total_count'] : '0';
   	}

}

