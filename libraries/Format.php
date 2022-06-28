<?php
namespace Restserver\Libraries;
use Exception;

defined('BASEPATH') OR exit('No direct script access allowed');

class Format {

    
    const ARRAY_FORMAT = 'array';

    
    const CSV_FORMAT = 'csv';

    
    const JSON_FORMAT = 'json';

    
    const HTML_FORMAT = 'html';

    
    const PHP_FORMAT = 'php';

   
    const SERIALIZED_FORMAT = 'serialized';

   
    const XML_FORMAT = 'xml';

    
    const DEFAULT_FORMAT = self::JSON_FORMAT; // Couldn't be DEFAULT, as this is a keyword

    
    private $_CI;

   
    protected $_data = [];

    
    protected $_from_type = NULL;

    

    public function __construct($data = NULL, $from_type = NULL)
    {
        // Get the CodeIgniter reference
        $this->_CI = &get_instance();

        // Load the inflector helper
        $this->_CI->load->helper('inflector');

        // If the provided data is already formatted we should probably convert it to an array
        if ($from_type !== NULL)
        {
            if (method_exists($this, '_from_'.$from_type))
            {
                $data = call_user_func([$this, '_from_'.$from_type], $data);
            }
            else
            {
                throw new Exception('Format class does not support conversion from "'.$from_type.'".');
            }
        }

        // Set the member variable to the data passed
        $this->_data = $data;
    }

    
    public static function factory($data, $from_type = NULL)
    {
        // $class = __CLASS__;
        // return new $class();

        return new static($data, $from_type);
    }

