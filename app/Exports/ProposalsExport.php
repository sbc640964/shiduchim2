<?php

namespace App\Exports;

use App\Models\Person;
use App\Models\Proposal;
use Illuminate\Support\Collection;
use Kirschbaum\PowerJoins\Mixins\JoinRelationship;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ProposalsExport implements FromCollection, WithHeadings
{
    protected ?array $createdAtRange;

    public function __construct(?array $createdAtRange = null)
    {
        $this->createdAtRange = $createdAtRange;
    }

    /**
    * @return Collection
    */
    public function collection(): Collection
    {
        return Proposal::query()
            ->when($this->createdAtRange, fn($query, $range) => $query->whereBetween("created_at", $range))
            ->with("girl", "guy", "users")
            ->withCount([
                "diaries as total_guy_diaries" => fn($query) => $query
                    ->where("diaries.model_type", Person::class)
                    ->whereColumn("proposals.guy_id", "diaries.model_id")
            ])
            ->withCount([
                "diaries as total_girl_diaries" => fn($query) => $query
                    ->where("diaries.model_type", Person::class)
                    ->whereColumn("proposals.girl_id", "diaries.model_id")
            ])
            ->withCount("diaries as total_diaries")
            ->withCount([
                "diaries as total_calls" => fn($query) => $query->where("type", "call")
            ])
            ->withMax("diaries as last_diary", "created_at")
            ->withSum("diaries as total_time", "data->duration")
            ->withCasts([
                "total_time" => "integer",
                "last_diary" => "datetime",
            ])
            ->get()
            ->map(function (Proposal $proposal) {
                return [
                    "id" => $proposal->id,
                    "users" => $proposal->users->pluck("name")->join(", "),
                    "created_at" => $proposal->created_at->format("d/m/Y H:i:s"),
                    "status" => $proposal->status,
                    "total_diaries" => $proposal->total_diaries,
                    "total_calls" => $proposal->total_calls,
                    "total_time" => gmdate("H:s:i" , $proposal->total_time),
                    "last_diary" => $proposal->last_diary ? $proposal->last_diary->format("d/m/Y H:i:s") : "-",

                    "guy_id" => $proposal->guy_id,
                    "guy_external_student_id" => $proposal->guy->external_code_students,
                    "guy_name" => $proposal->guy->full_name,
                    "guy_status" => $proposal->status_guy,
                    "total_guy_diaries" => $proposal->total_guy_diaries,

                    "girl_id" => $proposal->girl_id,
                    "girl_external_student_id" => $proposal->girl->external_code_students,
                    "girl_name" => $proposal->girl->full_name,
                    "girl_status" => $proposal->status_girl,
                    "total_girl_diaries" => $proposal->total_girl_diaries,
                ];
            });
    }

    public function headings(): array
    {
        return [
            "id" => "מזהה",
            "users" => "שדכנ/ים",
            "created_at" => "תאריך יצירה",
            "status" => "סטטוס",
            "total_diaries" => "סה\"כ יומנים",
            "total_calls" => "סה\"כ שיחות",
            "total_time" => "סה\"כ זמן",
            "last_diary" => "תאריך יומן אחרון",

            "guy_id" => "מזהה בחור",
            "guy_external_student_id" => "מזהה חיצוני בחור",
            "guy_name" => "שם בחור",
            "guy_status" => "סטטוס בחור",
            "total_guy_diaries" => "סה\"כ יומנים בחור",

            "girl_id" => "מזהה בחור",
            "girl_external_student_id" => "מזהה חיצוני בחורה",
            "girl_name" => "שם בחורה",
            "girl_status" => "סטטוס בחורה",
            "total_girl_diaries" => "סה\"כ יומנים בחורה",
        ];
    }
}
