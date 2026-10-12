<?php

namespace App\Http\Controllers;

use App\Models\FaskesFieldDefinition;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class FaskesFieldController extends Controller
{
    /**
     * Daftar jenis faskes yang valid.
     */
    public const VALID_JENIS_FASKES = [
        'puskesmas',
        'rumah_sakit',
        'klinik_pratama',
        'klinik_utama',
        'laboratorium',
        'upkdk',
        'griya_sehat',
        'tpmd',
        'tpmdg',
        'tpmb',
        'tpmp',
    ];

    /**
     * Menampilkan daftar definisi kolom tambahan.
     */
    public function index(Request $request)
    {
        $query = FaskesFieldDefinition::query()
            ->withCount('values')
            ->orderBy('sort_order')
            ->orderBy('id', 'desc');

        if ($request->filled('jenis_faskes')) {
            if ($request->jenis_faskes === 'all') {
                $query->whereNull('jenis_faskes');
            } else {
                $query->where('jenis_faskes', $request->jenis_faskes);
            }
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'aktif');
        }

        $fields = $query->paginate(15)->withQueryString();

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'status' => 'success',
                'data' => $fields,
            ]);
        }

        return view('faskes_fields.index', [
            'fields' => $fields,
            'validJenisFaskes' => self::VALID_JENIS_FASKES,
            'validTypes' => FaskesFieldDefinition::VALID_TYPES,
        ]);
    }

    /**
     * Menyimpan definisi kolom tambahan baru.
     */
    public function store(Request $request)
    {
        $type = $request->input('type', FaskesFieldDefinition::TYPE_SELECT);
        if (! in_array($type, FaskesFieldDefinition::VALID_TYPES, true)) {
            $type = FaskesFieldDefinition::TYPE_SELECT;
        }

        // Parsing options jika dikirim dalam bentuk text atau json (untuk select / multiselect)
        $options = $this->parseOptionsInput($request->input('options'));
        $request->merge(['options' => $options]);

        // Validasi kuota kolom aktif
        $jenisFaskes = $request->filled('jenis_faskes') ? $request->jenis_faskes : null;
        $this->validateActiveQuota($jenisFaskes);

        $rules = [
            'label' => 'required|string|max:100',
            'jenis_faskes' => ['nullable', 'string', Rule::in(self::VALID_JENIS_FASKES)],
            'type' => ['required', 'string', Rule::in(FaskesFieldDefinition::VALID_TYPES)],
            'is_required' => 'nullable|boolean',
            'is_filterable' => 'nullable|boolean',
            'show_in_list' => 'nullable|boolean',
            'sort_order' => 'nullable|integer',
        ];

        $messages = [
            'label.required' => 'Label kolom wajib diisi.',
            'label.max' => 'Label kolom maksimal 100 karakter.',
            'type.required' => 'Tipe kolom wajib dipilih.',
            'type.in' => 'Tipe data kolom tidak valid.',
            'jenis_faskes.in' => 'Jenis faskes tidak valid.',
        ];

        // Validasi khusus per tipe
        if (in_array($type, [FaskesFieldDefinition::TYPE_SELECT, FaskesFieldDefinition::TYPE_MULTISELECT], true)) {
            $rules['options'] = 'required|array|min:1|max:30';
            $rules['options.*'] = 'required|string|max:100';
            $messages['options.required'] = 'Daftar opsi pilihan wajib diisi minimal 1 opsi.';
            $messages['options.min'] = 'Daftar opsi pilihan wajib memiliki minimal 1 opsi.';
            $messages['options.max'] = 'Maksimal 30 opsi pilihan.';
            $messages['options.*.max'] = 'Tiap opsi maksimal 100 karakter.';
        }

        $request->validate($rules, $messages);

        $config = $this->buildConfigFromRequest($request, $type);
        $label = trim($request->label);
        $fieldKey = $this->generateUniqueFieldKey($label, $jenisFaskes);

        $field = FaskesFieldDefinition::create([
            'jenis_faskes' => $jenisFaskes,
            'label' => $label,
            'field_key' => $fieldKey,
            'type' => $type,
            'options' => in_array($type, [FaskesFieldDefinition::TYPE_SELECT, FaskesFieldDefinition::TYPE_MULTISELECT], true) ? $options : null,
            'config' => ! empty($config) ? $config : null,
            'is_required' => $request->boolean('is_required'),
            'is_filterable' => $request->has('is_filterable') ? $request->boolean('is_filterable') : true,
            'show_in_list' => $request->has('show_in_list') ? $request->boolean('show_in_list') : true,
            'sort_order' => (int) $request->input('sort_order', 0),
            'is_active' => true,
        ]);

        if ($request->wantsJson() || $request->ajax() || $request->expectsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => 'Kolom tambahan berhasil dibuat.',
                'data' => [
                    'id' => $field->id,
                    'label' => $field->label,
                    'field_key' => $field->field_key,
                    'jenis_faskes' => $field->jenis_faskes,
                    'type' => $field->type,
                    'options' => $field->options,
                    'config' => $field->config,
                    'is_required' => $field->is_required,
                    'is_filterable' => $field->is_filterable,
                    'show_in_list' => $field->show_in_list,
                ],
            ]);
        }

        return redirect()->route('faskes-fields.index')->with('success', 'Kolom tambahan berhasil ditambahkan.');
    }

    /**
     * Memperbarui definisi kolom tambahan.
     */
    public function update(Request $request, $id)
    {
        $field = FaskesFieldDefinition::findOrFail($id);

        $newType = $request->input('type', $field->type);
        if (! in_array($newType, FaskesFieldDefinition::VALID_TYPES, true)) {
            $newType = $field->type;
        }

        // Cek guard tipe data jika sudah ada nilai tersimpan di faskes
        if ($field->values()->exists() && $newType !== $field->type) {
            $isTextTextareaSwap = (
                ($field->type === FaskesFieldDefinition::TYPE_TEXT && $newType === FaskesFieldDefinition::TYPE_TEXTAREA) ||
                ($field->type === FaskesFieldDefinition::TYPE_TEXTAREA && $newType === FaskesFieldDefinition::TYPE_TEXT)
            );

            if (! $isTextTextareaSwap) {
                throw ValidationException::withMessages([
                    'type' => 'Nonaktifkan kolom ini lalu buat kolom baru dengan tipe yang diinginkan.',
                ]);
            }
        }

        $options = $this->parseOptionsInput($request->input('options'));
        $request->merge(['options' => $options]);

        $rules = [
            'label' => 'required|string|max:100',
            'jenis_faskes' => ['nullable', 'string', Rule::in(self::VALID_JENIS_FASKES)],
            'type' => ['required', 'string', Rule::in(FaskesFieldDefinition::VALID_TYPES)],
            'is_required' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
            'is_filterable' => 'nullable|boolean',
            'show_in_list' => 'nullable|boolean',
            'sort_order' => 'nullable|integer',
        ];

        $messages = [
            'label.required' => 'Label kolom wajib diisi.',
            'type.required' => 'Tipe kolom wajib dipilih.',
            'type.in' => 'Tipe data kolom tidak valid.',
        ];

        if (in_array($newType, [FaskesFieldDefinition::TYPE_SELECT, FaskesFieldDefinition::TYPE_MULTISELECT], true)) {
            $rules['options'] = 'required|array|min:1|max:30';
            $rules['options.*'] = 'required|string|max:100';
            $messages['options.required'] = 'Daftar opsi pilihan wajib diisi.';
            $messages['options.min'] = 'Daftar opsi pilihan wajib memiliki minimal 1 opsi.';
            $messages['options.max'] = 'Maksimal 30 opsi per dropdown/pilihan ganda.';
        }

        $request->validate($rules, $messages);

        $jenisFaskes = $request->filled('jenis_faskes') ? $request->jenis_faskes : null;
        $label = trim($request->label);

        // Jika label atau lingkup berubah, regenerasi field_key unik
        $fieldKey = $field->field_key;
        if ($label !== $field->label || $jenisFaskes !== $field->jenis_faskes) {
            $fieldKey = $this->generateUniqueFieldKey($label, $jenisFaskes, $field->id);
        }

        $config = $this->buildConfigFromRequest($request, $newType);

        $field->update([
            'jenis_faskes' => $jenisFaskes,
            'label' => $label,
            'field_key' => $fieldKey,
            'type' => $newType,
            'options' => in_array($newType, [FaskesFieldDefinition::TYPE_SELECT, FaskesFieldDefinition::TYPE_MULTISELECT], true) ? $options : null,
            'config' => ! empty($config) ? $config : null,
            'is_required' => $request->boolean('is_required'),
            'is_active' => $request->has('is_active') ? $request->boolean('is_active') : $field->is_active,
            'is_filterable' => $request->has('is_filterable') ? $request->boolean('is_filterable') : $field->is_filterable,
            'show_in_list' => $request->has('show_in_list') ? $request->boolean('show_in_list') : $field->show_in_list,
            'sort_order' => (int) $request->input('sort_order', $field->sort_order),
        ]);

        if ($request->wantsJson() || $request->ajax() || $request->expectsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => 'Kolom tambahan berhasil diperbarui.',
                'data' => [
                    'id' => $field->id,
                    'label' => $field->label,
                    'field_key' => $field->field_key,
                    'jenis_faskes' => $field->jenis_faskes,
                    'type' => $field->type,
                    'options' => $field->options,
                    'config' => $field->config,
                    'is_required' => $field->is_required,
                    'is_active' => $field->is_active,
                    'is_filterable' => $field->is_filterable,
                    'show_in_list' => $field->show_in_list,
                ],
            ]);
        }

        return redirect()->route('faskes-fields.index')->with('success', 'Kolom tambahan berhasil diperbarui.');
    }

    /**
     * Menghapus atau menonaktifkan kolom tambahan.
     */
    public function destroy(Request $request, $id)
    {
        $field = FaskesFieldDefinition::findOrFail($id);
        $hasValues = $field->values()->exists();

        if ($hasValues) {
            $field->update(['is_active' => false]);
            $message = 'Kolom "' . $field->label . '" dinonaktifkan karena sudah memiliki data riwayat faskes.';
            $action = 'deactivated';
        } else {
            $field->delete();
            $message = 'Kolom "' . $field->label . '" berhasil dihapus permanen.';
            $action = 'deleted';
        }

        if ($request->wantsJson() || $request->ajax() || $request->expectsJson()) {
            return response()->json([
                'status' => 'success',
                'action' => $action,
                'message' => $message,
            ]);
        }

        return redirect()->route('faskes-fields.index')->with('success', $message);
    }

    /**
     * Helper validasi kuota maks 30 kolom aktif.
     */
    protected function validateActiveQuota(?string $jenisFaskes): void
    {
        $globalCount = FaskesFieldDefinition::where('is_active', true)
            ->whereNull('jenis_faskes')
            ->count();

        if ($jenisFaskes === null) {
            // Kolom global baru akan menambah hitungan untuk SEMUA jenis
            if ($globalCount >= 30) {
                throw ValidationException::withMessages([
                    'label' => 'Kuota maksimal 30 kolom aktif untuk jenis faskes global telah tercapai.',
                ]);
            }

            foreach (self::VALID_JENIS_FASKES as $jf) {
                $specificCount = FaskesFieldDefinition::where('is_active', true)
                    ->where('jenis_faskes', $jf)
                    ->count();

                if ($globalCount + $specificCount >= 30) {
                    throw ValidationException::withMessages([
                        'label' => "Menambahkan kolom global akan melebihi kuota 30 kolom aktif pada jenis faskes {$jf}.",
                    ]);
                }
            }
        } else {
            $specificCount = FaskesFieldDefinition::where('is_active', true)
                ->where('jenis_faskes', $jenisFaskes)
                ->count();

            if ($globalCount + $specificCount >= 30) {
                throw ValidationException::withMessages([
                    'label' => "Kuota maksimal 30 kolom aktif untuk jenis faskes {$jenisFaskes} telah tercapai.",
                ]);
            }
        }
    }

    /**
     * Build config array sesuai tipe data.
     */
    protected function buildConfigFromRequest(Request $request, string $type): array
    {
        $cfg = [];
        $inputCfg = (array) $request->input('config', []);

        switch ($type) {
            case FaskesFieldDefinition::TYPE_TEXT:
                $maxLen = isset($inputCfg['max_length']) ? (int) $inputCfg['max_length'] : 255;
                $cfg['max_length'] = max(1, min(255, $maxLen));
                $cfg['placeholder'] = trim((string) ($inputCfg['placeholder'] ?? ''));
                $cfg['help_text'] = trim((string) ($inputCfg['help_text'] ?? ''));
                break;

            case FaskesFieldDefinition::TYPE_TEXTAREA:
                $maxLen = isset($inputCfg['max_length']) ? (int) $inputCfg['max_length'] : 2000;
                $cfg['max_length'] = max(1, min(5000, $maxLen));
                $rows = isset($inputCfg['rows']) ? (int) $inputCfg['rows'] : 3;
                $cfg['rows'] = max(2, min(10, $rows));
                $cfg['placeholder'] = trim((string) ($inputCfg['placeholder'] ?? ''));
                $cfg['help_text'] = trim((string) ($inputCfg['help_text'] ?? ''));
                break;

            case FaskesFieldDefinition::TYPE_NUMBER:
                $decimals = isset($inputCfg['decimals']) ? (int) $inputCfg['decimals'] : 0;
                $cfg['decimals'] = max(0, min(4, $decimals));
                $cfg['min'] = (isset($inputCfg['min']) && $inputCfg['min'] !== '') ? (float) $inputCfg['min'] : null;
                $cfg['max'] = (isset($inputCfg['max']) && $inputCfg['max'] !== '') ? (float) $inputCfg['max'] : null;
                $cfg['unit'] = trim((string) ($inputCfg['unit'] ?? ''));
                $cfg['help_text'] = trim((string) ($inputCfg['help_text'] ?? ''));
                break;

            case FaskesFieldDefinition::TYPE_MULTISELECT:
                $maxSelect = (isset($inputCfg['max_select']) && $inputCfg['max_select'] !== '') ? (int) $inputCfg['max_select'] : null;
                $cfg['max_select'] = $maxSelect ? max(1, min(30, $maxSelect)) : null;
                $cfg['help_text'] = trim((string) ($inputCfg['help_text'] ?? ''));
                break;

            case FaskesFieldDefinition::TYPE_URL:
            case FaskesFieldDefinition::TYPE_EMAIL:
            case FaskesFieldDefinition::TYPE_PHONE:
            case FaskesFieldDefinition::TYPE_DATE:
            case FaskesFieldDefinition::TYPE_BOOLEAN:
                $cfg['placeholder'] = trim((string) ($inputCfg['placeholder'] ?? ''));
                $cfg['help_text'] = trim((string) ($inputCfg['help_text'] ?? ''));
                break;
        }

        return array_filter($cfg, fn ($v) => $v !== null && $v !== '');
    }

    /**
     * Parse input options ke array bersih.
     */
    protected function parseOptionsInput(mixed $rawOptions): array
    {
        if (is_string($rawOptions)) {
            $decoded = json_decode($rawOptions, true);
            if (is_array($decoded)) {
                $rawOptions = $decoded;
            } else {
                $rawOptions = array_map('trim', explode("\n", $rawOptions));
            }
        }

        return array_values(array_unique(array_filter((array) $rawOptions, fn ($o) => trim((string) $o) !== '')));
    }

    /**
     * Generate slug field_key unik per jenis_faskes.
     */
    protected function generateUniqueFieldKey(string $label, ?string $jenisFaskes, ?int $ignoreId = null): string
    {
        $baseKey = Str::slug($label, '_');
        if (empty($baseKey)) {
            $baseKey = 'field_' . time();
        }

        $key = $baseKey;
        $counter = 2;

        while (true) {
            $query = FaskesFieldDefinition::where('field_key', $key);

            if ($jenisFaskes === null) {
                $query->whereNull('jenis_faskes');
            } else {
                $query->where('jenis_faskes', $jenisFaskes);
            }

            if ($ignoreId) {
                $query->where('id', '!=', $ignoreId);
            }

            if (! $query->exists()) {
                break;
            }

            $key = $baseKey . '_' . $counter;
            $counter++;
        }

        return $key;
    }
}
