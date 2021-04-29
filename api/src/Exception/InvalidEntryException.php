<?php
// src/Exception/InvalidEntryException.php

namespace App\Exception;

use Symfony\Component\HttpFoundation\Exception\RequestExceptionInterface;

final class InvalidEntryException extends \Exception implements RequestExceptionInterface
{
    protected $code = 400;
}
