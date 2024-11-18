<?php

namespace App\Catalog\Application;

use Jenssegers\Blade\Blade;

class SearchController
{
    private SearchService $searchService;
    private Blade $blade;

    public function __construct(SearchService $searchService, Blade $blade)
    {
        $this->searchService = $searchService;
        $this->blade = $blade;
    }

    public function index(): void
    {
        $params = $this->getSearchParams();
        $results = $this->searchService->search($params);
        $filters = $this->searchService->getFilterOptions();

        echo $this->blade->make('catalog.search', [
            'results' => $results,
            'filters' => $filters,
            'params' => $params
        ])->render();
    }

    private function getSearchParams(): array
    {
        return [
            'q' => filter_input(INPUT_GET, 'q', FILTER_SANITIZE_STRING),
            'category' => filter_input(INPUT_GET, 'category', FILTER_VALIDATE_INT),
            'price_min' => filter_input(INPUT_GET, 'price_min', FILTER_VALIDATE_FLOAT),
            'price_max' => filter_input(INPUT_GET, 'price_max', FILTER_VALIDATE_FLOAT),
            'in_stock' => filter_input(INPUT_GET, 'in_stock', FILTER_VALIDATE_BOOLEAN),
            'featured' => filter_input(INPUT_GET, 'featured', FILTER_VALIDATE_BOOLEAN),
            'sort' => filter_input(INPUT_GET, 'sort', FILTER_SANITIZE_STRING),
            'order' => filter_input(INPUT_GET, 'order', FILTER_SANITIZE_STRING),
            'page' => filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT),
            'per_page' => filter_input(INPUT_GET, 'per_page', FILTER_VALIDATE_INT)
        ];
    }
} 