<?php

namespace App\DTOs;

class ExtractedDocumentData
{
    public function __construct(
        public ?string $letterNumber = null,
        public ?string $letterDate = null,
        public ?string $originAgency = null,
        public ?string $subject = null,
        public ?string $studentName = null,
        public ?string $schoolName = null,
        public ?string $npsn = null,
        public ?int $competitionYear = null,
        public ?string $competitionCategory = null,
        public ?string $provider = null,
        public ?string $model = null,
        public float $confidence = 0.0,
        public array $raw = []
    ) {}

    public function toArray(): array
    {
        return [
            'letter_number' => $this->letterNumber,
            'letter_date' => $this->letterDate,
            'origin_agency' => $this->originAgency,
            'subject' => $this->subject,
            'student_name' => $this->studentName,
            'school_name' => $this->schoolName,
            'npsn' => $this->npsn,
            'competition_year' => $this->competitionYear,
            'competition_category' => $this->competitionCategory,
            'provider' => $this->provider,
            'model' => $this->model,
            'confidence' => $this->confidence,
        ];
    }
}

