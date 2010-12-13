<?

/**
 * Contains a list of facets for a filter
 */

class Facet
{
	public $field_name = null;
	
	// Used by smartfilter templates
	public $show_counts = true;
	public $count_ceiling = null;
	
	// Used by SOLRFilter and SOLRFilterField to exclude criteria
	// reducing facet counts in the case of multi-choice filter fields
	public $multi = false;

	// Used by SOLR (mincount and limit)
	public $min_count = null;
	public $limit = null;

	public $sort = null;
	
	/**
	 * Constructor
	 * 
	 * @param Model $model A reference to the model being filtered/sorted
	 */
	public function __construct($field_name)
	{
		$this->field_name = $field_name;
	}
}