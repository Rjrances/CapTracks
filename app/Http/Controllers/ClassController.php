<?php

namespace App\Http\Controllers;

use App\Models\ClassModel;
use Illuminate\Http\Request;

class ClassController extends Controller
{
    public function create()
    {
        return view('classes.create');
    }

    public function store(Request $request)
{
    $validated = $request->validate([
        'name' => 'required|string|max:255',
    ]);

    ClassModel::create($validated);

    return redirect()->route('classes.index')->with('success', 'Class created successfully!');
}
    public function index()
{
    $classes = ClassModel::all();

    return view('classes.index', compact('classes'));
}
}
