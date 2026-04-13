<?php

namespace FastForward\DevTools\Composer\Json;

use Composer\Factory;
use Composer\Json\JsonFile;

final class ComposerJson extends JsonFile
{
    private array $data;

    public function __construct(?string $path = null)
    {
        parent::__construct($path ?? Factory::getComposerFile());
        $this->data = $this->read();
    }

    public function getPackageName(): string
    {
        return $this->data['name'] ?? '';
    }

    public function getPackageDescription(): string
    {
        return $this->data['description'] ?? '';
    }

    public function getPackageLicense(): ?string
    {
        $license = $this->data['license'] ?? [];

        if (\is_string($license)) {
            return $license;
        }

        if (\is_array($license) && count($license) === 1) {
            return $license[0];
        }

        return null;
    }

    public function getAuthors(): array
    {
        return $this->data['authors'] ?? [];
    }

    public function getExtra(): array
    {
        return $this->data['extra'] ?? [];
    }

    public function getAutoload(string $type = 'psr-4'): array
    {
         $autoload = $this->data['autoload'] ?? [];

         return $autoload[$type] ?? [];
    }
}
