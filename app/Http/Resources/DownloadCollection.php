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

        $rs = Rs::find($rsid)->addSelect(
            [Rs::field_primary(), Rs::field_name()]
        )->first();

        $jenis = JenisLinen::where(JenisLinen::field_rs_id(), $rsid)
            ->addSelect([JenisLinen::field_primary(), JenisLinen::field_name()])
            ->get();

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

        // $check = Transaksi::addSelect(Transaksi::field_rfid())
        //     ->joinRelationship(HAS_DETAIL)
        //     ->whereNull(Transaksi::field_delivery())
        //     ->where(ViewDetailLinen::field_rs_id(), $request->rsid)
        //     ->whereDate(ViewDetailLinen::field_tanggal_update(), '<', date('Y-m-d'))
        //     ->get()->pluck(Transaksi::field_rfid(), Transaksi::field_rfid())
        //     ->toArray() ?? [];

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
            'rs' => $rs,
            'ruangan' => $ruangan,
            'jenis_linen' => $jenis,
            'status_proses' => $status,
            'opname' => $sendOpname,
        ];
        // return parent::toArray($request);
    }
}
