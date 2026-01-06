<?php

namespace App\Http\Controllers;

use App\Models\PlanTemplate;
use Illuminate\Http\Request;


class AdminTemplateController extends Controller
{
    public function index()
    {
        $templates = PlanTemplate::orderBy('type')->orderBy('title')->get();

        return view('admin.templates.index', compact('templates'));
    }

    public function create()
    {
        return view('admin.templates.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'type'        => ['required','in:workout,nutrition'],
            'title'       => ['required','string','max:255'],
            'description' => ['nullable','string'],
            'plan_details'=> ['nullable','string'],
            'is_active'   => ['nullable','boolean'],
        ]);

        $data['is_active'] = $request->has('is_active');

        PlanTemplate::create($data);

        return redirect()
            ->route('admin.templates.index')
            ->with('status','Template created successfully.');
    }

    public function edit($id)
    {
        $template = PlanTemplate::findOrFail($id);
        return view('admin.templates.edit', compact('template'));
    }

    public function update(Request $request, $id)
    {
        $template = PlanTemplate::findOrFail($id);

        $data = $request->validate([
            'type'        => ['required','in:workout,nutrition'],
            'title'       => ['required','string','max:255'],
            'description' => ['nullable','string'],
            'plan_details'=> ['nullable','string'],
            'is_active'   => ['nullable','boolean'],
        ]);

        $data['is_active'] = $request->has('is_active');

        $template->update($data);

        return redirect()
            ->route('admin.templates.index')
            ->with('status','Template updated successfully.');
    }

    public function destroy($id)
    {
        $template = PlanTemplate::findOrFail($id);
        $template->delete();

        return redirect()
            ->route('admin.templates.index')
            ->with('status','Template deleted.');
    }
} 