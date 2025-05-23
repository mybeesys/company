<?php

namespace Modules\Accounting\View\Components;

use Illuminate\View\Component;

class AccountRouting extends Component
{
    public $section;
    public $title;
    public $typeSelectId;
    public $typeSelectName;
    public $accountSelectId;
    public $accountSelectName;
    public $accounts;
    public $typeOptions;
    public function __construct(
        $section,
        $title,
        $typeSelectId,
        $typeSelectName,
        $accountSelectId,
        $accountSelectName,
        $accounts,
        $typeOptions
    ) {
        $this->section = $section;
        $this->title = $title;
        $this->typeSelectId = $typeSelectId;
        $this->typeSelectName = $typeSelectName;
        $this->accountSelectId = $accountSelectId;
        $this->accountSelectName = $accountSelectName;
        $this->accounts = $accounts;
        $this->typeOptions = $typeOptions;
    }

    public function render()
    {
        return view('accounting::components.account-routing');
    }
}
