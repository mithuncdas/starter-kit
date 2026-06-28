<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AuditLog\IndexAuditLogRequest;
use App\Http\Resources\Admin\AuditLogResource;
use App\Http\Responses\ApiResponder;
use Chronicle\Entry\Entry;
use Illuminate\Http\JsonResponse;

class AuditLogController extends Controller
{
    use ApiResponder;

    public function index(IndexAuditLogRequest $request): JsonResponse
    {
        $filters = $request->validated();

        $entries = Entry::query()
            ->when($filters['actor_type'] ?? null, fn ($q) => $q
                ->where('actor_type', $filters['actor_type'])
                ->where('actor_id', $filters['actor_id']))
            ->when($filters['subject_type'] ?? null, fn ($q) => $q
                ->where('subject_type', $filters['subject_type'])
                ->where('subject_id', $filters['subject_id']))
            ->orderByDesc('created_at')
            ->paginate($request->integer('per_page', 25))
            ->toResourceCollection(AuditLogResource::class);

        return $this->success(data: $entries, message: 'Audit logs fetched.');
    }

    public function show(string $entry): JsonResponse
    {
        $row = Entry::query()->findOrFail($entry);

        return $this->success(
            data: AuditLogResource::make($row),
            message: 'Audit log fetched.',
        );
    }
}
