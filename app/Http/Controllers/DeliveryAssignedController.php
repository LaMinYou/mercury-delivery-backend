<?php

namespace App\Http\Controllers;

use App\Models\OrderRiderOffer;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class DeliveryAssignedController extends Controller
{
    public function index(Request $request)
    {
        // 1. Base Query
        $query = OrderRiderOffer::query();

        // 2. Apply Search Filters
        if ($request->filled('name')) {
            $query->whereHas('rider', function ($q) use ($request) {
                $q->where('name', 'LIKE', '%' . $request->name . '%');
            });
        }
        if ($request->filled('status')) {
            if ($request->status != 'all')
                $query->where('status', $request->status);
        }
        if ($request->filled('dateFilter')) {
            if ($request->dateFilter != 'all') {
                switch ($request->dateFilter) {
                    case 'week':
                        $query->where('updated_at', '>=', Carbon::now()->startOfWeek());
                        break;
                    case 'month':
                        $query->where('updated_at', '>=', Carbon::now()->startOfMonth());
                        break;
                    case 'year':
                        $query->where('updated_at', '>=', Carbon::now()->startOfYear());
                        break;
                }
            }
        }

        // 3. Handle Sorting (The fix for the arrows)
        if ($request->has('sortBy') && is_array($request->sortBy)) {
            foreach ($request->sortBy as $sort) {
                $query->orderBy($sort['key'], $sort['order']);
            }
        } else {
            $query->latest(); // Default sort if no arrow is clicked
        }

        // 4. Pagination
        $perPage = $request->input('itemsPerPage', 5);
        $assigns = $query->paginate($perPage);

        return response()->json([
            'items' => $assigns->items(),
            'total' => $assigns->total(),
        ]);
    }

    public function destroy(OrderRiderOffer $orderRiderOffer)
    {
        try {
            $orderRiderOffer->delete();
            return response()->json(['message', 'deliver assigned deleted']);
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return response()->json(['message', 'something went wrong']);
        }
    }
}
