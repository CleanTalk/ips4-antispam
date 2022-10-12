<?php

namespace Cleantalk\ApbctIPS;
use IPS\Db as IpsDB;

class DB extends \Cleantalk\Common\DB {
    /**
     * Alternative constructor.
     * Initilize Database object and write it to property.
     * Set tables prefix.
     */
    protected function init() {
        $this->prefix = IpsDB::i()->prefix;
    }

    /**
     * Set $this->query string for next uses
     *
     * @param $query
     * @return $this
     */
    public function set_query( $query ) {
        $this->query = $query;
        return $this;
    }

    /**
     * Safely replace place holders
     *
     * @param string $query
     * @param array  $vars
     *
     * @return $this
     */
    public function prepare( $query, $vars = array() ) {
        $this->db_result = IpsDB::i()->preparedQuery($query, $vars);
        return $this->db_result;
    }

    /**
     * Run any raw request
     *
     * @param $query
     *
     * @return bool|int Raw result
     */
    public function execute( $query ) {
        $this->db_result = IpsDB::i()->query($query);
        return $this->db_result;
    }

    /**
     * Fetchs first column from query.
     * May receive raw or prepared query.
     *
     * @param bool $query
     * @param bool $response_type
     *
     * @return array|object|void|null
     */
    public function fetch( $query = false, $response_type = false ) {
        $this->result = IpsDB::i()->query($query)->fetch_row()[0];

        return $this->result;
    }

    /**
     * Fetchs all result from query.
     * May receive raw or prepared query.
     *
     * @param bool $query
     * @param bool $response_type
     *
     * @return array|object|null
     */
    public function fetch_all( $query = false, $response_type = false ) {
        foreach (IpsDB::i()->query($query) as $row) {
            $this->result[] = $row;
        }
        return $this->result;
    }

    public function get_last_error() {

    }
}