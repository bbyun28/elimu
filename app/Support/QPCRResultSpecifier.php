<?php

namespace App\Support;

class QPCRResultSpecifier
{
    private $targetParameters;
    private $result;
    private $styles;

    public function __construct($targetParameters, $result)
    {

        $this->targetParameters = $targetParameters;
        $this->result = $result;
        $this->styles = false;
    }

    public function withStyles($styles = true)
    {
        $this->styles = $styles;

        return $this;
    }

    public function qualitative()
    {
        $parameters = $this->targetParameters;
        if ($this->result->replicas < $parameters['minvalues']) {
            $error = 'Not enough data';
        } elseif ($this->result->positives != $this->result->replicas && $this->result->positives != 0) {
            $error = 'Needs repetition';
        } elseif ($this->result->positives > 0 && $this->result->stddev > $parameters['cuttoffstdev']) {
            $error = 'Standard deviation too high';
        }

        if (isset($error)) {
            return $this->styles ? sprintf('<span class="text-danger">%s</span>', $error) : $error;
        }

        return $this->result->avg_cq != null
        && $this->result->avg_cq <= $parameters['cutoff']
            ? 'Positive' : 'Negative';
    }

    public function quantitative()
    {
        return '';
    }
}
