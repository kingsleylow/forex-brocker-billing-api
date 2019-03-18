<?php

class FT_Form_validation extends CI_Form_validation {

    public function __construct()
    {
        parent::__construct();
    }

    public function set_rules($field, $label = '', $rules = '', $error = '')
    {
        // No reason to set rules if we have no POST data
        if (count($_POST) == 0)
        {
            return $this;
        }

        // If an array was passed via the first parameter instead of indidual string
        // values we cycle through it and recursively call this function.
        if (is_array($field))
        {
            foreach ($field as $row)
            {
                // Houston, we have a problem...
                if ( ! isset($row['field']) OR ! isset($row['rules']))
                {
                    continue;
                }

                // If the field label wasn't passed we use the field name
                $label = ( ! isset($row['label'])) ? $row['field'] : $row['label'];

                // Here we go!
                $this->set_rules($row['field'], $label, $row['rules']);
            }
            return $this;
        }

        // No fields? Nothing to do...
        if ( ! is_string($field) OR  ! is_string($rules) OR $field == '')
        {
            return $this;
        }

        // If the field label wasn't passed we use the field name
        $label = ($label == '') ? $field : $label;

        // Is the field name an array?  We test for the existence of a bracket "[" in
        // the field name to determine this.  If it is an array, we break it apart
        // into its components so that we can fetch the corresponding POST data later
        if (strpos($field, '[') !== FALSE AND preg_match_all('/\[(.*?)\]/', $field, $matches))
        {
            // Note: Due to a bug in current() that affects some versions
            // of PHP we can not pass function call directly into it
            $x = explode('[', $field);
            $indexes[] = current($x);

            for ($i = 0; $i < count($matches['0']); $i++)
            {
                if ($matches['1'][$i] != '')
                {
                    $indexes[] = $matches['1'][$i];
                }
            }

            $is_array = TRUE;
        }
        else
        {
            $indexes	= array();
            $is_array	= FALSE;
        }

        // Build our master array
        $this->_field_data[$field] = array(
            'field'				=> $field,
            'label'				=> $label,
            'rules'				=> $rules,
            'is_array'			=> $is_array,
            'keys'				=> $indexes,
            'postdata'			=> NULL,
            'error'				=> $error
        );

        return $this;
    }

