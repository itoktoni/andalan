<?php

namespace App\Http\Resources;

use App\Dao\Enums\OpnameType;
use App\Dao\Enums\ProcessType;
use App\Dao\Enums\TransactionType;
use App\Dao\Models\JenisLinen;
use App\Dao\Models\Opname;
use App\Dao\Models\OpnameDetail;
use App\Dao\Models\Outstanding;
use App\Dao\Models\Rs;
use App\Dao\Models\Ruangan;
use App\Dao\Models\Transaksi;
use App\Dao\Models\ViewDetailLinen;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Support\Facades\DB;

class DownloadCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $rsid = $request->rsid;

        $rs = Rs::find($rsid);
        $data_rs = [
            Rs::field_primary() => $rs->field_primary,
            Rs::field_name() => $rs->field_name,
        ];

        $jenis = JenisLinen::addSelect([DB::raw('jenis_linen.jenis_id, jenis_linen.jenis_nama')])
            ->join('rs_dan_jenis', 'rs_dan_jenis.jenis_id', 'jenis_linen.jenis_id')
            ->where('rs_id', $rsid)
            ->get();

        $ruangan = Ruangan::addSelect([DB::raw('ruangan.ruangan_id, ruangan.ruangan_nama')])
            ->join('rs_dan_ruangan', 'rs_dan_ruangan.ruangan_id', 'ruangan.ruangan_id')
            ->where('rs_id', $rsid)
            ->get();

        $opname = Opname::with(['has_detail' => function ($query) {
            $query->where(OpnameDetail::field_ketemu(), 1);
        }])
            ->where(Opname::field_rs_id(), $rsid)
            ->where(Opname::field_status(), OpnameType::Proses)
            ->first();

        $sendOpname = [];
        if (! empty($opname)) {
            if ($opname->has_detail) {
                $sendOpname = $opname->has_detail->pluck(OpnameDetail::field_rfid());
            }
        }

        $status = [];
        foreach (ProcessType::getInstances() as $value => $key) {
            $status[] = [
                'status_id' => $key,
                'status_nama' => formatWorld($value),
            ];
        }

        $rfid = $this->collection->pluck('view_linen_rfid')->toArray();
        $outstanding = Outstanding::whereIn(Outstanding::field_primary(), $rfid)->get();

        $check = [];

        $data = $this->collection->map(function ($item) use ($outstanding) {
            $tanggal = $item->view_tanggal_update;
            $status = TransactionType::BERSIH;

            if($outstanding->where('outstanding_rfid', $item)->count() > 0) {
                $tanggal = date('Y-m-d H:i:s');
                $status = $item->view_status_transaction;
            }

            return [
                'id' => $item->field_primary,
                'rs' => $item->field_rs_id,
                'loc' => $item->field_ruangan_id,
                'jns' => $item->field_id,
                'sts' => $status,
                'tgl' => $tanggal,
            ];
        });

        return [
            'status' => true,
            'code' => 200,
            'name' => 'List',
            'message' => 'Data berhasil diambil',
            'data' => $data,
            'rs' => $data_rs,
            'ruangan' => $ruangan,
            'jenis' => $jenis,
            'opname' => $sendOpname,
            'status_proses' => $status,
        ];
        // return parent::toArray($request);
    }
}
