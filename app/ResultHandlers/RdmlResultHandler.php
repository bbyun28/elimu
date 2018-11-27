<?php

namespace App\ResultHandlers;

use App\FileTypes\RDML;
use App\Models\Result;
use Illuminate\Support\Facades\DB;

class RdmlResultHandler extends ResultHandler
{
    public static $dataLabel = 'Cq Value';

    public static $additionalDataLabel = 'Position';

    public function handle()
    {
        if (!$this->inputParameters) {
            $this->error(__('Input parameters not set'));
        }
        $rdml = new RDML($this->file, $this->inputParameters);
        if ($rdml->getData()->isEmpty()) {
            $this->error(__('Invalid rdml file'));
        }
        if (!$rdml->hasValidTargets()) {
            $this->error(__($rdml->getLastError()));
        }
        if (!$rdml->hasValidControls()) {
              $this->error(__($rdml->getLastError()));
        }
        // the following
        if (!$rdml->hasEnoughRepetitions()) {
            $this->error(__($rdml->getLastError()));
        }
        if (!$rdml->hasCorrectDeviation()) {
            $this->error(__($rdml->getLastError()));
        }
        $this->validateSampleIds($rdml->getSampleIds()->toArray());

        DB::transaction(function () use ($rdml) {
            $this->removeData();
            $this->store($rdml);
        });
    }

    private function store(RDML $rdml)
    {
        $sampleIds = $this
            ->getDatabaseIdBySampleIds(
                $rdml
                    ->cyclesOfQuantificationWithoutControl()
                    ->pluck('sampleId')
            );
        $targets = $rdml
            ->cyclesOfQuantificationWithoutControl()
            ->groupBy(['target', 'sampleId']);
        $resultData = [];
        foreach ($targets as $target => $samples) {
            $targetInputParameter = collect($this->inputParameters)->firstWhere('target', $target);
            foreach ($samples as $sampleId => $sample) {
                $result = new Result;
                $result->sample_id = $sampleIds[$sampleId];
                $result->experiment_id = $this->experimentId;
                $result->target = $sample[0]['target'];
                $result->value = $this->determineValue(
                    collect($sample)->pluck('cq'),
                    $targetInputParameter['cutoff'],
                    $targetInputParameter['lod']
                );
                if ($result->value == 'Positive' &&
                    strtolower(
                        $targetInputParameter['quant']
                    ) == 'yes') {
                    $result->value = $targetInputParameter['slope'] * collect($sample)->avg('cq')
                     + $targetInputParameter['intercept'] . " (Positive)";
                }
                $result->save();
                foreach ($sample as $data) {
                    $resultData[] = [
                        'result_id' => $result->id,
                        'primary_value' => $data['cq'],
                        'secondary_value' => $data['position'],
                        'additional' => serialize(
                            collect($data)
                                ->except(['position', 'sampleId', 'cq', 'data'])
                                ->merge(['sample ID' => $sampleIds[$sampleId]])
                                ->sortKeys()
                                ->toArray()
                        )
                    ];
                }
            }
        }
        foreach (array_chunk($resultData, 100) as $chunk) {
            DB::table('result_data')->insert($chunk);
        }
    }

    private function determineValue($cqs, $cutoff, $lod)
    {
        $isPositive = null;
        $needsRepetition = false;
        foreach ($cqs as $cq) {
            $status = $cq && $cq <= $cutoff ? true : false;
            if ($isPositive === null) {
                $isPositive = $status;
            } elseif ($isPositive !== $status) {
                $needsRepetition = true;
            }
        }
        return $needsRepetition ? 'Invalid data' : ($isPositive ? 'Positive' : 'Negative');
    }
}
