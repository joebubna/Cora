<?php

class Demo extends \MyApp {
    
    public function __construct()
    {   parent::__construct($this->container);
        
        
    }
    
    public function index()
    {
        echo 'Index GET';
    }
    
    public function itemGET($id)
    {
        echo $id;
    }
    
    public function someWebPage()
    {
        $this->data->title = "Demo App!";
        $this->data->content = $this->load->view('demo/partialView', $this->data, true);
        $this->load->view('template', $this->data);
    }
    
    public function dbTest()
    {
        $db = new Cora\Db_MySQL();
        
        $db ->insert('name, email, type')
            ->into('users')
            ->values([
                ['bob', 'bob@gmail.com', 'admin'], 
                ['john', 'john@gmail.com', 'admin'],
                ['john', 'john@gmail.com', 'scrub']
            ])
            ->exec();
        
        $db ->delete()
            ->from('users')
            ->where('type', 'scrub')
            ->exec();
        
        $db ->update('users')
            ->set('name', 'BigJohn')
            ->where('name', 'john')
            ->exec();
        
        $db ->select('name')
            ->from('users');
        $query = $db->exec();
        foreach($query->fetchAll() as $v)
            echo $v['name'];
    }
    
    public function db()
    {
        $tables = array('table1', 'table2', 'table3');
        $fields = array('id', 'name', 'email');
        $conditions = array(
            ['id', '>', '100', 'OR'],
            ['name', 'LIKE', '%s']
        );
        
        $groupBys = ['field1', 'field2', 'field3'];
        
        $havings = array(
            ['amount', '>', '1000'],
            ['savings', '>', '100']
        );
        
        $orHavings = array(
            ['amount2', '>', '1000'],
            ['savings2', '>', '100']
        );
        
        $joinConditions = array(
            ['Orders.customerID', '=', 'Customers.customerID'],
            ['User.type', '=', 'Customer.type']
        );
        
        
        $db = new Cora\Db_MySQL();
        
        echo $db->select($fields)
                ->from($tables)
                ->join('customers', $joinConditions, 'OUTER')
                ->where($conditions)
                ->orWhere($conditions)
                ->in('name', 'value1, value2, value3')
                ->in('type', $groupBys)
                ->groupBy($groupBys)
                ->having($havings)
                ->orHaving($orHavings)
                ->having($orHavings)
                ->orderBy('name', 'DESC')
                ->orderBy('type', 'ASC')
                ->limit(10)
                ->offset(20)
                ->getQuery();
        $db->reset();
        echo '<br><br>';
        
        
        $query  = $db   ->select('*')
                        ->from('users')
                        ->join('members', [['users.user_id', '=', 'members.user_id']])
                        ->orderBy('users.user_id', 'ASC')
                        ->getQuery();
        $result = $db->exec();
        echo $query.'<br>';
        echo $result->rowCount();
        echo '<br><br>';
        
        $query  = $db   ->update('users')
                        ->set('name', 'John')
                        ->where('name', 'Randy', '>')
                        ->getQuery();
        echo $query.'<br>';
        $db->reset();
        echo '<br><br>';
        
        
        $query  = $db   ->insert('name, email, type')
                        ->into('users')
                        ->values([['bob', 'bob@gmail.com', 'admin'], ['john', 'john@gmail.com', 'admin']])
                        ->values(['bob', 'bob@gmail.com', 'admin'])
                        ->getQuery();
        $db->reset();
        echo $query.'<br>';
        echo '<br><br>';
        
        
        echo $db->select('id')
                ->distinct()
                ->from('users')
                ->from('profile')
                ->where('name', 'bob', '=')
                ->where('date', '2014-01-01', '>=')
                ->getQuery();
        $db->reset();
        echo '<br><br>';
    }
}