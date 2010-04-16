<?

/*
 *  Container for SOLR spellchecker information 
 */


class Spellcheck
{
	private $term;
	private $termFrequency;
	private $startOffset;
	private $endOffset;
	private $collation;
	
	public $correctlySpelled;
	public $suggestions = array(); // List of suggested terms followed by frequency
	
	public function __construct($solrSpellcheck)
	{
		$this->correctlySpelled = $solrSpellcheck['suggestions']['correctlySpelled'];
		$this->collation = $solrSpellcheck['suggestions']['collation'];

		foreach($solrSpellcheck['suggestions'] as $term => $info)
		{
			$this->term = $term;
			$this->termFrequency = $info['origFreq'];
			$this->startOffset = $info['startOffset'];
			$this->endOffset = $info['endOffset'];

			foreach($info['suggestion'] as $sugg)
			{
				$this->suggestions[$sugg['word']] = $sugg['freq'];
			}
			
			arsort($this->suggestions); // least frequent to most frequent 
			
			
			break;  // only should be getting back one of these
		}
	}
	
	public function suggestion()
	{
		return ($this->correctlySpelled)?null:$this->collation;
	}
	
}
 