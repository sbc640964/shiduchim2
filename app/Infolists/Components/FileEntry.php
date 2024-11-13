<?php

namespace App\Infolists\Components;

use Filament\Infolists\Components\Entry;
use File;

class FileEntry extends Entry
{
    protected string $view = 'filament.infolists.entries.file-entry';

    protected null|string|\Closure $notFoundMessage = null;

    protected string|\Closure $fileAttribute = 'file';

    protected string|\Closure $nameAttribute = 'name';

    protected string|\Closure $prefix = 'storage/';

    protected bool $grid = false;

    public function fileAttribute(string|\Closure $attribute): static
    {
        $this->fileAttribute = $attribute;

        return $this;
    }

    public function nameAttribute(string|\Closure $attribute): static
    {
        $this->nameAttribute = $attribute;

        return $this;
    }

    public function getFileAttribute(): string
    {
        return $this->evaluate($this->fileAttribute);
    }

    public function getNameAttribute(): string
    {
        return $this->evaluate($this->nameAttribute);
    }

    public function grid(bool|\Closure $boolean = true): static
    {
        $this->grid = $boolean;

        return $this;
    }

    public function isGrid(): bool
    {
        return $this->evaluate($this->grid);
    }

    public function mimeType(): bool|string
    {
        if ($this->isExternalAudio()) {
            return 'audio';
        }

        return File::mimeType($this->publicPath());
    }

    public function type(): string
    {
        $mimeType = $this->mimeType();

        if (str_contains($mimeType, 'image')) {
            return 'image';
        }

        if (str_contains($mimeType, 'audio')) {
            return 'audio';
        }

        if (str_contains($mimeType, 'video')) {
            return 'video';
        }

        return 'file';
    }

    public function publicPath(): ?string
    {
        if ($this->isExternalAudio()) {
            return $this->getState($this->getFileAttribute());
        }

        if ($path = $this->getState($this->getFileAttribute())) {
            return public_path($this->getPrefixPath().$path);
        }

        return null;
    }

    public function isImage(): bool
    {
        return $this->type() == 'image';
    }

    public function getState(?string $path = null, bool $strict = false): string|array|null
    {
        $state = parent::getState();

        if (is_array($state) && $path) {
            return data_get($state, $path) ?? ($strict ? null : $state);
        } elseif (! is_array($state) && $path && $strict) {
            return null;
        }

        return $state;
    }

    public function fileUrl(): string
    {
        if ($this->isExternalAudio()) {
            return $this->getState($this->getFileAttribute());
        }

        return asset($this->getPrefixPath().$this->getState($this->getFileAttribute()));
    }

    public function isExternalAudio(): bool
    {
        return \Str::startsWith(
            $this->getState($this->getFileAttribute()),
            'https://api.phonecall'
        );
    }

    public function fileExists(): bool
    {
        if ($this->isExternalAudio()) {
            return true;
        }

        return File::exists($this->publicPath() ?? 'null');
    }

    public function notFoundMessage(string|\Closure|null $message): static
    {
        $this->notFoundMessage = $message;

        return $this;
    }

    public function getDefaultNotFoundMessage(): string
    {
        return 'הקובץ לא נמצא';
    }

    public function getNotFoundMessage(): string
    {
        return $this->evaluate($this->notFoundMessage)
            ?? $this->getDefaultNotFoundMessage();
    }

    protected function resolveDefaultClosureDependencyForEvaluationByName(string $parameterName): array
    {
        return match ($parameterName) {
            'type' => [$this->type()],
            'fileUrl' => [$this->fileUrl()],
            'publicPath' => [$this->publicPath()],
            'fileExists' => [$this->fileExists()],
            'mimeType' => [$this->mimeType()],
            default => parent::resolveDefaultClosureDependencyForEvaluationByName($parameterName),
        };
    }

    public function prefixPath(string|\Closure $prefix): static
    {
        $this->prefix = $prefix;

        return $this;
    }

    public function getPrefixPath(): string
    {
        return $this->evaluate($this->prefix);
    }
}
