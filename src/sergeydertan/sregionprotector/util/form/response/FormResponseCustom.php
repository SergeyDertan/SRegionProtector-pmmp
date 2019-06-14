<?php
declare(strict_types=1);

namespace sergeydertan\sregionprotector\util\form\response;

class FormResponseCustom extends FormResponse
{
    /**
     * id => obj
     * @var object[]
     */
    public $responses;
    /**
     * @var string[]
     */
    public $inputResponses;
    /**
     * @var string[]
     */
    public $labelResponses;

    public function __construct(array $responses, array $inputResponses, array $labelResponses)
    {
        $this->responses = $responses;
        $this->inputResponses = $inputResponses;
        $this->labelResponses = $labelResponses;
    }
}
