<?php

namespace EzeeTools\Interfaces;

interface ProcessInterface
{
    public function __invoke(): ProcessInterface;

    public function getStatus(): string;

    public function setStatus(string $status): ProcessInterface;

    public function addError(string $error): ProcessInterface;

    public function addErrors(array $errors): ProcessInterface;

    public function clearErrors(): ProcessInterface;

    public function getErrors(): array;

    public function getFirstError(): ?string;

    public function addWarning(string $warning): ProcessInterface;

    public function addWarnings(array $warnings): ProcessInterface;

    public function clearWarnings(): ProcessInterface;

    public function addInfo(string $info): ProcessInterface;

    public function addInfos(array $infos): ProcessInterface;

    public function clearInfos(): ProcessInterface;

    public function setFailed(): ProcessInterface;

    public function fail(string $error): ProcessInterface;

    public function setCompleted(): ProcessInterface;

    public function complete(string $info = null): ProcessInterface;

    public function completed(): bool;

    public function started(): bool;

    public static function run(...$args): ProcessInterface;
}
