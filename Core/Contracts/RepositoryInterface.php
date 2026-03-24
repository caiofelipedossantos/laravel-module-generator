<?php

namespace App\Core\Contracts;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface RepositoryInterface
{
    public function all(array $columns = ['*'], array $relations = []): Collection;
    public function paginate(int $perPage = 15, array $columns = ['*'], array $relations = []): LengthAwarePaginator;
    public function findById(int $id, array $columns = ['*'], array $relations = []): ?Model;
    public function findByUuid(string $uuid, array $columns = ['*'], array $relations = []): ?Model;
    public function findBy(string $column, mixed $value, array $columns = ['*'], array $relations = []): ?Model;
    public function findWhere(string $column, mixed $value, array $columns = ['*'], array $relations = []): Collection;
    public function findWhereIn(string $column, array $values, array $columns = ['*'], array $relations = []): Collection;
    public function create(array $data): Model;
    public function update(Model $model, array $data): bool;
    public function delete(Model $model): bool;
    public function forceDelete(Model $model): bool;
    public function restore(Model $model): bool;
    public function exists(string $column, mixed $value): bool;
    public function count(): int;
    public function allWithTrashed(array $columns = ['*'], array $relations = []): Collection;
    public function onlyTrashed(array $columns = ['*'], array $relations = []): Collection;
}
