<?php

namespace App\Traits;

use Illuminate\Http\Request;

trait CardTrait
{

    public function setYear($year) {

        $year = trim($year);

        if (strlen($year) == 2) {
            $year = substr(date('Y'), 0, 2).$year;
        }

        $this->year = $year;

        return $this->year;

    }

    public function setMonth($month) {
        $this->month = str_pad($month, 2, '0', STR_PAD_LEFT);
        return $this->month;
    }

    /**
     * @param  string  $year
     * @param  string  $month
     * @return mixed
     */
    public static function validate(string $year, string $month)
    {

        $this->setMonth($month);
        $this->setYear($year);

        return $this->isValidExpiration();
    }

    /**
     * @return bool
     */
    public function isValidExpiration()
    {
        return $this->isValidYear()
            && $this->isValidMonth()
            && $this->isFeatureDate();
    }

    /**
     * @return bool
     */
    protected function isValidYear()
    {
        $test = '/^'.substr(date('Y'), 0, 2).'\d\d$/';

        return $this->year != ''
            && preg_match($test, $this->year);
    }

    /**
     * @return bool
     */
    protected function isValidMonth()
    {
        return $this->month != ''
            && $this->month() != '00'
            && preg_match('/^(0[1-9]|1[0-2])$/', $this->month());
    }

    /**
     * @return bool
     */
    protected function isFeatureDate()
    {
        return Carbon::now()->startOfDay()->lte(
            Carbon::createFromFormat('Y-m', $this->year.'-'.$this->month())->endOfDay()
        );
    }
}
