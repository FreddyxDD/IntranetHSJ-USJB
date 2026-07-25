<?php

namespace App\Http\Controllers\Egresos;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

final class AuditoriaController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:150'],
            'event_type' => ['nullable', 'string', 'max:120'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);

        $text = trim((string) ($validated['q'] ?? ''));
        $query = DB::table('auditoria.eventos')
            ->where('module', 'egresos')
            ->orderByDesc('occurred_at')
            ->orderByDesc('id');

        $query
            ->when($validated['event_type'] ?? null, fn ($builder, $type) => $builder->where('event_type', $type))
            ->when($validated['date_from'] ?? null, fn ($builder, $date) => $builder->whereDate('occurred_at', '>=', $date))
            ->when($validated['date_to'] ?? null, fn ($builder, $date) => $builder->whereDate('occurred_at', '<=', $date));

        if ($text !== '') {
            $escaped = str_replace(['[', '%', '_'], ['[[]', '[%]', '[_]'], $text);
            $query->where(function ($builder) use ($escaped): void {
                $like = '%'.$escaped.'%';
                $builder->where('actor_username', 'like', $like)
                    ->orWhere('actor_display_name', 'like', $like)
                    ->orWhere('subject_id', 'like', $like)
                    ->orWhere('event_type', 'like', $like);
            });
        }

        $page = $query->paginate(20);

        return response()->json([
            'ok' => true,
            'data' => collect($page->items())->map(fn (object $event): array => [
                'id' => (int) $event->id,
                'event_uuid' => $event->event_uuid,
                'event_type' => $event->event_type,
                'action' => $event->action,
                'subject_type' => $event->subject_type,
                'subject_id' => $event->subject_id,
                'actor_username' => $event->actor_username,
                'actor_display_name' => $event->actor_display_name,
                'ip' => $event->ip,
                'data_before' => $this->json($event->data_before),
                'data_after' => $this->json($event->data_after),
                'occurred_at' => $event->occurred_at,
            ]),
            'meta' => [
                'current_page' => $page->currentPage(),
                'last_page' => $page->lastPage(),
                'total' => $page->total(),
            ],
        ]);
    }

    private function json(?string $value): ?array
    {
        if (! $value) {
            return null;
        }

        $decoded = json_decode($value, true);

        return is_array($decoded) ? $decoded : null;
    }
}
