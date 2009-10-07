<?php
uses('sys.data.relation');
    /**
     * Accessor Relation
     *
     * For cases where the relation cannot be materialized through a simple foreign-key query,
     * this class expects and will execute a method called get_<$name>() (in the case of Relation::RELATION_SINGLE)
     * or get_<$name>s() (in the case of Relation::RELATION_MANY) on the parent model class.
     * 
     * As an example, this relation is being used to get permissions for a particular pitch
     * using the call $pitch->permissions.  For this to work, two things need to be added to the Pitch model class:
     * 1)  Add a new AccessorRelation to the related array: 
     *          $this->related['permissions']=new AccessorRelation($this, 'permissions',Relation::RELATION_MANY,'privacy/permission');
     * 2)  Implement a public method named 'get_permissions()' which will return the appropriate rowset of permissions. 
     * 
     */

class NotImplementedException extends Exception
{
    public function __construct($message, $code = 0, $class=null, $method=null)
    {
        if (isset($method))
        {
            $message .= " (Expected method ($method) not implemented in $class)";
        }
        else
        {
            $message .= " (Could not find class ($class))";
        }
        
        parent::__construct($message, $code);
    }
}

class MethodRelation extends Relation
{
    // Can safely ignore $model, $field and foreign_field since we're not using them.
    public function __construct($parent,$method_name)
    {
        parent::__construct($parent,$method_name,null,null,null);
    }
    
    // Override the standard db lookup in deference to a specialized accessor implementation
    public function get_single()
    {              
        return $this->call_method($this->parent, $this->name);
    }
    public function get_many()
    {
        return $this->call_method($this->parent, $this->name);
    }
    
    private function call_method($object, $method)
    {

        if (!method_exists($object, $method))
            throw new NotImplementedException(null,null,get_class($object), $method);
        
        return $object->$method();
    }
} 