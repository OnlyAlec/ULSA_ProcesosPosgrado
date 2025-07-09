<?php

class Quitted
{
    private int $id;
    private int $studentID;
    private int $quitDescriptionID;
    private ?string $requestedAt;
    private ?string $officialApplyingAt;
    private ?string $nofficialApplyingAt;
    private ?string $returningAt;
    private ?int $status;
    private ?int $quitReasonID;
    private ?int $quitStatusID;
    
    // Propiedades adicionales para información relacionada
    private ?string $studentName;
    private ?int $studentUlsaID;
    private ?string $programName;
    private ?string $quitDescriptionName;
    private ?string $quitReasonName;
    private ?string $quitStatusName;

    public function __construct(
        int $studentID,
        int $quitDescriptionID,
        ?string $requestedAt = null,
        ?string $officialApplyingAt = null,
        ?string $nofficialApplyingAt = null,
        ?string $returningAt = null,
        ?int $status = null,
        ?int $quitReasonID = null,
        ?int $quitStatusID = null,
        ?int $id = null
    ) {
        $this->studentID = $studentID;
        $this->quitDescriptionID = $quitDescriptionID;
        $this->requestedAt = $requestedAt;
        $this->officialApplyingAt = $officialApplyingAt;
        $this->nofficialApplyingAt = $nofficialApplyingAt;
        $this->returningAt = $returningAt;
        $this->status = $status;
        $this->quitReasonID = $quitReasonID;
        $this->quitStatusID = $quitStatusID;
        
        if ($id !== null) {
            $this->id = $id;
        }
    }

    // Getters
    public function getID(): int
    {
        return $this->id;
    }

    public function getStudentID(): int
    {
        return $this->studentID;
    }

    public function getQuitDescriptionID(): int
    {
        return $this->quitDescriptionID;
    }

    public function getRequestedAt(): ?string
    {
        return $this->requestedAt;
    }

    public function getOfficialApplyingAt(): ?string
    {
        return $this->officialApplyingAt;
    }

    public function getNofficialApplyingAt(): ?string
    {
        return $this->nofficialApplyingAt;
    }

    public function getReturningAt(): ?string
    {
        return $this->returningAt;
    }

    public function getStatus(): ?int
    {
        return $this->status;
    }

    public function getQuitReasonID(): ?int
    {
        return $this->quitReasonID;
    }

    public function getQuitStatusID(): ?int
    {
        return $this->quitStatusID;
    }

    public function getStudentName(): ?string
    {
        return $this->studentName;
    }

    public function getStudentUlsaID(): ?int
    {
        return $this->studentUlsaID;
    }

    public function getProgramName(): ?string
    {
        return $this->programName;
    }

    public function getQuitDescriptionName(): ?string
    {
        return $this->quitDescriptionName;
    }

    public function getQuitReasonName(): ?string
    {
        return $this->quitReasonName;
    }

    public function getQuitStatusName(): ?string
    {
        return $this->quitStatusName;
    }

    // Setters
    public function setRequestedAt(?string $requestedAt): void
    {
        $this->requestedAt = $requestedAt;
    }

    public function setOfficialApplyingAt(?string $officialApplyingAt): void
    {
        $this->officialApplyingAt = $officialApplyingAt;
    }

    public function setNofficialApplyingAt(?string $nofficialApplyingAt): void
    {
        $this->nofficialApplyingAt = $nofficialApplyingAt;
    }

    public function setReturningAt(?string $returningAt): void
    {
        $this->returningAt = $returningAt;
    }

    public function setStatus(?int $status): void
    {
        $this->status = $status;
    }

    public function setQuitReasonID(?int $quitReasonID): void
    {
        $this->quitReasonID = $quitReasonID;
    }

    public function setQuitStatusID(?int $quitStatusID): void
    {
        $this->quitStatusID = $quitStatusID;
    }

    public function setStudentName(string $studentName): void
    {
        $this->studentName = $studentName;
    }

    public function setStudentUlsaID(int $studentUlsaID): void
    {
        $this->studentUlsaID = $studentUlsaID;
    }

    public function setProgramName(string $programName): void
    {
        $this->programName = $programName;
    }

    public function setQuitDescriptionName(string $quitDescriptionName): void
    {
        $this->quitDescriptionName = $quitDescriptionName;
    }

    public function setQuitReasonName(string $quitReasonName): void
    {
        $this->quitReasonName = $quitReasonName;
    }

    public function setQuitStatusName(string $quitStatusName): void
    {
        $this->quitStatusName = $quitStatusName;
    }

    // Método para obtener los datos como array
    public function toArray(): array
    {
        return [
            'id' => $this->id ?? null,
            'studentID' => $this->studentID,
            'studentName' => $this->studentName,
            'studentUlsaID' => $this->studentUlsaID,
            'programName' => $this->programName,
            'quitDescriptionID' => $this->quitDescriptionID,
            'quitDescriptionName' => $this->quitDescriptionName,
            'requestedAt' => $this->requestedAt,
            'officialApplyingAt' => $this->officialApplyingAt,
            'nofficialApplyingAt' => $this->nofficialApplyingAt,
            'returningAt' => $this->returningAt,
            'status' => $this->status,
            'quitReasonID' => $this->quitReasonID,
            'quitReasonName' => $this->quitReasonName,
            'quitStatusID' => $this->quitStatusID,
            'quitStatusName' => $this->quitStatusName
        ];
    }

    // Método para obtener los datos como JSON
    public function getJSON(): array
    {
        return $this->toArray();
    }
} 