    // FORMATTING OUTPUT ---------------------------------------------------------

    
    public function to_array($data = NULL)
    {
        // If no data is passed as a parameter, then use the data passed
        // via the constructor
        if ($data === NULL && func_num_args() === 0)
        {
            $data = $this->_data;
        }

        // Cast as an array if not already
        if (is_array($data) === FALSE)
        {
            $data = (array) $data;
        }

        $array = [];
        foreach ((array) $data as $key => $value)
        {
            if (is_object($value) === TRUE || is_array($value) === TRUE)
            {
                $array[$key] = $this->to_array($value);
            }
            else
            {
                $array[$key] = $value;
            }
        }

        return $array;
    }

    
    public function to_xml($data = NULL, $structure = NULL, $basenode = 'xml')
    {
        if ($data === NULL && func_num_args() === 0)
        {
            $data = $this->_data;
        }

        if ($structure === NULL)
        {
            $structure = simplexml_load_string("<?xml version='1.0' encoding='utf-8'?><$basenode />");
        }

        // Force it to be something useful
        if (is_array($data) === FALSE && is_object($data) === FALSE)
        {
            $data = (array) $data;
        }

        foreach ($data as $key => $value)
        {

            //change false/true to 0/1
            if (is_bool($value))
            {
                $value = (int) $value;
            }

            // no numeric keys in our xml please!
            if (is_numeric($key))
            {
                // make string key...
                $key = (singular($basenode) != $basenode) ? singular($basenode) : 'item';
            }

            // replace anything not alpha numeric
            $key = preg_replace('/[^a-z_\-0-9]/i', '', $key);

            if ($key === '_attributes' && (is_array($value) || is_object($value)))
            {
                $attributes = $value;
                if (is_object($attributes))
                {
                    $attributes = get_object_vars($attributes);
                }

                foreach ($attributes as $attribute_name => $attribute_value)
                {
                    $structure->addAttribute($attribute_name, $attribute_value);
                }
            }
            // if there is another array found recursively call this function
            elseif (is_array($value) || is_object($value))
            {
                $node = $structure->addChild($key);

                // recursive call.
                $this->to_xml($value, $node, $key);
            }
            else
            {
                // add single node.
                $value = htmlspecialchars(html_entity_decode($value, ENT_QUOTES, 'UTF-8'), ENT_QUOTES, 'UTF-8');

                $structure->addChild($key, $value);
            }
        }

        return $structure->asXML();
    }

   
    public function to_html($data = NULL)
    {
        // If no data is passed as a parameter, then use the data passed
        // via the constructor
        if ($data === NULL && func_num_args() === 0)
        {
            $data = $this->_data;
        }

        // Cast as an array if not already
        if (is_array($data) === FALSE)
        {
            $data = (array) $data;
        }

        // Check if it's a multi-dimensional array
        if (isset($data[0]) && count($data) !== count($data, COUNT_RECURSIVE))
        {
            // Multi-dimensional array
            $headings = array_keys($data[0]);
        }
        else
        {
            // Single array
            $headings = array_keys($data);
            $data = [$data];
        }

        // Load the table library
        $this->_CI->load->library('table');

        $this->_CI->table->set_heading($headings);

        foreach ($data as $row)
        {
            // Suppressing the "array to string conversion" notice
            // Keep the "evil" @ here
            $row = @array_map('strval', $row);

            $this->_CI->table->add_row($row);
        }

        return $this->_CI->table->generate();
    }

    
    public function to_csv($data = NULL, $delimiter = ',', $enclosure = '"')
    {
        // Use a threshold of 1 MB (1024 * 1024)
        $handle = fopen('php://temp/maxmemory:1048576', 'w');
        if ($handle === FALSE)
        {
            return NULL;
        }

        // If no data is passed as a parameter, then use the data passed
        // via the constructor
        if ($data === NULL && func_num_args() === 0)
        {
            $data = $this->_data;
        }

        // If NULL, then set as the default delimiter
        if ($delimiter === NULL)
        {
            $delimiter = ',';
        }

        // If NULL, then set as the default enclosure
        if ($enclosure === NULL)
        {
            $enclosure = '"';
        }

        // Cast as an array if not already
        if (is_array($data) === FALSE)
        {
            $data = (array) $data;
        }

        // Check if it's a multi-dimensional array
        if (isset($data[0]) && count($data) !== count($data, COUNT_RECURSIVE))
        {
            // Multi-dimensional array
            $headings = array_keys($data[0]);
        }
        else
        {
            // Single array
            $headings = array_keys($data);
            $data = [$data];
        }

        // Apply the headings
        fputcsv($handle, $headings, $delimiter, $enclosure);

        foreach ($data as $record)
        {
            // If the record is not an array, then break. This is because the 2nd param of
            // fputcsv() should be an array
            if (is_array($record) === FALSE)
            {
                break;
            }

            // Suppressing the "array to string conversion" notice.
            // Keep the "evil" @ here.
            $record = @ array_map('strval', $record);

            // Returns the length of the string written or FALSE
            fputcsv($handle, $record, $delimiter, $enclosure);
        }

        // Reset the file pointer
        rewind($handle);

        // Retrieve the csv contents
        $csv = stream_get_contents($handle);

        // Close the handle
        fclose($handle);

        // Convert UTF-8 encoding to UTF-16LE which is supported by MS Excel
        $csv = mb_convert_encoding($csv, 'UTF-16LE', 'UTF-8');

        return $csv;
    }

   
    public function to_json($data = NULL)
    {
        // If no data is passed as a parameter, then use the data passed
        // via the constructor
        if ($data === NULL && func_num_args() === 0)
        {
            $data = $this->_data;
        }

        // Get the callback parameter (if set)
        $callback = $this->_CI->input->get('callback');

        if (empty($callback) === TRUE)
        {
            return json_encode($data, JSON_UNESCAPED_UNICODE);
        }

        // We only honour a jsonp callback which are valid javascript identifiers
        elseif (preg_match('/^[a-z_\$][a-z0-9\$_]*(\.[a-z_\$][a-z0-9\$_]*)*$/i', $callback))
        {
            // Return the data as encoded json with a callback
            return $callback.'('.json_encode($data, JSON_UNESCAPED_UNICODE).');';
        }

        // An invalid jsonp callback function provided.
        // Though I don't believe this should be hardcoded here
        $data['warning'] = 'INVALID JSONP CALLBACK: '.$callback;

        return json_encode($data, JSON_UNESCAPED_UNICODE);
    }

   
    public function to_serialized($data = NULL)
    {
        // If no data is passed as a parameter, then use the data passed
        // via the constructor
        if ($data === NULL && func_num_args() === 0)
        {
            $data = $this->_data;
        }

        return serialize($data);
    }

   
    public function to_php($data = NULL)
    {
        // If no data is passed as a parameter, then use the data passed
        // via the constructor
        if ($data === NULL && func_num_args() === 0)
        {
            $data = $this->_data;
        }

        return var_export($data, TRUE);
    }

    // INTERNAL FUNCTIONS

   
    protected function _from_xml($data)
    {
        return $data ? (array) simplexml_load_string($data, 'SimpleXMLElement', LIBXML_NOCDATA) : [];
    }

  
    protected function _from_csv($data, $delimiter = ',', $enclosure = '"')
    {
        // If NULL, then set as the default delimiter
        if ($delimiter === NULL)
        {
            $delimiter = ',';
        }

        // If NULL, then set as the default enclosure
        if ($enclosure === NULL)
        {
            $enclosure = '"';
        }

        return str_getcsv($data, $delimiter, $enclosure);
    }

  
    protected function _from_json($data)
    {
        return json_decode(trim($data));
    }

   
    protected function _from_serialize($data)
    {
        return unserialize(trim($data));
    }

   
    protected function _from_php($data)
    {
        return trim($data);
    }
}
