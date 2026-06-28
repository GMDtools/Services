<?php

namespace App\GeometryDashProxy\Exceptions;

use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Response;
use Throwable;

class GeometryDashProxyException extends Exception
{
    protected array $data = [];

    public function __construct(
        string $message = '',
        int $code = 0,
        ?Throwable $previous = null
    ) {
        parent::__construct('[GeometryDashProxy] '.$message, $code, $previous);
    }

    public function withData(array $data): static
    {
        $this->data = $data;

        return $this;
    }

    public function report(): void
    {
        Log::error($this->getMessage(), $this->data);
    }

    public function render()
    {
        return Response::make(status: 500);
    }
}
