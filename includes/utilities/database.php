<?php

require_once VENDOR_DIR . "/autoload.php";
require_once INCLUDES_DIR . "/models/program.php";
require_once INCLUDES_DIR . "/models/student.php";

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


/**
 * @return Student[]
 */
function getStudents()
{
    $studentsDB = [];
    $db = getDatabaseConnection();
    $query = "SELECT LOWER(n.last_name) AS last_name, 
                LOWER(n.first_name) AS first_name, 
                s.ulsa_id, 
                LOWER(TRIM(p.career)) AS career, 
                s.email AS ulsa_email, 
                s.sed,
                s.afi
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
                $row['ulsa_email']
            );
            $student->setSed($row['sed']);
            $student->setAfi($row['afi']);
            $studentsDB[] = $student;
        } catch (InvalidArgumentException $e) {
            ErrorList::add($e->getMessage());
            continue;
        }
    }
    return $studentsDB;
}

function getStudentFromUlsaID($ID)
{
    $db = getDatabaseConnection();
    $query = "SELECT LOWER(n.last_name) AS last_name,
                LOWER(n.first_name) AS first_name,
                s.ulsa_id,
                LOWER(TRIM(p.career)) AS career,
                s.email AS ulsa_email,
                s.sed,
                s.afi
              FROM student s
              JOIN name n ON s.name_id = n.id
              JOIN program p ON s.program_id = p.id
              WHERE s.ulsa_id = :ulsa_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':ulsa_id', $ID);
    $stmt->execute();

    $res = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($res === false) {
        return null;
    }

    try {
        $student = new Student(
            $res['first_name'],
            $res['last_name'],
            $res['ulsa_id'],
            $res['career'],
            $res['ulsa_email']
        );
        $student->setSed($res['sed']);
        $student->setAfi($res['afi']);
        return $student;
    } catch (InvalidArgumentException $e) {
        ErrorList::add($e->getMessage());
        return null;
    }
}

function getMastersPrograms(): array
{
    $programsM = [];
    $db = getDatabaseConnection();

    $query = "SELECT DISTINCT id, career FROM program WHERE LOWER(career) LIKE 'maestría%'";
    $stmt = $db->prepare($query);
    $stmt->execute();

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $programsM[] = new Program($row['id'], $row['career']);
    }
    return $programsM;
}

function getSpecialtyPrograms(): array
{
    $programsS = [];
    $db = getDatabaseConnection();

    $query = "SELECT DISTINCT id, career FROM program WHERE LOWER(career) LIKE 'especialidad%'";
    $stmt = $db->prepare($query);
    $stmt->execute();

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $programsS[] = new Program($row['id'], $row['career']);
    }
    return $programsS;
}

/**
 * @return Program[]
 */
function getPrograms(): array
{
    $programDB = [];
    $db = getDatabaseConnection();

    $query = "SELECT * FROM program";
    $stmt = $db->prepare($query);
    $stmt->execute();

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $programDB[] = new Program($row['id'], $row['career']);
    }
    return $programDB;
}

function getProgramByID(int $id): Program
{
    $db = getDatabaseConnection();

    $query = "SELECT * FROM program WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $id);
    $stmt->execute();

    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return new Program($row['id'], $row['career']);
}

function getProgramByName(string $name): Program
{
    $db = getDatabaseConnection();

    $query = "SELECT * FROM program WHERE career = :name";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':name', $name);
    $stmt->execute();

    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return new Program($row['id'], $row['career']);
}

function getConfig(string $type)
{
    $db = getDatabaseConnection();

    $query = "SELECT data FROM configs WHERE type LIKE '$type%'";
    $stmt = $db->prepare($query);
    $stmt->execute();

    $res = $stmt->fetch(PDO::FETCH_ASSOC);
    return $res['data'] ?? "";
}

//^ INSERTS

//^ UPDATES
function updateStudentFieldBoolean($id, $field, $value)
{
    try {
        $db = getDatabaseConnection();
        $value = (int) $value;

        $query = "UPDATE student
                  SET $field = :value
                  WHERE ulsa_id = :ulsa_id";

        $stmt = $db->prepare($query);
        $stmt->bindParam(':value', $value);
        $stmt->bindParam(':ulsa_id', $id);

        $stmt->execute();

        return $stmt->rowCount();
    } catch (PDOException $e) {
        echo "Error de conexión! ";
        print_r($e->getMessage());
        exit();
    }
}

function updateConfig(string $type, $value)
{
    try {
        $db = getDatabaseConnection();

        $querySelect = "SELECT * FROM configs
                        WHERE type LIKE :type";
        $queryUpdate = "UPDATE configs
                        SET data = :value
                        WHERE type LIKE :type";
        $queryInsert = "INSERT INTO configs (type, data)
                        VALUES (:type, :value)";

        $stmt = $db->prepare($querySelect);
        $stmt->bindParam(':type', $type);
        $stmt->execute();

        $stmt = ($stmt->rowCount() === 0) ? $db->prepare($queryInsert) : $db->prepare($queryUpdate);
        $stmt->bindParam(':value', $value);
        $stmt->bindParam(':type', $type);
        $stmt->execute();

        return $stmt->rowCount();
    } catch (PDOException $e) {
        error_log("Database error in updateConfig: " . $e->getMessage());
        throw new Exception("Failed to update configuration");
    } catch (Exception $e) {
        error_log($e->getMessage());
        throw $e;
    }
}
