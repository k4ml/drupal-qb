<?php

class QBuilderCondition {
    protected $conjunction = 'AND';
    protected $wheres = array();
    protected $arguments = array();

    public function __construct($conjunction = 'AND') {
        $this->conjunction = $conjunction;
    }

    public function condition($field, $value = NULL, $operator = '=') {
        if ($field instanceof QBuilderCondition) {
            $this->wheres[] = $field;
        }
        else {
            if (is_array($value)) {
                $this->arguments[] = $value[1];
                $value = $this->formatValue($value[1], $value[0]);
            }
            else {
                $value = $this->formatValue($value);
            }
            $this->wheres[] = $field .' '. $operator .' '. "$value";
        }
        return $this;
    }

    public function compile() {
        $wheres = '';
        $count = 0;
        foreach ($this->wheres as $where) {
            if ($where instanceof QBuilderCondition) {
                $wheres .= $where->compile();
            }
            else {
                if ($count > 0) {
                    $wheres .= ' '. $this->conjunction .' '. $where;
                }
                else {
                    $wheres .= ' '. $where;
                }
            }
            $count++;
        }
        
        return ' ('. $wheres .' )';
    }

    public function get_conjunction() {
        return $this->conjunction;
    }

    public function getArguments() {
        return $this->arguments;
    }

    protected function formatValue($value, $placeholder = NULL) {
        $maps = array(
            '%d' => $placeholder,
            '%s' => "'$placeholder'",
        );
        if ($placeholder) {
            return $maps[$placeholder];
        }

        if (is_int($value)) {
            return $value;
        }
        return "'". db_escape_string($value) ."'";
    }
}

class QBuilder {
    protected $base_table = '';
    protected $tables = array();
    protected $joins = array();
    protected $wheres = array();
    protected $arguments = array();
    protected $fields = array();
    protected $order_fields = array();

    public function select($table, $alias = NULL, $options = array()) {
        if (!$alias) {
            $table = $table .' '. $table;
        }
        else {
            $table = $table .' '. $alias;
        }
        $this->base_table = $table;
        return $this;
    }

    public function fields($alias, $fields = array()) {
        $this->fields[$alias] = $fields;
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
        }
        else {
            $condition = new QBuilderCondition;
            $condition->condition($field, $value, $operator);
            $this->wheres[] = $condition;
        }
        return $this;
    }

    public function orderBy($field, $order = 'ASC') {
        $this->order_fields[] = $field .' '. $order;
        return $this;
    }

    public function sql() {
        if (empty($this->fields)) {
            $fields = '*';
        }
        else {
            $fields = $this->compileFields();
        }
        $sql = "SELECT $fields FROM ". $this->base_table .
            $this->compileJoin() .
            $this->compile_where() .
            $this->compileOrderBy();

        return $sql;
    }

    public function getArguments() {
        $arguments = array();
        $this->arguments = array();
        foreach ($this->wheres as $condition) {
            $arguments = $condition->getArguments();
            foreach ($arguments as $argument) {
                $this->arguments[] = $argument;
            }
        }
        return $this->arguments;
    }

    protected function compileFields() {
        $to_select = '';
        foreach ($this->fields as $alias => $fields) {
            foreach ($fields as $field) {
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
        $wheres = ' WHERE';
        $count = 0;
        foreach ($this->wheres as $where) {
            if ($count > 0) {
                $wheres .= ' AND'. $where->compile();
            } else {
                $wheres .= $where->compile();
            }
            $count++;
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
        $order_by = '';
        if (empty($this->order_fields)) {
            return '';
        }
        foreach ($this->order_fields as $field) {
            $order_by .= $field .', ';
        }
        $order_by = trim(trim($order_by), ",");
        return $order_by;
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
