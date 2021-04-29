<?php
// src/Exception/TooManyPackagesException.php

namespace App\Exception;

use Symfony\Component\HttpFoundation\Exception\RequestExceptionInterface;

final class TooManyPackagesException extends \Exception implements RequestExceptionInterface
{
    protected $code = 400;
}
