<?php
namespace Library;


class Validate
{
    private $controller;    // Stores a reference to the calling controller.
    private $errors;        // Array of validation errors.
    private $data;          // The data array to be validated. Defaults to $_POST.
    
    // Some things to validate?
    // required
    // valid_email
    // matches[password]
    // min_length[5]
    // max_length[12]
    // PHP single arg functions like: htmlspecialchars, trim
    // Custom functions
    
    public function __construct($controller, $data = false)
    {
        // Store reference to calling controller.
        $this->controller = $controller;
        
        // If no data is passed in for validation, default to $_POST array.
        if ($data == false)
            $this->data = $_POST;
        else 
            $this->data = $data;
        
        // Init stuff.
        $this->errors = array();
        $this->lang = new \stdClass();
        
        // Setting this up for future language localization.
        $this->lang->required = '%s is required!';
    }
    
    
    /**
     *  Passes list of errors to calling controller.
     *  Returns TRUE if all checks passed. False otherwise.
     */
    public function run() 
    {
        if (count($this->errors) == 0) {
            return true;
        }
        else {
            $this->controller->setData('errors', $this->errors);
            return false;
        }       
    }
    
    
    /**
     *  Checks a data field for validness by running all the specified checks
     *  against it.
     */
    public function rule($fieldName, $humanName, $checks)
    {
        $checkFailures = 0;
        
        // Grab data from array or leave as false.
        $fieldData = false;
        if (isset($this->data[$fieldName])) {
            $fieldData = $this->data[$fieldName];
        }
        
        // Run checks
        foreach ($checks as $check) {
            
            if (is_array($check)) {
                
                // Grab custom check type. Ex. "call"
                $custom = '_'.$check[0];
                
                // Remove the check type from array.
                array_shift($check);
                
                // Add the fieldData to the front of the array
                array_unshift($check, $fieldData);
                
                // Call the custom check. 
                $checkResult = call_user_func_array(array($this, $custom), $check);
            }
            else {
                // Append underscore to the check method's name.
                $check = '_'.$check;
                
                // Call a built-in check that's part of this Validation class.
                $checkResult = $this->$check($fieldData, $humanName);    
            }
            
            // If the result of the called check is anything other than FALSE, set a validation error.
            if ($checkResult) {
                $this->errors[] = $checkResult;
                $checkFailures++;
            }
        }
        
        // After all checks for this data field have been run, return if Validation passed (TRUE) or failed (FALSE);
        return $checkFailures == 0 ? true : false;
    }
    
    /**
     *  For resetting a form field's data after a failed validation.
     *  Default is displayed if no data exists for this field.
     */
    public function setField($name, $default = '') 
    {
        if (isset($this->data[$name])) {
            return $this->data[$name];
        }
        else {
            return $default;
        }
    }
    
    
    /**
     *  For resetting a form Checkbox's data after a failed validation.
     *  $name = checkbox's name
     *  $value = checkbox's value
     *  $default = whether this checkbox should be checked by default.
     */
    public function setCheckbox($name, $value, $default = false) 
    {
        if (isset($this->data[$name])) {
            $return = '';
            foreach ($this->data[$name] as $checkBox) {
                if ($checkBox == $value) {
                    $return = 'checked';
                }
            }
            return $return;
        }
        else {
            if ($default) {
                return 'checked';
            }
            else {
                return '';
            }
        }
    }
    
    /**
     *  For resetting a form Select's data after a failed validation.
     *  $name = Select's name
     *  $value = Option's value
     *  $default = whether this Option should be selected by default.
     */
    public function setSelect($name, $value, $default = false) 
    {
        if (isset($this->data[$name])) {
            if ($this->data[$name] == $value) {
                return 'selected';
            }
            else {
                return '';
            }
        }
        else {
            if ($default) {
                return 'selected';
            }
            else {
                return '';
            }
        }
    }
    
    
    ////////////////////////////////////////////////
    //
    //  The Validation Methods
    //
    ////////////////////////////////////////////////
    
    
    /**
     *  Form field is required.
     */
    protected function _required($fieldData, $humanName)
    {
        if ($fieldData)
            return false;
        else
            return sprintf($this->lang->required, $humanName);
    }
    
    
    /**
     *  This handles when a user wants to call a custom validation method in some other class.
     *  A common example would be checking if a username is already taken in a database
     *  when using a user register form.
     *
     *  $fieldData is the actual data.
     *  $controller is the controller that needs to be invoked for this custom check.
     *  $method is the method that needs to be called.
     *  $passing defines what the method should return to pass the test. Two Examples below:
     *          $user->nameExists($fieldData) you would want to return FALSE. So set $passing = false.
     *          $user->nameAvailable($fieldData) you would want to return TRUE. So set $passing = true.
     *  $message is the custom error message to display if the check fails.
     */
    protected function _call($fieldData, $controller, $method, $passing, $message)
    {      
        // If data to be passed to the method isn't an array, put it in array format.
        if (!is_array($fieldData))
            $fieldData = array($fieldData);
        
        // Call custom controller->method and pass the data to it.
        $result = call_user_func_array(array($controller, $method), $fieldData);
        
        // If the returned result meets expections, pass test (return false) otherwise return message.
        return $result == $passing ? false : $message;
    }
    
    
    /**
     *  Just trims the field. This check Never fails.
     */
    protected function _trim(&$fieldData)
    {
        $fieldData = trim($fieldData);
        return false;
    }
    
}