<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\BookRequest;
use App\Models\Category;
use App\Models\Product;
use App\Support\BookCover;
use App\Support\Money;
use App\Support\ProductRules;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class BookController extends Controller
{
    public function index(Request $request): Response
    {
        $search = mb_substr(trim($request->string('q')->toString()), 0, 80);
        $books = Product::query()->with('category:id,name');

        if ($search !== '') {
            $like = '%'.addcslashes($search, '%_\\').'%';
            $books->whereRaw('title like ? escape ?', [$like, '\\']);
        }

        return Inertia::render('admin/books/Index', [
            'search' => $search,
            'books' => $books->orderBy('title')->get()->map(fn (Product $product): array => [
                'id' => $product->id,
                'title' => $product->title,
                'author' => $product->author,
                'category' => $product->category->name,
                'price' => Money::format($product->price_cents),
                'is_published' => $product->is_published,
                'edit_url' => route('admin.books.edit', $product),
            ])->values(),
        ]);
    }

    public function create(): Response
    {
        return $this->form(null);
    }

    public function store(BookRequest $request): RedirectResponse
    {
        $product = new Product;
        $this->fill($product, $request);
        $this->save($product, $request->file('cover'), null);

        return redirect()
            ->route('admin.books.edit', $product)
            ->with('success', 'Livro cadastrado.');
    }

    public function edit(Product $product): Response
    {
        return $this->form($product);
    }

    public function update(BookRequest $request, Product $product): RedirectResponse
    {
        $previous = $product->cover_path;
        $this->fill($product, $request);
        $this->save($product, $request->file('cover'), $previous);

        return redirect()
            ->route('admin.books.edit', $product)
            ->with('success', 'Livro atualizado.');
    }

    public function destroy(Product $product): RedirectResponse
    {
        if ($product->orderItems()->exists()) {
            return back()->with('error', 'Este livro já foi comprado. Despublique para tirá-lo da vitrine.');
        }

        $cover = $product->cover_path;

        try {
            $product->delete();
        } catch (QueryException) {
            return back()->with('error', 'Este livro já foi comprado. Despublique para tirá-lo da vitrine.');
        }

        BookCover::deleteIfUnused($cover);

        return redirect()
            ->route('admin.books')
            ->with('success', 'Livro apagado.');
    }

    public function unpublish(Product $product): RedirectResponse
    {
        $product->is_published = false;
        $product->save();

        return back()->with('success', 'Livro despublicado.');
    }

    private function form(?Product $product): Response
    {
        return Inertia::render('admin/books/Form', [
            'book' => $product instanceof Product ? $this->payload($product) : null,
            'categories' => Category::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    /**
     * @return array{id: int, category_id: int, title: string, author: string, description: string, content: string, price: string, is_featured: bool, is_published: bool, cover_url: string|null, purchased: bool}
     */
    private function payload(Product $product): array
    {
        $cover = is_string($product->cover_path) ? ProductRules::safeCoverPath($product->cover_path) : null;

        return [
            'id' => $product->id,
            'category_id' => $product->category_id,
            'title' => $product->title,
            'author' => $product->author,
            'description' => $product->description,
            'content' => $product->content,
            'price' => ProductRules::centsToReais($product->price_cents),
            'is_featured' => $product->is_featured,
            'is_published' => $product->is_published,
            'cover_url' => $cover !== null ? '/'.$cover : null,
            'purchased' => $product->orderItems()->exists(),
        ];
    }

    private function fill(Product $product, BookRequest $request): void
    {
        $product->category_id = (int) $request->validated('category_id');
        $product->title = $request->string('title')->toString();
        $product->author = $request->string('author')->toString();
        $product->description = $request->string('description')->toString();
        $product->content = $request->string('content')->toString();
        $product->price_cents = $request->priceCents();
        $product->is_featured = $request->boolean('is_featured');
        $product->is_published = $request->boolean('is_published');
    }

    private function save(Product $product, ?UploadedFile $cover, ?string $previous): void
    {
        $stored = null;

        if ($cover instanceof UploadedFile) {
            $stored = BookCover::store($cover);
            $product->cover_path = $stored;
        }

        try {
            $product->save();
        } catch (Throwable $exception) {
            BookCover::deleteIfUnused($stored);
            throw $exception;
        }

        if ($stored !== null && $previous !== null && $previous !== $stored) {
            BookCover::deleteIfUnused($previous);
        }
    }
}
