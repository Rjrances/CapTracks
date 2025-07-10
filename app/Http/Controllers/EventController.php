<?php

namespace App\Http\Controllers;

use App\Models\Event; // create Event model if needed
use Illuminate\Http\Request;

class EventController extends Controller
{
    public function show($id)
    {
        $event = Event::findOrFail($id);
        return view('events.show', compact('event'));
    }
}
