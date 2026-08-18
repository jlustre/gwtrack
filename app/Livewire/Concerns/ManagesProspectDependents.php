<?php

namespace App\Livewire\Concerns;

trait ManagesProspectDependents
{
    public function addDependent(): void
    {
        if (count($this->dependents) >= 12) {
            return;
        }

        $this->dependents[] = ['name' => '', 'age' => null];
    }

    public function removeDependent(int $index): void
    {
        if (! isset($this->dependents[$index])) {
            return;
        }

        unset($this->dependents[$index]);
        $this->dependents = array_values($this->dependents);

        if ($this->dependents === []) {
            $this->dependents = [['name' => '', 'age' => null]];
        }
    }
}
