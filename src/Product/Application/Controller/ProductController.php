<?php

namespace App\Product\Application\Controller;

use App\Shared\Infrastructure\Http\Response;
use App\Shared\Infrastructure\Middleware\AuthorizationMiddleware;

/**
 * @OA\Info(
 *     title="Product API",
 *     version="1.0.0"
 * )
 */
class ProductController
{
    /**
     * @OA\Post(
     *     path="/api/products",
     *     summary="Create a new product",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name","price"},
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="price", type="number"),
     *             @OA\Property(property="description", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Product created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="id", type="integer"),
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="price", type="number")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Validation error"
     *     )
     * )
     */
    public function create()
    {
        // Check permission using middleware
        $middleware = new AuthorizationMiddleware(
            $this->auth,
            'create_product'
        );

        return $middleware->handle(function () {
            $product = $this->createProductService->execute(
                $this->request->all()
            );
            
            return Response::json([
                'message' => 'Product created successfully',
                'product' => $product->toArray()
            ], 201);
        });
    }
} 