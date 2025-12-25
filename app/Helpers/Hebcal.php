<?php

namespace App\Helpers;

use Lang;
use Carbon\Carbon;

class Hebcal
{
    protected ?object $jewishDate = null;

    public function __construct(
        protected Carbon $date
    ) {
    }

    private function getJewishDateNative(): string
    {
        return jdtojewish(gregoriantojd(
            $this->date->month,
            $this->date->day,
            $this->date->year
        ));
    }

    private function setJewishDateCollection(): void
    {
        [$hebrewMonth, $hebrewDay, $hebrewYear] = explode('/', $this->getJewishDateNative());

        $this->jewishDate = (object) [
            'day' => $hebrewDay,
            'month' => $hebrewMonth,
            'year' => $hebrewYear,
        ];
    }

    public function getJewishDate(): ?object
    {
        if (is_null($this->jewishDate)) {
            $this->setJewishDateCollection();
        }

        return $this->jewishDate;
    }

    public function set(int $units, $type = 'day'): static
    {
        $this->getJewishDate();

        if ($type === 'day') {
            //TODO: check valid day
            $this->jewishDate->day = $units;
        }

        if ($type === 'month') {
            if ($units === 7 && ! $this->isLeapYear()) {
                $units = 6;
            }

            $this->jewishDate->month = $units;
        }

        if ($type === 'year') {
            $this->jewishDate->year = $units;
        }

        $this->refreshDate();

        return $this;
    }

    public function month(?int $value = null): static|int
    {
        if (is_null($value)) {
            return $this->getJewishDate()->month;
        }

        return $this->set($value, 'month');
    }

    public function day(?int $value = null): static|int
    {
        if (is_null($value)) {
            return $this->getJewishDate()->day;
        }

        return $this->set($value, 'day');
    }

    public function year(?int $value = null): static|int
    {
        if (is_null($value)) {
            return $this->getJewishDate()->year;
        }

        return $this->set($value, 'year');
    }

    public function hebrewDate($withThousands = false, $withQuotes = false)
    {
        return $this->hebrewDay().' '.$this->hebrewMonth().' '.$this->hebrewYear($withThousands, $withQuotes);
    }

    public function hebrewDay()
    {
        return $this->getGimatria($this->day());
    }

    public function hebrewMonth()
    {

        $months = [
            'tishrei',
            'cheshvan',
            'kislev',
            'tevet',
            'shevat',
            $this->isLeapYear() ? 'adar_i' : 'adar',
            'adar_ii',
            'nisan',
            'iyar',
            'sivan',
            'tammuz',
            'av',
            'elul',
        ];

        $month = $months[$this->month() - 1];

        if ($local = Lang::get('hebrew_date.months.'.$month, fallback: false)) {
            return $local;
        }

        return $month;
    }

    public function hebrewYear($withThousands = false, $withQuotes = false)
    {
        $year = $this->year();

        $thousands = floor($year / 1000);

        $gimatria = $this->getGimatria($year - ($thousands * 1000));

        if ($withThousands) {
            $gimatria = $this->getGimatria($thousands)."'".$gimatria;
        }

        if ($withQuotes) {
            //place quotes before last letter
            $gimatria = mb_str_split($gimatria);
            $lastLetter = array_pop($gimatria);
            $gimatria = implode('', $gimatria).'"'.$lastLetter;
        }

        return $gimatria;
    }

    public function isLeapYear(): bool
    {
        $year = $this->year();

        if ($year % 19 == 0 || $year % 19 == 3 || $year % 19 == 6 ||
            $year % 19 == 8 || $year % 19 == 11 || $year % 19 == 14 ||
            $year % 19 == 17) {
            return true;
        } else {
            return false;
        }
    }

