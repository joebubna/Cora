<?php

interface Db_Interface
{
    public function table($tables);
    public function select($fields);
    public function update($fields, $value = false);
    public function delete();
    public function where($conditions, $value = false);
    public function limit($num);
    public function offset($num);
    public function groupBy($fields);
    public function orderBy($field, $direction = 'DESC');
    public function having($fields, $value = false);
    public function set($field, $value);
    public function join($tables);
    public function insert($data);
    public function exec();
    public function reset();
    public function getQuery();
}