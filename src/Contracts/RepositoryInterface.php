<?php

namespace Rcv\Core\Contracts;

interface RepositoryInterface
{
    /**
     * Get all records
     *
     * @param array $columns
     * @return mixed
     */
    public function all(array $columns = ['*']);

    /**
     * Get paginated records
     *
     * @param int $perPage
     * @param array $columns
     * @return mixed
     */
    public function paginate(int $perPage = 15, array $columns = ['*']);

    /**
     * Find record by ID
     *
     * @param int $id
     * @param array $columns
     * @return mixed
     */
    public function find(int $id, array $columns = ['*']);

    /**
     * Find record by field
     *
     * @param string $field
     * @param mixed $value
     * @param array $columns
     * @return mixed
     */
    public function findBy(string $field, $value, array $columns = ['*']);

    /**
     * Create new record
     *
     * @param array $data
     * @return mixed
     */
    public function create(array $data);

    /**
     * Update record
     *
     * @param int $id
     * @param array $data
     * @return mixed
     */
    public function update(int $id, array $data);

    /**
     * Delete record
     *
     * @param int $id
     * @return mixed
     */
    public function delete(int $id);

    /**
     * Get model instance
     *
     * @return mixed
     */
    public function getModel();
} 