    public function diff(self|Carbon|null $date = null, $unit = 'year-month'): int|float|array
    {
        if (is_null($date)) {
            $date = Carbon::now();
        }

        if ($date instanceof self) {
            $date = $date->date;
        }

        if ($unit === 'day') {
            return $this->date->diffInDays($date);
        }

        if ($unit === 'year-month') {
            $dateHebcal = $date->hebcal();

            if ($dateHebcal->month() === $this->month()) {
                return $this->year() - $dateHebcal->year();
            }

            $dateHebcalMonth = $dateHebcal->month();

            if (! $dateHebcal->isLeapYear() && $dateHebcalMonth >= 8) {
                $dateHebcalMonth--;
            }

            $month = $this->month();

            if (! $dateHebcal->isLeapYear() && $month >= 8) {
                $month--;
            }

            $monthDiff = $dateHebcalMonth - $month;

            $yearDiff = abs($this->year() - $dateHebcal->year());

            if ($monthDiff < 0) {
                $yearDiff--;
                $monthDiff = 12 + $monthDiff;
            }

            return (float) ($yearDiff.'.'.$monthDiff);

        }

        if ($unit === 'year') {
            return $this->year() - $date->hebcal()->year();
        }

        if ($unit === 'month') {
            //
        }

        return 0;
    }

    public function age($unit = 'year-month'): float|int
    {
        return abs($this->diff(unit: $unit));
    }

    private function getGimatria(float|int $param): string
    {
        $units = [
            1 => 'א',
            2 => 'ב',
            3 => 'ג',
            4 => 'ד',
            5 => 'ה',
            6 => 'ו',
            7 => 'ז',
            8 => 'ח',
            9 => 'ט',
        ];

        $tens = [
            10 => 'י',
            20 => 'כ',
            30 => 'ל',
            40 => 'מ',
            50 => 'נ',
            60 => 'ס',
            70 => 'ע',
            80 => 'פ',
            90 => 'צ',
        ];

        $hundreds = [
            100 => 'ק',
            200 => 'ר',
            300 => 'ש',
            400 => 'ת',
            500 => 'תק',
            600 => 'תר',
            700 => 'תש',
            800 => 'תת',
            900 => 'תתק',
        ];

        $hundredNumber = floor($param / 100) * 100;

        $tenNumber = floor(($param - $hundredNumber) / 10) * 10;

        $unitNumber = $param - $hundredNumber - $tenNumber;

        $gimatria = '';

        if ($tenNumber) {
            $gimatria .= $tens[$tenNumber];
        }

        if ($unitNumber) {
            $gimatria .= $units[$unitNumber];
        }

        if ($gimatria == 'יה') {
            $gimatria = 'טו';
        }

        if ($gimatria == 'יו') {
            $gimatria = 'טז';
        }

        if ($hundredNumber) {
            $gimatria = $hundreds[$hundredNumber].$gimatria;
        }

        if ($gimatria == 'תתק') {
            $gimatria = 'תת';
        }

        if ($gimatria === 'רעב') {
            $gimatria = 'ערב';
        }

        return $gimatria;
    }

    public function sub(int $units, $unitType = 'day'): static
    {
        match ($unitType) {
            'day' => $this->day($this->day() - $units),
            'month' => $this->month($this->month() - $units),
            'year' => $this->year($this->year() - $units),
        };

        $this->refreshDate();

        return $this;
    }

    public function add($units, $unitType = 'day'): static
    {
        match ($unitType) {
            'day' => $this->day($this->day() + $units),
            'month' => $this->month($this->month() + $units),
            'year' => $this->year($this->year() + $units),
        };

        $this->refreshDate();

        return $this;
    }

    public function addYears($years): static
    {
        return $this->add($years, 'year');
    }

    public function addMonths($months): static
    {
        return $this->add($months, 'month');
    }

    public function addDays($days): static
    {
        return $this->add($days, 'day');
    }

    public function subYears($years): static
    {
        return $this->sub($years, 'year');
    }

    public function subMonths($months): static
    {
        return $this->sub($months, 'month');
    }

    public function subDays($days): static
    {
        return $this->sub($days, 'day');
    }

    /**
     * Get the georgian date from the jewish date.
     */
    public function georgianDate(): bool|Carbon
    {
        return Carbon::createFromFormat('m/d/Y',
            jdtogregorian(jewishtojd($this->month(), $this->day(), $this->year()))
        );
    }

    private function refreshDate(): void
    {
        $this->date = $this->georgianDate();
    }
}
