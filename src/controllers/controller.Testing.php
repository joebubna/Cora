<?php
namespace Controllers;

class Testing extends \Cora\App\Controller 
{
  public function di1()
  {
    //echo 'Init load<br>';
    //$name = \Classes\Users::class;
    //echo $name."<br>";
    //echo 'Named fetched<br>';
    //echo method_exists('\\'.\Classes\Users::class, 'di_config');
    $users = $this->app->{\Classes\Users::class}('Regular');
    $user = $users->fetch(3);
    echo $user->user_id."<br>";
    echo $users->type;
  }
  
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


    public function test()
    {
      $model = new \Models\User();
      $model->id = 1;
      var_dump(
        $model->comments(function($query) {
          return $query->in('id', [2]);
        })
      );
    }


    public function test1()
    {
      $model = new \Models\User();
      $model->id = 1;
      var_dump($model->primaryRole);
      var_dump(
        $model->primaryRole(function($query) {
          return $query->custom("SELECT * FROM roles WHERE id = 3");
        })
      );
      var_dump($model->primaryRole);
    }


    public function test2()
    {
      $users = $this->app->users;

      $loadMap = new \Cora\Adm\LoadMap([
        'email' => 'firstName'
      ], [
        'primaryRole' => new \Cora\Adm\LoadMap([
          'role_id' => 'id',
          'name' => '!name'
        ])
      ]);

      $results = $users->findAll(function($query, $roleId, $limit) {
        $query->in('primaryRole', $roleId)
              ->join('roles', [['users.primaryRole', '=', 'roles.id']])
              ->select('users.*')
              ->select('roles.id as role_id')
              ->select('roles.name as role_name')
              ->limit($limit);
        return $query;
      }, [1, 5], $loadMap);

      echo $results->count();

      var_dump($results[0]->model_data);

      echo $results[1]->firstName;
      echo $results[1]->primaryRole->name;
    }


    public function test3()
    {
      //$GLOBALS['coraRunningTests'] = true;
      $model = new \Models\User();
      $model->id = 1;

      $loadMap = new \Cora\Adm\LoadMap([], [
          'madeBy' => new \Cora\Adm\LoadMap([
            'user_firstName' => 'firstName',
            'user_lastName' => 'lastName',
            'user_id' => 'id'
          ])
        ],
        false,
        function($model, $str, $str2) {
          $model->status = $str;
          $model->status = $str2;
        },
        ['Hoola Hoop', 'Str2']
      );

      $model->comments(function($query) {
        return $query->join('users', [['comments.id', '=', 'users.id']], 'LEFT')
                     ->select('comments.*')
                     ->select('users.id as user_id')
                     ->select('users.firstName as user_firstName')
                     ->select('users.lastName as user_lastName');
      }, false, $loadMap);

      $model->comments[1]->model_dynamicOff = true;

      echo $model->comments[0]->status."<br>";
      var_dump($model->comments[1]->madeBy->firstName);
    }


    public function test4()
    {
      $model = new \Models\User();
      $model->id = 1;

      $loadMap = new \Cora\Adm\LoadMap([], [
        'madeBy' => new \Cora\Adm\LoadMap([
          'user_firstName' => 'firstName',
          'user_lastName' => 'lastName',
          'user_id' => 'id'
        ])
      ],
        function($model, $str) {
          $model->status = $str;
        },
        ['Hoola Hoop']
      );

      $model->comments(function($query) {
        return $query->custom("
          SELECT 
            comments.*,
            users.id as user_id,
            users.firstName as user_firstName,
            users.lastName as user_lastName
          FROM comments
          LEFT JOIN users ON (comments.id = users.id)
          WHERE 
            comments.id = :id OR comments.madeBy IN(:ids)
        ", [
          "id" => 1,
          "ids" => [2,3]
        ]);
      }, false, $loadMap);

      $model->comments[1]->model_dynamicOff = true;

