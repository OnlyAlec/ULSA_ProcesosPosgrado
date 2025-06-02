<?php

class Candidate
{
    private int $id;
    private int $userID;
    private int $programID;
    private ?int $ulsaID; // Este campo viene del JOIN con user, no se almacena en candidate
    private string $admissionFolio;
    private int $admissionBlockNumber;
    private string $firstName;
    private string $lastName;
    private string $email;
    private ?string $email2;
    private string $mobilePhone;
    private string $interviewRequestDate;
    private string $interviewDateTime;
    private bool $programCoordinatorApprovalFlag;
    private ?string $programCoordinatorDecision;
    private bool $candidatePendingFlag;
    private bool $admissionsPendingFlag;
    private ?string $admissionsPendingDescription;
    private bool $registrarPendingFlag;
    private ?string $registrarPendingDescription;
    private bool $engineeringFacultyPendingFlag;
    private ?string $engineeringFacultyPendingDescription;
    private bool $gradChiefPendingFlag;
    private ?string $gradChiefPendingDescription;
    private bool $programCoordinatorPendingFlag;
    private ?string $programCoordinatorPendingDescription;
    private bool $status;
    private ?string $programName;

    public function __construct(
        string $firstName,
        string $lastName,
        string $admissionFolio,
        int $admissionBlockNumber,
        string $email,
        string $mobilePhone,
        int $programID,
        string $interviewRequestDate,
        string $interviewDateTime,
        int $id = 0,
        int $userID = 0,
        ?int $ulsaID = null,
        ?string $email2 = null,
    ) {
        $this->setFirstName(ucwords($firstName));
        $this->setLastName(ucwords($lastName));
        $this->setAdmissionFolio($admissionFolio);
        $this->setAdmissionBlockNumber($admissionBlockNumber);
        $this->setEmail($email);
        $this->setEmail2($email2);
        $this->setMobilePhone($mobilePhone);
        $this->setProgramID($programID);
        $this->setInterviewRequestDate($interviewRequestDate);
        $this->setInterviewDateTime($interviewDateTime);
        $this->id = $id;
        $this->userID = $userID;
        $this->ulsaID = $ulsaID; // Este valor viene del JOIN con user

        // Valores por defecto
        $this->programCoordinatorApprovalFlag = false;
        $this->programCoordinatorDecision = null;
        $this->candidatePendingFlag = false;
        $this->admissionsPendingFlag = false;
        $this->admissionsPendingDescription = null;
        $this->registrarPendingFlag = false;
        $this->registrarPendingDescription = null;
        $this->engineeringFacultyPendingFlag = false;
        $this->engineeringFacultyPendingDescription = null;
        $this->gradChiefPendingFlag = false;
        $this->gradChiefPendingDescription = null;
        $this->programCoordinatorPendingFlag = false;
        $this->programCoordinatorPendingDescription = null;
        $this->status = false;
        $this->programName = null;
    }

    // Getters
    public function getID(): int
    {
        return $this->id;
    }

    public function getUserID(): int
    {
        return $this->userID;
    }

    public function getProgramID(): int
    {
        return $this->programID;
    }

    public function getUlsaID(): ?int
    {
        return $this->ulsaID;
    }

    public function getAdmissionFolio(): string
    {
        return $this->admissionFolio;
    }

    public function getAdmissionBlockNumber(): int
    {
        return $this->admissionBlockNumber;
    }

    public function getFirstName(): string
    {
        return $this->firstName;
    }

    public function getLastName(): string
    {
        return $this->lastName;
    }

