<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache; 

class ContainerController extends Controller
{
    // Fungsi bantuan untuk mengambil data dummy pakai Cache
    private function getDummyData()
    {
        if (!Cache::has('containers')) {
            Cache::forever('containers', [
                [
                    'container_id' => 'WH12345',
                    'waste_type' => 'Plastic',
                    'weight_kg' => 200,
                    'status' => 'Active',
                    'tracking_logs' => [
                        ['location' => 'Gudang A', 'timestamp' => '2026-04-16T08:00:00', 'description' => 'Disimpan di gudang awal']
                    ]
                ],
                [
                    'container_id' => 'CH98765',
                    'waste_type' => 'Chemical',
                    'weight_kg' => 500,
                    'status' => 'Archived',
                    'tracking_logs' => []
                ]
            ]);
        }
        return Cache::get('containers');
    }

    private function saveDummyData($data)
    {
        Cache::forever('containers', $data);
    }

    public function index()
    {
        return response()->json($this->getDummyData(), 200);
    }

    public function store(Request $request)
    {
        $request->validate([
            'container_id' => ['required', 'string', 'regex:/^[A-Za-z]{2}[0-9]{5}$/'],
            'waste_type' => 'required|string',
            'weight_kg' => 'required|numeric|min:10|max:5000',
        ]);

        $data = $this->getDummyData();

        if (collect($data)->where('container_id', $request->container_id)->first()) {
            return response()->json([
                'message' => 'Validasi gagal',
                'errors' => ['container_id' => ['Container ID sudah digunakan.']]
            ], 422);
        }

        if ($request->waste_type === 'Chemical' && $request->weight_kg > 1000) {
            return response()->json([
                'message' => 'Validasi gagal',
                'errors' => ['weight_kg' => ['Untuk limbah Chemical, berat maksimal adalah 1000 kg.']]
            ], 422);
        }

        $newContainer = [
            'container_id' => strtoupper($request->container_id),
            'waste_type' => $request->waste_type,
            'weight_kg' => (float) $request->weight_kg,
            'status' => 'Active',
            'tracking_logs' => [
                ['location' => 'Titik Awal', 'timestamp' => now()->toIso8601String(), 'description' => 'Kontainer didaftarkan']
            ]
        ];

        $data[] = $newContainer;
        $this->saveDummyData($data);

        return response()->json(['message' => 'Data berhasil disimpan', 'data' => $newContainer], 201);
    }

    public function archive($id)
    {
        $data = $this->getDummyData();
        $index = collect($data)->search(fn($item) => $item['container_id'] === $id);

        if ($index === false) {
            return response()->json(['message' => 'Data tidak ditemukan'], 404);
        }

        $data[$index]['status'] = 'Archived';
        $this->saveDummyData($data);

        return response()->json(['message' => 'Status diubah menjadi Archived'], 200);
    }

    public function destroy($id)
    {
        $data = $this->getDummyData();
        $index = collect($data)->search(fn($item) => $item['container_id'] === $id);

        if ($index === false) {
            return response()->json(['message' => 'Data tidak ditemukan'], 404);
        }

        array_splice($data, $index, 1);
        $this->saveDummyData($data);

        return response()->json(['message' => 'Data berhasil dihapus'], 200);
    }

    public function search(Request $request)
    {
        $data = collect($this->getDummyData());

        if ($request->has('type')) {
            $data = $data->where('waste_type', $request->type);
        }
        if ($request->has('min_weight')) {
            $data = $data->where('weight_kg', '>=', $request->min_weight);
        }

        return response()->json($data->values()->all(), 200);
    }

    public function logs($id)
    {
        $data = collect($this->getDummyData())->firstWhere('container_id', $id);

        if (!$data) {
            return response()->json(['message' => 'Data tidak ditemukan'], 404);
        }

        return response()->json($data['tracking_logs'], 200);
    }
}