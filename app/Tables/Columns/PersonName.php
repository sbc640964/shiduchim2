<?php

namespace App\Tables\Columns;

use Closure;
use Filament\Tables\Columns\Column;

class PersonName extends Column
{
    protected string $view = 'tables.columns.person-name';

    protected Closure|bool $fatherName = false;

    protected Closure|bool $motherName = false;

    public function withFatherName(Closure|bool $bool = true): self
    {
        $this->fatherName = $bool;

        return $this;
    }

    public function withMotherName(Closure|bool $bool = true): self
    {
        $this->motherName = $bool;

        return $this;
    }

    public function isWithFatherName(): ?string
    {
        return $this->evaluate($this->fatherName);
    }

    public function isWithMotherName(): ?string
    {
        return $this->evaluate($this->motherName);
    }
}
