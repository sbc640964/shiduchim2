<?php

namespace App\Models\Traits;

use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Casts\Attribute;

trait HasHebrewBirthday
{
    public function hebrewBirthdayDiffDays(): Attribute
    {
        return Attribute::make(
            get: fn (): ?int => $this->hebrewBirthdayDiffInDays(),
        );
    }

    public function hebrewBirthdayHumanDiff(): Attribute
    {
        return Attribute::make(
            get: function (): ?string {
                $diff = $this->hebrewBirthdayDiffInDays();

                if ($diff === null) {
                    return null;
                }

                if ($diff < -30 || $diff > 30) {
                    return null;
                }

                if ($diff === 0) {
                    return 'היום';
                }

                if ($diff > 0) {
                    if ($diff === 1) {
                        return 'בעוד יום';
                    }

                    if ($diff === 7) {
                        return 'בעוד שבוע';
                    }

                    if ($diff === 14) {
                        return 'בעוד שבועיים';
                    }

                    if ($diff === 30) {
                        return 'בעוד חודש';
                    }

                    return 'בעוד '.$diff.' ימים';
                }

                $absoluteDiff = abs($diff);

                if ($absoluteDiff === 1) {
                    return 'לפני יום';
                }

                if ($absoluteDiff === 7) {
                    return 'לפני שבוע';
                }

                if ($absoluteDiff === 14) {
                    return 'לפני שבועיים';
                }

                if ($absoluteDiff === 30) {
                    return 'לפני חודש';
                }

                return 'לפני '.$absoluteDiff.' ימים';
            },
        );
    }

    public function hebrewBirthdayDiffInDays(?CarbonInterface $from = null): ?int
    {
        if (! $this->born_at) {
            return null;
        }

        /** @var Carbon $fromDate */
        $fromDate = $from?->copy() ?? Carbon::today();

        $birthHebcal = $this->born_at->hebcal();

        $birthDay = $birthHebcal->day();
        $birthMonth = $birthHebcal->month();

        $fromHebcal = $fromDate->hebcal();

        $baseYear = $fromHebcal->year();

        // יום הולדת עברי בשנה העברית הנוכחית
        $currentYearHeb = $fromDate->hebcal();
        $currentYearHeb->year($baseYear)->month($birthMonth)->day($birthDay);
        $dateCurrentYear = $currentYearHeb->georgianDate();

        // יום הולדת עברי בשנה העברית הבאה
        $nextYearHeb = $fromDate->hebcal();
        $nextYearHeb->year($baseYear + 1)->month($birthMonth)->day($birthDay);
        $dateNextYear = $nextYearHeb->georgianDate();

        $diffCurrent = $fromDate->diffInDays($dateCurrentYear, false);
        $diffNext = $fromDate->diffInDays($dateNextYear, false);

        return abs($diffCurrent) <= abs($diffNext) ? $diffCurrent : $diffNext;
    }

    public function getAgeAttribute(): int|float|null
    {
        return $this->born_at?->hebcal()?->age();
    }
}

