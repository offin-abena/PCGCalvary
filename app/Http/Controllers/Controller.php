<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Routing\Controller as BaseController;

abstract class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    protected function render(string $view, array $data = []): void
    {
        \App\Core\View::render($view, $data);
    }

    protected function json(array $data, int $status = 200): void
    {
        \App\Core\View::json($data, $status);
    }

    protected function redirect(string $path): void
    {
        throw new HttpResponseException(redirect(church_url($path)));
    }

    protected function input(string $key, $default = null)
    {
        return request()->input($key, $default);
    }

    protected function validate(array $rules): array
    {
        $errors = [];

        foreach ($rules as $field => $rule) {
            $value = $this->input($field);
            $ruleList = explode('|', $rule);

            foreach ($ruleList as $r) {
                if ($r === 'required' && ($value === null || $value === '')) {
                    $errors[$field] = ucfirst(str_replace('_', ' ', $field)) . ' is required.';
                    break;
                }
                if (str_starts_with($r, 'min:') && strlen((string) $value) < (int) substr($r, 4)) {
                    $errors[$field] = ucfirst(str_replace('_', ' ', $field)) . ' is too short.';
                }
                if ($r === 'email' && $value && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    $errors[$field] = 'Invalid email address.';
                }
            }
        }

        return $errors;
    }

    protected function flash(string $type, string $message): void
    {
        session(['flash' => ['type' => $type, 'message' => $message]]);
    }

    protected function uploadFile(string $field, string $subdir = 'general', ?array $allowedTypes = null): ?string
    {
        $file = request()->file($field);
        if (!$file || !$file->isValid()) {
            return null;
        }

        $config = config('church');
        $maxSize = (int) ($config['max_upload_size'] ?? 5 * 1024 * 1024);

        if ($file->getSize() > $maxSize) {
            $this->flash('danger', 'Uploaded file is too large.');

            return null;
        }

        $allowedTypes = $allowedTypes ?? array_merge(
            $config['allowed_image_types'] ?? [],
            $config['allowed_doc_types'] ?? []
        );

        $mime = $file->getMimeType() ?: '';
        if (!in_array($mime, $allowedTypes, true)) {
            $this->flash('danger', 'File type is not allowed.');

            return null;
        }

        $safeExtensions = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
            'application/pdf' => 'pdf',
            'application/msword' => 'doc',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
        ];
        $ext = $safeExtensions[$mime] ?? strtolower($file->getClientOriginalExtension());
        if (!in_array($ext, array_values($safeExtensions), true)) {
            $this->flash('danger', 'File extension is not allowed.');

            return null;
        }

        $uploadDir = rtrim($config['upload_path'], '/') . '/' . $subdir . '/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $filename = bin2hex(random_bytes(16)) . '.' . $ext;
        $file->move($uploadDir, $filename);

        return $subdir . '/' . $filename;
    }
}
