<?php

namespace App\Http\Services;

use App\Dao\Enums\LogType;
use App\Dao\Enums\ProcessType;
use App\Dao\Enums\TransactionType;
use App\Dao\Models\Bersih;
use App\Dao\Models\Detail;
use App\Dao\Models\Outstanding;
use App\Dao\Models\Transaksi;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Plugins\History;
use Plugins\Notes;

class UpdateDeliveryService
{
    public function update($data)
    {
        DB::beginTransaction();

        try {

            $startDate = Carbon::createFromFormat('Y-m-d H:i', date('Y-m-d').' 13:00');
            $endDate = Carbon::createFromFormat('Y-m-d H:i', date('Y-m-d').' 23:59');

            $check_date = Carbon::now()->between($startDate, $endDate);
            $report_date = Carbon::now();
            if ($check_date) {
                $report_date = Carbon::now()->addDay(1);
            }

            $transaksi = $data->status_transaksi;
            if ($transaksi == TransactionType::KOTOR) {
                $transaksi = TransactionType::BERSIH;
            }

            $check = Bersih::query()
                ->whereNull(Bersih::field_delivery())
                ->where(Bersih::field_rs_id(), $data->rs_id)
                ->where(Bersih::field_status(), $transaksi)
                ->whereNotNull(Bersih::field_barcode())
                ->update([
                    Bersih::field_delivery() => $data->code,
                    Bersih::field_delivery_by() => auth()->user()->id,
                    Bersih::field_delivery_at() => date('Y-m-d H:i:s'),
                    Bersih::field_report() => $report_date->format('Y-m-d'),
                ]);

            $rfid = Bersih::select(Bersih::field_rfid())
                ->where(Bersih::field_delivery(), $data->code)
                ->get();

            if ($rfid && $check) {

                $data_rfid = $rfid->pluck(Bersih::field_rfid());

                $detail = [
                    Detail::field_status_linen() => TransactionType::BERSIH,
                    Detail::field_updated_by() => auth()->user()->id,
                ];

                if ($data->status_transaksi == TransactionType::REWASH) {
                    $detail = array_merge($detail, [
                        Detail::field_total_rewash() => DB::raw('detail_total_rewash + 1')
                    ]);
                } else if($data->status_transaksi == TransactionType::REJECT){
                    $detail = array_merge($detail, [
                        Detail::field_total_reject() => DB::raw('detail_total_reject + 1')
                    ]);
                } else {
                    $detail = array_merge($detail, [
                        Detail::field_total_bersih() => DB::raw('detail_total_bersih + 1')
                    ]);
                }

                Detail::whereIn(Detail::field_primary(), $data_rfid)
                ->update($detail);

                Outstanding::whereIn(Outstanding::field_primary(), $data_rfid)->delete();

                History::bulk($data_rfid, LogType::BERSIH);

            } else {
                DB::rollBack();

                return Notes::error('RFID tidak ditemukan!');
            }

            DB::commit();

            $return['code'] = $data->code;
            $return['rfid'] = $data_rfid;

            return Notes::data($return);

        } catch (\Throwable $th) {
            DB::rollBack();

            return Notes::error($th->getMessage());
        }
    }
}
