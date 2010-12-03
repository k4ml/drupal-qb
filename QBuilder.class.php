<?php

/*
Copyright (c) 2010, Mohd. Kamal Bin Mustafa <kamal.mustafa@gmail.com>

Permission to use, copy, modify, and/or distribute this software for any
purpose with or without fee is hereby granted, provided that the above
copyright notice and this permission notice appear in all copies.

THE SOFTWARE IS PROVIDED "AS IS" AND THE AUTHOR DISCLAIMS ALL WARRANTIES
WITH REGARD TO THIS SOFTWARE INCLUDING ALL IMPLIED WARRANTIES OF
MERCHANTABILITY AND FITNESS. IN NO EVENT SHALL THE AUTHOR BE LIABLE FOR
ANY SPECIAL, DIRECT, INDIRECT, OR CONSEQUENTIAL DAMAGES OR ANY DAMAGES
WHATSOEVER RESULTING FROM LOSS OF USE, DATA OR PROFITS, WHETHER IN AN
ACTION OF CONTRACT, NEGLIGENCE OR OTHER TORTIOUS ACTION, ARISING OUT OF
OR IN CONNECTION WITH THE USE OR PERFORMANCE OF THIS SOFTWARE.
*/

class QBuilder {
    protected $base_table = '';
    protected $tables = array();
    protected $joins = array();
    protected $wheres = array();
    protected $arguments = array();
    protected $fields = array();
    protected $order_fields = array();
    protected $group_fields = array();

    public $name = '';

    public function from($table, $alias = NULL, $options = array()) {
        if ($table instanceof QBuilder) {
            $this->base_table = $table;
            $this->base_table->name = $alias;
            $this->addArguments($table->getArguments());
            return $this;
        }
        if (!$alias) {
            $table = $table .' '. $table;
        }
        else {
            $table = $table .' '. $alias;
        }
        $this->base_table = $table;
        return $this;
    }

    public function select($alias = '*', $fields = array()) {
      $this->fields($alias, $fields);
      return $this;
    }

    public function fields($alias, $fields = array()) {
        if ($alias == '*') {
            return $this;
        }
        if (isset($this->fields[$alias])) {
            $this->fields[$alias] = array_merge($this->fields[$alias], $fields);
        }
        else {
            $this->fields[$alias] = $fields;
        }
        return $this;
    }

    public function addExpression($expression, $alias) {
        $expression = $expression ." AS ". $alias;
        $this->fields('EXPRESSION', array($expression));
        return $this;
    }

    public function join($table, $alias = NULL, $condition, $arguments = array()) {
        if (!$alias) {
            $alias = $table;
            $count = 1;
            while (in_array($alias, array_keys($this->joins))) {
                $alias = $alias .'_'. $count;
                $count++;
            }
        }
        if (in_array($alias, array_keys($this->joins))) {
            throw(new Exception("Alias $alias already exists"));
        }

        $this->joins[$alias] = array(
            'type' => 'INNER',
            'table' => $table,
            'alias' => $alias,
            'condition' => $condition,
        );

        return $this;
    }

    public function condition($field, $value = NULL, $operator = '=') {
        if ($field instanceof QBuilderCondition) {
            $this->wheres[] = $field;
            $this->addArguments($field->getArguments());
        }
        else {
            $condition = new QBuilderCondition;
            $condition->condition($field, $value, $operator);
            $this->wheres[] = $condition;
            $this->addArguments($condition->getArguments());
        }
        return $this;
    }

    public function where($where, $arguments = array()) {
      if (empty($this->wheres)) {
        $this->wheres[] = $where;
      }
      else {
        $this->wheres[] = ' AND '. $where;
      }
      if (!empty($arguments)) {
        $this->addArguments($arguments);
      }
      return $this;
    }

    public function orderBy($field, $order = 'ASC') {
        $this->order_fields[] = $field .' '. $order;
        return $this;
    }

    public function groupBy($field) {
        $this->group_fields[] = $field;
        return $this;
    }

    public function sql() {
        if (!empty($this->fields)) {
            $fields = $this->compileFields();
        }
        else {
            $fields = '*';
        }
        if ($this->base_table instanceof QBuilder) {
            $table = $this->base_table->sql();
            $table = "(". $table .") ". $this->base_table->name ;
        }
        else {
            $table = $this->base_table;
        }
        $sql = "SELECT $fields FROM ". $table .
            $this->compileJoin() .
            $this->compile_where() .
            $this->compileGroupBy() .
            $this->compileOrderBy();

        return $sql;
    }

    public function getArguments() {
        return $this->arguments;
    }

    protected function addArguments($arguments) {
        foreach ($arguments as $argument) {
            $this->arguments[] = $argument;
        }
    }

    protected function compileFields() {
        $to_select = '';
        foreach ($this->fields as $alias => $fields) {
            foreach ($fields as $field) {
                if ($alias == 'EXPRESSION') {
                    $to_select .= $field .", ";
                    continue;
                }
                $to_select .= $alias .".". $field .", ";
            }
        }
        $to_select = trim(trim($to_select), ",");
        return $to_select;
    }

    protected function compile_where() {
        if (empty($this->wheres)) {
            return '';
        }
        $wheres = ' WHERE ';
        foreach ($this->wheres as $where) {
          $wheres .= $where;
        }
        return $wheres;
    }

    function compileJoin() {
        $join_fragments = '';
        foreach ($this->joins as $_join) {
            $join_fragments .= " ". $_join['type'] .' JOIN '. $_join['table'] .' '. $_join['alias'] .' ON ('. $_join['condition'] .')';
        }
        return $join_fragments;
    }

    function compileOrderBy() {
        if (empty($this->order_fields)) {
            return '';
        }
        $order_by = ' ORDER BY ';
        foreach ($this->order_fields as $field) {
            $order_by .= $field .', ';
        }
        $order_by = rtrim(rtrim($order_by), ",");
        return $order_by;
    }

    function compileGroupBy() {
        if (empty($this->group_fields)) {
            return '';
        }
        return " GROUP BY " .implode(" ,", $this->group_fields);
    }

    function formatSql() {
        $sql = $this->sql();
        $sql = str_replace("FROM", "\nFROM", $sql);
        $sql = str_replace("INNER JOIN", "\n  INNER JOIN", $sql);
        $sql = str_replace("WHERE", "\nWHERE", $sql);
        $sql = str_replace("AND", "\n  AND", $sql);
        return $sql;
    }

    public function __toString() {
        return $this->sql();
    }
}
