<?php

namespace App\Http\Services;

use App\Dao\Interfaces\CrudInterface;
use App\Dao\Models\Bersih;
use App\Dao\Models\ConfigLinen;
use App\Dao\Models\Detail;
use App\Dao\Models\History;
use App\Dao\Models\OpnameDetail;
use App\Dao\Models\Rs;
use App\Dao\Models\Transaksi;
use Illuminate\Support\Facades\DB;
use Plugins\Alert;
use Plugins\Notes;

class UpdateDetailService
{
    public function update(CrudInterface $repository, $request, $code)
    {
        DB::beginTransaction();

        try {
            $data = $request->all();
            $check = $repository->updateRepository($data, $code);

            Detail::where(Detail::field_primary(), $code)->update([
                Detail::field_primary() => $data[Detail::field_primary()],
            ]);

            Transaksi::where(Transaksi::field_rfid(), $code)->update([
                Transaksi::field_rfid() => $data[Detail::field_primary()],
            ]);

            ConfigLinen::where(Detail::field_primary(), $code)
                ->where(Rs::field_primary(), $data[Detail::field_rs_id()])
                ->update([
                    Detail::field_primary() => $data[Detail::field_primary()],
                ]);

            Bersih::where(Bersih::field_rfid(), $code)->update([
                Bersih::field_rfid() => $data[Detail::field_primary()],
            ]);

            History::where(History::field_name(), $code)->update([
                History::field_name() => $data[Detail::field_primary()],
            ]);

            OpnameDetail::where(OpnameDetail::field_rfid(), $code)->update([
                OpnameDetail::field_rfid() => $data[Detail::field_primary()],
            ]);

            if (request()->wantsJson()) {
                return response()->json($check)->getData();
            }

            Alert::update();

            DB::commit();

            return $check;

        } catch (\Throwable $th) {
            DB::rollBack();

            return Notes::error($th->getMessage());
        }

        return $check;
    }
}
