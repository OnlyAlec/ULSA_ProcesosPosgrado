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
        } catch (\PDOException $e) {
            throw new \RuntimeException("Error in connection:". $e->getMessage());
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
    $query = "SELECT s.id,
                LOWER(n.last_name) AS last_name, 
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
                $row['ulsa_email'],
                $row['id'],
            );
            $student->setSed($row['sed']);
            $student->setAfi($row['afi']);
            $studentsDB[] = $student;
        } catch (InvalidArgumentException $e) {
            ErrorList::add($e->getMessage());
            continue;
        }
    }

    if (count($studentsDB) > 0) {
        return $studentsDB;
    }
    ErrorList::add("No students found");
    return [];
}

function getStudentByUlsaID($ID)
{
    try {
        $db = getDatabaseConnection();
        $query = "SELECT s.id,
                LOWER(n.last_name) AS last_name,
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
            ErrorList::add("No student found with ID $ID");
            return false;
        }

        $student = new Student(
            $res['first_name'],
            $res['last_name'],
            $res['ulsa_id'],
            $res['career'],
            $res['ulsa_email'],
            $res['id']
        );
        $student->setSed($res['sed']);
        $student->setAfi($res['afi']);
        return $student;
    } catch (\PDOException $e) {
        throw new \RuntimeException("Error getting student by Ulsa ID:". $e->getMessage());
    } catch (\InvalidArgumentException $e) {
        ErrorList::add($e->getMessage());
        return false;
    }
}

function getStudentByID($ID)
{
    try {
        $db = getDatabaseConnection();
        $query = "SELECT s.id,
                LOWER(n.last_name) AS last_name,
                LOWER(n.first_name) AS first_name,
                s.ulsa_id,
                LOWER(TRIM(p.career)) AS career,
                s.email AS ulsa_email,
                s.sed,
                s.afi
              FROM student s
              JOIN name n ON s.name_id = n.id
              JOIN program p ON s.program_id = p.id
              WHERE s.id = :ID";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':ID', $ID);
        $stmt->execute();
        $res = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($res === false) {
            return null;
        }

        $student = new Student(
            $res['first_name'],
            $res['last_name'],
            $res['ulsa_id'],
            $res['career'],
            $res['ulsa_email'],
            $res['id']
        );
        $student->setSed($res['sed']);
        $student->setAfi($res['afi']);
        return $student;
    } catch (\PDOException $e) {
        throw new \RuntimeException("Error getting student by ID:" . $e->getMessage());
    } catch (\InvalidArgumentException $e) {
        ErrorList::add($e->getMessage());
        return null;
    }
}

// !FIXME: Catch errors
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

// !FIXME: Catch errors
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

// !FIXME: Catch errors
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

// !FIXME: Catch errors
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

// !FIXME: Catch errors
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

// !FIXME: Catch errors
function getConfig(string $type)
{
    $db = getDatabaseConnection();

    $query = "SELECT data FROM configs WHERE type LIKE '$type%'";
    $stmt = $db->prepare($query);
    $stmt->execute();

    $res = $stmt->fetch(PDO::FETCH_ASSOC);
    return $res['data'] ?? "";
}

function getToken(int $studentID)
{
    try {
        $db = getDatabaseConnection();

        $query = "SELECT token
                  FROM email_token 
                  WHERE student_id = :studentID;";

        $stmt = $db->prepare($query);
        $stmt->bindParam(':studentID', $studentID);
        $stmt->execute();
        $res = $stmt->fetch(PDO::FETCH_ASSOC);

        return $res["token"] ?? "";
    } catch (\PDOException $e) {
        throw new \RuntimeException("Error getting token by student ID: {$e->getMessage()}");
    }
}

function getStudentIDByToken(string $token)
{
    try {
        $db = getDatabaseConnection();

        $query = "SELECT student_id
                  FROM email_token 
                  WHERE token = :token;";

        $stmt = $db->prepare($query);
        $stmt->bindParam(':token', $token);
        $stmt->execute();
        $res = $stmt->fetch(PDO::FETCH_ASSOC);

        return $res["student_id"] ?? "";
    } catch (PDOException $e) {
        throw new \RuntimeException("Error getting student by token: {$e->getMessage()}");
    }
}

//^ INSERTS
function insertToken(int $studentID, string $token)
{
    try {
        $db = getDatabaseConnection();
        $query = "INSERT INTO email_token (student_id, token)
                  VALUES  (:studentID, :token)";
        $stmt = $db->prepare($query);
        $stmt->bindParam(":studentID", $studentID);
        $stmt->bindParam(":token", $token);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            return true;
        }
        return false;
    } catch (\PDOException $e) {
        throw new \RuntimeException("Error create token: {$e->getMessage()}");
    }
}

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

        if ($stmt->rowCount() > 0) {
            return true;
        }
        return false;
    } catch (\PDOException $e) {
        throw new \RuntimeException("Error update student bool field: {$e->getMessage()}");
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

        if ($stmt->rowCount() > 0) {
            return true;
        }
        return false;
    } catch (\PDOException $e) {
        throw new \RuntimeException("Error update config: {$e->getMessage()}");
    }
}

function updateToken(int $studentID, string $token)
{
    try {
        $db = getDatabaseConnection();

        $query = "UPDATE email_token
                  SET token = :token
                  WHERE student_id = :studentID;";

        $stmt = $db->prepare($query);
        $stmt->bindParam(':token', $token);
        $stmt->bindParam(':studentID', $studentID);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            return true;
        }
        return false;
    } catch (\PDOException $e) {
        throw new \RuntimeException("Error update token: {$e->getMessage()}");
    }
}
