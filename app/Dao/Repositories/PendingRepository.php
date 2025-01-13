<?php

namespace App\Dao\Repositories;

use App\Dao\Interfaces\CrudInterface;
use App\Dao\Models\Outstanding;
use App\Dao\Models\Pending;
use App\Dao\Models\ViewOutstanding;
use Illuminate\Support\Facades\DB;
use Plugins\Notes;

class PendingRepository extends MasterRepository implements CrudInterface
{
    public function __construct()
    {
        $this->model = empty($this->model) ? new Pending() : $this->model;
    }

    public function dataRepository()
    {
        $query = ViewOutstanding::query()
            ->sortable()->filter();

        if (request()->hasHeader('authorization')) {
            if ($paging = request()->get('paginate')) {
                return $query->paginate($paging);
            }

            if (method_exists($this->model, 'getApiCollection')) {
                return $this->model->getApiCollection($query->get());
            }

            return Notes::data($query->get());
        }

        $query = $query->paginate(env('PAGINATION_NUMBER'));

        return $query;
    }

    public function getPrint()
    {
        $query = Pending::query()
            ->leftJoinRelationship('has_view')
            ->addSelect([
                'view_detail_linen.*',
            ])
            ->sortable()->filter();

        return $query;
    }
}
