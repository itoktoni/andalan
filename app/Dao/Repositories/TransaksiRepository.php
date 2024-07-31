<?php

namespace App\Dao\Repositories;

use App\Dao\Enums\TransactionType;
use App\Dao\Interfaces\CrudInterface;
use App\Dao\Models\Rs;
use App\Dao\Models\Transaksi;
use App\Dao\Models\ViewBarcode;
use App\Dao\Models\ViewDelivery;
use App\Dao\Models\ViewTransaksi;
use Doctrine\DBAL\Query\QueryException;
use Illuminate\Database\QueryException as DatabaseQueryException;
use Plugins\Notes;
use Plugins\Query;

class TransaksiRepository extends MasterRepository implements CrudInterface
{
    public function __construct()
    {
        $this->model = empty($this->model) ? new ViewTransaksi() : $this->model;
    }

    public function filterRepository($query)
    {
        if (request()->hasHeader('authorization')) {
            if ($paging = request()->get('paginate')) {
                return $query->paginate($paging);
            }

            return Notes::data($query->get());
        }

        $query = env('PAGINATION_SIMPLE') ? $query->simplePaginate(env('PAGINATION_NUMBER')) : $query->paginate(env('PAGINATION_NUMBER'));

        return $query;
    }

    public function dataRepository()
    {
        $query = $this->model
            ->select($this->model->getSelectedField())
            ->sortable()->filter();

        return $this->filterRepository($query);
    }

    public function deleteRepository($request)
    {
        try {
            is_array($request) ? Transaksi::destroy(array_values($request)) : Transaksi::destroy($request);

            return Notes::delete($request);
        } catch (DatabaseQueryException $ex) {
            return Notes::error($ex->getMessage());
        }
    }

    public function getTransactionDetail()
    {
        return Transaksi::query()
            ->addSelect(['*'])
            ->leftJoinRelationship(HAS_RUANGAN)
            ->leftJoinRelationship(HAS_DETAIL)
            ->leftJoinRelationship(HAS_USER)
            ->filter();
    }

    public function getDetailKotor($status = false)
    {
        $query = $this->getTransactionDetail()
            ->leftJoin(Rs::getTableName() . ' as scan', function ($join) {
                $join->on('transaksi.transaksi_rs_scan', '=', 'scan.rs_id');
            })
            ->sortable();

        if($status){
            $query = $query->where('transaksi_status', $status);
        }

        return $query;
    }
}
