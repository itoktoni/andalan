<?php

namespace App\Http\Services;

use App\Dao\Enums\LogType;
use App\Dao\Interfaces\CrudInterface;
use App\Dao\Models\Bersih;
use App\Dao\Models\ConfigLinen;
use App\Dao\Models\Detail;
use App\Dao\Models\History;
use App\Dao\Models\OpnameDetail;
use App\Dao\Models\Outstanding;
use App\Dao\Models\Rs;
use App\Dao\Models\Transaksi;
use Illuminate\Database\QueryException;
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
            $newRfid = $data[Detail::field_primary()];
            $oldRfid = $code;

            // Check if new RFID already exists (and is different from old)
            $existingNew = Detail::where(Detail::field_primary(), $newRfid)->first();

            if ($existingNew && $newRfid !== $oldRfid) {
                // Merge: transfer old RFID data to existing new RFID, then delete old
                Detail::where(Detail::field_primary(), $oldRfid)->delete();
                Outstanding::where(Outstanding::field_primary(), $oldRfid)->delete();
                Transaksi::where(Transaksi::field_rfid(), $oldRfid)->delete();
                ConfigLinen::where(ConfigLinen::field_primary(), $oldRfid)->delete();
                Bersih::where(Bersih::field_rfid(), $oldRfid)->delete();
                History::where(History::field_name(), $oldRfid)->delete();
                OpnameDetail::where(OpnameDetail::field_rfid(), $oldRfid)->delete();

                $check = $existingNew;

                History::create([
                    History::field_rs_id() => $data[Detail::field_rs_id()],
                    History::field_name() => $newRfid,
                    History::field_status() => LogType::GANTI_LINEN,
                    History::field_created_by() => auth()->user()->name,
                    History::field_created_at() => date('Y-m-d H:i:s'),
                    History::field_description() => json_encode(['lama' => $oldRfid, 'rfid_baru' => $newRfid, 'action' => 'merged']),
                ]);
            } else {
                // Normal update: new RFID doesn't exist or same as old
                $check = $repository->updateRepository($data, $code);

                $det = [
                    Detail::field_primary() => $newRfid,
                ];

                if(!empty($data['rfid_lama']))
                {
                    $det = array_merge($det, [
                        'detail_lama' => $data['rfid_lama'],
                        'detail_pengantian_user' => auth()->user()->id,
                        'detail_pengantian_waktu' => date('Y-m-d H:i:s'),
                    ]);
                }

                Detail::where(Detail::field_primary(), $oldRfid)->update($det);

                Outstanding::where(Outstanding::field_primary(), $oldRfid)->update([
                    Outstanding::field_primary() => $newRfid,
                    Outstanding::field_ruangan_id() => $data[Detail::field_ruangan_id()],
                ]);

                Transaksi::where(Transaksi::field_rfid(), $oldRfid)->update([
                    Transaksi::field_rfid() => $newRfid,
                    Transaksi::field_ruangan_id() => $data[Detail::field_ruangan_id()],
                ]);

                ConfigLinen::where(Detail::field_primary(), $oldRfid)
                    ->where(Rs::field_primary(), $data[Detail::field_rs_id()])
                    ->update([
                        Detail::field_primary() => $newRfid,
                    ]);

                Bersih::where(Bersih::field_rfid(), $oldRfid)->update([
                    Bersih::field_rfid() => $newRfid,
                    Bersih::field_ruangan_id() => $data[Detail::field_ruangan_id()],
                ]);

                History::where(History::field_name(), $oldRfid)->update([
                    History::field_name() => $newRfid,
                ]);

                History::create([
                    History::field_rs_id() => $data[Detail::field_rs_id()],
                    History::field_name() => $newRfid,
                    History::field_status() => LogType::GANTI_LINEN,
                    History::field_created_by() => auth()->user()->name,
                    History::field_created_at() => date('Y-m-d H:i:s'),
                    History::field_description() => json_encode(['lama' => $data['rfid_lama'], 'rfid_baru' => $newRfid]),
                ]);

                OpnameDetail::where(OpnameDetail::field_rfid(), $oldRfid)->update([
                    OpnameDetail::field_rfid() => $newRfid,
                ]);
            }

            if (request()->wantsJson()) {
                return response()->json($check)->getData();
            }

            Alert::update();

            DB::commit();

            return $check;

        }

        catch (\Throwable $th) {

            if ($th instanceof QueryException)
            {
                Alert::error("No RFID yang di input (" . request()->get('detail_rfid').") Tidak boleh Duplikat di database !");
            }
            else
            {
                Alert::error($th->getMessage());
            }

            DB::rollBack();

            return Notes::error($th->getMessage());
        }

        return $check;
    }
}
