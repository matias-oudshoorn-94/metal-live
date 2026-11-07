<?php

declare(strict_types=1);

namespace App\Core;

abstract class Controller
{
    public function __construct(protected readonly Request $request)
    {
    }

    protected function render(string $template, array $data = [], string $layout = 'admin'): void
    {
        echo View::render($template, $data, $layout);
    }

    protected function redirect(string $to, ?string $success = null): never
    {
        if ($success !== null) {
            Session::flash('success', $success);
        }
        Response::redirect($to);
    }

    protected function back(?string $error = null, ?string $success = null): never
    {
        if ($error !== null) {
            Session::flash('error', $error);
        }
        if ($success !== null) {
            Session::flash('success', $success);
        }
        Response::back();
    }

    /** Valida y, si falla, vuelve al formulario con errores y datos previos. */
    protected function validate(array $rules, array $labels = [], ?array $input = null): array
    {
        $input ??= $_POST;
        [$data, $errors] = Validator::make($input, $rules, $labels);
        if ($errors) {
            $old = $input;
            unset($old['_token'], $old['password'], $old['password_confirmation']);
            Session::flash('_errors', $errors);
            Session::flash('_old_input', $old);
            Session::flash('error', 'Revisá los campos marcados.');
            Response::back();
        }
        return $data;
    }

    protected function authorize(string ...$permissions): void
    {
        if (!Auth::canAny(...$permissions)) {
            throw new HttpException(403);
        }
    }

    protected function page(): int
    {
        return max(1, (int) $this->request->query('page', 1));
    }
}
