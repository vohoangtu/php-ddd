<?php

namespace App\Catalog\Application;

use App\Catalog\Infrastructure\ProductRepository;
use Jenssegers\Blade\Blade;

class ProductController
{
    private ProductRepository $repository;
    private Blade $blade;

    public function __construct(ProductRepository $repository, Blade $blade)
    {
        $this->repository = $repository;
        $this->blade = $blade;
    }

    public function index()
    {
        $products = $this->repository->findAll();
        return $this->blade->render('products.index', ['products' => $products]);
    }

    public function show(int $id)
    {
        $product = $this->repository->findById($id);
        return $this->blade->render('products.show', ['product' => $product]);
    }
} 