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
}