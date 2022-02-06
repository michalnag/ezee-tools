<?php

namespace EzeeTools;

use EzeeTools\Error;
use EzeeTools\Interfaces\ProcessInterface;
use EzeeTools\Helpers\Utils\ArrayUtils;

abstract class BaseProcess implements ProcessInterface
{
    // List all statuses available for the process
    const
        NOT_STARTED             = 'not_started',
        COMPLETED               = 'completed',
        COMPLETED_WITH_WARNINGS = 'completed_with_warnings',
        FAILED                  = 'failed';

    // Array with all statuses supported by a process
    const STATUSES = [
        self::NOT_STARTED,
        self::COMPLETED,
        self::COMPLETED_WITH_WARNINGS,
        self::FAILED
    ];

    /** @var string $status */
    private $status = self::NOT_STARTED;

    /** @var string[] $errors */
    private $errors = [];

    /** @var string[] $warnings */
    private $warnings = [];

    /** @var string[] $infos */
    private $infos = [];

    /**
     * Getter for $status
     *
     * @return string
     */
    public function getStatus(): string
    {
        return $this->status;
    }

    /**
     * Setter for status
     *
     * @param string $status
     * @return ProcessInterface
     */
    public function setStatus(string $status): ProcessInterface
    {
        if (!$this->isStatusSupported($status)) {
            throw new Error\InvalidArgumentError(sprintf('Status %s is not supported by a process.', $status));
        }
        $this->status = $status;
        return $this;
    }

    /**
     * Checks if given status is supported
     *
     * @param string $status
     * @return boolean
     */
    protected function isStatusSupported(string $status): bool
    {
        return \in_array($status, self::STATUSES);
    }

    public function addError(string $error): ProcessInterface
    {
        $this->errors[] = $error;
        return $this;
    }

    public function addErrors(array $errors): ProcessInterface
    {
        foreach ($errors as $error) {
            $this->addError($error);
        }
        return $this;
    }

    public function clearErrors(): ProcessInterface
    {
        $this->errors = [];
        return $this;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function getFirstError(): ?string
    {
        return ArrayUtils::getByKey(0, $this->getErrors());
    }

    public function addWarning(string $warning): ProcessInterface
    {
        $this->warnings[] = $warning;
        return $this;
    }

    public function addWarnings(array $warnings): ProcessInterface
    {
        foreach ($warnings as $warning) {
            $this->addWarning($warning);
        }
        return $this;
    }

    public function clearWarnings(): ProcessInterface
    {
        $this->warnings = [];
        return $this;
    }

    public function addInfo(string $info): ProcessInterface
    {
        $this->infos[] = $info;
        return $this;
    }

    public function addInfos(array $infos): ProcessInterface
    {
        foreach ($infos as $info) {
            $this->addInfo($info);
        }
        return $this;
    }

    public function getInfos(): array
    {
        return $this->infos;
    }

    public function getFirstInfo(): ?string
    {
        return ArrayUtils::getByKey(0, $this->getInfos());
    }

    public function clearInfos(): ProcessInterface
    {
        $this->infos = [];
        return $this;
    }

    public function setFailed(): ProcessInterface
    {
        $this->setStatus(self::FAILED);
        return $this;
    }

    public function fail(string $error): ProcessInterface
    {
        $this->addError($error);
        return $this->setFailed();
    }

    public function setCompleted(): ProcessInterface
    {
        $this->setStatus(self::COMPLETED);
        return $this;
    }

    public function complete(string $info = null): ProcessInterface
    {
        if ($info) {
            $this->addInfo($info);
        }
        return $this->setCompleted();
    }

    public function completed(): bool
    {
        return in_array($this->getStatus(), [
            self::COMPLETED,
            self::COMPLETED_WITH_WARNINGS
        ]);
    }

    public function started(): bool
    {
        return $this->getStatus() !== self::NOT_STARTED;
    }

    public static function run(...$args): ProcessInterface
    {
        $callerClass = get_called_class();
        return (new $callerClass(...$args))();
    }

    public function start(): ProcessInterface
    {
        return $this();
    }

}
