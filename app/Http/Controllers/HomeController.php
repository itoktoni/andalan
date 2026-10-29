<?php

namespace App\Http\Controllers;

use Alkhachatryan\LaravelWebConsole\LaravelWebConsole;
use App\Charts\DashboardBersihHarian;
use App\Charts\DashboardBersihVsKotor;
use App\Charts\DashboardKotorHarian;
use App\Dao\Enums\HilangType;
use App\Dao\Enums\TransactionType;
use App\Dao\Models\Bersih;
use App\Dao\Models\Detail;
use App\Dao\Models\Outstanding;
use App\Dao\Models\Pending;
use App\Dao\Models\Transaksi;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        if (auth()->check()) {
            return redirect()->route('login');
        }
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index(DashboardKotorHarian $dkotor, DashboardBersihHarian $dbersih, DashboardBersihVsKotor $dperbandingan)
    {
        if (auth()->check() && auth()->user()->active == false) {
            return redirect()->route('login');
        }

        $bersih = 0;
        $kotor = 0;
        $reject = 0;
        $rewash = 0;
        $register = 0;
        $pending_kotor = 0;
        $pending_reject = 0;
        $pending_rewash = 0;
        $available = 0;
        $pending = 0;
        $hilang = 0;
        $kemarin = 0;

        $date = date('Y-m-d');
        $yesterday = now()->addDay(-1)->format('Y-m-d');

        $rs_id = auth()->user()->rs_id;

        //shortcut: rs_id kosong = total semua RS; query premier yang sama, filter RS hanya bila terisi
        $register = Detail::when($rs_id, function ($q) use ($rs_id) {
            return $q->where(Detail::field_rs_id(), $rs_id);
        })->count();

        //shortcut: available = linen master berstatus BERSIH saja; REGISTER tidak dihitung
        $available = Detail::where(Detail::field_status_linen(), TransactionType::BERSIH)
            ->when($rs_id, function ($q) use ($rs_id) {
                return $q->where(Detail::field_rs_id(), $rs_id);
            })
            ->count();

        $pending_kotor = Pending::query()
         ->select(['pending_rfid'])
        ->leftJoin('rs', 'rs.rs_id', '=', 'pending.pending_id_rs')
        ->leftJoin('ruangan', 'ruangan.ruangan_id', '=', 'pending.pending_id_ruangan')
        ->leftJoin('jenis_linen', 'jenis_linen.jenis_id', '=', 'pending.pending_id_jenis')
        ->leftJoin('view_detail_linen', 'view_detail_linen.view_linen_rfid', '=', 'pending.pending_rfid')
        ->join('config_linen', function ($join) {
            $join->on('config_linen.detail_rfid', '=', 'pending.pending_rfid') // Perbaikan penulisan detail_rfid / details_rfid
                ->on('config_linen.rs_id', '=', 'rs.rs_id');
        })->whereNull('pending_bersih_at')
        ->when($rs_id, function ($q) use ($rs_id) {
            return $q->where('pending_id_rs', $rs_id);
        })->where('pending_transaksi', TransactionType::KOTOR)->count();

        $pending_reject = Pending::query()
         ->select(['pending_rfid'])
        ->leftJoin('rs', 'rs.rs_id', '=', 'pending.pending_id_rs')
        ->leftJoin('ruangan', 'ruangan.ruangan_id', '=', 'pending.pending_id_ruangan')
        ->leftJoin('jenis_linen', 'jenis_linen.jenis_id', '=', 'pending.pending_id_jenis')
        ->leftJoin('view_detail_linen', 'view_detail_linen.view_linen_rfid', '=', 'pending.pending_rfid')
        ->join('config_linen', function ($join) {
            $join->on('config_linen.detail_rfid', '=', 'pending.pending_rfid') // Perbaikan penulisan detail_rfid / details_rfid
                ->on('config_linen.rs_id', '=', 'rs.rs_id');
        })->whereNull('pending_bersih_at')
        ->when($rs_id, function ($q) use ($rs_id) {
            return $q->where('pending_id_rs', $rs_id);
        })->where('pending_transaksi', TransactionType::REJECT)->count();


        $pending_rewash = Pending::query()
         ->select(['pending_rfid'])
        ->leftJoin('rs', 'rs.rs_id', '=', 'pending.pending_id_rs')
        ->leftJoin('ruangan', 'ruangan.ruangan_id', '=', 'pending.pending_id_ruangan')
        ->leftJoin('jenis_linen', 'jenis_linen.jenis_id', '=', 'pending.pending_id_jenis')
        ->leftJoin('view_detail_linen', 'view_detail_linen.view_linen_rfid', '=', 'pending.pending_rfid')
        ->join('config_linen', function ($join) {
            $join->on('config_linen.detail_rfid', '=', 'pending.pending_rfid') // Perbaikan penulisan detail_rfid / details_rfid
                ->on('config_linen.rs_id', '=', 'rs.rs_id');
        })->whereNull('pending_bersih_at')
        ->when($rs_id, function ($q) use ($rs_id) {
            return $q->where('pending_id_rs', $rs_id);
        })->where('pending_transaksi', TransactionType::REWASH)->count();

        $bersih = Bersih::where(Bersih::field_report(), $date)
            ->where(Bersih::field_status(), TransactionType::BERSIH)
            ->when($rs_id, function ($q) use ($rs_id) {
                return $q->where(Bersih::field_rs_id(), $rs_id);
            })
            ->count();

        //shortcut: scan linen belum register simpan rs_ori NULL (hanya rs_scan); tetap hitung di RS yang scan
        $rsScope = function ($q) use ($rs_id) {
            return $q->where(function ($qq) use ($rs_id) {
                $qq->where(Transaksi::field_rs_ori(), $rs_id)
                    ->orWhere(function ($qqq) use ($rs_id) {
                        $qqq->whereNull(Transaksi::field_rs_ori())
                            ->where(Transaksi::field_rs_scan(), $rs_id);
                    });
            });
        };

        $kotor = Transaksi::whereDate(Transaksi::field_created_at(), $date)
            ->where(Transaksi::field_status_transaction(), TransactionType::KOTOR)
            ->when($rs_id, $rsScope)
            ->count();

        $kemarin = Transaksi::whereDate(Transaksi::field_created_at(), $yesterday)
            ->where(Transaksi::field_status_transaction(), TransactionType::KOTOR)
            ->when($rs_id, $rsScope)
            ->count();

        $reject = Transaksi::whereDate(Transaksi::field_created_at(), $date)
            ->where(Transaksi::field_status_transaction(), TransactionType::REJECT)
            ->when($rs_id, $rsScope)
            ->count();

        $rewash = Transaksi::whereDate(Transaksi::field_created_at(), $date)
            ->where(Transaksi::field_status_transaction(), TransactionType::REWASH)
            ->when($rs_id, $rsScope)
            ->count();

        $pending = Outstanding::where(Outstanding::field_status_hilang(), HilangType::PENDING)
            ->when($rs_id, function ($q) use ($rs_id) {
                return $q->where(Outstanding::field_rs_scan(), $rs_id);
            })
            ->joinRelationship('has_rfid')
            ->count();

        $hilang = Outstanding::where(Outstanding::field_status_hilang(), HilangType::HILANG)
            ->when($rs_id, function ($q) use ($rs_id) {
                return $q->where(Outstanding::field_rs_scan(), $rs_id);
            })
            ->joinRelationship('has_rfid')
            ->count();

        return view('pages.home.home', [
            'available' => $available,
            'dbersih' => $dbersih->build(),
            'dkotor' => $dkotor->build(),
            'dperbandingan' => $dperbandingan->build(),
            'kotor' => $kotor,
            'bersih' => $bersih,
            'reject' => $reject,
            'pending' => $pending,
            'hilang' => $hilang,
            'register' => $register,
            'kemarin' => $kemarin,
            'rewash' => $rewash,
            'pending_kotor' => $pending_kotor,
            'pending_reject' => $pending_reject,
            'pending_rewash' => $pending_rewash,
        ]);
    }

    public function console()
    {
        return LaravelWebConsole::show();
    }

    public function doc()
    {
        return view('doc');
    }

    public function error402()
    {
        return view('errors.402');
    }
}
