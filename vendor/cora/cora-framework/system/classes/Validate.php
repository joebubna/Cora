<?php
namespace Cora;


class Validate
{
    protected $controller;    // Stores a reference to the calling controller.
    protected $errors;        // Array of validation errors.
    protected $data;          // The data array to be validated. Defaults to $_POST.
    protected $lang;          // For holding error strings.
    protected $customChecks;  // For holding custom check definitions.
    protected $matchedArg;

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
        $this->customChecks = new \stdClass();
        $this->matchedArg = false;

        // Setting this up for future language localization.
        $this->lang->required       = '%s is required!';
        $this->lang->valid_email    = "%s must be a valid email address.";
        $this->lang->matches        = "%s does not match %s!";
        $this->lang->min_length     = "%s must be at least %s characters.";
        $this->lang->max_length     = "%s must be at most %s characters.";
    }


    public function getValue($fieldName)
    {
        // Grab data from array or leave as false if no data exists.
        $fieldData = false;
        if (isset($this->data[$fieldName])) {
            $fieldData = $this->data[$fieldName];
        }
        return $fieldData;
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
     *  Stores a customly defined validation check.
     */
    public function def ($checkName, $class, $method, $errorMessage, $passing = true, $arguments = false)
    {
        $this->customChecks->$checkName = ['_call', $class, $method, $passing, $arguments];
        $this->lang->$checkName = $errorMessage;
    }


    /**
     *  Checks a data field for validness by running all the specified checks
     *  against it.
     */
    public function rule($fieldName, $checks, $humanName = false)
    {
        $checkFailures = 0;

        // Default human readable name of form field to the field name.
        if ($humanName == false) {
            $humanName = ucfirst($fieldName);
        }

        // Grab data from array or leave as false if no data exists.
        $fieldData = $this->getValue($fieldName);

        if (!is_array($checks)) {
            $checks = explode('|', $checks);
        }

        // Run checks
        foreach ($checks as $check) {

            $checkName = $check;

            if (isset($this->customChecks->$check)) {

                // Grab this custom check's definition.
                $customCheckDef = $this->customChecks->$check;

                // Grab custom check type. Ex. "call"
                //$checkName = $check;
                $customType = $customCheckDef[0];

                // Define the arguments that will be passed to the custom check.
                $arguments = array($fieldData, $customCheckDef[1], $customCheckDef[2], $customCheckDef[3], $customCheckDef[4]);

                // Call the custom check.
                $checkResult = call_user_func_array(array($this, $customType), $arguments);
            }
            else {

                // See if validation check needs additional argument. E.g "matches[password]"
                $checkArgs = explode('[', $check, 2);
                if (count($checkArgs)>1) {

                    // Set corrected method check name to call. E.g. "matches[password]" calls "matches"
                    $check = $checkArgs[0];
                    $checkName = $checkArgs[0];

                    // explode strips out the leading '[' from '[value]' so we are just restoring it.
                    $checkArgs[1] = '['.$checkArgs[1];

                    // Grab arguments that need to be passed to Check. E.g. "matches[password][title]" would
                    // return "array('password', 'title')"
                    $args = array();
                    preg_match_all("/\[([^\]]*)\]/", $checkArgs[1], $args);
                    $this->matchedArg = $args[1][0];

                    // Append underscore to the check method's name.
                    $check = '_'.$checkName;

                    // Call a built-in check that's part of this Validation class.
                    $checkResult = $this->$check($fieldData, $args[1]);
                }
                else {
                    // Append underscore to the check method's name.
                    $check = '_'.$checkName;

                    // Call a built-in check that's part of this Validation class.
                    $checkResult = $this->$check($fieldData);
                }
            }

            // If the result of the called check is anything other than true, set a validation error.
            if ($checkResult == false) {
                $this->errors[] =  sprintf($this->lang->$checkName, $humanName, $this->matchedArg);
                $this->matchedArg = false;
                $checkFailures++;
            }
        }

        // After all checks for this data field have been run, return if Validation passed (TRUE) or failed (FALSE);
        return $checkFailures == 0 ? true : false;
    }



    ////////////////////////////////////////////////
    //
    //  The Validation Methods
    //
    ////////////////////////////////////////////////


    /**
     *  Form field is required.
     */
    protected function _required($fieldData)
    {
        if ($fieldData)
            return true;
        else
            return false;
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
     */
    protected function _call($fieldData, $controller, $method, $passing, $arguments)
    {
        // If data to be passed to the method isn't an array, put it in array format.
        $fieldData = array($fieldData, $arguments);
            

        // Call custom controller->method and pass the data to it.
        $result = call_user_func_array(array($controller, $method), $fieldData);

        // If the returned result meets expections, pass test (return false) otherwise return message.
        return $result == $passing ? true : false;
    }


    /**
     *  Just trims the field. This check Never fails.
     */
    protected function _trim(&$fieldData)
    {
        $fieldData = trim($fieldData);
        return true;
    }

    // valid_email
    protected function _valid_email($fieldData)
    {
        if (preg_match('/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,4}/', $fieldData)) {
            return true;
        }
        return false;
    }

    // matches
    protected function _matches($fieldData, $array)
    {
        $passes = true;
        foreach ($array as $dataName) {
            $dataValue = $this->getValue($dataName);
            if ($fieldData != $dataValue) {
                $passes = false;
            }
        }
        return $passes;
    }

    protected function _min_length($fieldData, $reqLength)
    {
        $passes = true;
        if (strlen($fieldData) < $reqLength[0]) {
            $passes = false;
        }
        return $passes;
    }

    protected function _max_length($fieldData, $reqLength)
    {
        $passes = true;
        if (strlen($fieldData) > $reqLength[0]) {
            $passes = false;
        }
        return $passes;
    }


    // matches[password]
    // min_length[5]
    // max_length[12]
    // PHP single arg functions like: htmlspecialchars, trim
    // Custom functions




    ////////////////////////////////////////////////
    //
    //  Form Utility Methods
    //
    ////////////////////////////////////////////////


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

}
