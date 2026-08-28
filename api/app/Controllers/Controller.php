<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Exceptions\AuthException;
use App\Exceptions\ValidationException;

abstract class Controller
{
    public function callAction(string $method, array $parameters = []): mixed
    {
        try {
            return $this->{$method}(...$parameters);
        } catch (ValidationException $exception) {
            $this->error($exception->getMessage(), 422, $exception->errors());
        } catch (AuthException $exception) {
            $this->error($exception->getMessage(), $exception->status());
        }
    }

    protected function json(mixed $data, int $status = 200): never
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    protected function ok(mixed $data = [], string $message = 'OK'): never
    {
        $this->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ]);
    }

    protected function error(string $message, int $status = 400, mixed $errors = null): never
    {
        $payload = [
            'success' => false,
            'message' => $message,
        ];

        if ($errors !== null) {
            $payload['errors'] = $errors;
        }

        $this->json($payload, $status);
    }
}
