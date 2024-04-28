<?php

namespace App\Http\Services;

use App\Dao\Enums\LogType;
use App\Dao\Enums\ProcessType;
use App\Dao\Models\Bersih;
use App\Dao\Models\Outstanding;
use Illuminate\Support\Facades\DB;
use Plugins\History;
use Plugins\Notes;

class UpdatePackingService
{
    public function update($data)
    {
        DB::beginTransaction();

        try {
            Bersih::insert($data->bersih);

            Outstanding::whereIn(Outstanding::field_primary(), $data->rfid)
                ->update([
                    Outstanding::field_rs_ori() => $data->rs_id,
                    Outstanding::field_ruangan_id() => $data->ruangan_id,
                    Outstanding::field_status_process() => ProcessType::PACKING,
                    Outstanding::field_updated_at() => date('Y-m-d H:i:s'),
                ]);

            History::bulk($data->rfid, LogType::PACKING);

            DB::commit();

            return Notes::data($data->bersih);

        } catch (\Throwable $th) {
            DB::rollBack();

            return Notes::error($th->getMessage());
        }
    }
}
