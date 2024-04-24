<?php

use App\Dao\Enums\BooleanType;
use App\Dao\Enums\CetakType;
use App\Dao\Enums\CuciType;
use App\Dao\Enums\LinenType;
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

    Route::get('status_register', function () {

        $data = [];
        foreach (RegisterType::getInstances() as $value => $key) {
            $data[] = [
                'status_id' => $key,
                'status_nama' => formatWorld($value),
            ];
        }

        return Notes::data($data);
    });

    Route::get('status_cuci', function () {

        $data = [];
        foreach (CuciType::getInstances() as $value => $key) {
            $data[] = [
                'status_id' => $key,
                'status_nama' => formatWorld($value),
            ];
        }

        return Notes::data($data);
    });

    Route::get('status_proses', function () {

        $data = [];
        foreach (ProcessType::getInstances() as $value => $key) {
            $data[] = [
                'status_id' => $key,
                'status_nama' => formatWorld($value),
            ];
        }

        return Notes::data($data);
    });

    Route::get('status_transaksi', function () {

        $data = [];
        foreach (TransactionType::getInstances() as $value => $key) {
            $data[] = [
                'status_id' => $key,
                'status_nama' => formatWorld($value),
            ];
        }

        return Notes::data($data);
    });

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
                'status_nama' => formatWorld($value),
            ];
        }

        $status_cuci = [];
        foreach (CuciType::getInstances() as $value => $key) {
            $status_cuci[] = [
                'status_id' => $key,
                'status_nama' => formatWorld($value),
            ];
        }

        $status_proses = [];
        foreach (ProcessType::getInstances() as $value => $key) {
            $status_proses[] = [
                'status_id' => $key,
                'status_nama' => formatWorld($value),
            ];
        }

        $status_transaksi = [];
        foreach (TransactionType::getInstances() as $value => $key) {
            $status_transaksi[] = [
                'status_id' => $key,
                'status_nama' => formatWorld($value),
            ];
        }

        try {

            $rs = Rs::with([HAS_RUANGAN, HAS_JENIS])->get();
            $collection = RsResource::collection($rs);

            $data_supplier = [];
            $supplier = Supplier::get();
            foreach($supplier as $vendor){
                $data_supplier[] = [
                'supplier_id' => $vendor->field_primary,
                'supplier_name' => $vendor->field_name,
                ];
            }

            $data_bahan = [];
            $bahan = JenisBahan::get();
            foreach($bahan as $vendor){
                $data_bahan[] = [
                'bahan_id' => $vendor->field_primary,
                'bahan_name' => $vendor->field_name,
                ];
            }

            $add = [
                'status_transaksi' => $status_transaksi,
                'status_proses' => $status_proses,
                'status_cuci' => $status_cuci,
                'status_register' => $status_register,
                'bahan' => $data_bahan,
                'supplier' => $data_supplier,
            ];

            $data = Notes::data($collection, $add);

            return $data;

        } catch (\Throwable $th) {

            return Notes::error($th->getMessage());
        }

    });

    Route::get('rs/{rsid}', function ($rsid) {

        $status_register = [];
        foreach (RegisterType::getInstances() as $value => $key) {
            $status_register[] = [
                'status_id' => $key,
                'status_nama' => formatWorld($value),
            ];
        }

        $status_cuci = [];
        foreach (CuciType::getInstances() as $value => $key) {
            $status_cuci[] = [
                'status_id' => $key,
                'status_nama' => formatWorld($value),
            ];
        }

        $status_proses = [];
        foreach (ProcessType::getInstances() as $value => $key) {
            $status_proses[] = [
                'status_id' => $key,
                'status_nama' => formatWorld($value),
            ];
        }

        $status_transaksi = [];
        foreach (TransactionType::getInstances() as $value => $key) {
            $status_transaksi[] = [
                'status_id' => $key,
                'status_nama' => formatWorld($value),
            ];
        }

        $data_supplier = [];
        $supplier = Supplier::get();
        foreach($supplier as $vendor){
            $data_supplier[] = [
              'supplier_id' => $vendor->field_primary,
              'supplier_name' => $vendor->field_name,
            ];
        }

        $data_bahan = [];
        $bahan = JenisBahan::get();
        foreach($bahan as $vendor){
            $data_bahan[] = [
              'bahan_id' => $vendor->field_primary,
              'bahan_name' => $vendor->field_name,
            ];
        }

        $data_jenis = [];
        $jenis = JenisLinen::get();
        foreach($jenis as $item){
            $data_jenis[] = [
              'jenis_id' => $item->field_primary,
              'jenis_name' => $item->field_name,
            ];
        }

        try {

            $rs = Rs::with([HAS_RUANGAN, HAS_JENIS])->findOrFail($rsid);
            $collection = new RsResource($rs);
            $add['status_transaksi'] = $status_transaksi;
            $add['status_proses'] = $status_proses;
            $add['status_cuci'] = $status_cuci;
            $add['bahan'] = $data_bahan;
            $add['jenis'] = $data_jenis;
            $add['supplier'] = $data_supplier;

            return Notes::data($collection, $add);

        } catch (\Throwable $th) {

            return Notes::error($th->getMessage());
        }

    });

    Route::post('register', function (RegisterRequest $request) {
        set_time_limit(0);
        ini_set('memory_limit', '512M');

        try {

            $code = env('CODE_BERSIH', 'BSH');
            // $autoNumber = Query::autoNumber(Transaksi::getTableName(), Transaksi::field_delivery(), $code.date('ymd'), env('AUTO_NUMBER', 15));

            if ($request->status_register == RegisterType::GANTI_CHIP) {
                $transaksi_status = TransactionType::Kotor;
                $proses_status = ProcessType::Kotor;
            } else {
                $transaksi_status = TransactionType::Register;
                $proses_status = ProcessType::Register;
            }

            if (is_array($request->rfid)) {

                DB::beginTransaction();

                foreach ($request->rfid as $item) {
                    $detail[] = [
                        Detail::field_primary() => $item,
                        Detail::field_ruangan_id() => $request->ruangan_id,
                        Detail::field_jenis_id() => $request->jenis_id,
                        Detail::field_bahan_id() => $request->bahan_id,
                        Detail::field_supplier_id() => $request->supplier_id,
                        Detail::field_dedicated() => LinenType::FREE,
                        Detail::field_status_cuci() => $request->status_cuci,
                        Detail::field_status_register() => $request->status_register ? $request->status_register : RegisterType::Register,
                        Detail::field_created_at() => date('Y-m-d H:i:s'),
                        Detail::field_updated_at() => date('Y-m-d H:i:s'),
                        Detail::field_created_by() => auth()->user()->id,
                        Detail::field_updated_by() => auth()->user()->id,
                    ];

                    foreach($request->rs_id as $id_rs){
                        $config[] = [
                            ConfigLinen::field_name() => $item,
                            ConfigLinen::field_rs_id() => $id_rs,
                        ];
                    }

                    $outstanding[] = [
                        Outstanding::field_primary() => $item,
                        Outstanding::field_ruangan_id() => $request->ruangan_id,
                        Outstanding::field_status_transaction() => $transaksi_status,
                        Outstanding::field_status_process() => $proses_status,
                        Outstanding::field_created_at() => date('Y-m-d H:i:s'),
                        Outstanding::field_updated_at() => date('Y-m-d H:i:s'),
                        Outstanding::field_created_by() => auth()->user()->id,
                        Outstanding::field_updated_by() => auth()->user()->id,
                    ];
                }

                Detail::insert($detail);
                ConfigLinen::insert($config);
                Outstanding::insert($outstanding);

                $history = collect($request->rfid)->map(function ($item) {

                    return [
                        ModelsHistory::field_name() => $item,
                        ModelsHistory::field_status() => ProcessType::Register,
                        ModelsHistory::field_created_by() => auth()->user()->name,
                        ModelsHistory::field_created_at() => date('Y-m-d H:i:s'),
                        ModelsHistory::field_description() => json_encode([ModelsHistory::field_name() => $item]),
                    ];
                });

                ModelsHistory::insert($history->toArray());
                DB::commit();

                $return = ViewDetailLinen::whereIn(ViewDetailLinen::field_primary(), $request->rfid)->get();

                return Notes::data(DetailResource::collection($return));

            } else {
                DB::beginTransaction();

                $detail = Detail::create([
                    Detail::field_primary() => $request->rfid,
                    Detail::field_rs_id() => $request->rs_id,
                    Detail::field_ruangan_id() => $request->ruangan_id,
                    Detail::field_bahan_id() => $request->bahan_id,
                    Detail::field_supplier_id() => $request->supplier_id,
                    Detail::field_status_cuci() => $request->status_cuci,
                    Detail::field_dedicated() => LinenType::DEDICATED,
                    Detail::field_status_register() => $request->status_register ? $request->status_register : RegisterType::REGISTER,
                    Detail::field_created_at() => date('Y-m-d H:i:s'),
                    Detail::field_updated_at() => date('Y-m-d H:i:s'),
                    Detail::field_created_by() => auth()->user()->id,
                    Detail::field_updated_by() => auth()->user()->id,
                ]);

                $register = ConfigLinen::create([
                    ConfigLinen::field_primary() => $request->rfid,
                    ConfigLinen::field_rs_id() => $request->rs_id,
                ]);

                $outstanding = Outstanding::create([
                    Outstanding::field_primary() => $request->rfid,
                    Outstanding::field_rs_id() => $request->rs_id,
                    Outstanding::field_ruangan_id() => $request->ruangan_id,
                    Outstanding::field_status_transaction() => $transaksi_status,
                    Outstanding::field_status_process() => $proses_status,
                    Outstanding::field_created_at() => date('Y-m-d H:i:s'),
                    Outstanding::field_updated_at() => date('Y-m-d H:i:s'),
                    Outstanding::field_created_by() => auth()->user()->id,
                    Outstanding::field_updated_by() => auth()->user()->id,
                ]);

                History::log($request->rfid, ProcessType::Register, $request->rfid);

                $view = ViewDetailLinen::findOrFail($request->rfid);

                $collection = new DetailResource($view);
                DB::commit();

                return Notes::data($collection);

            }

        } catch (\Illuminate\Database\QueryException $th) {
            DB::rollBack();

            if ($th->getCode() == 23000) {
                return Notes::error($request->all(), 'data RFID yang dikirim sudah ada di database');
            }

            return Notes::error($request->all(), $th->getMessage());
        } catch (\Throwable $th) {
            DB::rollBack();

            return Notes::error($request->all(), $th->getMessage());
        }

    });

    Route::get('detail/{rfid}', function ($rfid) {
        try {
            $data = ViewDetailLinen::findOrFail($rfid);
            $collection = new DetailResource($data);

            return Notes::data($collection);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $th) {
            return Notes::error($rfid, 'RFID '.$rfid.' tidak ditemukan');
        } catch (\Throwable $th) {
            return Notes::error($rfid, $th->getMessage());
        }
    });

    Route::match(['POST', 'GET'], 'detail', function (Request $request) {
        try {
            $query = ViewDetailLinen::query();
            $data = $query->filter()->paginate(env('PAGINATION_NUMBER', 10));

            $collection = new DetailCollection($data);

            return $collection;
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $th) {
            return Notes::error('data RFID tidak ditemukan');
        } catch (\Throwable $th) {
            return Notes::error($th->getMessage());
        }
    });

    Route::post('detail/rfid', function (DetailDataRequest $request) {
        try {

            $item = Query::getDetail()->whereIn(Detail::field_primary(), $request->rfid)->get();

            if ($item->count() == 0) {
                return Notes::error('data RFID tidak ditemukan');
            }

            $collection = [];
            foreach ($item as $data) {

                $collection[] = [
                    'linen_id' => $data->detail_rfid,
                    'linen_nama' => $data->jenis_nama ?? '',
                    'rs_id' => $data->detail_id_rs ?? '',
                    'rs_nama' => $data->rs_nama ?? '',
                    'ruangan_id' => $data->detail_id_ruangan,
                    'ruangan_nama' => $data->ruangan_nama ?? '',
                    'status_register' => RegisterType::getDescription($data->detail_status_register),
                    'status_cuci' => $data->detail_status_cuci,
                    'status_transaksi' => TransactionType::getDescription($data->detail_status_transaksi),
                    'status_proses' => ProcessType::getDescription($data->detail_status_proses),
                    'tanggal_create' => $data->detail_created_at ? $data->detail_created_at->format('Y-m-d') : null,
                    'tanggal_update' => $data->detail_updated_at ? $data->detail_updated_at->format('Y-m-d') : null,
                    'tanggal_delete' => $data->detail_deleted_at ? $data->detail_deleted_at->format('Y-m-d') : null,
                    'pemakaian' => $data->detail_total_cuci ?? 0,
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

    Route::post('linen_detail', function (DetailDataRequest $request) {
        try {
            $data = ViewDetailLinen::whereIn(ViewDetailLinen::field_primary(), $request->rfid)->get();
            $collection = DetailResource::collection($data);

            return Notes::data($collection);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $th) {
            return Notes::error('data RFID tidak ditemukan');
        } catch (\Throwable $th) {
            return Notes::error($th->getMessage());
        }
    });

    Route::post('detail/{rfid}', function ($rfid, DetailUpdateRequest $request) {
        try {

            $data = Detail::with(HAS_VIEW)->findOrFail($rfid);

            if ($data) {

                $lama = clone $data;
                $data->{Detail::field_rs_id()} = $request->rs_id;
                $data->{Detail::field_ruangan_id()} = $request->ruangan_id;
                $data->{Detail::field_jenis_id()} = $request->jenis_id;

                $data->{Detail::field_updated_at()} = date('Y-m-d H:i:s');
                $data->{Detail::field_updated_by()} = auth()->user()->id;

                if ($request->status_cuci) {
                    $data->{Detail::field_status_cuci()} = $request->status_cuci;
                }
                if ($request->status_register) {
                    $data->{Detail::field_status_register()} = $request->status_register;
                }

                $data->save();

                History::log($rfid, ProcessType::UpdateChip, [
                    'lama' => $lama->toArray(),
                    'baru' => $data->toArray(),
                ]);
            }

            $view = ViewDetailLinen::findOrFail($rfid);

            return Notes::data(new DetailResource($view));

        } catch (\Throwable $th) {
            return Notes::error($rfid, $th->getMessage());
        }
    });

    Route::post('kotor', [TransaksiController::class, 'kotor']);
    Route::post('retur', [TransaksiController::class, 'retur']);
    Route::post('rewash', [TransaksiController::class, 'rewash']);

    Route::get('grouping/{rfid}', function ($rfid, SaveTransaksiService $service) {
        try {
            $data = Detail::with([HAS_RS, HAS_RUANGAN, HAS_JENIS])->findOrFail($rfid);
            $save = Transaksi::where(Transaksi::field_rfid(), $rfid)
                ->whereNull(Transaksi::field_barcode());

            $jenis = $data->has_jenis;
            $rs = $data->has_rs;
            $ruangan = $data->has_ruangan;

            $data_transaksi = [];
            $linen[] = $rfid;

            $rs_id = $data->detail_id_rs;

            $date = date('Y-m-d H:i:s');
            $user = auth()->user()->id;
            $code_rs = $rs->rs_code ?? 'XXX';

            $status_transaksi = $data->field_status_transaction;
            $status_register = $data->field_status_register;

            $status_baru = TransactionType::Kotor;
            if (in_array($status_transaksi, [TransactionType::BersihKotor, TransactionType::BersihRetur, TransactionType::BersihRewash])) {
                $status_baru = TransactionType::Kotor;
            } elseif ($status_transaksi == TransactionType::Kotor) {
                $status_baru = TransactionType::Kotor;
            } elseif ($status_transaksi == TransactionType::Retur) {
                $status_baru = TransactionType::Retur;
            } elseif ($status_transaksi == TransactionType::Rewash) {
                $status_baru = TransactionType::Rewash;
            } elseif ($status_transaksi == TransactionType::Register) {
                $status_baru = TransactionType::Register;
                if ($status_register == RegisterType::GantiChip) {
                    $status_baru = TransactionType::Kotor;
                }

                $save->update([
                    Transaksi::field_status_transaction() => $status_baru,
                    Transaksi::field_rs_id() => $rs_id,
                    Transaksi::field_rs_ori() => $rs_id,
                ]);
            }

            $check_transaksi = Transaksi::where(Transaksi::field_rfid(), $rfid)
                ->whereNull(Transaksi::field_delivery())
                ->count();

            if ($check_transaksi == 0 and (in_array($status_transaksi, [
                TransactionType::BersihKotor,
                TransactionType::BersihRetur,
                TransactionType::BersihRewash,
                TransactionType::Kotor,
                TransactionType::Retur,
                TransactionType::Rewash,
                TransactionType::Register,
            ]))) {

                $startDate = Carbon::createFromFormat('Y-m-d H:i', date('Y-m-d').' 00:00');
                $endDate = Carbon::createFromFormat('Y-m-d H:i', date('Y-m-d').' 05:59');

                $check_date = Carbon::now()->between($startDate, $endDate);

                if ($check_date) {
                    $date = Carbon::now()->addDay(-1);
                }

                $data_transaksi[] = [
                    Transaksi::field_key() => Query::autoNumber((new Transaksi())->getTable(), Transaksi::field_key(), 'GROUP'.date('ymd').$code_rs, 20),
                    Transaksi::field_rfid() => $rfid,
                    Transaksi::field_status_transaction() => $status_baru,
                    Transaksi::field_rs_id() => $rs_id,
                    Transaksi::field_rs_ori() => $rs_id,
                    Transaksi::field_beda_rs() => BooleanType::No,
                    Transaksi::field_created_at() => $date,
                    Transaksi::field_created_by() => $user,
                    Transaksi::field_updated_at() => $date,
                    Transaksi::field_updated_by() => $user,
                ];

                $log[] = [
                    ModelsHistory::field_name() => $rfid,
                    ModelsHistory::field_status() => $status_baru,
                    ModelsHistory::field_created_by() => auth()->user()->name,
                    ModelsHistory::field_created_at() => $date,
                    ModelsHistory::field_description() => json_encode($data_transaksi),
                ];
            } else {
                $log[] = [
                    ModelsHistory::field_name() => $rfid,
                    ModelsHistory::field_status() => ProcessType::Grouping,
                    ModelsHistory::field_created_by() => auth()->user()->name,
                    ModelsHistory::field_created_at() => $date,
                    ModelsHistory::field_description() => json_encode($linen),
                ];
            }

            $data->update([
                Detail::field_updated_at() => date('Y-m-d H:i:s'),
                Detail::field_updated_by() => auth()->user()->id,
                Detail::field_pending_created_at() => null,
                Detail::field_pending_updated_at() => null,
                Detail::field_hilang_created_at() => null,
                Detail::field_hilang_updated_at() => null,
            ]);

            // $update = ViewDetailLinen::with([HAS_CUCI])->findOrFail($rfid);
            // $collection = new DetailResource($update);

            $collection = [
                'linen_id' => $data->detail_id_jenis,
                'linen_nama' => $jenis->field_name ?? '',
                'rs_id' => $data->detail_id_rs,
                'rs_nama' => $rs->field_name ?? '',
                'ruangan_id' => $data->detail_id_ruangan,
                'ruangan_nama' => $ruangan->field_name ?? '',
                'status_register' => RegisterType::getDescription($data->detail_status_register),
                'status_cuci' => CuciType::getDescription($data->detail_status_cuci),
                'status_transaksi' => TransactionType::getDescription($data->detail_status_transaksi),
                'status_proses' => ProcessType::getDescription($data->detail_status_proses),
                'tanggal_create' => $data->detail_created_at ? $data->detail_created_at->format('Y-m-d') : null,
                'tanggal_update' => $data->detail_updated_at ? $data->detail_updated_at->format('Y-m-d') : null,
                'tanggal_delete' => $data->detail_deleted_at ? $data->detail_deleted_at->format('Y-m-d') : null,
                'pemakaian' => 0,
                'user_nama' => auth()->user()->name ?? null,
            ];

            $status_grouping = ProcessType::Grouping;
            if (in_array($data->field_status_process, [ProcessType::Barcode, ProcessType::Delivery])) {
                $status_grouping = $data->field_status_process;
            }

            return $service->save($status_baru, $status_grouping, $data_transaksi, $linen, $log, $collection);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $th) {
            return Notes::error($rfid, 'RFID '.$rfid.' tidak ditemukan');
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

        if ($transaksi == TransactionType::BersihKotor) {
            $transaksi = TransactionType::Kotor;
        } elseif ($transaksi == TransactionType::BersihRetur) {
            $transaksi = TransactionType::Retur;
        } elseif ($transaksi == TransactionType::BersihRewash) {
            $transaksi = TransactionType::Rewash;
        } elseif ($transaksi == TransactionType::Unknown) {
            $transaksi = TransactionType::Register;
        }

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
