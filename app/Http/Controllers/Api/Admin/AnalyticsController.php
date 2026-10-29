<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Complaint;
use App\Models\ComplaintCategory;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AnalyticsController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);

        $to = isset($validated['to']) ? CarbonImmutable::parse($validated['to'])->endOfDay() : CarbonImmutable::now()->endOfDay();
        $from = isset($validated['from']) ? CarbonImmutable::parse($validated['from'])->startOfDay() : $to->subDays(29)->startOfDay();

        $inRange = fn () => Complaint::query()->whereBetween('created_at', [$from, $to]);

        $statusTotals = $inRange()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $categoryTotals = $inRange()
            ->selectRaw('complaint_category_id, count(*) as total')
            ->groupBy('complaint_category_id')
            ->pluck('total', 'complaint_category_id');
        $categoryNames = ComplaintCategory::query()->whereIn('id', $categoryTotals->keys())->pluck('name', 'id');

        $monthly = $from->diffInDays($to) > 92;
        $trendTotals = [];
        $inRange()->pluck('created_at')->each(function ($createdAt) use (&$trendTotals, $monthly): void {
            $date = CarbonImmutable::parse($createdAt);
            $key = $monthly ? $date->format('Y-m') : $date->toDateString();
            $trendTotals[$key] = ($trendTotals[$key] ?? 0) + 1;
        });

        $trend = [];
        $cursor = $monthly ? $from->startOfMonth() : $from;
        while ($cursor <= $to) {
            $key = $monthly ? $cursor->format('Y-m') : $cursor->toDateString();
            $trend[] = [
                'key' => $key,
                'label' => $monthly ? $cursor->format('M Y') : $cursor->format('j M'),
                'total' => $trendTotals[$key] ?? 0,
            ];
            $cursor = $monthly ? $cursor->addMonth() : $cursor->addDay();
        }

        $resolved = $inRange()->whereNotNull('resolved_at')->get(['created_at', 'resolved_at']);
        $averageHours = $resolved->isEmpty()
            ? null
            : round($resolved->avg(fn ($complaint) => $complaint->created_at->diffInMinutes($complaint->resolved_at)) / 60, 1);

        $total = (int) $statusTotals->sum();

        return response()->json([
            'range' => ['from' => $from->toDateString(), 'to' => $to->toDateString(), 'bucket' => $monthly ? 'month' : 'day'],
            'totals' => [
                'total' => $total,
                'pending' => (int) ($statusTotals[Complaint::STATUS_PENDING] ?? 0),
                'in_progress' => (int) ($statusTotals[Complaint::STATUS_IN_PROGRESS] ?? 0),
                'resolved' => (int) ($statusTotals[Complaint::STATUS_RESOLVED] ?? 0),
                'rejected' => (int) ($statusTotals[Complaint::STATUS_REJECTED] ?? 0),
            ],
            'resolution_rate' => $total > 0 ? round((int) ($statusTotals[Complaint::STATUS_RESOLVED] ?? 0) / $total * 100, 1) : null,
            'average_resolution_hours' => $averageHours,
            'trend' => $trend,
            'category_breakdown' => $categoryTotals
                ->map(fn ($count, $categoryId) => [
                    'category_id' => $categoryId,
                    'name' => $categoryNames[$categoryId] ?? 'Uncategorized',
                    'total' => (int) $count,
                ])
                ->sortByDesc('total')
                ->values()
                ->all(),
        ]);
    }
}
