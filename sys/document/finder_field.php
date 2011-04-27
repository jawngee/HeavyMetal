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

/**
 * The FinderField encapsulates the criteria for querying a document field.
 */
abstract class FinderField
{
    /**
     * Instance of the Finder that owns this field.
     * @var Finder
     */
    var $finder=null;

    /**
     * The name of the field.
     * @var string
     */
    var $field='';

    /**
     * The expression to match on the field.
     * @var mixed
     */
    var $expr=null;

    /**
     * FinderField constructor
     * @param  $finder The finder that owns this.
     * @param  $field The name of the field it represents.
     */
    public function __construct($finder,$field)
    {
        $this->finder=$finder;
        $this->field=$field;
    }

    /**
     * Equal to
     * @abstract
     * @param  $value
     * @return Finder
     */
    abstract function equals($value);

    /**
     * Not equal to
     *
     * @abstract
     * @param  $value
     * @return Finder
     */
    abstract function not_equal($value);

    /**
     * Greater than equal
     *
     * @abstract
     * @param  $value
     * @return Finder
     */
    abstract function greater_equal($value);

    /**
     * Less than equal
     *
     * @abstract
     * @param  $value
     * @return Finder
     */
    abstract function less_equal($value);

    /**
     * Greater than
     *
     * @abstract
     * @param  $value
     * @return Finder
     */
    abstract function greater($value);

    /**
     * Less than
     *
     * @abstract
     * @param  $value
     * @return Finder
     */
    abstract function less($value);

    /**
     * in (val1,val2,val3)
     *
     * @abstract
     * @param  $values
     * @return Finder
     */
    abstract function is_in($values);

    /**
     * not in (val1,val2,val3)
     *
     * @abstract
     * @param  $values
     * @return Finder
     */
    abstract function is_not_in($values);

    /**
     * Starts with string.
     *
     * @abstract
     * @param  $value
     * @return Finder
     */
    abstract function starts_with($value);

    /**
     * Ends with string.
     * @abstract
     * @param  $value
     * @return Finder
     */
    abstract function ends_with($value);

    /**
     * Contains string.
     *
     * @abstract
     * @param  $value
     * @return Finder
     */
    abstract function contains($value);

    /**
     * Within range
     *
     * @abstract
     * @param  $min
     * @param  $max
     * @return Finder
     */
    abstract function within($min,$max);

    /**
     * Not null
     *
     * @abstract
     * @return Finder
     */
    abstract function not_null();

    /**
     * Is null
     *
     * @abstract
     * @return Finder
     */
    abstract function is_null();

    /**
     * Field exists in document
     *
     * @abstract
     * @return Finder
     */
    abstract function exists();

    /**
     * Field does not exist in document
     *
     * @abstract
     * @return Finder
     */
    abstract function not_exists();
}
