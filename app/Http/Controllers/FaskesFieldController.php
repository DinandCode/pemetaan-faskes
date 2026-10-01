<?php

namespace App\Http\Controllers;

use App\Models\FaskesFieldDefinition;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

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
        ]);
    }

    /**
     * Menyimpan definisi kolom tambahan baru.
     */
    public function store(Request $request)
    {
        // Parsing options jika dikirim dalam bentuk text atau json
        $rawOptions = $request->input('options');
        if (is_string($rawOptions)) {
            $decoded = json_decode($rawOptions, true);
            if (is_array($decoded)) {
                $rawOptions = $decoded;
            } else {
                $rawOptions = array_map('trim', explode("\n", $rawOptions));
            }
        }

        $options = array_values(array_unique(array_filter((array) $rawOptions, fn ($o) => trim($o) !== '')));
        $request->merge(['options' => $options]);

        $request->validate([
            'label' => 'required|string|max:100',
            'jenis_faskes' => ['nullable', 'string', Rule::in(self::VALID_JENIS_FASKES)],
            'options' => 'required|array|min:1|max:30',
            'options.*' => 'required|string|max:100',
            'is_required' => 'nullable|boolean',
            'sort_order' => 'nullable|integer',
        ], [
            'label.required' => 'Label kolom wajib diisi.',
            'label.max' => 'Label kolom maksimal 100 karakter.',
            'options.required' => 'Daftar opsi dropdown wajib diisi minimal 1 opsi.',
            'options.min' => 'Daftar opsi dropdown wajib memiliki minimal 1 opsi.',
            'options.max' => 'Maksimal 30 opsi per dropdown.',
            'options.*.max' => 'Tiap opsi maksimal 100 karakter.',
            'jenis_faskes.in' => 'Jenis faskes tidak valid.',
        ]);

        $jenisFaskes = $request->filled('jenis_faskes') ? $request->jenis_faskes : null;
        $label = trim($request->label);
        $fieldKey = $this->generateUniqueFieldKey($label, $jenisFaskes);

        $field = FaskesFieldDefinition::create([
            'jenis_faskes' => $jenisFaskes,
            'label' => $label,
            'field_key' => $fieldKey,
            'type' => 'select',
            'options' => $options,
            'is_required' => $request->boolean('is_required'),
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
                    'is_required' => $field->is_required,
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

        $rawOptions = $request->input('options');
        if (is_string($rawOptions)) {
            $decoded = json_decode($rawOptions, true);
            if (is_array($decoded)) {
                $rawOptions = $decoded;
            } else {
                $rawOptions = array_map('trim', explode("\n", $rawOptions));
            }
        }

        $options = array_values(array_unique(array_filter((array) $rawOptions, fn ($o) => trim($o) !== '')));
        $request->merge(['options' => $options]);

        $request->validate([
            'label' => 'required|string|max:100',
            'jenis_faskes' => ['nullable', 'string', Rule::in(self::VALID_JENIS_FASKES)],
            'options' => 'required|array|min:1|max:30',
            'options.*' => 'required|string|max:100',
            'is_required' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
            'sort_order' => 'nullable|integer',
        ], [
            'label.required' => 'Label kolom wajib diisi.',
            'options.required' => 'Daftar opsi dropdown wajib diisi.',
            'options.min' => 'Daftar opsi dropdown wajib memiliki minimal 1 opsi.',
            'options.max' => 'Maksimal 30 opsi per dropdown.',
        ]);

        $jenisFaskes = $request->filled('jenis_faskes') ? $request->jenis_faskes : null;
        $label = trim($request->label);

        // Jika label berubah, regenerasi slug unik
        $fieldKey = $field->field_key;
        if ($label !== $field->label || $jenisFaskes !== $field->jenis_faskes) {
            $fieldKey = $this->generateUniqueFieldKey($label, $jenisFaskes, $field->id);
        }

        $field->update([
            'jenis_faskes' => $jenisFaskes,
            'label' => $label,
            'field_key' => $fieldKey,
            'options' => $options,
            'is_required' => $request->boolean('is_required'),
            'is_active' => $request->has('is_active') ? $request->boolean('is_active') : $field->is_active,
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
                    'options' => $field->options,
                    'is_required' => $field->is_required,
                    'is_active' => $field->is_active,
                ],
            ]);
        }

        return redirect()->route('faskes-fields.index')->with('success', 'Kolom tambahan berhasil diperbarui.');
    }

    /**
     * Menghapus atau menonaktifkan kolom tambahan.
     * Jika sudah ada nilai tersimpan di faskes, hanya dinonaktifkan (is_active = false).
     * Jika belum ada nilai sama sekali, dihapus permanen.
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
