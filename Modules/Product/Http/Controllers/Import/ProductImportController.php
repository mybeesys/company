<?php

namespace Modules\Product\Http\Controllers\Import;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Modules\Product\Services\ProductImportService;
use Throwable;

class ProductImportController extends Controller
{
    public function import()
    {
        return view('product::product.import');
    }

    public function readData(Request $request, ProductImportService $service)
    {
        $request->validate([
            'file' => 'required|file|max:10240',
        ]);

        try {
            $file = $request->file('file');
            $data = Excel::toArray([], $file);
            $sheet = $data[0] ?? [];
            $mapped = $service->mapSheet($sheet);
            $rows = $service->validateRows($mapped);

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

    public function validateRows(Request $request, ProductImportService $service)
    {
        $request->validate([
            'rows' => 'required|array|min:1',
        ]);

        $rows = $service->validateRows($request->input('rows', []));

        return response()->json([
            'rows' => $rows,
            'count' => count($rows),
            'summary' => $this->summary($rows),
        ]);
    }

    public function upload(Request $request, ProductImportService $service)
    {
        try {
            if ($request->filled('rows') || $request->has('rows')) {
                $rows = $request->input('rows');
                if (is_string($rows)) {
                    $rows = json_decode($rows, true) ?: [];
                }
                if (! is_array($rows) || count($rows) === 0) {
                    return response()->json([
                        'message' => 'Error',
                        'errors' => [[
                            'row' => ['name_ar' => '—', 'name_en' => '—'],
                            'message' => ['message' => 'INVALID_import', 'data' => ['empty rows']],
                        ]],
                    ], 200);
                }

                $result = $service->importRows($rows);

                if ($result['imported'] > 0 && $result['failed'] === 0) {
                    return response()->json([
                        'message' => 'Done',
                        'imported' => $result['imported'],
                        'skipped' => $result['skipped'],
                        'failed' => $result['failed'],
                        'rows' => $result['rows'],
                        'summary' => $this->summary($result['rows']),
                    ], 200);
                }

                if ($result['imported'] > 0) {
                    return response()->json([
                        'message' => 'Partial',
                        'imported' => $result['imported'],
                        'skipped' => $result['skipped'],
                        'failed' => $result['failed'],
                        'errors' => $result['errors'],
                        'rows' => $result['rows'],
                        'summary' => $this->summary($result['rows']),
                    ], 200);
                }

                return response()->json([
                    'message' => 'Error',
                    'imported' => $result['imported'],
                    'skipped' => $result['skipped'],
                    'failed' => $result['failed'],
                    'errors' => $result['errors'],
                    'rows' => $result['rows'],
                    'summary' => $this->summary($result['rows']),
                ], 200);
            }

            $request->validate([
                'file' => 'required|file|max:10240',
            ]);

            $file = $request->file('file');
            $data = Excel::toArray([], $file);
            $mapped = $service->mapSheet($data[0] ?? []);
            $result = $service->importRows($mapped);

            if ($result['imported'] > 0 && $result['failed'] === 0) {
                return response()->json([
                    'message' => 'Done',
                    'imported' => $result['imported'],
                    'skipped' => $result['skipped'],
                    'failed' => $result['failed'],
                    'rows' => $result['rows'],
                    'summary' => $this->summary($result['rows']),
                ], 200);
            }

            if ($result['imported'] > 0) {
                return response()->json([
                    'message' => 'Partial',
                    'imported' => $result['imported'],
                    'skipped' => $result['skipped'],
                    'failed' => $result['failed'],
                    'errors' => $result['errors'],
                    'rows' => $result['rows'],
                    'summary' => $this->summary($result['rows']),
                ], 200);
            }

            return response()->json([
                'message' => 'Error',
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
