<?php

namespace App\Http\Controllers;

use Alkhachatryan\LaravelWebConsole\LaravelWebConsole;
use App\Charts\DashboardBersihHarian;
use App\Charts\DashboardKotorHarian;
use App\Dao\Enums\HilangType;
use App\Dao\Enums\TransactionType;
use App\Dao\Models\Bersih;
use App\Dao\Models\Outstanding;
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
    public function index(DashboardKotorHarian $sebaran, DashboardBersihHarian $perbandingan)
    {
        if (auth()->check() && auth()->user()->active == false) {
            return redirect()->route('login');
        }

        if (auth()->check() && auth()->user()->active == false) {
            return redirect()->route('login');
        }

        $bersih = 0;
        $kotor = 0;
        $reject = 0;
        $rewash = 0;

        $date = date('Y-m-d');

        $rs_id = auth()->user()->rs_id;

        $bersih = Bersih::select(Bersih::field_rfid())
            ->where(Bersih::field_report(), $date)
            ->where(Bersih::field_status(), TransactionType::BERSIH);

        $transaksi = Transaksi::select(Transaksi::field_rfid())
            ->whereDate(Transaksi::field_created_at(), $date);

            $reject = clone $transaksi;
            $rewash = clone $transaksi;

        if (!empty($rs_id))
        {
            $bersih = $bersih->where(Bersih::field_rs_id(), $rs_id);
            $transaksi = $transaksi->where(Transaksi::field_rs_ori(), $rs_id);
        }

        $kotor = $transaksi->where(Transaksi::field_status_transaction(), TransactionType::KOTOR)
            ->whereNotNull(Transaksi::field_rs_ori());
        $reject = $reject->where(Transaksi::field_status_transaction(), TransactionType::REJECT)
            ->whereNotNull(Transaksi::field_rs_ori());
        $rewash = $rewash->where(Transaksi::field_status_transaction(), TransactionType::REWASH)
            ->whereNotNull(Transaksi::field_rs_ori());

        $pending = Outstanding::where(Outstanding::field_status_hilang(), HilangType::PENDING)
            ->joinRelationship('has_rfid')
            ->whereNotNull(Outstanding::field_rs_ori());

        $hilang = Outstanding::where(Outstanding::field_status_hilang(), HilangType::HILANG)
            ->joinRelationship('has_rfid')
            ->whereNotNull(Outstanding::field_rs_ori());

        return view('pages.home.home', [
            'sebaran' => $sebaran->build(),
            'perbandingan' => $perbandingan->build(),
            'kotor' => $kotor->count(),
            'bersih' => $bersih->count(),
            'reject' => $reject->count(),
            'rewash' => $rewash->count(),
            'hilang' => $hilang->count(),
            'pending' => $pending->count(),
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
