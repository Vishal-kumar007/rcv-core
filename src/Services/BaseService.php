<?php

namespace Rcv\Core\Services;

use Rcv\Core\Contracts\ServiceInterface;
use Rcv\Core\Contracts\RepositoryInterface;

abstract class BaseService implements ServiceInterface
{
    /**
     * @var RepositoryInterface
     */
    protected $repository;

    /**
     * BaseService constructor.
     *
     * @param RepositoryInterface $repository
     */
    public function __construct(RepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    /**
     * {@inheritdoc}
     */
    public function getAll(array $columns = ['*'])
    {
        return $this->repository->all($columns);
    }

    /**
     * {@inheritdoc}
     */
    public function getPaginated(int $perPage = 15, array $columns = ['*'])
    {
        return $this->repository->paginate($perPage, $columns);
    }

    /**
     * {@inheritdoc}
     */
    public function getById(int $id, array $columns = ['*'])
    {
        return $this->repository->find($id, $columns);
    }

    /**
     * {@inheritdoc}
     */
    public function getByField(string $field, $value, array $columns = ['*'])
    {
        return $this->repository->findBy($field, $value, $columns);
    }

    /**
     * {@inheritdoc}
     */
    public function create(array $data)
    {
        return $this->repository->create($data);
    }

    /**
     * {@inheritdoc}
     */
    public function update(int $id, array $data)
    {
        return $this->repository->update($id, $data);
    }

    /**
     * {@inheritdoc}
     */
    public function delete(int $id)
    {
        return $this->repository->delete($id);
    }

    /**
     * {@inheritdoc}
     */
    public function getRepository()
    {
        return $this->repository;
    }
} 