<?php

namespace App\Criteria\Users;

use Prettus\Repository\Contracts\CriteriaInterface;
use Prettus\Repository\Contracts\RepositoryInterface;

/**
 * Class DriversCriteria.
 *
 * @package namespace App\Criteria\Users;
 */
class DriversCriteria implements CriteriaInterface
{
    /**
     * Apply criteria in query repository
     *
     * @param string              $model
     * @param RepositoryInterface $repository
     *
     * @return mixed
     */
    public function apply($model, RepositoryInterface $repository)
    {
        return $model->where('users.is_active',1)->whereHas("roles", function($q){ $q->where("name", "driver"); });
    }
}
