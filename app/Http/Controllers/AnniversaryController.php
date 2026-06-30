<?php

namespace App\Http\Controllers;

use App\Models\Anniversary;
use Illuminate\Http\Request;

class AnniversaryController extends Controller
{
    public function index()
    {
        $anniversaries = Anniversary::orderBy('date')->get();
        return view('anniversaries.index', compact('anniversaries'));
    }

    public function create()
    {
        return view('anniversaries.form', ['anniversary' => null]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'date' => 'required|date',
            'note' => 'nullable|string|max:2000',
        ]);

        Anniversary::create($validated);
        return redirect()->route('anniversaries.index')->with('success', '纪念日已添加 💕');
    }

    public function edit(Anniversary $anniversary)
    {
        return view('anniversaries.form', compact('anniversary'));
    }

    public function update(Request $request, Anniversary $anniversary)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'date' => 'required|date',
            'note' => 'nullable|string|max:2000',
        ]);

        $anniversary->update($validated);
        return redirect()->route('anniversaries.index')->with('success', '纪念日已更新 💕');
    }

    public function destroy(Anniversary $anniversary)
    {
        $anniversary->delete();
        return redirect()->route('anniversaries.index')->with('success', '纪念日已删除');
    }
}
