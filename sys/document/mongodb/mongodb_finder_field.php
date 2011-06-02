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
**/

uses('sys.document.finder_field');

class MongodbFinderField extends FinderField
{
    var $finder=null;
    var $field='';
    var $expr=null;

    public function __construct($finder,$field)
    {
        $this->finder=$finder;
        $this->field=$field;
    }

    public function equals($value)
    {
        $this->expr=$value;
        
        return $this->finder;
    }

    public function not_equal($value)
    {
        $this->expr=array (
            '$ne' => $value
        );

        return $this->finder;
    }

    public function greater_equal($value)
    {
        $this->expr=array (
            '$gte' => $value
        );

        return $this->finder;
    }

    public function less_equal($value)
    {
        $this->expr=array (
            '$lte' => $value
        );

        return $this->finder;
    }

    public function greater($value)
    {
        $this->expr=array (
            '$gt' => $value
        );

        return $this->finder;
    }

    public function less($value)
    {
        $this->expr=array (
            '$lt' => $value
        );

        return $this->finder;
    }
    
    public function is_in($values)
    {
        $this->expr=array (
            '$in' => $values
        );
    	
        return $this->finder;
    }
    
    public function is_not_in($values)
    {
        $this->expr=array (
            '$nin' => $values
        );
    	
        return $this->finder;
    }
    
    public function starts_with($value)
    {
        $this->expr=array(
            '$regex'=> "^$value",
            '$options' => 'i'
        );
        return $this->finder;
    }
    
    public function ends_with($value)
    {
        $this->expr=array(
            '$regex'=> "$value\$",
            '$options' => 'i'
        );
        return $this->finder;
    }
    
    public function contains($value)
    {
        $this->expr=array(
            '$regex'=> "$value",
            '$options' => 'i'
        );
        return $this->finder;
    }
    
    public function within($min,$max)
    {
        $this->expr=array (
            '$lte' => min($min,$max),
            '$gte' => max($min,$max),
        );
        return $this->finder;
    }
    
    public function not_null()
    {
        $this->expr=array (
                    '$ne' => null
                );

        return $this->finder;
    }
    
    public function is_null()
    {
        $this->expr=null;

        return $this->finder;
    }

    public function exists()
    {
        $this->expr=array(
            '$exists' => true
        );

        return $this->finder;
    }

    public function not_exists()
    {
        $this->expr=array(
            '$exists' => false
        );

        return $this->finder;
    }

    public function near($loc,$distance=5)
    {
        $this->expr=array(
            '$near' => $loc,
            '$distance' => $distance
        );

        return $this->finder;
    }

}
