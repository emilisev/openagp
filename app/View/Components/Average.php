<?php

namespace App\View\Components;

use App\Models\DiabetesData;
use Illuminate\View\Component;

class Average extends Component {
    private $m_data;


    /**
     * Create the component instance.
     */
    public function __construct(DiabetesData $data) {
        $this->m_data = $data;
    }

    /* * * * * * * * * * * * * * * * * * * * * * PUBLIC METHODS  * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
    /**
     * Get the view / contents that represent the component.
     */
    public function render() {
        return view('cards.average', ['data' => $this->m_data]);
    }

}