      echo $model->comments[0]->status."<br>";
      var_dump($model->comments[1]->madeBy->firstName);
    }


    public function test5()
    {
      $users = $this->app->users;

      $loadMap = new \Cora\Adm\LoadMap([
        'email' => 'firstName'
      ], [
        'primaryRole' => new \Cora\Adm\LoadMap([
          'role_id' => 'id',
          'name' => '!name'
        ])
      ]);

      $results = $users->findAll(function($query, $arg) {
        return $query->custom('
          SELECT 
            users.*,
            roles.id as role_id,
            roles.name as role_name 
          FROM users LEFT JOIN roles ON (users.primaryRole = roles.id) 
          LIMIT 5
        ');
      }, 1, $loadMap);

      var_dump($results[0]->model_data);

      echo $results[1]->firstName;
      echo $results[1]->primaryRole->name;
    }


    public function test6()
    {
      $list = new \Cora\Collection();
      var_dump($list->{'testAtt'});

      $q = \Cora\Database::getDefaultDb(true);
      $q->custom('SELECT * FROM users WHERE id = :id OR firstName IN(:names)', [
        'id' => 1,
        'names' => ['Josiah', "Susan"]
      ]);
      var_dump($q->fetchAll());
      // $q->select('*')
      //   ->from('users')
      //   ->where('id', 5, '<');
      // var_dump($q->fetchAll());

      var_dump($list->fetchAll());
    }


    public function testActive()
    {
      $repo = \Cora\RepositoryFactory::make('Tests\InactiveUser');
      $users = $repo->findAll();
      echo $users->count();


      foreach ($users as $user) {
        $user->model_dynamicOff = true;
        echo $user->primaryRole->name."<br>";
      }
    }


    /**
     *  
     */
    public function getAllIdeal($list_id) 
    {
      // Fetch list model
      $list = $this->lists->find($list_id);

      // Define how the model should be loaded 
      $loadMapping = [
        'practice' => [
          'practice.id' => 'id'
        ],
        'practice.business' => [
          'business.id' => 'id'
        ]
      ];

      // Fetch the items we are interested in from that list.
      $list->items(function($query, $controller) {
        $query->join('practices', [['practices.business_id', '=', 'leads_lists_items.business_id']])
              ->join('businesses', [['businesses.business_id', '=', 'leads_lists_items.business_id']]);
        return $controller->_paginate($query, 16, ['name']);
      }, $this, $loadMapping);

      // Return JSON
      echo $list->items->toJson();
    }



    public function test7()
    {
      $users = $this->app->users;

      $loadMap = new \Cora\Adm\LoadMap([
        'email' => 'firstName'
      ], [
        'primaryRole' => new \Cora\Adm\LoadMap([
          'role_id' => 'id',
          'name' => '!name',
          'role_name' => 'name'
        ]),
        'parent' => new \Cora\Adm\LoadMap([
          'father_id' => 'id',
          'father_firstName' => 'firstName',
          'father_lastName' => 'lastName',
          'father_parent' => 'parent'
          //'parent' => '!parent'
        ], [
          'parent' => new \Cora\Adm\LoadMap([], [], true)
          // 'parent' => new \Cora\Adm\LoadMap([], [
          //   'roles' => true
          // ], true)
        ])
      ]);

      $results = $users->findAll(function($query, $arg) {
        return $query->custom('
          SELECT 
            usersA.*,
            roles.id as role_id,
            roles.name as role_name,
            usersB.id as father_id,
            usersB.firstName as father_firstName,
            usersB.lastName as father_lastName,
            4 as father_parent
          FROM users usersA
          LEFT JOIN roles ON (usersA.primaryRole = roles.id) 
          LEFT JOIN users usersB ON (usersA.parent = usersB.id)
          LIMIT 1
        ');
      }, 1, $loadMap);

      //var_dump($results[0]->model_data);

      // echo $results[1]->firstName."<br>";
      // echo $results[1]->primaryRole->name."<br>";
      // echo $results[0]->parent->firstName;
      //var_dump($results[1]->primaryRole);
      header('Content-Type: application/json; charset=utf-8');
      echo $results[0]->toJson();
      //var_dump($results[0]->parent);
    }


    public function test8() 
    {
      $users = $this->app->users;
      $user = $users->find(1);

      $loadMap = new \Cora\Adm\LoadMap([], [
        'parent' => true
        //'father' => new \Cora\Adm\LoadMap([],[], true)
      ], true);

      //var_dump($user->model_data);

      $user->parent(function($query) {
        return $query;
      }, false, $loadMap);

      $user->model_dynamicOff = true;

      // Ensure we have the correct user
      echo 'User name = Bob : '.('Bob' == $user->firstName);

      echo 'User parent = Captain : '.('Captain' == $user->parent->firstName);
    }


    public function test9()
    {
      $comments = $this->app->repository('Tests\Users\commentUser');
      $c = $comments->find(5);
      echo $c->text;
      echo "\r\n\r\n<br><br>";
      echo $c->name;
    }
}