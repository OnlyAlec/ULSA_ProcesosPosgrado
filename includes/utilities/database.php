<?php
require_once VENDOR_DIR . "/autoload.php";

$dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__, 2));
$dotenv->load();

function getDatabaseConnection()
{
    static $connection = null;

    if ($connection === null) {
        try {
            $connection = new PDO(
                'pgsql:host=' . $_ENV['DB_HOST'] . ';dbname=' . $_ENV['DB_NAME'],
                $_ENV['DB_USER'],
                $_ENV['DB_PWD']
            );
        } catch (PDOException $e) {
            echo "Error de conexión! ";
            print_r($e->getMessage());
            exit();
        }
    }
    return $connection;
}

function getStudents()
{
    $studentsDB = [];
    $db = getDatabaseConnection();
    $query = "SELECT LOWER(paternal_surname) AS paternal_surname, 
                LOWER(maternal_surname) AS maternal_surname, 
                LOWER(first_name) AS first_name, 
                ulsa_id, 
                LOWER(TRIM(type_desc)) AS type_desc, 
                LOWER(TRIM(area)) AS area, 
                ulsa_email, 
                sed
              FROM student 
              JOIN contact ON student.contact_id = contact.id 
              JOIN name ON student.name_id = name.id 
              JOIN program ON student.program_id = program.id";
    $stmt = $db->prepare($query);
    $stmt->execute();

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        try {
            $student = new Student(
                $row['first_name'],
                $row['maternal_surname'],
                $row['paternal_surname'],
                $row['ulsa_id'],
                $row['type_desc'],
                $row['area'],
            );
            $student->setEmail($row['ulsa_email']);
            $student->setSed($row['sed']);
            $studentsDB[] = $student;
        } catch (InvalidArgumentException $e) {
            ErrorList::add($e->getMessage());
            continue;
        }
    }
    return $studentsDB;
}

function getMastersPrograms(): array
{
    $programsM = [];
    $db = getDatabaseConnection();

    $query = "SELECT DISTINCT area FROM program WHERE LOWER(type_desc) = 'maestría'";
    $stmt = $db->prepare($query);
    $stmt->execute();

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $programsM[] = ucfirst(strtoupper($row['area'])); 
    }
    return $programsM;
}

function getSpecialtyPrograms(): array
{
    $programsS = [];
    $db = getDatabaseConnection();

    $query = "SELECT DISTINCT area FROM program WHERE LOWER(type_desc) = 'especialidad'";
    $stmt = $db->prepare($query);
    $stmt->execute();

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $programsS[] = ucfirst(strtoupper($row['area'])); 
    }
    return $programsS;
}