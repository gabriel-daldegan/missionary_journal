<?php

namespace App\View\Components\Products;

use App\Services\OneTimeProductService;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class All extends Component
{
    public function __construct(
        private OneTimeProductService $productService,
        private string $sortBy = 'name',
        private string $sortDirection = 'asc',
    ) {}

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.products.all', $this->calculateViewData());
    }

    protected function calculateViewData()
    {
        return [
            'products' => $this->productService->getAllProductsWithPrices($this->sortBy, $this->sortDirection, true),
        ];
    }
}
