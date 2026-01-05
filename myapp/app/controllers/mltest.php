<?php

/**
 # Copyright Rakesh Shrestha (rakesh.shrestha@gmail.com)
 # All rights reserved.
 #
 # Redistribution and use in source and binary forms, with or without
 # modification, are permitted provided that the following conditions are
 # met:
 #
 # Redistributions must retain the above copyright notice.
 */
use ML\Classification\KNearestNeighbors;
use ML\Dataset\CsvDataset;

final class cMLTest extends cController
{

    public function __construct()
    {
        parent::__construct();
    }

    private function _removeIndex($index, array &$array): array
    {
        unset($array[$index]);
        return $array;
    }

    public function index()
    {
        $dataset = new CsvDataset(APP_DIR . '/sampledata/air.csv', 2);

        $k = 3;
        $correct = 0;
        $cval = 0;

        $mdata = $dataset->getSamples();
        $mtarget = $dataset->getTargets();

        foreach ($mdata as $index => $sample) {
            $cval ++;
            $estimator = new KNearestNeighbors($k);

            $estimator->train($other = $this->_removeIndex($index, $mdata), $this->_removeIndex($index, $mtarget));

            $predicted = $estimator->predict([
                $sample
            ]);

            if ($predicted[0] === $dataset->getTargets()[$index]) {
                $correct ++;
            }
        }

        echo sprintf('Accuracy (k=%s): %.02f%% correct: %s totaldata: %s<br/>', $k, ($correct / $cval) * 100, $correct, $cval) . PHP_EOL;
    }
}
