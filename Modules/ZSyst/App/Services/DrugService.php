<?php

namespace Modules\ZSyst\App\Services;

use Modules\ZSyst\App\Repositories\DrugRepository;

class DrugService
{
    public function __construct(protected DrugRepository $repository)
    {
    }

    public function search(?string $search = null, int $limit = 50)
    {
        return $this->repository->search($search, $limit);
    }

    public function create(array $data)
    {
        return $this->repository->create($data);
    }
}
