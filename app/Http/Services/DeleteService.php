<?php

namespace App\Http\Services;

use App\Dao\Enums\LogType;
use App\Dao\Interfaces\CrudInterface;
use App\Dao\Models\History;
use Plugins\Alert;

class DeleteService
{
    public function delete(CrudInterface $repository, $code)
    {
        $rules = ['code' => 'required'];
        request()->validate($rules, ['code.required' => 'Please select any data !']);

        $log = [];
        $date = now();
        $user = auth()->user()->name ?? 'system';

        $codes = is_array($code) ? array_values(array_unique($code)) : [$code];
        foreach ($codes as $key) {
            $rs_id = 0;
            try {
                $model = $repository->model ?? null;
                if ($model && method_exists($model, 'field_rs_id')) {
                    $pk = $model->getKeyName();
                    $rsField = $model::field_rs_id();
                    $rs_id = $model::where($pk, $key)->value($rsField) ?? 0;
                }
            } catch (\Throwable $th) {
            }
            $log[] = [
                History::field_rs_id() => $rs_id,
                History::field_name() => $key,
                History::field_status() => LogType::DELETE_RFID,
                History::field_created_by() => $user,
                History::field_created_at() => $date,
                History::field_description() => "user mendelete rfid ".$key,
            ];
        }

        History::insert($log);

        $check = $repository->deleteRepository($code);

        if ($check['status']) {
            Alert::delete();
        } else {
            Alert::error($check['message']);
        }

        if (request()->wantsJson()) {

            return response()->json($check)->getData();
        }

        return $check;
    }
}
