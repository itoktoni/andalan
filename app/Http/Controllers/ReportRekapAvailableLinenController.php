<?php

namespace App\Http\Controllers;

use App\Dao\Enums\TransactionType;
use App\Dao\Models\Rs;
use App\Dao\Models\User;
use App\Dao\Models\ViewDetailLinen;
use App\Http\Requests\AvailableLinenReportRequest;
use Illuminate\Support\Facades\DB;

class ReportRekapAvailableLinenController extends MinimalController
{
    public $data;

    protected function beforeForm()
    {

        $rs = Rs::getOptions();
        $user = User::getOptions();

        self::$share = [
            'user' => $user,
            'rs' => $rs,
        ];
    }

    private function getQuery($request)
    {
        //shortcut: available = linen di detail_linen yang statusnya BERSIH (snapshot per RS, tanpa filter tanggal)
        $query = DB::table('detail_linen as d')
            ->join('jenis_linen as j', 'j.jenis_id', '=', 'd.detail_id_jenis')
            ->leftJoin('ruangan as r', 'r.ruangan_id', '=', 'd.detail_id_ruangan')
            ->select([
                'd.detail_id_jenis as view_linen_id',
                'j.jenis_nama as view_linen_nama',
                'd.detail_id_ruangan as view_ruangan_id',
                'r.ruangan_nama as view_ruangan_nama',
                DB::raw('COUNT(*) as view_qty'),
                DB::raw('COUNT(*) * j.jenis_berat as view_kg'),
            ])
            ->where('d.detail_id_rs', $request->rs_id)
            ->where('d.detail_status_linen', TransactionType::BERSIH)
            ->groupBy('d.detail_id_jenis', 'd.detail_id_ruangan');

        return $query->get();
    }

    public function getPrint(AvailableLinenReportRequest $request)
    {
        set_time_limit(0);
        ini_set('memory_limit', '512M');

        $location = $linen = [];

        $bersih = $this->getQuery($request);

        $rs = Rs::find(request()->get(Rs::field_primary()));
        $linen = $bersih->sortBy(ViewDetailLinen::field_name())->pluck(ViewDetailLinen::field_name(), ViewDetailLinen::field_id());
        $location = $bersih->sortBy(ViewDetailLinen::field_ruangan_name())->pluck(ViewDetailLinen::field_ruangan_name(), ViewDetailLinen::field_ruangan_id());

        return moduleView(modulePathPrint(), $this->share([
            'data' => $this->data,
            'rs' => $rs,
            'location' => $location,
            'linen' => $linen,
            'bersih' => $bersih,
        ]));
    }
}
