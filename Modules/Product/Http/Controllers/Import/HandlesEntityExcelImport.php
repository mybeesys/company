<?php

namespace Modules\Product\Http\Controllers\Import;

use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Throwable;

trait HandlesEntityExcelImport
{
    abstract protected function importService(): object;

    abstract protected function importView(): string;

    abstract protected function emsEntity(): string;

    public function import()
    {
        return view($this->importView());
    }

    public function readData(Request $request)
    {
        $request->validate([
            'file' => 'required|file|max:10240',
        ]);

        try {
            $data = Excel::toArray([], $request->file('file'));
            $mapped = $this->importService()->mapSheet($data[0] ?? []);
            $rows = $this->importService()->validateRows($mapped);

            return response()->json([
                'rows' => $rows,
                'count' => count($rows),
                'summary' => $this->summary($rows),
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'message' => 'Error',
                'detail' => $e->getMessage(),
                'rows' => [],
                'count' => 0,
            ], 422);
        }
    }

    public function validateRows(Request $request)
    {
        $request->validate([
            'rows' => 'required|array|min:1',
        ]);

        $rows = $this->importService()->validateRows($request->input('rows', []));

        return response()->json([
            'rows' => $rows,
            'count' => count($rows),
            'summary' => $this->summary($rows),
        ]);
    }

    public function upload(Request $request)
    {
        try {
            $rows = $request->input('rows');
            if (is_string($rows)) {
                $rows = json_decode($rows, true) ?: [];
            }

            if (! is_array($rows) || count($rows) === 0) {
                if (! $request->hasFile('file')) {
                    return response()->json([
                        'message' => 'Error',
                        'errors' => [[
                            'row' => ['name_ar' => '—', 'name_en' => '—'],
                            'message' => ['message' => 'INVALID_import', 'data' => ['empty rows']],
                        ]],
                    ], 200);
                }

                $data = Excel::toArray([], $request->file('file'));
                $rows = $this->importService()->mapSheet($data[0] ?? []);
            }

            $result = $this->importService()->importRows($rows);

            $message = 'Error';
            if ($result['imported'] > 0 && $result['failed'] === 0) {
                $message = 'Done';
            } elseif ($result['imported'] > 0) {
                $message = 'Partial';
            }

            return response()->json([
                'message' => $message,
                'imported' => $result['imported'],
                'skipped' => $result['skipped'],
                'failed' => $result['failed'],
                'errors' => $result['errors'],
                'rows' => $result['rows'],
                'summary' => $this->summary($result['rows']),
            ], 200);
        } catch (Throwable $e) {
            return response()->json([
                'message' => 'Error',
                'errors' => [[
                    'row' => ['name_ar' => '—', 'name_en' => '—'],
                    'message' => ['message' => 'INVALID_import', 'data' => [$e->getMessage()]],
                ]],
                'detail' => config('app.debug') ? $e->getMessage() : null,
            ], 200);
        }
    }

    protected function summary(array $rows): array
    {
        $counts = ['ok' => 0, 'exists' => 0, 'error' => 0, 'imported' => 0];
        foreach ($rows as $row) {
            $status = $row['status'] ?? 'ok';
            if (! isset($counts[$status])) {
                $counts[$status] = 0;
            }
            $counts[$status]++;
        }

        return $counts;
    }
}
