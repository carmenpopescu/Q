<?php
namespace Q;

require_once 'Q/Transform/Exception.php';
require_once 'Q/Transform.php';
require_once 'Q/Transform/Serialize/Ini.php';
require_once 'Q/Fs.php';

/**
 * Load a ini file into an array
 *
 * @package Transform
 */
class Transform_Unserialize_Ini extends Transform
{
    /**
     * Get a transformer that does the reverse action.
     * 
     * @param Transformer $chain
     * @return Transformer
     */
    public function getReverse($chain=null)
    {
        $ob = new Transform_Serialize_Ini($this);
        if ($chain) $ob->chainInput($chain);
        return $this->chainInput ? $this->chainInput->getReverse($ob) : $ob;  
    }
		
    /**
     * Transform data and return the result.
     *
     * @param string $data  Yaml string
     * @return array
     */
    public function process($data)
    {
        if ($this->chainInput) $data = $this->chainInput->process($data);
        if ($data instanceof Fs_Node) $data = parse_ini_file($data, true);
          else $data = parse_ini_string((string)$data, true);
          
        return $data;
    }
}
