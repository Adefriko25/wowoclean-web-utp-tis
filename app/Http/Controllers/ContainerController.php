<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Container;
use App\Models\TrackingLog;
use OpenApi\Attributes as OA;

class ContainerController extends Controller
{
    #[OA\Get(
        path: "/api/v1/gateway/containers",
        summary: "Ambil semua data kontainer",
        security: [["bearerAuth" => []]],
        tags: ["Containers V1"],
        responses: [
            new OA\Response(response: 200, description: "Berhasil mengambil data kontainer"),
            new OA\Response(response: 401, description: "Unauthorized")
        ]
    )]
    public function index()
    {
        $containers = Container::with('trackingLogs')->get();
        return response()->json($containers, 200);
    }

    #[OA\Post(
        path: "/api/v1/gateway/containers",
        summary: "Tambah kontainer baru (Admin only)",
        security: [["bearerAuth" => []]],
        tags: ["Containers V1"],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: "container_id", type: "string", example: "WH12345"),
                    new OA\Property(property: "waste_type", type: "string", example: "Plastic"),
                    new OA\Property(property: "weight_kg", type: "number", example: 200)
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: "Data berhasil disimpan"),
            new OA\Response(response: 401, description: "Unauthorized"),
            new OA\Response(response: 403, description: "Forbidden - Hanya untuk Admin"),
            new OA\Response(response: 422, description: "Validasi gagal")
        ]
    )]
    public function store(Request $request)
    {
        $request->validate([
            'container_id' => ['required', 'string', 'regex:/^[A-Za-z]{2}[0-9]{5}$/', 'unique:containers,container_id'],
            'waste_type'   => 'required|string',
            'weight_kg'    => 'required|numeric|min:10|max:5000',
        ]);

        if ($request->waste_type === 'Chemical' && $request->weight_kg > 1000) {
            return response()->json([
                'message' => 'Validasi gagal',
                'errors'  => ['weight_kg' => ['Untuk limbah Chemical, berat maksimal adalah 1000 kg.']]
            ], 422);
        }

        $container = Container::create([
            'container_id' => strtoupper($request->container_id),
            'waste_type'   => $request->waste_type,
            'weight_kg'    => (float) $request->weight_kg,
            'status'       => 'Active',
        ]);

        TrackingLog::create([
            'container_id' => $container->container_id,
            'location'     => 'Titik Awal',
            'description'  => 'Kontainer didaftarkan'
        ]);

        $container->load('trackingLogs');

        return response()->json(['message' => 'Data berhasil disimpan', 'data' => $container], 201);
    }

    #[OA\Patch(
        path: "/api/v1/gateway/containers/{id}/archive",
        summary: "Arsipkan kontainer (Admin only)",
        security: [["bearerAuth" => []]],
        tags: ["Containers V1"],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))
        ],
        responses: [
            new OA\Response(response: 200, description: "Status diubah menjadi Archived"),
            new OA\Response(response: 401, description: "Unauthorized"),
            new OA\Response(response: 403, description: "Forbidden - Hanya untuk Admin"),
            new OA\Response(response: 404, description: "Data tidak ditemukan")
        ]
    )]
    public function archive($id)
    {
        $container = Container::find($id);

        if (!$container) {
            return response()->json(['message' => 'Data tidak ditemukan'], 404);
        }

        $container->update(['status' => 'Archived']);

        return response()->json(['message' => 'Status diubah menjadi Archived'], 200);
    }

    #[OA\Delete(
        path: "/api/v1/gateway/containers/{id}",
        summary: "Hapus kontainer (Admin only)",
        security: [["bearerAuth" => []]],
        tags: ["Containers V1"],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))
        ],
        responses: [
            new OA\Response(response: 200, description: "Data berhasil dihapus"),
            new OA\Response(response: 401, description: "Unauthorized"),
            new OA\Response(response: 403, description: "Forbidden - Hanya untuk Admin"),
            new OA\Response(response: 404, description: "Data tidak ditemukan")
        ]
    )]
    public function destroy($id)
    {
        $container = Container::find($id);

        if (!$container) {
            return response()->json(['message' => 'Data tidak ditemukan'], 404);
        }

        $container->delete();

        return response()->json(['message' => 'Data berhasil dihapus'], 200);
    }

    #[OA\Get(
        path: "/api/v1/gateway/containers/search",
        summary: "Cari kontainer berdasarkan filter",
        security: [["bearerAuth" => []]],
        tags: ["Containers V1"],
        parameters: [
            new OA\Parameter(name: "type", in: "query", required: false, schema: new OA\Schema(type: "string", example: "Plastic")),
            new OA\Parameter(name: "min_weight", in: "query", required: false, schema: new OA\Schema(type: "number", example: 100))
        ],
        responses: [
            new OA\Response(response: 200, description: "Hasil pencarian kontainer"),
            new OA\Response(response: 401, description: "Unauthorized")
        ]
    )]
    public function search(Request $request)
    {
        $query = Container::with('trackingLogs');

        if ($request->has('type')) {
            $query->where('waste_type', $request->type);
        }

        if ($request->has('min_weight')) {
            $query->where('weight_kg', '>=', $request->min_weight);
        }

        return response()->json($query->get(), 200);
    }

    #[OA\Get(
        path: "/api/v1/gateway/containers/{id}/logs",
        summary: "Ambil tracking logs kontainer",
        security: [["bearerAuth" => []]],
        tags: ["Containers V1"],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))
        ],
        responses: [
            new OA\Response(response: 200, description: "Data logs berhasil diambil"),
            new OA\Response(response: 401, description: "Unauthorized"),
            new OA\Response(response: 404, description: "Data tidak ditemukan")
        ]
    )]
    public function logs($id)
    {
        $container = Container::with('trackingLogs')->find($id);

        if (!$container) {
            return response()->json(['message' => 'Data tidak ditemukan'], 404);
        }

        return response()->json($container->trackingLogs, 200);
    }
}