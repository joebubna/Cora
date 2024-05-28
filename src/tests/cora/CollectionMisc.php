<?php
namespace Controllers;
use Cora\Event;

class CollectionTest extends \Cora\App\Controller {

    public function performanceTest()
    {
        $reps = 30000;
        $c = $this->app->collection;
        $l = new \Illuminate\Support\Collection();
        $a = [];
        $s = new \stdClass();

        ////////
        echo "Adding $reps items to: <br>";
        $time = $this->measure(function(&$a, $reps) {
            for ($i=0; $i<$reps; $i++) {
                $a[] = $i;
            }
        }, $a, $reps);
        echo "Standard PHP Array = $time seconds<br>";

        $time = $this->measure(function(&$c, $reps) {
            for ($i=0; $i<$reps; $i++) {
                $c->add($i);
            }
        }, $c, $reps);
        echo "Cora Collection = $time seconds<br>";

        $time = $this->measure(function(&$l, $reps) {
            for ($i=0; $i<$reps; $i++) {
                $l->push($i);
            }
        }, $l, $reps);
        echo "Laravel Collection = $time seconds<br>";

        $time = $this->measure(function(&$s, $reps) {
            for ($i=0; $i<$reps; $i++) {
                $s->{"off$i"} = $i;
            }
        }, $s, $reps);
        echo "stdClass object = $time seconds<br><br>";

        ////////
        
        echo "Accessing all $reps items: <br>";
        $time = $this->measure(function(&$a, $reps) {
            $j = 0;
            while ($a[$j] != ($reps-1)) {
                $j += 1;
            }
        }, $a, $reps);
        echo "Standard PHP Array = $time seconds<br>";

        $time = $this->measure(function(&$c, $reps) {
            $j = 0;
            while ($c->fetchOffset($j) != ($reps-1)) {
                $j += 1;
            }
        }, $c, $reps);
        echo "Cora Collection = $time seconds<br>";

        $time = $this->measure(function(&$l, $reps) {
            $j = 0;
            while ($l->get($j) != ($reps-1)) {
                $j += 1;
            }
        }, $l, $reps);
        echo "Laravel Collection = $time seconds<br><br>";


        ////////
        
        echo "Counting all $reps items $reps times: <br>";
        $time = $this->measure(function(&$a, $reps) {
            for ($i=0; $i<$reps; $i++) {
                count($a);
            }
        }, $a, $reps);
        echo "Standard PHP Array = $time seconds<br>";

        $time = $this->measure(function(&$c, $reps) {
            for ($i=0; $i<$reps; $i++) {
                $c->count();
            }
        }, $c, $reps);
        echo "Cora Collection = $time seconds<br>";

        $time = $this->measure(function(&$l, $reps) {
            for ($i=0; $i<$reps; $i++) {
                $l->count();
            }
        }, $l, $reps);
        echo "Laravel Collection = $time seconds<br><br>";

        $this->sortTest();
    }

    public function sortTest() 
    {
        ini_set('memory_limit','2G');
        $reps = 30000;
        $c = $this->app->collection;
        $a = [];
        $a1 = [];

        ////////

        //echo "Adding $reps items to: <br>";
        $time = $this->measure(function(&$a, $reps) {
            for ($i=0; $i<$reps; $i++) {
                $a[] = new \Models\User($i);
            }
        }, $a, $reps);
        //echo "Standard PHP Array = $time seconds<br>";

        $time = $this->measure(function(&$c, $reps) {
            for ($i=0; $i<$reps; $i++) {
                $c->add(new \Models\User($i));
            }
        }, $c, $reps);
        //echo "Cora Collection = $time seconds<br><br>";


        // Setting up an array for sorting test
        for ($i=0; $i<$reps; $i++) {
            $a1[] = ['name' => "bob$i", 'value' => $i];
        }

        // ////////
        
        echo "Sorting all $reps items ".(1)." times: <br>";
        $time = $this->measure(function(&$c, $reps) {
            usort($c, function($a, $b) {
                return $a['value'] < $b['value'];
            });
        }, $a1, 1);
        echo "Standard PHP Array = $time seconds<br>";

        global $cs1;
        $time = $this->measure(function(&$c, $reps) {
            global $cs1;
            for ($i=0; $i<$reps; $i++) {
                $cs1 = $c->sort('email', false, true);
            }
        }, $c, 1);
        echo "Cora Collection = $time seconds<br>";
        
        // //////////

        echo "<br>";

        echo "Array Results:<br>";
        for ($i=0; $i<5; $i++) {
            echo $a1[$i]['value']."<br>";
        }
        echo "<BR>";

        echo "Cora Collection:<br>";
        for ($i=0; $i<5; $i++) {
            echo $cs1[$i]->email."<br>";
        }
        echo "<BR>";
    }

    protected function measure($code, &$input, $repetitions = 10000) 
    {
        $time_start = microtime(true);
        $code($input, $repetitions);
        $time_end = microtime(true);
        $seconds = $time_end - $time_start;
        return $seconds;
    }
    
}