    protected function _execute($row, $rules, $postdata = NULL, $cycles = 0)
    {
        // If the $_POST data is an array we will run a recursive call
        if (is_array($postdata))
        {
            foreach ($postdata as $key => $val)
            {
                $this->_execute($row, $rules, $val, $cycles);
                $cycles++;
            }

            return;
        }

        // --------------------------------------------------------------------

        // If the field is blank, but NOT required, no further tests are necessary
        $callback = FALSE;
        if ( ! in_array('required', $rules) AND is_null($postdata))
        {
            // Before we bail out, does the rule contain a callback?
            if (preg_match("/(callback_\w+(\[.*?\])?)/", implode(' ', $rules), $match))
            {
                $callback = TRUE;
                $rules = (array('1' => $match[1]));
            }
            else
            {
                return;
            }
        }

        // --------------------------------------------------------------------

        // Isset Test. Typically this rule will only apply to checkboxes.
        if (is_null($postdata) AND $callback == FALSE)
        {
            if (in_array('isset', $rules, TRUE) OR in_array('required', $rules))
            {
                // Set the message type
                $type = (in_array('required', $rules)) ? 'required' : 'isset';

                if ( ! isset($this->_error_messages[$type]))
                {
                    if (FALSE === ($line = $this->CI->lang->line($type)))
                    {
                        $line = 'The field was not set';
                    }
                }
                else
                {
                    $line = $this->_error_messages[$type];
                }

                // Build the error message
                $message = sprintf($line, $this->_translate_fieldname($row['label']));

                // Save the error message
                $this->_field_data[$row['field']]['error'] = $message;

                if ( ! isset($this->_error_array[$row['field']]))
                {
                    $this->_error_array[$row['field']] = $message;
                }
            }

            return;
        }

        // --------------------------------------------------------------------

        // Cycle through each rule and run it
        foreach ($rules As $rule)
        {
            $_in_array = FALSE;

            // We set the $postdata variable with the current data in our master array so that
            // each cycle of the loop is dealing with the processed data from the last cycle
            if ($row['is_array'] == TRUE AND is_array($this->_field_data[$row['field']]['postdata']))
            {
                // We shouldn't need this safety, but just in case there isn't an array index
                // associated with this cycle we'll bail out
                if ( ! isset($this->_field_data[$row['field']]['postdata'][$cycles]))
                {
                    continue;
                }

                $postdata = $this->_field_data[$row['field']]['postdata'][$cycles];
                $_in_array = TRUE;
            }
            else
            {
                $postdata = $this->_field_data[$row['field']]['postdata'];
            }

            // --------------------------------------------------------------------

            // Is the rule a callback?
            $callback = FALSE;
            if (substr($rule, 0, 9) == 'callback_')
            {
                $rule = substr($rule, 9);
                $callback = TRUE;
            }

            // Strip the parameter (if exists) from the rule
            // Rules can contain a parameter: max_length[5]
            $param = FALSE;
            if (preg_match("/(.*?)\[(.*)\]/", $rule, $match))
            {
                $rule	= $match[1];
                $param	= $match[2];
            }

            if (!is_null($param) && !is_numeric($param) && $param !== FALSE)
            {
                $param = $this->_field_data[$param]['postdata'];
            }
            // Call the function that corresponds to the rule
            if ($callback === TRUE)
            {
                if ( ! method_exists($this->CI, $rule))
                {
                    continue;
                }

                // Run the function and grab the result
                $result = $this->CI->$rule($postdata, $param);

                // Re-assign the result to the master data array
                if ($_in_array == TRUE)
                {
                    $this->_field_data[$row['field']]['postdata'][$cycles] = (is_bool($result)) ? $postdata : $result;
                }
                else
                {
                    $this->_field_data[$row['field']]['postdata'] = (is_bool($result)) ? $postdata : $result;
                }

                // If the field isn't required and we just processed a callback we'll move on...
                if ( ! in_array('required', $rules, TRUE) AND $result !== FALSE)
                {
                    continue;
                }
            }
            else
            {
                if ( ! method_exists($this, $rule))
                {
                    // If our own wrapper function doesn't exist we see if a native PHP function does.
                    // Users can use any native PHP function call that has one param.
                    if (function_exists($rule))
                    {
                        $result = $rule($postdata);

                        if ($_in_array == TRUE)
                        {
                            $this->_field_data[$row['field']]['postdata'][$cycles] = (is_bool($result)) ? $postdata : $result;
                        }
                        else
                        {
                            $this->_field_data[$row['field']]['postdata'] = (is_bool($result)) ? $postdata : $result;
                        }
                    }
                    else
                    {
                        log_message('debug', "Unable to find validation rule: ".$rule);
                    }

                    continue;
                }

                $result = $this->$rule($postdata, $param);

                if ($_in_array == TRUE)
                {
                    $this->_field_data[$row['field']]['postdata'][$cycles] = (is_bool($result)) ? $postdata : $result;
                }
                else
                {
                    $this->_field_data[$row['field']]['postdata'] = (is_bool($result)) ? $postdata : $result;
                }
            }

            // Did the rule test negatively?  If so, grab the error.
            if ($result === FALSE)
            {
                if ( ! isset($this->_error_messages[$rule]))
                {
                    if (FALSE === ($line = $this->CI->lang->line($rule)))
                    {
                        //$line = 'Unable to access an error message corresponding to your field name.';
                        $line = 'Wrong value';
                    }
                }
                else
                {
                    $line = $this->_error_messages[$rule];
                }

                // Is the parameter we are inserting into the error message the name
                // of another field?  If so we need to grab its "field label"
                if (isset($this->_field_data[$param]) AND isset($this->_field_data[$param]['label']))
                {
                    $param = $this->_translate_fieldname($this->_field_data[$param]['label']);
                }

                // Build the error message
                $message = sprintf($line, $this->_translate_fieldname($row['label']), $param);

                if (empty($this->_field_data[$row['field']]['error']))
                {
                    // Save the error message
                    $this->_field_data[$row['field']]['error'] = $message;

                    if ( ! isset($this->_error_array[$row['field']]))
                    {
                        $this->_error_array[$row['field']] = $message;
                    }
                }
                else
                {
                    $this->_error_array[$row['field']] = $this->_field_data[$row['field']]['error'];
                }

                return;
            }
        }
    }

    public function commitError($name, $message)
    {
        $this->_error_array[$name] = $message;
    }

    public function getErrorsArray()
    {
        return $this->_error_array;
    }
} 