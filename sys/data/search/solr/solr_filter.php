<?
uses('system.data.field');
uses('system.data.filter');
uses('system.data.order_by');
uses('system.data.join');
uses('system.data.search.solr.solr_filter_field');
uses('system.data.search.solr.facets');
uses('system.data.search.solr.highlights');
uses('system.data.search.solr.spellcheck');

/**
 * Model filter
 * 
 */


class SOLRFilter extends Filter
{
	protected $fields=array();		/** List of filtered fields */
	public $q_value=null;

	public  $model=null;			/** Reference to the Model being filtered */
	public $class=null;
	
	public $select='*'; // SOLR defaults to returning everything unless otherwise specified

	public $order_by=null;			/** OrderBy class for controlling sort order */
	
	public $limit=null;				/** Result limits */
	public $offset=null;			/** Result offset */
	
	private $grouplevel=0;
	
	public $result_format = 'phps';
	
	public $facet_search_ext = null;
	
	public $facet=null;
	public $highlight=null;
	
	public $clustering=false;
	
	public $spellcheck=false;
	
	public $tv=null; // term vectors
	public $tv_unique_key=null; // uses SOLR docUniqueId not docId
	
	public $add_phrase_query; // workaround to get exact matching w/ shingles
		
	public $boost_function=null;

	public $query_parser='edismax';
	
	public $more_like_this=false;


	
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
		//$model=str_replace('.','/',$model);
		
		uses('model.'.$model);

		$parts=explode('.',$model);
		
		$class=str_replace('_','',$parts[1]);
		$instance=new $class();
		
		return new SOLRFilter($instance,$class, false);
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
		$q = array($this->q_value);
		$loc = array();

		
		// Need this phrase query (which looks like a quoted mirror of the main query:  ?q=new york "new york"&...)
		// in special cases where the edismax/dismax parsers currently crap out when matching multi-word terms.
		// SOLR doesn't really handle a term like [new york] very well when it's all part of one token.
		// This is only needed if you want to force an exact match using a ShingleFilter.  
		// Otherwise, the std behavior is to match [new] [york] on either term when searched individually
		if ($this->add_phrase_query)
		{
			$phrase_query = $this->build_phrase_query($this->q_value);
			$phrase_query = trim($phrase_query);

			if (!empty($phrase_query))
				$q[] = $phrase_query;
		}	
		

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
				$query[] = 'fq='.urlencode(str_replace('+',' ', $filter_query));
				
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

		if ($this->clustering)
			$query[] = 'clustering=true';
			
		if ($this->spellcheck)
			$query[] = 'spellcheck=true';
			
		if ($this->tv)
		{
			$query[] = 'tv=true';
			
			foreach(split(',',$this->tv) as $tv_field)
				$query[] = 'tv.fl='.trim($tv_field);
		}	
			// handle faceting
   		if (!empty($this->facet->fields))
   			$query[] = 'facet=true';
   		
   			
   		// TODO:  Factor this into Facet
   		foreach ($this->facet->fields as $facet)
   		{
   			if (($facet->multi || $facet->freeze) && isset($this->fields[$facet->filter_name]))
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
   		
		
		// specify query parser
		if (!empty($this->query_parser))
			$query[] = 'qt='.$this->query_parser;

		// handle boost function
		if (!empty($this->boost_function))
			$query[] = 'bf='.$this->boost_function;
			
   		// tack on the result_format
   		$query[] = 'wt='.$this->result_format;			

   		// don't need the header
   		$query[] = 'omitHeader=true';
   		
   		
   		$handler = ($this->more_like_this) ? 'mlt' : 'select';
   		
   		return SOLR_SERVER . '/' . $handler . '?' . implode($query, '&');
   	}

   	
   	function build_phrase_query($query)
   	{
   		// don't duplicate queries that already have quotes in them
   		if (false!==strpos($query,'"'))
   			return null;
   			
	   	$input = explode(' ', $query);
	   	$output = null;
	   	
	   	$phrases = array();
	   	$phrase_no = 0;
	   	$seen_phrase_break = false;
	   	
	   	foreach($input as $in)
	   	{
	   		$in = trim($in);
	   		
	   		if ($in[0]!='-' && $in[0]!='+' && strtolower($in)!='and' && strtolower($in)!='or')
	   		{
	   			if ($seen_phrase_break)
	   			{
	   				$phrase_no++;
	   				$seen_phrase_break = false;
	   			}
	   				   				
	   			$phrases[$phrase_no][] = $in;
	   		}	   		
   			else
   			{
	   			$seen_phrase_break = true;
   			}	
	   	}

	   	foreach ($phrases as $phrase)
	   		if (count($phrase) > 1)
	   			$output .= ' "' . implode(' ', $phrase) . '"';	
	   	
	   	return $output;
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
//dump('solr_filter->execute(): ' . $this->build_query());   			

   		$response = file_get_contents($this->build_query());

   		if ($this->result_format == 'php')
			eval("\$response_array = " . $response . ";");
		else if ($this->result_format == 'phps')
			$response_array = unserialize($response);
		else 
			throw new Exception ("HeavyMetal does not support SOLR result format: wt=".$this->response_type);

		$result = array();
		
		$result = $response_array['response']['docs'];
		$result_count = count($result);

		//  weave in facets and meta info	
		$result['count'] = $result_count;
		$result['total_count'] = $response_array['response']['numFound'];
		$result['facet_counts'] = $response_array['facet_counts']['facet_fields'];
		$result['interesting_terms'] = $response_array['interestingTerms'];

		//  weave clusters in as a facet (replace cluster facet)
		if ($response_array['clusters'])
		{
			$result['facet_counts']['cluster'] = array();
			
			foreach ($response_array['clusters'] as $cluster)
				if ($cluster['labels'][0] != "Other Topics")
					$result['facet_counts']['cluster'][$cluster['labels'][0]] = count($cluster['docs']);
		}

		// attach spelling suggestion info
		if ($response_array['spellcheck'])
		{
			$result['spellcheck'] = new Spellcheck($response_array['spellcheck']);
		}
		
		// overlay any highlit results into main result set
		foreach ($response_array['highlighting'] as $id => $hi_fields)
			for ($i=0; $i<$result_count; $i++)
				if ($result[$i][$this->model->primary_key]==$id)	
					foreach ($hi_fields as $field_name => $frags)
						if (!empty($frags[0]))	
							$result[$i][$field_name]=$frags[0];
							
		
		// overlay any term-vector information if present
		foreach ($response_array['termVectors'] as $doc)
			for ($i=0; $i<$result_count; $i++)
				if ($result[$i][$this->tv_unique_key]==$doc['uniqueKey'])
					foreach($doc as $fieldname => $termlist)
						if (is_array($termlist))
							$result[$i]['termvectors'][$fieldname] = implode('|', array_keys($termlist));

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
			if (is_array($row))
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

