<?php

use App\Dao\Enums\CetakType;
use App\Dao\Enums\CuciType;
use App\Dao\Enums\LinenType;
use App\Dao\Enums\LogType;
use App\Dao\Enums\ProcessType;
use App\Dao\Enums\RegisterType;
use App\Dao\Enums\TransactionType;
use App\Dao\Models\Cetak;
use App\Dao\Models\ConfigLinen;
use App\Dao\Models\Detail;
use App\Dao\Models\History as ModelsHistory;
use App\Dao\Models\JenisBahan;
use App\Dao\Models\JenisLinen;
use App\Dao\Models\Opname;
use App\Dao\Models\Outstanding;
use App\Dao\Models\Register;
use App\Dao\Models\Rs;
use App\Dao\Models\Supplier;
use App\Dao\Models\Transaksi;
use App\Dao\Models\ViewDetailLinen;
use App\Dao\Models\ViewOutstanding;
use App\Http\Controllers\BarcodeController;
use App\Http\Controllers\DeliveryController;
use App\Http\Controllers\TransaksiController;
use App\Http\Controllers\UserController;
use App\Http\Requests\DetailDataRequest;
use App\Http\Requests\DetailUpdateRequest;
use App\Http\Requests\OpnameDetailRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Resources\DetailCollection;
use App\Http\Resources\DetailResource;
use App\Http\Resources\DownloadCollection;
use App\Http\Resources\OpnameResource;
use App\Http\Resources\RsResource;
use App\Http\Services\SaveOpnameService;
use App\Http\Services\SaveTransaksiService;
use App\PushSubscription;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Plugins\History;
use Plugins\Notes;
use Plugins\Query;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
 */
Route::post('push-subscribe', function (Request $request) {
    PushSubscription::create(['data' => $request->getContent()]);
});

Route::post('login', [UserController::class, 'postLoginApi'])->name('postLoginApi');

