<?php

namespace App\Http\Services;

use App\Dao\Enums\BooleanType;
use App\Dao\Enums\LogType;
use App\Dao\Enums\ProcessType;
use App\Dao\Enums\TransactionType;
use App\Dao\Models\ConfigLinen;
use App\Dao\Models\Detail;
use App\Dao\Models\History as ModelsHistory;
use App\Dao\Models\OpnameDetail;
use App\Dao\Models\Outstanding;
use App\Dao\Models\Rs;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Plugins\Alert;

class CaptureOpnameService
{
    public function save($model)
    {
        $check = false;
        try {

            DB::beginTransaction();

            $tgl = date('Y-m-d H:i:s');
            $model->opname_capture = $tgl;
            $model->save();

            $opname = $model;
            $opname_id = $opname->opname_id;

            $data_rfid = ConfigLinen::where(Rs::field_primary(), $opname->opname_id_rs)
                ->leftJoin(Outstanding::getTableName(), 'config_linen.detail_rfid', '=', 'outstanding.outstanding_rfid')
                ->join(Detail::getTableName(), function($sql){
                    $sql->on('config_linen.detail_rfid', '=', 'detail_linen.detail_rfid');
                    $sql->on('config_linen.rs_id', '=', 'detail_linen.detail_id_rs');
                })
                ->get();

            $log = [];
            if ($data_rfid) {
                $id = auth()->user()->id;

                foreach ($data_rfid as $item) {

                    $status_transaksi = $item->outstanding_status_transaksi ?? TransactionType::BERSIH;
                    $status_proses = $item->outstanding_status_proses ?? TransactionType::BERSIH;

                    $ketemu = $this->checkKetemu($item);
                    $data[] = [
                        OpnameDetail::field_rfid() => $item->detail_rfid,
                        OpnameDetail::field_transaksi() => $status_transaksi,
                        OpnameDetail::field_proses() => $status_proses,
                        OpnameDetail::field_created_at() => $tgl,
                        OpnameDetail::field_created_by() => $id,
                        OpnameDetail::field_updated_at() => ! empty($item->detail_updated_at) ? Carbon::make($item->detail_updated_at)->format('Y-m-d H:i:s') : null,
                        OpnameDetail::field_updated_by() => $id,
                        OpnameDetail::field_waktu() => $tgl,
                        OpnameDetail::field_ketemu() => $ketemu,
                        OpnameDetail::field_opname() => $opname_id,
                        OpnameDetail::field_pending() => ! empty($item->outstanding_pending_at) ? Carbon::make($item->outstanding_pending_at)->format('Y-m-d H:i:s') : null,
                        OpnameDetail::field_hilang() => ! empty($item->outstanding_hilang_at) ? Carbon::make($item->outstanding_hilang_at)->format('Y-m-d H:i:s') : null,
                    ];

                    $log[] = [
                        ModelsHistory::field_name() => $item,
                        ModelsHistory::field_status() => LogType::OPNAME,
                        ModelsHistory::field_created_by() => auth()->user()->name ?? 'System',
                        ModelsHistory::field_created_at() => $tgl,
                        ModelsHistory::field_description() => 'Opname',
                    ];
                }

                foreach (array_chunk($data, env('TRANSACTION_CHUNK')) as $save_transaksi) {
                    OpnameDetail::insert($save_transaksi);
                }

                foreach (array_chunk($log, env('TRANSACTION_CHUNK')) as $log_transaksi) {
                    ModelsHistory::insert($log_transaksi);
                }
            }

            Alert::create();

            DB::commit();

        } catch (\Throwable $th) {
            DB::rollBack();
            Alert::error($th->getMessage());

            return $th->getMessage();
        }

        return $check;
    }

    private function checkKetemu($item)
    {
        if (in_array($item->outstanding_status_proses, [ProcessType::PENDING, ProcessType::HILANG])) {
            return BooleanType::YES;
        }

        if (in_array($item->outstanding_status_transaksi, [TransactionType::REJECT, TransactionType::REWASH])) {
            return BooleanType::YES;
        }

        return BooleanType::NO;
    }
}
