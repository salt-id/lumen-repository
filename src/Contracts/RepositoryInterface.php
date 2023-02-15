<?php

namespace SaltId\LumenRepository\Contracts;

interface RepositoryInterface
{
    /**
     * Retrieve all data of repository.
     *
     * @param array $columns
     */
    public function all(array $columns = ['*']);

    /**
     * Retrieve all data of repository, paginated.
     *
     * @param int $limit
     * @param array $columns
     */
    public function paginate(int $limit = 5, array $columns = ['*']);

    /**
     * Retrieve first data of repository.
     *
     * @param array $columns
     */
    public function first(array $columns = ['*']);

    /**
     * Retrieve last data of repository.
     *
     * @param array $columns
     */
    public function last(array $columns = ['*']);

    /**
     * Find data by id.
     *
     * @param int $id
     * @param array $columns
     *
     */
    public function find(int $id, array $columns = ['*']);

    /**
     * Find data by field and value.
     *
     * @param string $field
     * @param string|array|int|null $value
     * @param array $columns
     *
     */
    public function findByField(string $field, string|array|int|null $value, array $columns = ['*']);

    /**
     * Find data by multiple fields.
     *
     * @param array $where
     * @param array $columns
     * @param int|null $limit
     *
     */
    public function findWhere(array $where, array $columns = ['*'], int|null $limit = null);

    /**
     * Find data by multiple values in one field.
     *
     * @param string $field
     * @param array $values
     *
     */
    public function findWhereIn(string $field, array $values);

    /**
     * Save a new entity in repository.
     *
     * @param array $attributes
     *
     */
    public function create(array $attributes);

    /**
     * Delete an entity in repository by id.
     *
     * @param int $id
     *
     */
    public function delete(int $id);

    /**
     * Update an entity in repository by id.
     *
     * @param array $attributes
     * @param int $id
     *
     */
    public function update(array $attributes, int $id);
}
