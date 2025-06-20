<?php

namespace Rcv\Core\Contracts;

interface ModuleInterface
{
    /**
     * Get the module name.
     *
     * @return string
     */
    public function getName(): string;

    /**
     * Get the module version.
     *
     * @return string
     */
    public function getVersion(): string;

    /**
     * Get the module description.
     *
     * @return string
     */
    public function getDescription(): string;

    /**
     * Check if the module is enabled.
     *
     * @return bool
     */
    public function isEnabled(): bool;

    /**
     * Enable the module.
     *
     * @return bool
     */
    public function enable(): bool;

    /**
     * Disable the module.
     *
     * @return bool
     */
    public function disable(): bool;

    /**
     * Get the module path.
     *
     * @return string
     */
    public function getPath(): string;
} 