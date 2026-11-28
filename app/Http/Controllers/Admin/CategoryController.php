<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CategoryRequest;
use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class CategoryController extends Controller
{
    public function index(): Response
    {
        $categories = Category::query()
            ->withCount('products')
            ->orderBy('name')
            ->get()
            ->map(fn (Category $category): array => [
                'id' => $category->id,
                'name' => $category->name,
                'books' => (int) $category->products_count,
                'edit_url' => route('admin.categories.edit', $category),
            ])
            ->values();

        return Inertia::render('admin/categories/Index', [
            'categories' => $categories,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/categories/Form', [
            'category' => null,
        ]);
    }

    public function store(CategoryRequest $request): RedirectResponse
    {
        $category = new Category;
        $category->name = $request->string('name')->toString();
        $category->save();

        return redirect()
            ->route('admin.categories.edit', $category)
            ->with('success', 'Categoria cadastrada.');
    }

    public function edit(Category $category): Response
    {
        return Inertia::render('admin/categories/Form', [
            'category' => [
                'id' => $category->id,
                'name' => $category->name,
                'books' => $category->products()->count(),
            ],
        ]);
    }

    public function update(CategoryRequest $request, Category $category): RedirectResponse
    {
        $category->name = $request->string('name')->toString();
        $category->save();

        return redirect()
            ->route('admin.categories.edit', $category)
            ->with('success', 'Categoria atualizada.');
    }

    public function destroy(Category $category): RedirectResponse
    {
        $this->authorize('delete', $category);

        if ($category->products()->exists()) {
            return back()->with('error', 'Esta categoria tem livros. Mova ou apague os livros antes de apagá-la.');
        }

        $category->delete();

        return redirect()
            ->route('admin.categories')
            ->with('success', 'Categoria apagada.');
    }
}
