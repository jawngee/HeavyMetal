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

uses('sys.document.document_store');
uses('sys.document.mongodb.mongodb_finder');

class MongodbDocumentStore extends DocumentStore
{
    /**
     * Instance of the Mongo class
     * @var Mongo
     */
    private $mongo=null;

    /**
     * Instance of a MongoDB class
     * @var MongoDB
     */
    private $mongo_db=null;

    /**
     * Cache of collections
     * @var array
     */
    private $collections=array();

    /**
     * Constructor 
     * @param  $dsn
     */
    public function __construct($dsn)
    {
        $db=array_pop(explode('/',$dsn));
        $this->mongo=new Mongo($dsn);
        $this->mongo_db=$this->mongo->selectDB($db);
    }

    /**
     * Retrieves a collection by name.
     * @param  $name
     * @return MongoCollection
     */
    public function collection($name)
    {
        if ($this->collections[$name])
            return $this->collections[$name];

        $c=$this->mongo_db->selectCollection($name);
        $this->collections[$name]=$c;

        return $c;
    }

    /**
     * Performs an update.
     *
     * @param  $document
     * @return void
     */
    private function _update($document)
    {
        if (!$document->pre_save(true))
            return;

        $c=$this->collection($document->doc_collection);
        $c->update(array('_id'=>$document->id),$document->to_array());
        $document->doc_state=Document::DOCUMENT_LIVE;

        $document->post_save(true);
    }

    /**
     * Performs a save.
     * @param  $document
     * @return void
     */
    private function _save($document)
    {
        if (!$document->pre_save(false))
            return;

        $c=$this->collection($document->doc_collection);
        
        try
        {
        	$c->insert($document->to_array());
        } 
        catch (Exception $ex)
        {
        	vomit($ex);
        }

        $document->doc_state=Document::DOCUMENT_LIVE;
        $document->post_save(false);
    }

    /**
     * @param  $document
     * @return void
     */
    function save($document)
    {
        if (($document->doc_state==Document::DOCUMENT_NEW) || ($document->doc_state==Document::DOCUMENT_DELETED))
            $this->_save($document);
        else if ($document->doc_state==Document::DOCUMENT_DIRTY)
            $this->_update($document);
    }

    /**
     * @param  $document
     * @return void
     */
    function remove($document)
    {
        if (!$document->pre_delete())
            return;

        $c=$this->collection($document->doc_collection);
        $c->remove(array('_id'=>$document->id));
        $document->doc_state=Document::DOCUMENT_DELETED;

        $document->post_delete();
    }

    /**
     * Performs the query and returns a cursor.
     * @param  $document
     * @param  $query
     * @param null $sorts
     * @param int $offset
     * @param int $limit
     * @return MongoCursor
     */
    private function _query($document,$query,$sorts=null,$offset=0,$limit=0)
    {
        $res=$this->collection($document->doc_collection)->find($query);

        if (count($sorts)>0)
        	$res->sort($sorts);

        if ($offset>0)
            $res->skip($offset);

        if ($limit>0)
            $res->limit($limit);

        return $res;
    }

    /**
     * @param  $document
     * @param  $query
     * @param null $sorts
     * @param int $offset
     * @param int $limit
     * @param bool $reconstitute
     * @return array
     */
    function query($document,$query,$sorts=null,$offset=0,$limit=0,$reconstitute=true)
    {
        $result=array();
        $res=$this->_query($document,$query,$sorts,$offset,$limit);

        foreach($res as $r)
        {
            if (($reconstitute) && (isset($r['__class'])))
            {
                $class=$r['__class'];
                if (class_exists($class))
                {
                    $r=new $class(null,$r);
                }
            }

            $result[]=$r;
        }            

        return $result;
    }


    /**
     * @param  $document
     * @param  $query
     * @return int
     */
    function count($document,$query)
    {
        $res=$this->_query($document,$query);
        return $res->count();
    }

    /**
     * @param  $document
     * @return MongodbFinder
     */
     function create_finder($document)
     {
        return new MongodbFinder($document);
     }
}