<?php
namespace App\Shared\Infrastructure\FileUpload;

use App\Shared\Infrastructure\Error\ErrorHandler;

class FileUploader
{
    private string $uploadDir;
    private array $allowedTypes;
    private int $maxSize;
    private ErrorHandler $errorHandler;

    public function __construct(
        string $uploadDir,
        array $allowedTypes,
        int $maxSize,
        ErrorHandler $errorHandler
    ) {
        $this->uploadDir = rtrim($uploadDir, '/');
        $this->allowedTypes = $allowedTypes;
        $this->maxSize = $maxSize;
        $this->errorHandler = $errorHandler;
    }

    public function upload(array $file, string $directory = ''): UploadedFile
    {
        $this->validateFile($file);

        $fileName = $this->generateFileName($file);
        $directory = trim($directory, '/');
        $targetPath = $this->uploadDir . '/' . $directory;

        if (!is_dir($targetPath)) {
            mkdir($targetPath, 0755, true);
        }

        $filePath = $targetPath . '/' . $fileName;

        if (!move_uploaded_file($file['tmp_name'], $filePath)) {
            throw new FileUploadException('Failed to move uploaded file');
        }

        return new UploadedFile(
            $fileName,
            $directory,
            $file['type'],
            $file['size']
        );
    }

    public function delete(string $path): bool
    {
        $fullPath = $this->uploadDir . '/' . ltrim($path, '/');
        
        if (file_exists($fullPath) && unlink($fullPath)) {
            return true;
        }

        $this->errorHandler->logError('Failed to delete file', [
            'path' => $path
        ]);

        return false;
    }

    private function validateFile(array $file): void
    {
        if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            throw new FileUploadException('Invalid upload');
        }

        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new FileUploadException(
                $this->getUploadErrorMessage($file['error'])
            );
        }

        if ($file['size'] > $this->maxSize) {
            throw new FileUploadException('File is too large');
        }

        $type = mime_content_type($file['tmp_name']);
        if (!in_array($type, $this->allowedTypes)) {
            throw new FileUploadException('File type not allowed');
        }
    }

    private function generateFileName(array $file): string
    {
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        return uniqid() . '_' . time() . '.' . $extension;
    }
} 