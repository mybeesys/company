<?php

namespace Modules\Product\Services;

use Illuminate\Support\Facades\DB;
use Modules\Product\Models\Attribute;
use Modules\Product\Models\AttributeClass;
use Throwable;

class AttributeImportService
{
    public function mapSheet(array $sheet): array
    {
        return collect($sheet)
            ->map(function ($row) {
                $row = array_values(is_array($row) ? $row : []);

                return [
                    'name_ar' => $this->str($row[0] ?? null),
                    'name_en' => $this->str($row[1] ?? null),
                    'attribute_class' => $this->str($row[2] ?? null),
                    'active' => $row[3] ?? 1,
                    'order' => $row[4] ?? null,
                ];
            })
            ->filter(fn ($row) => ($row['name_ar'] ?? '') !== '' || ($row['name_en'] ?? '') !== '')
            ->reject(function ($row) {
                $nameAr = strtolower((string) ($row['name_ar'] ?? ''));

                return in_array($nameAr, ['arabic_name', 'name_ar'], true);
            })
            ->values()
            ->all();
    }

    public function validateRows(array $rows): array
    {
        $seen = [];
        $result = [];

        foreach (array_values($rows) as $index => $raw) {
            $row = $this->normalizeRow($raw);
            $issues = [];
            $problemFields = [];

            if ($row['name_ar'] === '') {
                $issues[] = ['code' => 'REQUIRED', 'field' => 'name_ar', 'value' => ''];
                $problemFields[] = 'name_ar';
            }
            if ($row['name_en'] === '') {
                $issues[] = ['code' => 'REQUIRED', 'field' => 'name_en', 'value' => ''];
                $problemFields[] = 'name_en';
            }
            if ($row['attribute_class'] === '') {
                $issues[] = ['code' => 'REQUIRED', 'field' => 'attribute_class', 'value' => ''];
                $problemFields[] = 'attribute_class';
            }

            $dupKey = mb_strtolower($row['attribute_class'].'|'.$row['name_ar'].'|'.$row['name_en']);
            if (($row['name_ar'] !== '' || $row['name_en'] !== '') && isset($seen[$dupKey])) {
                $issues[] = ['code' => 'UNIQUE_FILE', 'field' => 'name_ar', 'value' => $row['name_ar']];
                $problemFields[] = 'name_ar';
            }
            $seen[$dupKey] = true;

            $existsFields = [];
            if ($row['attribute_class'] !== '' && ($row['name_ar'] !== '' || $row['name_en'] !== '')) {
                $class = AttributeClass::query()
                    ->where(fn ($q) => $q->where('name_ar', $row['attribute_class'])->orWhere('name_en', $row['attribute_class']))
                    ->first();
                if ($class) {
                    if ($row['name_ar'] !== '' && Attribute::where('parent_id', $class->id)->where('name_ar', $row['name_ar'])->exists()) {
                        $existsFields[] = 'name_ar';
                    }
                    if ($row['name_en'] !== '' && Attribute::where('parent_id', $class->id)->where('name_en', $row['name_en'])->exists()) {
                        $existsFields[] = 'name_en';
                    }
                }
            }

            $problemFields = array_values(array_unique($problemFields));

            if (count($issues) > 0) {
                $status = 'error';
            } elseif (count($existsFields) > 0) {
                $status = 'exists';
                $issues[] = ['code' => 'EXISTS', 'field' => implode(',', $existsFields), 'value' => $row['name_ar'] ?: $row['name_en']];
                $problemFields = $existsFields;
            } else {
                $status = 'ok';
            }

            $result[] = array_merge($row, [
                '_index' => $index,
                'status' => $status,
                'issues' => $issues,
                'problem_fields' => $problemFields,
            ]);
        }

        return $result;
    }

    public function importRows(array $rows): array
    {
        $validated = $this->validateRows($rows);
        $imported = 0;
        $skipped = 0;
        $failed = 0;
        $errors = [];
        $outRows = [];

        foreach ($validated as $row) {
            if ($row['status'] === 'exists') {
                $skipped++;
                $outRows[] = $row;
                continue;
            }
            if ($row['status'] === 'error') {
                $failed++;
                $errors[] = [
                    'row' => ['name_ar' => $row['name_ar'], 'name_en' => $row['name_en']],
                    'message' => [
                        'message' => 'INVALID_row',
                        'data' => collect($row['issues'])->map(fn ($i) => ($i['field'] ?? '').':'.($i['code'] ?? ''))->all(),
                    ],
                ];
                $outRows[] = $row;
                continue;
            }

            try {
                DB::transaction(fn () => $this->createFromRow($row));
                $imported++;
                $row['status'] = 'imported';
                $outRows[] = $row;
            } catch (Throwable $e) {
                $failed++;
                $row['status'] = 'error';
                $errors[] = [
                    'row' => ['name_ar' => $row['name_ar'], 'name_en' => $row['name_en']],
                    'message' => ['message' => 'INVALID_import', 'data' => [$e->getMessage()]],
                ];
                $outRows[] = $row;
            }
        }

        return compact('imported', 'skipped', 'failed', 'errors') + ['rows' => $outRows];
    }

    protected function createFromRow(array $row): Attribute
    {
        $class = $this->resolveClass($row['attribute_class']);
        $order = $row['order'];
        if ($order === null || $order === '') {
            $order = ((int) Attribute::where('parent_id', $class->id)->max('order')) + 1;
        }

        return Attribute::create([
            'name_ar' => $row['name_ar'],
            'name_en' => $row['name_en'],
            'parent_id' => $class->id,
            'active' => (int) ($row['active'] ?? 1),
            'order' => $order,
        ]);
    }

    protected function resolveClass(string $name): AttributeClass
    {
        $class = AttributeClass::query()
            ->where(fn ($q) => $q->where('name_ar', $name)->orWhere('name_en', $name))
            ->first();

        if ($class) {
            return $class;
        }

        return AttributeClass::create([
            'name_ar' => $name,
            'name_en' => $name,
            'active' => 1,
            'order' => ((int) AttributeClass::max('order')) + 1,
        ]);
    }

    protected function normalizeRow(array $raw): array
    {
        return [
            'name_ar' => $this->str($raw['name_ar'] ?? ''),
            'name_en' => $this->str($raw['name_en'] ?? ''),
            'attribute_class' => $this->str($raw['attribute_class'] ?? ''),
            'active' => $raw['active'] ?? 1,
            'order' => $raw['order'] ?? null,
        ];
    }

    protected function str($value): string
    {
        return $value === null ? '' : trim((string) $value);
    }
}
