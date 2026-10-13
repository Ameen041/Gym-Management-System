<?php

namespace App\Http\Controllers;

use App\Models\Food;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Food library used by coaches in the nutrition-plan calculator.
 */
class AdminFoodController extends Controller
{
    public function index(Request $request)
    {
        $category = $request->query('category');
        $search = trim((string) $request->query('q'));

        $foods = Food::query()
            ->when($category, fn ($q) => $q->where('category', $category))
            ->when($search !== '', fn ($q) => $q->where(fn ($q) => $q
                ->where('name', 'like', "%{$search}%")->orWhere('name_ar', 'like', "%{$search}%")))
            ->orderBy('name')
            ->get()
            ->sortBy(fn ($f) => array_search($f->category, array_keys(Food::CATEGORIES)))
            ->values();

        return view('admin.foods.index', compact('foods', 'category', 'search'));
    }

    public function create()
    {
        return view('admin.foods.form', ['food' => new Food(['is_active' => true, 'category' => 'other'])]);
    }

    public function store(Request $request)
    {
        Food::create($this->validated($request) + ['created_by' => $request->user()->id]);

        return redirect()->route('admin.foods.index')->with('status', __('Food saved.'));
    }

    public function edit(Food $food)
    {
        return view('admin.foods.form', compact('food'));
    }

    public function update(Request $request, Food $food)
    {
        $food->update($this->validated($request));

        return redirect()->route('admin.foods.index')->with('status', __('Food saved.'));
    }

    public function destroy(Food $food)
    {
        // Plans keep their own copy of the values, so deleting a food never changes a sent plan
        $food->delete();

        return redirect()->route('admin.foods.index')->with('status', __('Food deleted.'));
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'name'            => ['required', 'string', 'max:255'],
            'name_ar'         => ['nullable', 'string', 'max:255'],
            'category'        => ['required', Rule::in(array_keys(Food::CATEGORIES))],
            'calories'        => ['required', 'numeric', 'min:0', 'max:900'],
            'protein'         => ['required', 'numeric', 'min:0', 'max:100'],
            'carbs'           => ['required', 'numeric', 'min:0', 'max:100'],
            'fat'             => ['required', 'numeric', 'min:0', 'max:100'],
            'fiber'           => ['nullable', 'numeric', 'min:0', 'max:100'],
            'serving_grams'   => ['nullable', 'numeric', 'min:0.1', 'max:2000', 'required_with:serving_name'],
            'serving_name'    => ['nullable', 'string', 'max:100', 'required_with:serving_grams'],
            'serving_name_ar' => ['nullable', 'string', 'max:100'],
            'is_active'       => ['nullable', 'boolean'],
        ]);
        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }
}
