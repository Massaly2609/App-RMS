<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FifoQueueEntry;
use App\Models\EligibleNextGain;
use App\Models\Rotation;
use App\Models\User;
use Illuminate\Http\Request;

class AdminController extends Controller
{


    public function stats()
    {
        return response()->json([
            'users_count' => User::count(),
            'queue_active' => FifoQueueEntry::where('active', true)->count(),
            'eligible_count' => EligibleNextGain::where('processed', false)->count(),
            'total_rotations_amount' => Rotation::sum('amount'),
        ]);
    }

    public function usersCount()
    {
        return response()->json(['count' => User::count()]);
    }

    public function queueStats()
    {
        return response()->json([
            'active' => FifoQueueEntry::where('active', true)->count(),
        ]);
    }
}
