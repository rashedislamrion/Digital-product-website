<?php

namespace App\View\Components;

use App\Models\Category;
use Illuminate\Support\Collection;
use Illuminate\View\Component;
use Illuminate\View\View;

class StorefrontLayout extends Component
{
    public function __construct(
        public ?Collection $categories = null,
        public ?string $title = null,
        public ?string $metaDescription = null,
    ) {
        if (! $this->categories || $this->categories->isEmpty()) {
            $this->categories = Category::visible()
                ->whereNull('parent_id')
                ->with(['children' => fn ($q) => $q->visible()->withCount('publishedProducts')])
                ->withCount('publishedProducts')
                ->get();
        }
    }

    /**
     * Get the view / contents that represents the component.
     */
    public function render(): View
    {
        return view('layouts.storefront');
    }
}
