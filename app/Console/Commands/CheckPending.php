<?php

namespace App\Console\Commands;

use App\Dao\Enums\HilangType;
use App\Dao\Enums\LogType;
use App\Dao\Enums\ProcessType;
use App\Dao\Enums\TransactionType;
use App\Dao\Models\Detail;
use App\Dao\Models\Outstanding;
use App\Dao\Models\Pending;
use App\Dao\Models\Transaksi;
use App\Dao\Models\ViewDetailLinen;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Plugins\History as PluginsHistory;
use Plugins\Query;

class CheckPending extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'check:pending';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This Commands check is there any pending rfid';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $outstanding = Outstanding::leftJoinRelationship('has_detail')
            ->addSelect([Detail::field_jenis_id(), Detail::field_ruangan_id(), Detail::field_rs_id()])
            ->whereDate(Outstanding::field_updated_at(), '<=', Carbon::now()->subMinutes(1440)->toDateString())
            ->whereNot(Outstanding::field_status_transaction(), TransactionType::REGISTER)
            ->where(Outstanding::field_status_hilang(), HilangType::NORMAL)
            ->get();

        if ($outstanding) {

            $rfid = $outstanding->pluck(Outstanding::field_primary());

            PluginsHistory::bulk($rfid, LogType::PENDING, 'RFID Pending');
            Outstanding::whereIn(Outstanding::field_primary(), $rfid)->update([
                Outstanding::field_status_hilang() => HilangType::PENDING,
                Outstanding::field_pending_created_at() => date('Y-m-d H:i:s'),
                Outstanding::field_pending_updated_at() => date('Y-m-d H:i:s'),
            ]);

            $autonumber = Query::autoNumber(Pending::getTableName(), Pending::field_key(), 'PND'.date('ymd'), 15);
            $pending = [];
            foreach($outstanding as $ending)
            {
                $pending[] = [
                    Pending::field_rfid() => $ending->field_primary,
                    Pending::field_rs_id() => $ending->detail_id_rs,
                    Pending::field_jenis_id() => $ending->detail_id_jenis,
                    Pending::field_ruangan_id() => $ending->detail_id_ruangan,
                    Pending::field_tanggal() => date('Y-m-d'),
                    Pending::field_kotor() => $ending->field_created_at->format('Y-m-d'),
                    Pending::field_status_transaction() => $ending->field_status_transaction,
                    Pending::field_status_process() => $ending->field_status_process,
                    Pending::field_key() => $autonumber,
                ];
            }

            Pending::insert($pending);
        }

        $this->info('The system has been check successfully!');
    }
}