    public function getFullName(): string
    {
        return $this->firstName . ' ' . $this->lastName;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getEmail2(): ?string
    {
        return $this->email2;
    }

    public function getMobilePhone(): string
    {
        return $this->mobilePhone;
    }

    public function getInterviewRequestDate(): string
    {
        return $this->interviewRequestDate;
    }

    public function getInterviewDateTime(): string
    {
        return $this->interviewDateTime;
    }

    public function getProgramCoordinatorApprovalFlag(): bool
    {
        return $this->programCoordinatorApprovalFlag;
    }

    public function getProgramCoordinatorDecision(): ?string
    {
        return $this->programCoordinatorDecision;
    }

    public function getCandidatePendingFlag(): bool
    {
        return $this->candidatePendingFlag;
    }

    public function getAdmissionsPendingFlag(): bool
    {
        return $this->admissionsPendingFlag;
    }

    public function getAdmissionsPendingDescription(): ?string
    {
        return $this->admissionsPendingDescription;
    }

    public function getRegistrarPendingFlag(): bool
    {
        return $this->registrarPendingFlag;
    }

    public function getRegistrarPendingDescription(): ?string
    {
        return $this->registrarPendingDescription;
    }

    public function getEngineeringFacultyPendingFlag(): bool
    {
        return $this->engineeringFacultyPendingFlag;
    }

    public function getEngineeringFacultyPendingDescription(): ?string
    {
        return $this->engineeringFacultyPendingDescription;
    }

    public function getGradChiefPendingFlag(): bool
    {
        return $this->gradChiefPendingFlag;
    }

    public function getGradChiefPendingDescription(): ?string
    {
        return $this->gradChiefPendingDescription;
    }

    public function getProgramCoordinatorPendingFlag(): bool
    {
        return $this->programCoordinatorPendingFlag;
    }

    public function getProgramCoordinatorPendingDescription(): ?string
    {
        return $this->programCoordinatorPendingDescription;
    }

    public function getStatus(): bool
    {
        return $this->status;
    }

    public function getProgramName(): ?string
    {
        return $this->programName;
    }

    // Setters con validación
    public function setFirstName(string $firstName): void
    {
        if (empty($firstName)) {
            throw new InvalidArgumentException('El nombre no puede estar vacío');
        }
        $this->firstName = trim($firstName);
    }

    public function setLastName(string $lastName): void
    {
        if (empty($lastName)) {
            throw new InvalidArgumentException('Los apellidos no pueden estar vacíos');
        }
        $this->lastName = trim($lastName);
    }

    public function setAdmissionFolio(string $folio): void
    {
        if (empty($folio)) {
            throw new InvalidArgumentException('El folio de admisión no puede estar vacío');
        }
        $this->admissionFolio = trim($folio);
    }

    public function setAdmissionBlockNumber(int $blockNumber): void
    {
        if ($blockNumber < 1 || $blockNumber > 5) {
            throw new InvalidArgumentException('El número de bloque debe estar entre 1 y 5');
        }
        $this->admissionBlockNumber = $blockNumber;
    }

    public function setEmail(string $email): void
    {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('El correo electrónico no es válido');
        }
        $this->email = strtolower(trim($email));
    }

