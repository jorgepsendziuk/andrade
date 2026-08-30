<?php

namespace Mad\Database;

use Adianti\Database\TRecord;

/**
 * Class DBQuery
 *
 * Extends the TRecord class and provides a custom SQL query execution mechanism.
 * This class overrides the default persistence methods to disable standard database operations.
 *
 * @package Mad\Database
 */
class DBQuery extends TRecord{

    protected $sql;

    /**
     * Sets the SQL query to be used by this instance.
     *
     * @param string $sql The SQL query string.
     *
     * @return void
     */
    public function setSqlQuery($sql)
    {
        $this->sql = $sql;
    }

    /**
     * Retrieves the SQL query stored in this instance.
     *
     * @return string|null The stored SQL query or null if not set.
     */
    public function getEntity()
    {
        return $this->sql;
    }

    /**
     * Retrieves the column name for soft deletes.
     *
     * @return false Always returns false, indicating that soft delete is not used.
     */
    static function getDeletedAtColumn()
    {
        return false;
    }

    /**
     * Overrides the default store method to disable data persistence.
     *
     * @return false Always returns false, indicating that data storage is disabled.
     */
    public function store()
    {
        return false;
    }

    /**
     * Overrides the default load method to disable data retrieval.
     *
     * @param mixed $id The identifier of the record to be loaded.
     *
     * @return false Always returns false, indicating that data loading is disabled.
     */
    public function load($id)
    {
        return false;
    }

    /**
     * Overrides the default delete method to disable data deletion.
     *
     * @param mixed|null $id The identifier of the record to be deleted (optional).
     *
     * @return false Always returns false, indicating that data deletion is disabled.
     */
    public function delete($id = null)
    {
        return false;
    }
}