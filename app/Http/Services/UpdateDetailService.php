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
            $newRfid = trim($data[Detail::field_primary()] ?? $code);
            $oldRfid = $code;

            $rsId = $data[Detail::field_rs_id()] ?? null;
            $ruanganId = $data[Detail::field_ruangan_id()] ?? null;

            // Payload non-PK untuk repository (hindari double rename PK via Eloquent).
            // Buang key non-fillable agar tidak ikut ke Eloquent update.
            $payload = $data;
            unset(
                $payload[Detail::field_primary()],
                $payload['rfid_lama'],
                $payload['_token'],
                $payload['_method']
            );

            // Check if new RFID already exists (and is different from old)
            $existingNew = Detail::where(Detail::field_primary(), $newRfid)->first();

            if ($existingNew && $newRfid !== $oldRfid) {
                // Merge: update data existing new RFID dengan data form, lalu hapus old
                if (!empty($payload)) {
                    $existingNew->update($payload);
                }
                Detail::where(Detail::field_primary(), $oldRfid)->delete();
                Outstanding::where(Outstanding::field_primary(), $oldRfid)->delete();
                Transaksi::where(Transaksi::field_rfid(), $oldRfid)->delete();
                ConfigLinen::where(ConfigLinen::field_primary(), $oldRfid)->delete();
                Bersih::where(Bersih::field_rfid(), $oldRfid)->delete();
                History::where(History::field_name(), $oldRfid)->delete();
                OpnameDetail::where(OpnameDetail::field_rfid(), $oldRfid)->delete();

                History::create([
                    History::field_rs_id() => $rsId,
                    History::field_name() => $newRfid,
                    History::field_status() => LogType::GANTI_LINEN,
                    History::field_created_by() => optional(auth()->user())->name,
                    History::field_created_at() => date('Y-m-d H:i:s'),
                    History::field_description() => json_encode(['lama' => $oldRfid, 'rfid_baru' => $newRfid, 'action' => 'merged']),
                ]);

                $check = Notes::update($existingNew->refresh());
            } else {
                // Normal update: update field non-PK via repository dulu
                $check = $repository->updateRepository($payload, $code);

                // Jika repository gagal, rollback dan return — jangan lanjut update tabel lain
                if (empty($check['status'])) {
                    DB::rollBack();

                    if (!request()->wantsJson()) {
                        Alert::error($check['message'] ?? 'Gagal update data.');
                    }

                    return $check;
                }

                $det = [
                    Detail::field_primary() => $newRfid,
                ];

                if(!empty($data['rfid_lama']))
                {
                    $det = array_merge($det, [
                        'detail_lama' => $data['rfid_lama'],
                        'detail_pengantian_user' => optional(auth()->user())->id,
                        'detail_pengantian_waktu' => date('Y-m-d H:i:s'),
                    ]);
                }

                Detail::where(Detail::field_primary(), $oldRfid)->update($det);

                $childRuangan = [];
                if ($ruanganId !== null) {
                    $childRuangan = [Transaksi::field_ruangan_id() => $ruanganId];
                }

                Outstanding::where(Outstanding::field_primary(), $oldRfid)->update(array_merge(
                    [Outstanding::field_primary() => $newRfid],
                    $ruanganId !== null ? [Outstanding::field_ruangan_id() => $ruanganId] : []
                ));

                Transaksi::where(Transaksi::field_rfid(), $oldRfid)->update(array_merge(
                    [Transaksi::field_rfid() => $newRfid],
                    $childRuangan
                ));

                ConfigLinen::where(ConfigLinen::field_primary(), $oldRfid)
                    ->when($rsId !== null, function ($query) use ($rsId) {
                        return $query->where(ConfigLinen::field_rs_id(), $rsId);
                    })
                    ->update([
                        ConfigLinen::field_primary() => $newRfid,
                    ]);

                Bersih::where(Bersih::field_rfid(), $oldRfid)->update(array_merge(
                    [Bersih::field_rfid() => $newRfid],
                    $ruanganId !== null ? [Bersih::field_ruangan_id() => $ruanganId] : []
                ));

                History::where(History::field_name(), $oldRfid)->update([
                    History::field_name() => $newRfid,
                ]);

                History::create([
                    History::field_rs_id() => $rsId,
                    History::field_name() => $newRfid,
                    History::field_status() => LogType::GANTI_LINEN,
                    History::field_created_by() => optional(auth()->user())->name,
                    History::field_created_at() => date('Y-m-d H:i:s'),
                    History::field_description() => json_encode(['lama' => $data['rfid_lama'] ?? $oldRfid, 'rfid_baru' => $newRfid]),
                ]);

                OpnameDetail::where(OpnameDetail::field_rfid(), $oldRfid)->update([
                    OpnameDetail::field_rfid() => $newRfid,
                ]);

                // Refresh model agar redirect controller memakai RFID baru
                $fresh = Detail::where(Detail::field_primary(), $newRfid)->first();
                $check = Notes::update($fresh ?? $check['data'] ?? null);
            }

            Alert::update();

            DB::commit();

            if (request()->wantsJson()) {
                return response()->json($check)->getData();
            }

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
