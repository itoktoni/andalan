<x-layout>
    <x-card>
        <x-form :model="$model">
            <x-action form="form" />

            @bind($model)

            <x-form-select col="6" class="search" label="Rumah sakit" name="detail_id_rs" :options="$rs" />
            <x-form-select col="6" class="search" name="detail_id_ruangan" :options="$ruangan" />
            <x-form-select col="6" class="search" name="detail_id_jenis" :options="$jenis" />
            <x-form-select col="6" class="search" name="detail_id_bahan" :options="$bahan" />
            <x-form-select col="6" class="search" name="detail_id_supplier" :options="$supplier" />
            <x-form-select col="6" class="search" name="detail_status_cuci" :options="$cuci" />

            @if($model)
            <input type="hidden" name="detail_rfid" value="{{ $model->detail_rfid }}">
            <div class="form-group col-md-6 ">
                <label>RFID Baru <small class="text-muted">(kosongkan jika tidak ganti)</small></label>
                <input type="text" class="form-control" value="{{ old('rfid_baru') ?? null }}" name="rfid_baru" placeholder="Scan / ketik RFID baru" autofocus>
            </div>
            <div class="form-group col-md-6 ">
                <label>RFID Lama</label>
                <input type="text" class="form-control" value="{{ old('rfid_lama') ?? $model->detail_rfid ?? null }}" name="rfid_lama" readonly>
            </div>
            @else
            <div class="form-group col-md-6 ">
                <label>No. RFID</label>
                <input type="text" class="form-control" value="{{ old('detail_rfid') ?? null }}" name="detail_rfid" placeholder="Scan / ketik RFID" required autofocus>
            </div>
            @endif

            @endbind

        </x-form>
    </x-card>
</x-layout>
