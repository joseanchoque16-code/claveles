<?php

namespace App\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;

class AppLayout extends Component
{
    public string $headerTitle;

    public function __construct(string $headerTitle = 'Panel de Control')
    {
        $this->headerTitle = $headerTitle;
    }

    public function render(): View
    {
        return view('layouts.app');
    }
}
