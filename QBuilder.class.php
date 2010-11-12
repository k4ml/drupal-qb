<?php

class QBuilderCondition {
    protected $conjunction = 'AND';
    protected $wheres = array();

    public function __construct($conjunction = 'AND') {
        $this->conjunction = $conjunction;
    }

    public function condition($field, $value = NULL, $operator = '=') {
        if ($field instanceof QBuilderCondition) {
            $this->wheres[] = $field;
        }
        else {
            $this->wheres[] = $field .' '. $operator .' '. "'$value'";
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
}

class QBuilder {
    protected $base_table = '';
    protected $tables = array();
    protected $wheres = array();

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

    public function sql() {
        $sql = "SELECT * FROM ". $this->base_table .
            " WHERE".
            $this->compile_where();

        return $sql;
    }

    protected function compile_where() {
        $wheres = '';
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

    public function __toString() {
        return $this->sql();
    }
}