Route::middleware(['auth:sanctum'])->group(function () {

    Route::get('download/{rsid}', function ($rsid, Request $request) {
        set_time_limit(0);
        $data = ViewDetailLinen::where(ViewDetailLinen::field_rs_id(), $rsid)->get();
        if (count($data) == 0) {
            return Notes::error('Data Tidak Ditemukan !');
        }
        $request->request->add([
            'rsid' => $rsid,
        ]);
        $resource = new DownloadCollection($data);

        return $resource;
    });

    Route::get('rs', function (Request $request) {

        $status_register = [];
        foreach (RegisterType::getInstances() as $value => $key) {
            $status_register[] = [
                'status_id' => $key,
                'status_nama' => formatCapitilizeSentance($value),
            ];
        }

        $status_cuci = [];
        foreach (CuciType::getInstances() as $value => $key) {
            $status_cuci[] = [
                'status_id' => $key,
                'status_nama' => formatCapitilizeSentance($value),
            ];
        }

        $status_proses = [];
        foreach (ProcessType::getInstances() as $value => $key) {
            $status_proses[] = [
                'status_id' => $key,
                'status_nama' => formatCapitilizeSentance($value),
            ];
        }

        $status_transaksi = [];
        foreach (TransactionType::getInstances() as $value => $key) {
            $status_transaksi[] = [
                'status_id' => $key,
                'status_nama' => formatCapitilizeSentance($value),
            ];
        }

        try {
            $rs = Rs::with([HAS_RUANGAN, HAS_JENIS])->get();
            $collection = RsResource::collection($rs);

            $data_supplier = [];
            $supplier = Supplier::get();
            foreach ($supplier as $vendor) {
                $data_supplier[] = [
                    'supplier_id' => $vendor->field_primary,
                    'supplier_name' => $vendor->field_name,
                ];
            }

            $data_bahan = [];
            $bahan = JenisBahan::get();
            foreach ($bahan as $vendor) {
                $data_bahan[] = [
                    'bahan_id' => $vendor->field_primary,
                    'bahan_name' => $vendor->field_name,
                ];
            }

            $data_jenis = [];
            $jenis = JenisLinen::get();
            foreach ($jenis as $item) {
                $data_jenis[] = [
                    'jenis_id' => $item->field_primary,
                    'jenis_name' => $item->field_name,
                ];
            }

            $add = [
                'status_transaksi' => $status_transaksi,
                'status_proses' => $status_proses,
                'status_cuci' => $status_cuci,
                'status_register' => $status_register,
                'bahan' => $data_bahan,
                'supplier' => $data_supplier,
                'jenis' => $data_jenis,
            ];

            $data = Notes::data($collection, $add);

            return $data;

        } catch (\Throwable $th) {

            return Notes::error($th->getMessage());
        }

    });

    Route::get('rs/{rsid}', function ($rsid) {

        try {

            $rs = Rs::with([HAS_RUANGAN, HAS_JENIS])->findOrFail($rsid);
            $collection = new RsResource($rs);

            return Notes::data($collection);

        } catch (\Throwable $th) {

            return Notes::error($th->getMessage());
        }

    });

    Route::post('register', function (RegisterRequest $request) {
        set_time_limit(0);
        ini_set('memory_limit', '512M');

        try {

            $code = env('CODE_BERSIH', 'REG');
            $autoNumber = Query::autoNumber(Outstanding::getTableName(), Outstanding::field_key(), $code.date('ymd'), env('AUTO_NUMBER', 15));

            if ($request->status_register == RegisterType::GANTI_CHIP) {
                $transaksi_status = TransactionType::KOTOR;
                $proses_status = ProcessType::KOTOR;
            } else {
                $transaksi_status = TransactionType::REGISTER;
                $proses_status = ProcessType::REGISTER;
            }

            $config = [];

            DB::beginTransaction();

            foreach ($request->rfid as $item) {
                $merge = [
                    Detail::field_primary() => $item,
                    Detail::field_jenis_id() => $request->jenis_id,
                    Detail::field_bahan_id() => $request->bahan_id,
                    Detail::field_supplier_id() => $request->supplier_id,
                    Detail::field_dedicated() => LinenType::FREE,
                    Detail::field_status_transaction() => $transaksi_status,
                    Detail::field_status_cuci() => $request->status_cuci,
                    Detail::field_status_register() => $request->status_register ? $request->status_register : RegisterType::REGISTER,
                    Detail::field_created_at() => date('Y-m-d H:i:s'),
                    Detail::field_updated_at() => date('Y-m-d H:i:s'),
                    Detail::field_created_by() => auth()->user()->id,
                    Detail::field_updated_by() => auth()->user()->id,
                ];

                $outstanding = [
                    Outstanding::field_key() => $autoNumber,
                    Outstanding::field_primary() => $item,
                    Outstanding::field_status_transaction() => $transaksi_status,
                    Outstanding::field_status_process() => $proses_status,
                    Outstanding::field_created_at() => date('Y-m-d H:i:s'),
                    Outstanding::field_updated_at() => date('Y-m-d H:i:s'),
                    Outstanding::field_created_by() => auth()->user()->id,
                    Outstanding::field_updated_by() => auth()->user()->id,
                ];

                if ($request->has('ruangan_id')) {
                    $merge = array_merge($merge, [
                        Detail::field_dedicated() => LinenType::DEDICATED,
                        Detail::field_ruangan_id() => $request->ruangan_id,
                        Detail::field_rs_id() => $request->rs_id,
                    ]);

                    $outstanding = array_merge($outstanding, [
                        Outstanding::field_rs_ori() => $request->rs_id,
                        Outstanding::field_rs_scan() => $request->rs_id,
                        Outstanding::field_ruangan_id() => $request->ruangan_id,
                    ]);

                    ConfigLinen::create([
                        ConfigLinen::field_primary() => $item,
                        ConfigLinen::field_rs_id() => $request->rs_id,
                    ]);

                } else {
                    foreach ($request->rs_id as $id_rs) {
                        $config[] = [
                            ConfigLinen::field_name() => $item,
                            ConfigLinen::field_rs_id() => $id_rs,
                        ];
                    }
                }

                $detail[] = $merge;
                $transaksi[] = $outstanding;
            }

            Detail::insert($detail);
            Outstanding::insert($transaksi);

            if(!empty($config)){
                ConfigLinen::insert($config);
            }

            $history = collect($request->rfid)->map(function ($item) {
                return [
                    ModelsHistory::field_name() => $item,
                    ModelsHistory::field_status() => LogType::REGISTER,
                    ModelsHistory::field_created_by() => auth()->user()->name,
                    ModelsHistory::field_created_at() => date('Y-m-d H:i:s'),
                    ModelsHistory::field_description() => json_encode([ModelsHistory::field_name() => $item]),
                ];
            });

            ModelsHistory::insert($history->toArray());

            DB::commit();

            $return = ViewDetailLinen::whereIn(ViewDetailLinen::field_primary(), $request->rfid)->get();

            return Notes::data(DetailResource::collection($return));

        } catch (\Illuminate\Database\QueryException $th) {
            DB::rollBack();

            if ($th->getCode() == 23000 && env('APP_ENV') == 'production') {
                return Notes::error($request->all(), 'data RFID sudah ada di database');
            }

            return Notes::error($request->all(), $th->getMessage());
        } catch (\Throwable $th) {
            DB::rollBack();

            return Notes::error($request->all(), $th->getMessage());
        }

    });

    Route::post('detail/rfid', function (DetailDataRequest $request) {
        try {

            $item = Query::getDetail()
                ->leftJoinRelationship(HAS_OUTSTANDING)
                ->whereIn(Detail::field_primary(), $request->rfid)
                ->get();

            if ($item->count() == 0) {
                return Notes::error('data RFID tidak ditemukan');
            }

            $collection = [];
            foreach ($item as $data) {

                $collection[] = [
                    'rfid' => $data->detail_rfid,
                    'jenis_id' => $data->detail_rfid,
                    'jenis_nama' => $data->jenis_nama ?? '',
                    'bahan_id' => $data->detail_id_bahan,
                    'bahan_nama' => $data->bahan_nama ?? '',
                    'rs_id' => $data->detail_id_rs ?? '',
                    'rs_nama' => $data->rs_nama ?? '',
                    'ruangan_id' => $data->detail_id_ruangan,
                    'ruangan_nama' => $data->ruangan_nama ?? '',
                    'status_register' => $data->detail_status_register,
                    'status_cuci' => $data->detail_status_cuci,
                    'status_transaksi' => $data->outstanding_status_transaksi ?? TransactionType::BERSIH,
                    'status_proses' => $data->outstanding_status_proses ?? TransactionType::BERSIH,
                    'tanggal_create' => $data->outstanding_created_at ? Carbon::make($data->outstanding_created_at)->format('Y-m-d') : null,
                    'tanggal_update' => $data->outstanding_updated_at ? Carbon::make($data->outstanding_updated_at)->format('Y-m-d') : null,
                    'pemakaian' => $data->detail_total_bersih_kotor ?? 0,
                    'user_nama' => $data->name ?? null,
                ];
            }

            return Notes::data($collection);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $th) {
            return Notes::error($th->getMessage());
        } catch (\Throwable $th) {
            return Notes::error($th->getMessage());
        }
    });

    Route::post('kotor', [TransaksiController::class, 'kotor']);
    Route::post('retur', [TransaksiController::class, 'retur']);
    Route::post('rewash', [TransaksiController::class, 'rewash']);

    Route::get('grouping/{rfid}', function ($rfid, SaveTransaksiService $service) {
        try {
            $data = Outstanding::with('has_view')->findOrFail($rfid);
            $view = $data->has_view;

            /*
            update status di outstanding
            */
            $data->outstanding_updated_at = ProcessType::QC;
            $data->outstanding_updated_by = auth()->user()->id;
            $data->outstanding_status_proses = ProcessType::QC;
            $data->save();

            $collection = [
                'linen_id' => $view->jenis_id,
                'linen_nama' => $view->jenis_nama ?? '',
                'rs_id' => $view->view_rs_ori_id,
                'rs_nama' => $view->view_rs_ori_name ?? '',
                'ruangan_id' => $view->ruangan_id,
                'ruangan_nama' => $view->ruangan_nama ?? '',
                'status_transaksi' => $data->outstanding_status_transaksi,
                'status_proses' => $data->outstanding_status_proses,
                'tanggal_create' => $view->outstanding_created_at ? $view->outstanding_created_at->format('Y-m-d') : null,
                'tanggal_update' => $view->outstanding_updated_at ? $view->outstanding_updated_at->format('Y-m-d') : null,
                'pemakaian' => 0,
                'user_nama' => $view->view_operator ?? null,
            ];

            return $collection;

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $th) {
            return Notes::error($rfid, 'RFID ' . $rfid . ' tidak ditemukan');
        } catch (\Throwable $th) {
            return Notes::error($rfid, $th->getMessage());
        }
    });

    Route::post('barcode', [BarcodeController::class, 'barcode']);
    Route::get('barcode/{code}', [BarcodeController::class, 'print']);

    Route::get('list/barcode/{rsid}', function ($rsid) {
        $data = Cetak::select([Cetak::field_name()])
            ->where(Cetak::field_rs_id(), $rsid)
            ->where(Cetak::field_type(), CetakType::Barcode)
            ->where(Cetak::field_date(), '>=', now()->addDay(-30))
            ->get();

        return Notes::data(['total' => $data]);
    });

    Route::post('delivery', [DeliveryController::class, 'delivery']);
    Route::get('delivery/{code}', [DeliveryController::class, 'print']);

    Route::get('list/delivery/{rsid}', function ($rsid) {
        $data = Cetak::select([Cetak::field_name()])
            ->where(Cetak::field_rs_id(), $rsid)
            ->where(Cetak::field_type(), CetakType::Delivery)
            ->where(Cetak::field_date(), '>=', now()->addDay(-30));

        if (request()->get('tgl')) {
            $data->where(Cetak::field_date(), '=', request()->get('tgl'));
        }

        return Notes::data(['total' => $data->get()]);
    });

    Route::get('total/delivery/{rsid}', function ($rsid) {
        $data = Transaksi::whereNull(Transaksi::field_delivery())
            ->whereNotNull(Transaksi::field_barcode())
            ->where(Transaksi::field_rs_ori(), $rsid)
            ->count();

        return Notes::data(['total' => $data]);
    });

    Route::get('total/delivery/{rsid}/{transaksi}', function ($rsid, $transaksi) {

        // if ($transaksi == TransactionType::BersihKotor) {
        //     $transaksi = TransactionType::Kotor;
        // } elseif ($transaksi == TransactionType::BersihRetur) {
        //     $transaksi = TransactionType::Retur;
        // } elseif ($transaksi == TransactionType::BersihRewash) {
        //     $transaksi = TransactionType::Rewash;
        // } elseif ($transaksi == TransactionType::Unknown) {
        //     $transaksi = TransactionType::Register;
        // }

        $data = Transaksi::whereNull(Transaksi::field_delivery())
            ->whereNotNull(Transaksi::field_barcode())
            ->where(Transaksi::field_status_transaction(), $transaksi)
            ->where(Transaksi::field_rs_ori(), $rsid)
            ->count();

        return Notes::data(['total' => $data]);
    });

    Route::get('opname', function (Request $request) {
        try {
            $today = today()->format('Y-m-d');
            $data = Opname::with([HAS_RS])
                ->where(Opname::field_start(), '<=', $today)
                ->where(Opname::field_end(), '>=', $today)
                ->get();

            $collection = OpnameResource::collection($data);

            return Notes::data($collection);

        } catch (\Throwable $th) {
            return Notes::error($th->getCode(), $th->getMessage());
        }
    })->name('opname_data');

    Route::get('opname/{id}', function ($id, Request $request) {
        try {
            $data = Opname::with([HAS_RS])->find($id);

            $collection = new OpnameResource($data);

            return Notes::data($collection);

        } catch (\Throwable $th) {
            return Notes::error($th->getCode(), $th->getMessage());
        }
    })->name('opname_detail');

    Route::post('/opname', function (OpnameDetailRequest $request, SaveOpnameService $service) {
        $data = $service->save($request->{Opname::field_primary()}, $request->data);

        return $data;
    });

});
