<?php
namespace Controllers;

class Testing extends \Cora\App\Controller 
{
    public function abstractSaving()
    {
        // Setup
        $users = $this->app->tests->users;
        
        // Grab Jenine
        $user = $users->find(3);

        // Load data if none present
        if (!$user) {
            $testUsers = new \Cora\Collection([
                new \Models\Tests\User('Bob1', 'Adult'),
                new \Models\Tests\User('Jimmy', 'Child'),
                new \Models\Tests\User('Jenine', 'Adult'),
                new \Models\Tests\User('Jeff', 'Adult'),
                new \Models\Tests\User('Matt', 'Child'),
                new \Models\Tests\User('Sarah', 'Child'),
                new \Models\Tests\User('Kevin', 'Adult')
            ]);
    
            $users->save($testUsers);
            $user = $users->find(3);
        }

        // Ensure we have jenine
        echo $user->name."<br>";

        // Change Admin to Admin2
        $user->multiAbstract[1]->name = 'Admin2';
        
        // Save user
        $users->save($user);

        // Re-Grab Jenine
        $user = $users->find(3);

        // Check that the abstract relationship to other "adults" works
        echo $user->multiAbstract[1]->name;
    }


    public function queryBuilder1()
    {
        list($field, $value, $comp) = array_pad(['name', 'bob'], 3, null);
        echo $field;
        echo $value;
        var_dump($comp);
    }


    public function queryBuilder2()
    {
        $qb = new \Cora\Data\QueryBuilder();
        $qb->select(['name', 'email'])
           ->from('users')
           ->where('status', 'active')
           ->where(function($qb) {
               $qb->where('name', '%dolly%', 'LIKE')
                  ->orWhere('type', 'Admin');
           });
    }

    // [1, 1, 4, 9, 49]
    // [2, 4, 6, 11, 112, 6360]
    public function intTest($maxN = 6361)
    {
        $n = $maxN;
        $remaining = $n**2;
        while ($remaining > 0) {
            $i = $n-1;
            echo "I: $i, ";
            $remaining -= $i**2;
            echo "Remaining: $remaining, I: $i, ";
            $nextN = $n > 12 ? ((int) sqrt($remaining))+2 : $n-1;
            $sqrt = ((int) sqrt($remaining))+2;
            echo "Next: $nextN, Sqrt: $sqrt<br>";
            $n = $nextN;
        }
    }































    public static function decompose3($n) {
        $GLOBALS['i'] = 0;
        var_dump(self::partInSquares($n*$n, $n-1));
        echo $GLOBALS['i'];
    }   
    
    private static function partInSquares($area, $maxN){
      for ($n = $maxN; $n > 0; $n--){
        $testArea = $n * $n;
        $remain = $area - $testArea;
        $GLOBALS['i']++;
        if ($remain == 0) {
          return array($n);
        } elseif ($remain > 0){
          $result = self::partInSquares($remain, $n - 1);
          if (is_array($result)){
            $result[] = $n;
            return $result;
          }
        }
      }
      return null;
    }


    public static function decompose2($n) {
        $GLOBALS['i'] = 0;
        var_dump(self::reduce($n**2, $n-1));
        echo $GLOBALS['i'];
    }   
    
    private static function reduce($total, $maxN) {
        for ($n = $maxN; $n > 0; $n--) {
            $remaining = $total - $n**2;
            $GLOBALS['i']++;
            if ($remaining == 0) {
                return [$n];
            } else if ($remaining > 0) {
                $nextN = $nextN = $n > 12 ? ((int) sqrt($remaining))+2 : $n-1;
                $results = self::reduce($remaining, $nextN);
                if (is_array($results)) {
                    $results[] = $n;
                    return $results;
                }
            }
        }
        return null;
    }


    public static function decompose($n) 
    {
        // 11² = 121 = 1 + 4 + 16 + 100 = 1² + 2² + 4² + 10² but don't return [2,6,9]
        // 50^2 = don't return [1, 1, 4, 9, 49] but [1, 3, 5, 8, 49]
        $GLOBALS['i'] = 0;
        $result = self::search($n-1, [], $n**2, 'S');
        $value = count($result) ? $result : null;
        var_dump($value);
        //echo "Comps: ".$GLOBALS['i'];
    }

    protected static function search($n, $result, $remaining) 
    {
        $GLOBALS['i'] += 1;
        // Base cases
        if ($remaining == 0) {
            return $result;
        }
        else if ($n <= 0 || $remaining < 0) {
            return false;
        }

        // Logic
        $addNumber = self::canDeduct($n, $remaining);
        if ($addNumber) {
            $branchResult = $result;
            array_unshift($branchResult, $n); // 10
            //var_dump($branchResult);
            $i = $n > 12 ? ((int) sqrt($remaining))+2 : $n;
            //echo "N: $n, Remaining: $remaining, I: $i<br>";
            while ($i > 0) {
                $subSearch = self::search($i-1, $branchResult, $remaining);
                if ($subSearch) {
                    return $subSearch;
                }
                $i--;
            }
        }

        // Recursion
        return self::search($n-1, $result, $remaining);
    }

    protected static function canDeduct($n, &$remaining) 
    {
        $subTotal = $remaining - ($n**2);
        if ($subTotal >= 0) {
            $remaining = $subTotal;
            return true;
        }
        return false;
    }


    public function dbFunction() 
    {
        $fn = new \Cora\Data\DbFunction('SUM', 'thing1', 'thing2');
        echo $fn->name;
        var_dump($fn->args);
    }

    public function modelExtends()
    {
        $business = new \Models\Ifuel\Business('Business1');
        $business->type = 'Lead';

        $practice = new \Models\Ifuel\Practice('Practice1');
        $practice->business = $business;
        $practice->type = 'Member';
        echo $practice->business->type;
    }

}