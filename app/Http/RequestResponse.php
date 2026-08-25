<?php

namespace App\Http;

use Illuminate\Http\JsonResponse;
use Illuminate\Contracts\Support\Responsable;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class RequestResponse implements Responsable
{
    protected int $httpCode;
    protected mixed $data;
    protected string $message;
    protected bool $isDownload;

    public function __construct(int $httpCode, mixed $data = [], string $message = '', bool $isDownload = false)
    {
        if (!in_array($httpCode, range(100, 599))) {
            throw new \RuntimeException("$httpCode is not a valid HTTP status code");
        }

        $this->httpCode = $httpCode;
        $this->data = $data;
        $this->message = $message;
        $this->isDownload = $isDownload;
    }

    public function toResponse($request): mixed
    {
        if ($this->isDownload) {
            return $this->data;
        }

        $payload = match (true) {
            $this->httpCode >= 500 => ['message' => 'Server error'],
            $this->httpCode >= 400 => ['message' => $this->message, 'errors' => $this->data ?: null],
            $this->httpCode >= 200 => ['message' => $this->message, 'data' => $this->formatData($this->data)],
            default => ['message' => 'Unhandled status code']
        };

        return response()->json(
            data: $payload,
            status: $this->httpCode,
            options: JSON_UNESCAPED_UNICODE
        );
    }

    protected function formatData(mixed $data): mixed
    {
        if ($data instanceof \Illuminate\Http\Resources\Json\JsonResource) {
            return $data->toArray(request());
        }

        if ($data instanceof \Illuminate\Support\Collection) {
            return $data->toArray();
        }

        return $data;
    }

    public static function ok(string $message, mixed $data = []): static
    {
        return new static(200, $data, $message);
    }

    public static function created(string $message, mixed $data = []): static
    {
        return new static(201, $data, $message);
    }

    public static function badRequest(string $message, mixed $data = []): static
    {
        return new static(400, $data, $message);
    }

    public static function unauthorized(string $message = 'Unauthorized'): static
    {
        return new static(401, [], $message);
    }

    public static function forbidden(string $message = 'Forbidden'): static
    {
        return new static(403, [], $message);
    }

    public static function notFound(string $message = 'Item not found'): static
    {
        return new static(404, [], $message);
    }

    public static function internalServerError(string $message = 'Internal server error'): static
    {
        return new static(500, [], $message);
    }

    public static function download(string $filePath, string $filename): static
    {
        $response = new BinaryFileResponse($filePath);
        $response->setContentDisposition('attachment', $filename);
        return new static(200, $response, '', true);
    }
}
