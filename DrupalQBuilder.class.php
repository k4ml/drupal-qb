<?php

class DrupalQBuilder extends QBuilder {
    public function fetch($as_array = FALSE) {
        $db_fetch = 'db_fetch_object';
        if ($as_array) {
            $db_fetch = 'db_fetch_array';
        }

        $result = db_query($this->sql(), $this->getArguments());
        $rows = array();
        while ($row = $db_fetch($result)) {
            $rows[] = $row;
        }
        return $rows;
    }

    public function fetchArray() {
        return $this->fetch($as_array = TRUE);
    }

    public function fetchRow($as_array = FALSE) {
        $db_fetch = 'db_fetch_object';
        if ($as_array) {
            $db_fetch = 'db_fetch_array';
        }
        $result = db_query($this->sql(), $this->getArguments());
        if ($result) {
            return $db_fetch($result);
        }
        return FALSE;
    }
}
