<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use Illuminate\Http\Request;

class BookingController extends Controller
{
    public function index()
    {
        $activeBookings = Booking::with('propertyMapping')
            ->where('status', 'Booked')
            ->orderBy('arrival_date', 'asc')
            ->get();

        return view('pages.index', compact('activeBookings'));
    }
}