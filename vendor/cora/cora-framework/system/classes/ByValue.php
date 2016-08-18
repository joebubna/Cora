<?php 
namespace Cora;
/**
 * 
 */
 class ByValue implements Strategy {
 
    private $value;

    function __construct( $value ) {
        $this->value = $value;
    }

    function getValue() {
         return $this->value;
    }

    public function compareTo($a, $b) { 

        $value = $this->value;

        if (is_object($a->$value) && is_object($b->$value)) {
            if (preg_match('/DueDate/', get_class($a->$value))) {
                if (process_date($a->$value->due_date) == process_date($b->$value->due_date)) return 0;
                return process_date($a->$value->due_date) > process_date($b->$value->due_date) ? 1 : -1;
            }
        }

        if (preg_match('/id/', $value) || in_array($value, array('calls', 'appts'))) {
            if ($a->$value == $b->$value) return 0;
            return $a->$value > $b->$value ? 1 : -1;
        }
        elseif($value == 'score') {
            if ($a->$value == $b->$value) return 0;
            return $a->$value > $b->$value ? 1 : -1;
        }
        elseif(preg_match('/date/', $value)) {
            if ($a->$value == $b->$value) return 0;
            return process_date($a->$value, 'Y-m-d') > process_date($b->$value, 'Y-m-d') ? 1 : -1;
        }
        elseif(preg_match('/time/', $value)) {
            if ($a->$value == $b->$value) return 0;
            return process_date($a->$value, 'Y-m-d H:i:s') > process_date($b->$value, 'Y-m-d H:i:s') ? 1 : -1;
        }
        elseif($value == 'month' || $value == 'DOB_day' || $value == 'DOB_month') {
            if(str_pad($a->$value, 2, '0', STR_PAD_LEFT) == str_pad($b->$value, 2, '0', STR_PAD_LEFT)) return 0;
            return str_pad($a->$value, 2, '0', STR_PAD_LEFT) > str_pad($b->$value, 2, '0', STR_PAD_LEFT) ? 1 : -1;
        }
        elseif(preg_match('/cost/', $value) || preg_match('/price/', $value) || preg_match('/markup/', $value)) {
            if ($a->$value == $b->$value) return 0;
            return $a->$value > $b->$value ? 1 : -1;
        } elseif($value == 'priority') {
            $priorities = array('highest', 'high', 'normal', 'low', 'lowest');
            if (array_search($a->$value, $priorities) == array_search($b->$value, $priorities)) return 0;
            return array_search($a->$value, $priorities) > array_search($b->$value, $priorities) ? 1 : -1;
        } else {
            return strcasecmp($a->$value, $b->$value);
        }
    }
}