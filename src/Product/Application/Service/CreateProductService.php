<?php

namespace App\Product\Application\Service;

use App\Shared\Application\Service\AbstractService;
use App\Product\Infrastructure\Repository\ProductRepository;
use App\Product\Domain\Event\ProductCreatedEvent;
use App\Shared\Infrastructure\Event\EventDispatcher;
use App\Shared\Infrastructure\Validation\Validator;
use App\Shared\Infrastructure\FileUpload\FileUploader;
use App\Shared\Infrastructure\Notification\NotificationManager;

class CreateProductService extends AbstractService
{
    private ProductRepository $productRepository;
    private EventDispatcher $eventDispatcher;
    private Validator $validator;
    private FileUploader $fileUploader;
    private NotificationManager $notificationManager;

    public function __construct(
        ProductRepository $productRepository,
        EventDispatcher $eventDispatcher,
        Validator $validator,
        FileUploader $fileUploader,
        NotificationManager $notificationManager,
        // ... other dependencies
    ) {
        parent::__construct(/* ... */);
        $this->productRepository = $productRepository;
        $this->eventDispatcher = $eventDispatcher;
        $this->validator = $validator;
        $this->fileUploader = $fileUploader;
        $this->notificationManager = $notificationManager;
    }

    public function execute(array $data = [])
    {
        // Validate input
        $validation = $this->validator->validate($data, [
            'name' => 'required|min:3',
            'price' => 'required|numeric|min:0',
            'image' => 'required|image|max:2048'
        ]);

        if (!$validation->isValid()) {
            throw new ValidationException($validation->getErrors());
        }

        return $this->withTransaction(function () use ($data) {
            // Upload image
            $uploadedFile = $this->fileUploader->upload(
                $data['image'],
                'products'
            );

            // Create product
            $product = $this->productRepository->create([
                'name' => $data['name'],
                'price' => $data['price'],
                'image_path' => $uploadedFile->getPath()
            ]);

            // Send notification
            $this->notificationManager->send(new ProductCreatedNotification(
                $product,
                $this->getCurrentUser()
            ));

            return $product;
        });
    }
} 