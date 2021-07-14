<?php

namespace Drupal\filmykhabar_api;

use NepaliCalendar\AdToBs\AdToBs;

trait NepaliCalendarTrait
{
    public function getNepaliDateFormatted($timestamp, $includeTime = false)
    {
        $adToBs = new AdToBs();
        $nepaliDate = $adToBs->getNepaliDate($timestamp);
        $returnData = "{$this->translateMonth($nepaliDate['n'])} {$this->translateDigits($nepaliDate['j'])}, {$this->translateDigits($nepaliDate['Y'])}";
        return $returnData;
    }

    public function translateDigits($digits)
    {
        // @todo: number translation returns unexpected results.
        // temporarily adding colon ':' infront of number tranlation to mitigate the issue.
        $returnData = '';
        $arrDigits = array(
            '0' => $this->t(':0'),
            '1' => $this->t(':1'),
            '2' => $this->t(':2'),
            '3' => $this->t(':3'),
            '4' => $this->t(':4'),
            '5' => $this->t(':5'),
            '6' => $this->t(':6'),
            '7' => $this->t(':7'),
            '8' => $this->t(':8'),
            '9' => $this->t(':9'),
        );

        foreach (str_split($digits) as $val) {
            $returnData .= $arrDigits[$val];
        }
        return $returnData;
    }

    public function translateMonth($month)
    {
        $nepaliMonths = array(
            1 => $this->t('Baishakh'),
            2 => $this->t('Jestha'),
            3 => $this->t('Ashadh'),
            4 => $this->t('Shrawan'),
            5 => $this->t('Bhadra'),
            6 => $this->t('Ashwin'),
            7 => $this->t('Kartik'),
            8 => $this->t('Mangsir'),
            9 => $this->t('Poush'),
            10 => $this->t('Magh'),
            11 => $this->t('Falgun'),
            12 => $this->t('Chaitra'),
        );
        return $nepaliMonths[$month];
    }
}
