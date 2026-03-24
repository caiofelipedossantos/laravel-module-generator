<?php

namespace App\Core\Repositories;

use App\Core\Contracts\RepositoryInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

abstract class BaseRepository implements RepositoryInterface
{
    /**
     * @var Model
     */
    protected Model $model;

    /**
     * Construtor
     *
     * @param Model $model
     */
    public function __construct(Model $model)
    {
        $this->model = $model;
    }

    /**
     * Obter query builder
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function query()
    {
        return $this->model->newQuery();
    }

    /**
     * Todos os registros
     *
     * @param array $columns
     * @param array $relations
     * @return Collection
     */
    public function all(array $columns = ['*'], array $relations = []): Collection
    {
        return $this->model->with($relations)->get($columns);
    }

    /**
     * Registros paginados
     *
     * @param int $perPage
     * @param array $columns
     * @param array $relations
     * @return LengthAwarePaginator
     */
    public function paginate(int $perPage = 15, array $columns = ['*'], array $relations = []): LengthAwarePaginator
    {
        return $this->model->with($relations)->paginate($perPage, $columns);
    }

    /**
     * Buscar por ID
     *
     * @param int $id
     * @param array $columns
     * @param array $relations
     * @return Model|null
     */
    public function findById(int $id, array $columns = ['*'], array $relations = []): ?Model
    {
        return $this->model->with($relations)->find($id, $columns);
    }

    /**
     * Buscar por UUID
     *
     * @param string $uuid
     * @param array $columns
     * @param array $relations
     * @return Model|null
     */
    public function findByUuid(string $uuid, array $columns = ['*'], array $relations = []): ?Model
    {
        return $this->model->with($relations)->where('uuid', $uuid)->first($columns);
    }

    /**
     * Buscar por coluna
     *
     * @param string $column
     * @param mixed $value
     * @param array $columns
     * @param array $relations
     * @return Model|null
     */
    public function findBy(string $column, mixed $value, array $columns = ['*'], array $relations = []): ?Model
    {
        return $this->model->with($relations)->where($column, $value)->first($columns);
    }

    /**
     * Buscar onde coluna = valor
     *
     * @param string $column
     * @param mixed $value
     * @param array $columns
     * @param array $relations
     * @return Collection
     */
    public function findWhere(string $column, mixed $value, array $columns = ['*'], array $relations = []): Collection
    {
        return $this->model->with($relations)->where($column, $value)->get($columns);
    }

    /**
     * Buscar onde coluna IN array
     *
     * @param string $column
     * @param array $values
     * @param array $columns
     * @param array $relations
     * @return Collection
     */
    public function findWhereIn(string $column, array $values, array $columns = ['*'], array $relations = []): Collection
    {
        return $this->model->with($relations)->whereIn($column, $values)->get($columns);
    }

    /**
     * Criar registro
     *
     * @param array $data
     * @return Model
     */
    public function create(array $data): Model
    {
        return $this->model->create($data);
    }

    /**
     * Atualizar registro
     *
     * @param Model $model
     * @param array $data
     * @return bool
     */
    public function update(Model $model, array $data): bool
    {
        return $model->update($data);
    }

    /**
     * Deletar (soft delete)
     *
     * @param Model $model
     * @return bool
     */
    public function delete(Model $model): bool
    {
        return $model->delete();
    }

    /**
     * Deletar permanentemente
     *
     * @param Model $model
     * @return bool
     */
    public function forceDelete(Model $model): bool
    {
        return $model->forceDelete();
    }

    /**
     * Restaurar soft deleted
     *
     * @param Model $model
     * @return bool
     */
    public function restore(Model $model): bool
    {
        return $model->restore();
    }

    /**
     * Verificar existência
     *
     * @param string $column
     * @param mixed $value
     * @return bool
     */
    public function exists(string $column, mixed $value): bool
    {
        return $this->model->where($column, $value)->exists();
    }

    /**
     * Contar registros
     *
     * @return int
     */
    public function count(): int
    {
        return $this->model->count();
    }

    /**
     *  Todos incluindo deletados
     *
     * @param array $columns
     * @param array $relations
     * @return Collection
     */
    public function allWithTrashed(array $columns = ['*'], array $relations = []): Collection
    {
        return $this->model->withTrashed()->with($relations)->get($columns);
    }

    /**
     * Apenas deletados
     *
     * @param array $columns
     * @param array $relations
     * @return Collection
     */
    public function onlyTrashed(array $columns = ['*'], array $relations = []): Collection
    {
        return $this->model->onlyTrashed()->with($relations)->get($columns);
    }
}
