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
    $query = "SELECT LOWER(n.last_name) AS last_name, 
                LOWER(n.first_name) AS first_name, 
                s.ulsa_id, 
                LOWER(TRIM(p.career)) AS career, 
                s.email AS ulsa_email, 
                s.sed
              FROM student s
              JOIN name n ON s.name_id = n.id 
              JOIN program p ON s.program_id = p.id";
    $stmt = $db->prepare($query);
    $stmt->execute();

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        try {
            $student = new Student(
                $row['first_name'],
                $row['last_name'],
                $row['ulsa_id'],
                $row['career'],
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

    $query = "SELECT DISTINCT career FROM program WHERE LOWER(career) LIKE 'maestría%'";
    $stmt = $db->prepare($query);
    $stmt->execute();

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $programsM[] = ucfirst(strtoupper($row['career']));
    }
    return $programsM;
}

function getSpecialtyPrograms(): array
{
    $programsS = [];
    $db = getDatabaseConnection();

    $query = "SELECT DISTINCT career FROM program WHERE LOWER(career) LIKE 'especialidad%'";
    $stmt = $db->prepare($query);
    $stmt->execute();

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $programsS[] = ucfirst(strtoupper($row['career']));
    }
    return $programsS;
}