    public function setEmail2(?string $email): void
    {
        if ($email !== null && !empty($email)) {
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new InvalidArgumentException('El correo electrónico 2 no es válido');
            }
            $this->email2 = strtolower(trim($email));
        } else {
            $this->email2 = null;
        }
    }

    public function setMobilePhone(string $phone): void
    {
        $phone = preg_replace('/[^0-9]/', '', $phone);
        if (strlen($phone) !== 10) {
            throw new InvalidArgumentException('El número de celular debe tener 10 dígitos');
        }
        $this->mobilePhone = $phone;
    }

    public function setProgramID(int $programID): void
    {
        if ($programID <= 0) {
            throw new InvalidArgumentException('El ID del programa no es válido');
        }
        $this->programID = $programID;
    }

    public function setInterviewRequestDate(string $date): void
    {
        if (empty($date)) {
            throw new InvalidArgumentException('La fecha de solicitud no puede estar vacía');
        }
        $this->interviewRequestDate = $date;
    }

    public function setInterviewDateTime(string $datetime): void
    {
        if (empty($datetime)) {
            throw new InvalidArgumentException(
                'La fecha y hora de entrevista no puede estar vacía',
            );
        }
        $this->interviewDateTime = $datetime;
    }

    public function setUlsaID(?int $ulsaID): void
    {
        if ($ulsaID !== null && ($ulsaID < 100000 || $ulsaID > 999999)) {
            throw new InvalidArgumentException('La clave ULSA debe ser un número de 6 dígitos');
        }
        $this->ulsaID = $ulsaID;
    }

    public function setProgramCoordinatorApprovalFlag(bool $flag): void
    {
        $this->programCoordinatorApprovalFlag = $flag;
    }

    public function setProgramCoordinatorDecision(?string $decision): void
    {
        $this->programCoordinatorDecision = $decision;
    }

    public function setCandidatePendingFlag(bool $flag): void
    {
        $this->candidatePendingFlag = $flag;
    }

    public function setAdmissionsPendingFlag(bool $flag): void
    {
        $this->admissionsPendingFlag = $flag;
    }

    public function setAdmissionsPendingDescription(?string $description): void
    {
        $this->admissionsPendingDescription = $description;
    }

    public function setRegistrarPendingFlag(bool $flag): void
    {
        $this->registrarPendingFlag = $flag;
    }

    public function setRegistrarPendingDescription(?string $description): void
    {
        $this->registrarPendingDescription = $description;
    }

    public function setEngineeringFacultyPendingFlag(bool $flag): void
    {
        $this->engineeringFacultyPendingFlag = $flag;
    }

    public function setEngineeringFacultyPendingDescription(?string $description): void
    {
        $this->engineeringFacultyPendingDescription = $description;
    }

    public function setGradChiefPendingFlag(bool $flag): void
    {
        $this->gradChiefPendingFlag = $flag;
    }

    public function setGradChiefPendingDescription(?string $description): void
    {
        $this->gradChiefPendingDescription = $description;
    }

    public function setProgramCoordinatorPendingFlag(bool $flag): void
    {
        $this->programCoordinatorPendingFlag = $flag;
    }

    public function setProgramCoordinatorPendingDescription(?string $description): void
    {
        $this->programCoordinatorPendingDescription = $description;
    }

    public function setStatus(bool $status): void
    {
        $this->status = $status;
    }

    public function setProgramName(?string $programName): void
    {
        $this->programName = $programName;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'userID' => $this->userID,
            'programID' => $this->programID,
            'ulsaID' => $this->ulsaID,
            'admissionFolio' => $this->admissionFolio,
            'admissionBlockNumber' => $this->admissionBlockNumber,
            'firstName' => $this->firstName,
            'lastName' => $this->lastName,
            'fullName' => $this->getFullName(),
            'email' => $this->email,
            'email2' => $this->email2,
            'mobilePhone' => $this->mobilePhone,
            'interviewRequestDate' => $this->interviewRequestDate,
            'interviewDateTime' => $this->interviewDateTime,
            'programCoordinatorApprovalFlag' => $this->programCoordinatorApprovalFlag,
            'programCoordinatorDecision' => $this->programCoordinatorDecision,
            'candidatePendingFlag' => $this->candidatePendingFlag,
            'admissionsPendingFlag' => $this->admissionsPendingFlag,
            'admissionsPendingDescription' => $this->admissionsPendingDescription,
            'registrarPendingFlag' => $this->registrarPendingFlag,
            'registrarPendingDescription' => $this->registrarPendingDescription,
            'engineeringFacultyPendingFlag' => $this->engineeringFacultyPendingFlag,
            'engineeringFacultyPendingDescription' => $this->engineeringFacultyPendingDescription,
            'gradChiefPendingFlag' => $this->gradChiefPendingFlag,
            'gradChiefPendingDescription' => $this->gradChiefPendingDescription,
            'programCoordinatorPendingFlag' => $this->programCoordinatorPendingFlag,
            'programCoordinatorPendingDescription' => $this->programCoordinatorPendingDescription,
            'status' => $this->status,
            'programName' => $this->programName,
        ];
    }

    public function getJSON(): array
    {
        return $this->toArray();
    }
}
