<?php

require_once VENDOR_DIR . '/autoload.php';
require_once INCLUDES_DIR . '/models/program.php';
require_once INCLUDES_DIR . '/models/student.php';
require_once INCLUDES_DIR . '/models/professor.php';
require_once INCLUDES_DIR . '/models/subject.php';
require_once INCLUDES_DIR . '/models/candidate.php';

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
                $_ENV['DB_PWD'],
            );
        } catch (\PDOException $e) {
            throw new \RuntimeException('Error in connection:' . $e->getMessage());
        }
    }
    return $connection;
}

/**
 * @return Student[]
 */
function getStudents()
{
    try {
        $studentsDB = [];
        $db = getDatabaseConnection();
        $query = 'SELECT s.id,
                    LOWER(usr.last_name) AS last_name, 
                    LOWER(usr.first_name) AS first_name, 
                    usr.ulsa_id, 
                    LOWER(TRIM(p.career)) AS career, 
                    usr.email AS ulsa_email, 
                    s.sed,
                    s.afi
                FROM public.user usr
                JOIN student s ON s.user_id = usr.id 
                JOIN program p ON s.program_id = p.id';
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
            } catch (\InvalidArgumentException $e) {
                ErrorList::add($e->getMessage());
                continue;
            }
        }

        if (count($studentsDB) > 0) {
            return $studentsDB;
        }

        ErrorList::add('No students found');
        return [];
    } catch (\PDOException $e) {
        throw new \RuntimeException("Error al obtener estudiantes: {$e->getMessage()}");
    } catch (\Exception $e) {
        ErrorList::add("Error inesperado al obtener estudiantes: {$e->getMessage()}");
        return [];
    }
}

/**
 * @return Student | null
 */
function getStudentByUlsaID($ID): Student|null
{
    try {
        $db = getDatabaseConnection();
        $query = 'SELECT s.id,
                LOWER(usr.last_name) AS last_name,
                LOWER(usr.first_name) AS first_name,
                usr.ulsa_id,
                LOWER(TRIM(p.career)) AS career,
                usr.email AS ulsa_email,
                s.sed,
                s.afi
              FROM student s
              JOIN public.user usr ON s.user_id = usr.id
              JOIN program p ON s.program_id = p.id
              WHERE usr.ulsa_id = :ulsa_id';
        $stmt = $db->prepare($query);
        $stmt->bindParam(':ulsa_id', $ID);
        $stmt->execute();

        $res = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($res === false) {
            ErrorList::add("No student found with ID $ID");
            return null;
        }

        $student = new Student(
            $res['first_name'],
            $res['last_name'],
            $res['ulsa_id'],
            $res['career'],
            $res['ulsa_email'],
            $res['id'],
        );
        $student->setSed($res['sed']);
        $student->setAfi($res['afi']);
        return $student;
    } catch (\PDOException $e) {
        throw new \RuntimeException('Error getting student by Ulsa ID:' . $e->getMessage());
    } catch (\InvalidArgumentException $e) {
        ErrorList::add($e->getMessage());
        return null;
    }
}

/**
 * @return Student | null
 */
function getStudentByID($ID): Student|null
{
    try {
        $db = getDatabaseConnection();
        $query = 'SELECT s.id,
                LOWER(usr.last_name) AS last_name,
                LOWER(usr.first_name) AS first_name,
                usr.ulsa_id,
                LOWER(TRIM(p.career)) AS career,
                usr.email AS ulsa_email,
                s.sed,
                s.afi
              FROM student s
              JOIN public.user usr ON s.user_id = usr.id
              JOIN program p ON s.program_id = p.id
              WHERE s.id = :ID';
        $stmt = $db->prepare($query);
        $stmt->bindParam(':ID', $ID);
        $stmt->execute();
        $res = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($res === false) {
            ErrorList::add("No student found with ID: $ID");
            return null;
        }

        $student = new Student(
            $res['first_name'],
            $res['last_name'],
            $res['ulsa_id'],
            $res['career'],
            $res['ulsa_email'],
            $res['id'],
        );
        $student->setSed($res['sed']);
        $student->setAfi($res['afi']);
        return $student;
    } catch (\PDOException $e) {
        throw new \RuntimeException('Error getting student by ID:' . $e->getMessage());
    } catch (\InvalidArgumentException $e) {
        ErrorList::add($e->getMessage());
        return null;
    }
}

/**
 * @return Program[] | []
 */
function getMastersPrograms(): array
{
    try {
        $programsM = [];
        $db = getDatabaseConnection();

        $query = "SELECT DISTINCT id, career FROM program WHERE LOWER(career) LIKE 'maestría%'";
        $stmt = $db->prepare($query);
        $stmt->execute();

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $programsM[] = new Program($row['id'], $row['career']);
        }

        if (empty($programsM)) {
            ErrorList::add('No master programs found');
        }

        return $programsM;
    } catch (\PDOException $e) {
        throw new \RuntimeException("Error getting master programs: {$e->getMessage()}");
    } catch (\Exception $e) {
        ErrorList::add($e->getMessage());
        return [];
    }
}

/**
 * @return Program[] | []
 */
function getSpecialtyPrograms(): array
{
    try {
        $programsS = [];
        $db = getDatabaseConnection();

        $query = "SELECT DISTINCT id, career FROM program WHERE LOWER(career) LIKE 'especialidad%'";
        $stmt = $db->prepare($query);
        $stmt->execute();

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $programsS[] = new Program($row['id'], $row['career']);
        }

        if (empty($programsS)) {
            ErrorList::add('No specialty programs found');
        }

        return $programsS;
    } catch (\PDOException $e) {
        throw new \RuntimeException("Error getting specialty programs: {$e->getMessage()}");
    } catch (\Exception $e) {
        ErrorList::add($e->getMessage());
        return [];
    }
}

/**
 * @return Program[] | []
 */
function getPrograms(): array
{
    try {
        $programDB = [];
        $db = getDatabaseConnection();

        $query = 'SELECT * FROM program';
        $stmt = $db->prepare($query);
        $stmt->execute();

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $programDB[] = new Program($row['id'], $row['career']);
        }

        if (empty($programDB)) {
            ErrorList::add('No programs found');
        }

        return $programDB;
    } catch (\PDOException $e) {
        throw new \RuntimeException("Error getting programs: {$e->getMessage()}");
    } catch (\Exception $e) {
        ErrorList::add($e->getMessage());
        return [];
    }
}

/**
 * @return Program[] | null
 */
function getProgramByID(int $id): Program|null
{
    try {
        $db = getDatabaseConnection();

        $query = 'SELECT * FROM program WHERE id = :id';
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row === false) {
            ErrorList::add("No program found with ID: $id");
            return null;
        }

        return new Program($row['id'], $row['career']);
    } catch (\PDOException $e) {
        throw new \RuntimeException("Error getting program by ID: {$e->getMessage()}");
    } catch (\Exception $e) {
        ErrorList::add($e->getMessage());
        return null;
    }
}

/**
 * @return Program | null
 */
function getProgramByName(string $name): Program|null
{
    try {
        $db = getDatabaseConnection();

        $query = 'SELECT * FROM program WHERE career = :name';
        $stmt = $db->prepare($query);
        $stmt->bindParam(':name', $name);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row === false) {
            // Si no se encuentra un programa con ese nombre
            ErrorList::add("No program found with name: $name");
            return null;
        }

        return new Program($row['id'], $row['career']);
    } catch (\PDOException $e) {
        throw new \RuntimeException("Error getting program by name: {$e->getMessage()}");
    } catch (\Exception $e) {
        ErrorList::add($e->getMessage());
        return null;
    }
}

/**
 * @return string | null
 */
function getConfig(string $type): string|null
{
    try {
        $db = getDatabaseConnection();

        $query = 'SELECT data FROM configs WHERE type LIKE :type';
        $stmt = $db->prepare($query);
        $stmt->bindParam(':type', $type);
        $stmt->execute();

        $res = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($res === false) {
            ErrorList::add("No config found for type: $type");
            return null;
        }
        return $res['data'];
    } catch (\PDOException $e) {
        throw new \RuntimeException("Error getting config: {$e->getMessage()}");
    } catch (\Exception $e) {
        ErrorList::add($e->getMessage());
        return null;
    }
}

/**
 * @return string | null
 */
function getToken(int $userID): string|null
{
    try {
        $db = getDatabaseConnection();

        $query = 'SELECT token
                  FROM email_token 
                  WHERE user_id = :userID;';

        $stmt = $db->prepare($query);
        $stmt->bindParam(':userID', $userID);
        $stmt->execute();
        $res = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($res === false) {
            ErrorList::add("No token found for user ID: $userID");
            return null;
        }

        return $res['token'];
    } catch (\PDOException $e) {
        throw new \RuntimeException("Error getting token by student ID: {$e->getMessage()}");
    } catch (\Exception $e) {
        ErrorList::add("Error inesperado al obtener token: {$e->getMessage()}");
        return null;
    }
}

/**
 * @return string | null
 */
function getStudentIDByToken(string $token): string|null
{
    try {
        $db = getDatabaseConnection();

        $query = 'SELECT user_id
                  FROM email_token 
                  WHERE token = :token;';

        $stmt = $db->prepare($query);
        $stmt->bindParam(':token', $token);
        $stmt->execute();
        $res = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($res === false) {
            ErrorList::add("No user found with token: $token");
            return null;
        }

        return $res['user_id'];
    } catch (\PDOException $e) {
        throw new \RuntimeException("Error getting student by token: {$e->getMessage()}");
    } catch (\Exception $e) {
        ErrorList::add("Error inesperado al obtener estudiante por token: {$e->getMessage()}");
        return null;
    }
}

//^ INSERTS
/**
 * @return int | bool
 */
function insertToken(int $userID, string $token, bool $returnID = false): int|bool
{
    try {
        $db = getDatabaseConnection();
        $db->beginTransaction();
        $query = 'INSERT INTO email_token (user_id, token)
                  VALUES  (:userID, :token)
                  RETURNING id';
        $stmt = $db->prepare($query);
        $stmt->bindParam(':userID', $userID);
        $stmt->bindParam(':token', $token);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            $db->commit();
            return $returnID ? $db->lastInsertId() : true;
        }

        $db->rollBack();
        ErrorList::add("No se pudo agregar el token para el usuario ID: $userID");
        return false;
    } catch (\PDOException $e) {
        $db->rollBack();
        throw new \RuntimeException("Error create token: {$e->getMessage()}");
    } catch (\Exception $e) {
        $db->rollBack();
        ErrorList::add("Error inesperado al crear token: {$e->getMessage()}");
        return false;
    }
}

/**
 * @return int | bool
 */
function insertProgram(string $name, bool $returnID = false): int|bool
{
    try {
        $db = getDatabaseConnection();
        $db->beginTransaction();
        $query = 'INSERT INTO program (career)
                  VALUES (:career)
                  RETURNING id';
        $stmt = $db->prepare($query);
        $stmt->bindParam(':career', $name);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            $db->commit();
            return $returnID ? $db->lastInsertId() : true;
        }

        $db->rollBack();
        ErrorList::add("No se pudo agregar el programa: $name");
        return false;
    } catch (\PDOException $e) {
        $db->rollBack();
        throw new \RuntimeException("Error create program: {$e->getMessage()}");
    } catch (\Exception $e) {
        $db->rollBack();
        ErrorList::add("Error inesperado al crear programa: {$e->getMessage()}");
        return false;
    }
}

/**
 * @return int | bool
 */
function insertUser(
    int $ulsaID,
    string $firstname,
    string $lastname,
    string $email,
    bool $returnID = false,
): int|bool {
    try {
        $db = getDatabaseConnection();
        $db->beginTransaction();
        $query = 'INSERT INTO public.user (ulsa_id, first_name, last_name, email)
                  VALUES (:ulsa_id, :first_name, :last_name, :email)
                  RETURNING id';
        $stmt = $db->prepare($query);
        $stmt->bindParam(':ulsa_id', $ulsaID);
        $stmt->bindParam(':first_name', $firstname);
        $stmt->bindParam(':last_name', $lastname);
        $stmt->bindParam(':email', $email);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            $db->commit();
            return $returnID ? $db->lastInsertId() : true;
        }

        $db->rollBack();
        ErrorList::add("No se pudo agregar al usuario: $firstname $lastname");
        return false;
    } catch (\PDOException $e) {
        $db->rollBack();
        throw new \RuntimeException("Error create user: {$e->getMessage()}");
    } catch (\Exception $e) {
        $db->rollBack();
        ErrorList::add("Error inesperado al crear usuario: {$e->getMessage()}");
        return false;
    }
}

/**
 * @return int | bool
 */
function insertStudent(int $userID, int $programID, bool $returnID = false): int|bool
{
    try {
        $db = getDatabaseConnection();
        $db->beginTransaction();
        $query = 'INSERT INTO student (user_id, program_id)
                  VALUES (:user_id, :program_id)
                  RETURNING id';
        $stmt = $db->prepare($query);
        $stmt->bindParam(':user_id', $userID);
        $stmt->bindParam(':program_id', $programID);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            $db->commit();
            return $returnID ? $db->lastInsertId() : true;
        }

        $db->rollBack();
        ErrorList::add("No se pudo agregar al estudiante con ID: $userID");
        return false;
    } catch (\PDOException $e) {
        $db->rollBack();
        throw new \RuntimeException("Error create student: {$e->getMessage()}");
    } catch (\Exception $e) {
        $db->rollBack();
        ErrorList::add("Error inesperado al crear estudiante: {$e->getMessage()}");
        return false;
    }
}

//^ UPDATES
/**
 * @return bool
 */
function updateStudentFieldBoolean($id, $field, $value): bool
{
    try {
        $db = getDatabaseConnection();
        $value = (int) $value;

        $query = "UPDATE student s
                  SET $field = :value
                  FROM public.user usr
                  WHERE s.user_id = usr.id AND usr.ulsa_id = :ulsa_id";

        $stmt = $db->prepare($query);
        $stmt->bindParam(':value', $value);
        $stmt->bindParam(':ulsa_id', $id);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            return true;
        }

        ErrorList::add("No se actualizó ningún registro con ULSA ID: $id");
        return false;
    } catch (\PDOException $e) {
        throw new \RuntimeException(
            "Error al actualizar campo booleano de estudiante: {$e->getMessage()}",
        );
    } catch (\Exception $e) {
        ErrorList::add("Error inesperado al actualizar campo: {$e->getMessage()}");
        return false;
    }
}

/**
 * @return bool
 */
function updateConfig(string $type, $value): bool
{
    try {
        $db = getDatabaseConnection();

        $querySelect = 'SELECT * FROM configs
                        WHERE type LIKE :type';
        $queryUpdate = 'UPDATE configs
                        SET data = :value
                        WHERE type LIKE :type';
        $queryInsert = 'INSERT INTO configs (type, data)
                        VALUES (:type, :value)';

        $stmt = $db->prepare($querySelect);
        $stmt->bindParam(':type', $type);
        $stmt->execute();

        $stmt = $stmt->rowCount() === 0 ? $db->prepare($queryInsert) : $db->prepare($queryUpdate);
        $stmt->bindParam(':value', $value);
        $stmt->bindParam(':type', $type);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            return true;
        }

        ErrorList::add("No se pudo actualizar la configuración: $type");
        return false;
    } catch (\PDOException $e) {
        throw new \RuntimeException("Error update config: {$e->getMessage()}");
    } catch (\Exception $e) {
        ErrorList::add("Error inesperado al actualizar configuración: {$e->getMessage()}");
        return false;
    }
}

/**
 * @return bool
 */
function updateToken(int $userID, string $token): bool
{
    try {
        $db = getDatabaseConnection();

        $query = 'UPDATE email_token
                  SET token = :token
                  WHERE user_id = :userID;';

        $stmt = $db->prepare($query);
        $stmt->bindParam(':token', $token);
        $stmt->bindParam(':userID', $userID);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            return true;
        }
        ErrorList::add("No se pudo actualizar el token para el usuario ID: $userID");
        return false;
    } catch (\PDOException $e) {
        throw new \RuntimeException("Error update token: {$e->getMessage()}");
    } catch (\Exception $e) {
        ErrorList::add("Error inesperado al actualizar token: {$e->getMessage()}");
        return false;
    }
}

//^ DELETES
function deleteStudent(int $ulsaID)
{
    try {
        $db = getDatabaseConnection();
        $query = 'DELETE FROM public.user
                  WHERE ulsa_id = (:ulsaId)';
        $stmt = $db->prepare($query);
        $stmt->bindParam(':ulsaId', $ulsaID);
        $stmt->execute();

        if ($stmt->rowCount() === 0) {
            return false;
        }

        return true;
    } catch (\PDOException $e) {
        throw new \RuntimeException("Error delete student: {$e->getMessage()}");
    } catch (\Exception $e) {
        ErrorList::add("Error inesperado al borrar estudiante: {$e->getMessage()}");
        return false;
    }
}

function deleteAllStudents()
{
    try {
        $db = getDatabaseConnection();
        $db->beginTransaction();
        $db->exec('DELETE FROM public.user WHERE id IN (SELECT user_id FROM student)');
        $db->commit();

        return true;
    } catch (\PDOException $e) {
        $db->rollBack();
        throw new \RuntimeException("Error wipe students: {$e->getMessage()}");
    } catch (\Exception $e) {
        $db->rollBack();
        ErrorList::add("Error inesperado al limpiar estudiantes: {$e->getMessage()}");
        return false;
    }
}

/**
 * @return Professor[]
 */
function getProfessors()
{
    $professorsDB = [];
    $db = getDatabaseConnection();
    $query = 'SELECT p.id,
                LOWER(u.last_name) AS last_name, 
                LOWER(u.first_name) AS first_name, 
                u.ulsa_id, 
                u.email AS ulsa_email 
              FROM professor p
              JOIN public.user u ON p.user_id = u.id';
    $stmt = $db->prepare($query);
    $stmt->execute();

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        try {
            $professor = new Professor(
                $row['first_name'],
                $row['last_name'],
                $row['ulsa_id'],
                $row['ulsa_email'],
                $row['id'],
            );
            $professorsDB[] = $professor;
        } catch (InvalidArgumentException $e) {
            ErrorList::add($e->getMessage());
            continue;
        }
    }

    if (count($professorsDB) > 0) {
        return $professorsDB;
    }
    ErrorList::add('No professors found');
    return [];
}

function getProfessorByUlsaID($ID)
{
    try {
        $db = getDatabaseConnection();
        $query = 'SELECT p.id,
                LOWER(u.last_name) AS last_name,
                LOWER(u.first_name) AS first_name,
                u.ulsa_id,
                u.email AS ulsa_email
              FROM professor p
              JOIN public.user u ON p.user_id = u.id
              WHERE u.ulsa_id = :ulsa_id';
        $stmt = $db->prepare($query);
        $stmt->bindParam(':ulsa_id', $ID);
        $stmt->execute();

        $res = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($res === false) {
            ErrorList::add("No professor found with ID $ID");
            return false;
        }

        $professor = new Professor(
            $res['first_name'],
            $res['last_name'],
            $res['ulsa_id'],
            $res['ulsa_email'],
            $res['id'],
        );
        return $professor;
    } catch (\PDOException $e) {
        throw new \RuntimeException('Error getting pofessor by Ulsa ID:' . $e->getMessage());
    } catch (\InvalidArgumentException $e) {
        ErrorList::add($e->getMessage());
        return false;
    }
}

function getProfessorByID($ID)
{
    try {
        $db = getDatabaseConnection();
        $query = 'SELECT p.id,
                LOWER(u.last_name) AS last_name,
                LOWER(u.first_name) AS first_name,
                u.ulsa_id,
                u.email AS ulsa_email
              FROM professor p
              JOIN public.user u ON p.user_id = u.id
              WHERE p.id = :ID';
        $stmt = $db->prepare($query);
        $stmt->bindParam(':ID', $ID);
        $stmt->execute();
        $res = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($res === false) {
            return null;
        }

        $professor = new Professor(
            $res['first_name'],
            $res['last_name'],
            $res['ulsa_id'],
            $res['ulsa_email'],
            $res['id'],
        );
        return $professor;
    } catch (\PDOException $e) {
        throw new \RuntimeException('Error getting professo by ID:' . $e->getMessage());
    } catch (\InvalidArgumentException $e) {
        ErrorList::add($e->getMessage());
        return null;
    }
}

function getProfessorSubjectsAndProgramsByUlsaID($ulsaID)
{
    try {
        $db = getDatabaseConnection();
        $query = 'SELECT s.name AS subject_name, p.career AS program_name, pr.id AS professor_id, s.id AS subject_id, p.id AS program_id
                  FROM program_subject ps
                  JOIN professor pr ON ps.professor_id = pr.id
                  JOIN public.user u ON pr.user_id = u.id
                  JOIN subject s ON ps.subject_id = s.id
                  JOIN program p ON ps.program_id = p.id
                  WHERE u.ulsa_id = :ulsa_id';
        $stmt = $db->prepare($query);
        $stmt->bindParam(':ulsa_id', $ulsaID);
        $stmt->execute();

        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if (empty($results)) {
            ErrorList::add("No subjects or programs found for professor with ULSA ID $ulsaID");
            return [];
        }
        return $results;
    } catch (\PDOException $e) {
        throw new \RuntimeException(
            'Error getting subjects and programs by ULSA ID: ' . $e->getMessage(),
        );
    }
}

function deleteProgramSubject($professorId, $subjectId, $programId)
{
    try {
        $db = getDatabaseConnection();
        $query =
            'DELETE FROM program_subject WHERE professor_id = :professor_id AND subject_id = :subject_id AND program_id = :program_id';
        $stmt = $db->prepare($query);
        $stmt->bindParam(':professor_id', $professorId);
        $stmt->bindParam(':subject_id', $subjectId);
        $stmt->bindParam(':program_id', $programId);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            return true;
        }
        return false;
    } catch (\PDOException $e) {
        throw new \RuntimeException('Error deleting program subject: ' . $e->getMessage());
    }
}

function getSubjects(): array
{
    $subjects = [];
    $db = getDatabaseConnection();

    $query = 'SELECT * FROM subject';
    $stmt = $db->prepare($query);
    $stmt->execute();

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $subjects[] = new Subject($row['id'], $row['name']);
    }
    return $subjects;
}

function addProgramSubject($professorId, $subjectId, $programId)
{
    try {
        $db = getDatabaseConnection();
        $query =
            'INSERT INTO program_subject (professor_id, subject_id, program_id) VALUES (:professor_id, :subject_id, :program_id)';
        $stmt = $db->prepare($query);
        $stmt->bindParam(':professor_id', $professorId);
        $stmt->bindParam(':subject_id', $subjectId);
        $stmt->bindParam(':program_id', $programId);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            return true;
        }
        return false;
    } catch (\PDOException $e) {
        throw new \RuntimeException('Error al asignar la materia al programa: ' . $e->getMessage());
    }
}

function getProgramSubjects()
{
    $db = getDatabaseConnection();
    $query = "SELECT ps.id, ps.has_signed, ps.will_be_absent, s.name AS subject_name, p.career AS program_name, 
                     CONCAT(u.first_name, ' ', u.last_name) AS professor_name
              FROM program_subject ps
              JOIN subject s ON ps.subject_id = s.id
              JOIN program p ON ps.program_id = p.id
              JOIN professor pr ON ps.professor_id = pr.id
              JOIN public.user u ON pr.user_id = u.id";
    $stmt = $db->prepare($query);
    $stmt->execute();

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function updateHasSigned($programSubjectId, $newState)
{
    try {
        $db = getDatabaseConnection();
        $query = 'UPDATE program_subject SET has_signed = :newState WHERE id = :programSubjectId';
        $stmt = $db->prepare($query);
        $stmt->bindParam(':newState', $newState, PDO::PARAM_BOOL);
        $stmt->bindParam(':programSubjectId', $programSubjectId, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->rowCount() > 0;
    } catch (\PDOException $e) {
        throw new \RuntimeException('Error updating has_signed: ' . $e->getMessage());
    }
}

function updateWillBeAbsent($programSubjectId, $newState)
{
    try {
        $db = getDatabaseConnection();
        $query =
            'UPDATE program_subject SET will_be_absent = :newState WHERE id = :programSubjectId';
        $stmt = $db->prepare($query);
        $stmt->bindParam(':newState', $newState, PDO::PARAM_BOOL);
        $stmt->bindParam(':programSubjectId', $programSubjectId, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->rowCount() > 0;
    } catch (\PDOException $e) {
        throw new \RuntimeException('Error updating will_be_absent: ' . $e->getMessage());
    }
}

function insertComment($programSubjectId, $comment, $author)
{
    try {
        $db = getDatabaseConnection();
        $query =
            'INSERT INTO comments (comment, author, program_subject_id) VALUES (:comment, :author, :programSubjectId)';
        $stmt = $db->prepare($query);
        $stmt->bindParam(':comment', $comment);
        $stmt->bindParam(':author', $author);
        $stmt->bindParam(':programSubjectId', $programSubjectId, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->rowCount() > 0;
    } catch (\PDOException $e) {
        throw new \RuntimeException('Error inserting comment: ' . $e->getMessage());
    }
}

function insertEvidence($programSubjectId, $path)
{
    try {
        $db = getDatabaseConnection();
        $query =
            'INSERT INTO evidence (directory_path, program_subject_id) VALUES (:directory_path, :programSubjectId)';
        $stmt = $db->prepare($query);
        $stmt->bindParam(':directory_path', $path);
        $stmt->bindParam(':programSubjectId', $programSubjectId, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->rowCount() > 0;
    } catch (\PDOException $e) {
        throw new \RuntimeException('Error inserting evidence: ' . $e->getMessage());
    }
}

function getCommentsAndEvidence($programSubjectId)
{
    try {
        $db = getDatabaseConnection();

        $queryComments = 'SELECT c.comment AS comment, c.author AS author
                          FROM comments c
                          WHERE c.program_subject_id = :programSubjectId';
        $stmtComments = $db->prepare($queryComments);
        $stmtComments->bindParam(':programSubjectId', $programSubjectId, PDO::PARAM_INT);
        $stmtComments->execute();

        $comments = [];
        while ($row = $stmtComments->fetch(PDO::FETCH_ASSOC)) {
            if ($row['comment'] !== null && $row['author'] !== null) {
                $comments[] = ['comment' => $row['comment'], 'author' => $row['author']];
            }
        }

        $queryEvidence = 'SELECT e.directory_path AS path
                          FROM evidence e
                          WHERE e.program_subject_id = :programSubjectId';
        $stmtEvidence = $db->prepare($queryEvidence);
        $stmtEvidence->bindParam(':programSubjectId', $programSubjectId, PDO::PARAM_INT);
        $stmtEvidence->execute();

        $evidence = [];
        while ($row = $stmtEvidence->fetch(PDO::FETCH_ASSOC)) {
            if ($row['path'] !== null) {
                $evidence[] = ['path' => $row['path'], 'name' => basename($row['path'])];
            }
        }

        return ['comments' => $comments, 'evidence' => $evidence];
    } catch (\PDOException $e) {
        throw new \RuntimeException('Error getting comments and evidence: ' . $e->getMessage());
    }
}

function deleteProfessorByUlsaId($ulsaId)
{
    try {
        $db = getDatabaseConnection();
        $stmt = $db->prepare('DELETE FROM public.user WHERE ulsa_id = (:ulsaId)');
        $stmt->bindParam(':ulsaId', $ulsaId);
        $stmt->execute();

        $stmt->rowCount() > 0;
    } catch (\PDOException $e) {
        throw new \RuntimeException("Error getting subject by ID: {$e->getMessage()}");
    } catch (\Exception $e) {
        ErrorList::add("Error inesperado al obtener materia: {$e->getMessage()}");
        return false;
    }
}

function getSubjectByID(int $id)
{
    try {
        $db = getDatabaseConnection();

        $query = 'SELECT * FROM subject WHERE id = :id';
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return new Subject($row['id'], $row['name']);
    } catch (\PDOException $e) {
        throw new \RuntimeException("Error get subject: {$e->getMessage()}");
    } catch (\Exception $e) {
        ErrorList::add("Error inesperado al obtener materia: {$e->getMessage()}");
        return null;
    }
}

function deleteAllProfessors()
{
    try {
        $db = getDatabaseConnection();
        $stmt = $db->prepare('DELETE FROM public.user WHERE id IN (SELECT user_id FROM professor)');
        $stmt->bindParam(':ulsaId', $ulsaID);
        $stmt->execute();

        return $stmt->rowCount() > 0;
    } catch (\PDOException $e) {
        throw new \RuntimeException("Error deleting professors: {$e->getMessage()}");
    } catch (\Exception $e) {
        ErrorList::add("Error inesperado al borrar profesores: {$e->getMessage()}");
        return false;
    }
}

//^ CANDIDATES FUNCTIONS

/**
 * @return Candidate[]
 */
function getCandidates(): array
{
    try {
        $candidatesDB = [];
        $db = getDatabaseConnection();
        $query = 'SELECT c.id,
                    c.admission_folio,
                    c.admission_block_number,
                    c.email as candidate_email,
                    c.mobile_phone,
                    c.interview_request_date,
                    c.interview_datetime,
                    c.program_coordinator_approval_flag,
                    c.program_coordinator_decision,
                    c.admissions_pending_flag,
                    c.admissions_pending_description,
                    c.registrar_pending_flag,
                    c.registrar_pending_description,
                    c.engineering_faculty_pending_flag,
                    c.engineering_faculty_pending_description,
                    c.grad_chief_pending_flag,
                    c.grad_chief_pending_description,
                    c.program_coordinator_pending_flag,
                    c.program_coordinator_pending_description,
                    c.status,
                    cd.description as status_description,
                    u.id as user_id,
                    u.ulsa_id,
                    LOWER(u.first_name) AS first_name,
                    LOWER(u.last_name) AS last_name,
                    u.email,
                    p.id as program_id,
                    p.career as program_name
                FROM candidate c
                JOIN public.user u ON c.user_id = u.id
                JOIN program p ON c.program_id = p.id
                LEFT JOIN candidatedescription cd ON c.status = cd.id
                ORDER BY c.admission_folio';
        $stmt = $db->prepare($query);
        $stmt->execute();

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            try {
                $candidate = new Candidate(
                    $row['first_name'],
                    $row['last_name'],
                    $row['admission_folio'],
                    $row['admission_block_number'],
                    $row['candidate_email'] ?: $row['email'],
                    $row['mobile_phone'],
                    $row['program_id'],
                    $row['interview_request_date'],
                    $row['interview_datetime'],
                    $row['id'],
                    $row['user_id'],
                    $row['ulsa_id'],
                    $row['candidate_email'] ? null : null
                );
                
                // Establecer todos los campos adicionales
                $candidate->setProgramCoordinatorApprovalFlag($row['program_coordinator_approval_flag']);
                $candidate->setProgramCoordinatorDecision($row['program_coordinator_decision']);
                $candidate->setAdmissionsPendingFlag($row['admissions_pending_flag']);
                $candidate->setAdmissionsPendingDescription($row['admissions_pending_description']);
                $candidate->setRegistrarPendingFlag($row['registrar_pending_flag']);
                $candidate->setRegistrarPendingDescription($row['registrar_pending_description']);
                $candidate->setEngineeringFacultyPendingFlag($row['engineering_faculty_pending_flag']);
                $candidate->setEngineeringFacultyPendingDescription($row['engineering_faculty_pending_description']);
                $candidate->setGradChiefPendingFlag($row['grad_chief_pending_flag']);
                $candidate->setGradChiefPendingDescription($row['grad_chief_pending_description']);
                $candidate->setProgramCoordinatorPendingFlag($row['program_coordinator_pending_flag']);
                $candidate->setProgramCoordinatorPendingDescription($row['program_coordinator_pending_description']);
                $candidate->setStatus($row['status']);
                $candidate->setStatusDescription($row['status_description']);
                $candidate->setProgramName($row['program_name']);
                
                $candidatesDB[] = $candidate;
            } catch (\InvalidArgumentException $e) {
                ErrorList::add($e->getMessage());
                continue;
            }
        }

        if (count($candidatesDB) > 0) {
            return $candidatesDB;
        }

        ErrorList::add('No se encontraron candidatos');
        return [];
    } catch (\PDOException $e) {
        throw new \RuntimeException("Error al obtener candidatos: {$e->getMessage()}");
    } catch (\Exception $e) {
        ErrorList::add("Error inesperado al obtener candidatos: {$e->getMessage()}");
        return [];
    }
}

/**
 * @return Candidate|null
 */
function getCandidateByAdmissionFolio(string $folio): ?Candidate
{
    try {
        $db = getDatabaseConnection();
        $query = 'SELECT c.id,
                    c.admission_folio,
                    c.admission_block_number,
                    c.email as candidate_email,
                    c.mobile_phone,
                    c.interview_request_date,
                    c.interview_datetime,
                    c.program_coordinator_approval_flag,
                    c.program_coordinator_decision,
                    c.admissions_pending_flag,
                    c.admissions_pending_description,
                    c.registrar_pending_flag,
                    c.registrar_pending_description,
                    c.engineering_faculty_pending_flag,
                    c.engineering_faculty_pending_description,
                    c.grad_chief_pending_flag,
                    c.grad_chief_pending_description,
                    c.program_coordinator_pending_flag,
                    c.program_coordinator_pending_description,
                    c.status,
                    cd.description as status_description,
                    u.id as user_id,
                    u.ulsa_id,
                    LOWER(u.first_name) AS first_name,
                    LOWER(u.last_name) AS last_name,
                    u.email,
                    p.id as program_id,
                    p.career as program_name
                FROM candidate c
                JOIN public.user u ON c.user_id = u.id
                JOIN program p ON c.program_id = p.id
                LEFT JOIN candidatedescription cd ON c.status = cd.id
                WHERE c.admission_folio = :folio';
        $stmt = $db->prepare($query);
        $stmt->bindParam(':folio', $folio);
        $stmt->execute();

        $res = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($res === false) {
            ErrorList::add("No se encontró candidato con folio: $folio");
            return null;
        }

        $candidate = new Candidate(
            $res['first_name'],
            $res['last_name'],
            $res['admission_folio'],
            $res['admission_block_number'],
            $res['candidate_email'] ?: $res['email'],
            $res['mobile_phone'],
            $res['program_id'],
            $res['interview_request_date'],
            $res['interview_datetime'],
            $res['id'],
            $res['user_id'],
            $res['ulsa_id']
        );
        
        // Establecer campos adicionales
        $candidate->setProgramCoordinatorApprovalFlag($res['program_coordinator_approval_flag']);
        $candidate->setProgramCoordinatorDecision($res['program_coordinator_decision']);
        $candidate->setAdmissionsPendingFlag($res['admissions_pending_flag']);
        $candidate->setAdmissionsPendingDescription($res['admissions_pending_description']);
        $candidate->setRegistrarPendingFlag($res['registrar_pending_flag']);
        $candidate->setRegistrarPendingDescription($res['registrar_pending_description']);
        $candidate->setEngineeringFacultyPendingFlag($res['engineering_faculty_pending_flag']);
        $candidate->setEngineeringFacultyPendingDescription($res['engineering_faculty_pending_description']);
        $candidate->setGradChiefPendingFlag($res['grad_chief_pending_flag']);
        $candidate->setGradChiefPendingDescription($res['grad_chief_pending_description']);
        $candidate->setProgramCoordinatorPendingFlag($res['program_coordinator_pending_flag']);
        $candidate->setProgramCoordinatorPendingDescription($res['program_coordinator_pending_description']);
        $candidate->setStatus($res['status']);
        $candidate->setStatusDescription($res['status_description']);
        $candidate->setProgramName($res['program_name']);
        
        return $candidate;
    } catch (\PDOException $e) {
        throw new \RuntimeException('Error al obtener candidato por folio: ' . $e->getMessage());
    } catch (\InvalidArgumentException $e) {
        ErrorList::add($e->getMessage());
        return null;
    }
}

/**
 * @return bool
 */
function insertCandidate(
    string $firstName,
    string $lastName,
    string $admissionFolio,
    int $admissionBlockNumber,
    string $email1,
    ?string $email2,
    string $mobilePhone,
    int $programID,
    string $interviewRequestDate,
    string $interviewDateTime,
    ?int $ulsaID = null
): bool {
    try {
        $db = getDatabaseConnection();
        $db->beginTransaction();
        
        // Primero crear o actualizar el usuario
        $userID = null;
        
        // Si tiene clave ULSA, buscar si ya existe el usuario
        if ($ulsaID !== null) {
            $queryCheck = 'SELECT id FROM public.user WHERE ulsa_id = :ulsa_id';
            $stmtCheck = $db->prepare($queryCheck);
            $stmtCheck->bindParam(':ulsa_id', $ulsaID);
            $stmtCheck->execute();
            $existingUser = $stmtCheck->fetch(PDO::FETCH_ASSOC);
            
            if ($existingUser) {
                $userID = $existingUser['id'];
                // Actualizar datos del usuario existente
                $queryUpdate = 'UPDATE public.user 
                               SET first_name = :first_name, 
                                   last_name = :last_name, 
                                   email = :email
                               WHERE id = :id';
                $stmtUpdate = $db->prepare($queryUpdate);
                $stmtUpdate->bindParam(':first_name', $firstName);
                $stmtUpdate->bindParam(':last_name', $lastName);
                $stmtUpdate->bindParam(':email', $email1);
                $stmtUpdate->bindParam(':id', $userID);
                $stmtUpdate->execute();
            }
        }
        
        // Si no existe o no tiene ULSA ID, crear nuevo usuario
        if ($userID === null) {
            $queryUser = 'INSERT INTO public.user (ulsa_id, first_name, last_name, email)
                         VALUES (:ulsa_id, :first_name, :last_name, :email)
                         RETURNING id';
            $stmtUser = $db->prepare($queryUser);
            $stmtUser->bindParam(':ulsa_id', $ulsaID);
            $stmtUser->bindParam(':first_name', $firstName);
            $stmtUser->bindParam(':last_name', $lastName);
            $stmtUser->bindParam(':email', $email1);
            $stmtUser->execute();
            
            $userID = $db->lastInsertId();
        }
        
        // Insertar candidato con status por defecto 1 (pendiente)
        $queryCandidate = 'INSERT INTO candidate (
                            user_id, program_id, admission_folio, 
                            admission_block_number, email, mobile_phone, 
                            interview_request_date, interview_datetime, status
                          ) VALUES (
                            :user_id, :program_id, :admission_folio,
                            :admission_block_number, :email, :mobile_phone,
                            :interview_request_date, :interview_datetime, 1
                          )';
        $stmtCandidate = $db->prepare($queryCandidate);
        $stmtCandidate->bindParam(':user_id', $userID);
        $stmtCandidate->bindParam(':program_id', $programID);
        $stmtCandidate->bindParam(':admission_folio', $admissionFolio);
        $stmtCandidate->bindParam(':admission_block_number', $admissionBlockNumber);
        $stmtCandidate->bindParam(':email', $email2); // email2 se guarda en candidate.email
        $stmtCandidate->bindParam(':mobile_phone', $mobilePhone);
        $stmtCandidate->bindParam(':interview_request_date', $interviewRequestDate);
        $stmtCandidate->bindParam(':interview_datetime', $interviewDateTime);
        $stmtCandidate->execute();
        
        if ($stmtCandidate->rowCount() > 0) {
            $db->commit();
            return true;
        }
        
        $db->rollBack();
        ErrorList::add("No se pudo agregar al candidato: $firstName $lastName");
        return false;
    } catch (\PDOException $e) {
        $db->rollBack();
        throw new \RuntimeException("Error al crear candidato: {$e->getMessage()}");
    } catch (\Exception $e) {
        $db->rollBack();
        ErrorList::add("Error inesperado al crear candidato: {$e->getMessage()}");
        return false;
    }
}

/**
 * @return bool
 */
function updateCandidateStatus($candidateID, int $newStatus): bool
{
    try {
        $db = getDatabaseConnection();
        $db->beginTransaction();
        
        // Obtener información del candidato y usuario
        $queryCandidate = 'SELECT c.user_id, c.program_id, c.status, u.ulsa_id 
                          FROM candidate c 
                          JOIN public.user u ON c.user_id = u.id 
                          WHERE c.id = :id';
        $stmtCandidate = $db->prepare($queryCandidate);
        $stmtCandidate->bindParam(':id', $candidateID);
        $stmtCandidate->execute();
        $candidateData = $stmtCandidate->fetch(PDO::FETCH_ASSOC);
        
        if (!$candidateData) {
            throw new \RuntimeException("No se encontró candidato con ID: $candidateID");
        }
        
        $oldStatus = (int)$candidateData['status'];
        $userID = $candidateData['user_id'];
        $programID = $candidateData['program_id'];
        $ulsaID = $candidateData['ulsa_id'];
        
        // VALIDACIÓN: Si se intenta cambiar a inscrito (status = 2) pero no tiene ULSA ID
        if ($newStatus === 2 && empty($ulsaID)) {
            $db->rollBack();
            throw new \RuntimeException("No se puede inscribir el candidato. Debe tener una Clave ULSA asignada.");
        }
        
        // Si el status cambia a inscrito (2), insertar en student
        if ($oldStatus !== 2 && $newStatus === 2) {
            // Verificar si ya existe el estudiante
            $queryCheckStudent = 'SELECT id FROM student WHERE user_id = :user_id';
            $stmtCheckStudent = $db->prepare($queryCheckStudent);
            $stmtCheckStudent->bindParam(':user_id', $userID);
            $stmtCheckStudent->execute();
            
            if ($stmtCheckStudent->rowCount() === 0) {
                $queryInsertStudent = 'INSERT INTO student (user_id, program_id) VALUES (:user_id, :program_id)';
                $stmtInsertStudent = $db->prepare($queryInsertStudent);
                $stmtInsertStudent->bindParam(':user_id', $userID);
                $stmtInsertStudent->bindParam(':program_id', $programID);
                $stmtInsertStudent->execute();
            }
        }
        // Si el status cambia de inscrito (2) a otro, eliminar de student
        elseif ($oldStatus === 2 && $newStatus !== 2) {
            $queryDeleteStudent = 'DELETE FROM student WHERE user_id = :user_id';
            $stmtDeleteStudent = $db->prepare($queryDeleteStudent);
            $stmtDeleteStudent->bindParam(':user_id', $userID);
            $stmtDeleteStudent->execute();
        }
        
        // Actualizar el status del candidato
        $queryUpdateStatus = 'UPDATE candidate SET status = :status WHERE id = :id';
        $stmtUpdateStatus = $db->prepare($queryUpdateStatus);
        $stmtUpdateStatus->bindParam(':status', $newStatus);
        $stmtUpdateStatus->bindParam(':id', $candidateID);
        $stmtUpdateStatus->execute();
        
        $db->commit();
        return true;
    } catch (\PDOException $e) {
        $db->rollBack();
        throw new \RuntimeException("Error al actualizar status del candidato: {$e->getMessage()}");
    } catch (\Exception $e) {
        $db->rollBack();
        throw new \RuntimeException($e->getMessage());
    }
}

/**
 * @return bool
 */
function updateCandidateField($candidateID, string $field, $value): bool
{
    try {
        $db = getDatabaseConnection();
        
        // Lista de campos permitidos
        $allowedFields = [
            'admission_folio', 'admission_block_number', 'email', 
            'mobile_phone', 'interview_request_date', 'interview_datetime',
            'program_coordinator_approval_flag', 'program_coordinator_decision',
            'admissions_pending_flag', 'admissions_pending_description', 
            'registrar_pending_flag', 'registrar_pending_description', 
            'engineering_faculty_pending_flag', 'engineering_faculty_pending_description',
            'grad_chief_pending_flag', 'grad_chief_pending_description',
            'program_coordinator_pending_flag', 'program_coordinator_pending_description',
            'status', 'program_id'
        ];
        
        // Campos que van en la tabla user
        $userFields = ['first_name', 'last_name', 'email', 'ulsa_id'];
        
        if (!in_array($field, $allowedFields) && !in_array($field, $userFields)) {
            throw new \RuntimeException("Campo no permitido: $field");
        }
        
        // Si es el campo status, manejar la lógica especial
        if ($field === 'status') {
            return updateCandidateStatus($candidateID, (int)$value);
        }
        
        // Si es un campo de user, actualizar en tabla user
        if (in_array($field, $userFields)) {
            return updateCandidateUserField($candidateID, $field, $value);
        }
        
        // Convertir valores boolean que vienen como cadenas
        $booleanFields = [
            'program_coordinator_approval_flag', 'admissions_pending_flag', 
            'registrar_pending_flag', 'engineering_faculty_pending_flag', 
            'grad_chief_pending_flag', 'program_coordinator_pending_flag'
        ];
        
        if (in_array($field, $booleanFields)) {
            // Convertir cadenas a boolean
            if (is_string($value)) {
                $value = filter_var($value, FILTER_VALIDATE_BOOLEAN);
            }
            $value = (bool)$value;
        }
        
        // LÓGICA ESPECIAL PARA PROGRAM_ID: Si el candidato está inscrito, también actualizar en student
        if ($field === 'program_id') {
            $db->beginTransaction();
            
            try {
                // Obtener información del candidato
                $queryCandidate = 'SELECT user_id, status FROM candidate WHERE id = :id';
                $stmtCandidate = $db->prepare($queryCandidate);
                $stmtCandidate->bindParam(':id', $candidateID);
                $stmtCandidate->execute();
                $candidateData = $stmtCandidate->fetch(PDO::FETCH_ASSOC);
                
                if (!$candidateData) {
                    throw new \RuntimeException("No se encontró candidato con ID: $candidateID");
                }
                
                // Actualizar en tabla candidate
                $queryUpdateCandidate = "UPDATE candidate SET $field = :value WHERE id = :id";
                $stmtUpdateCandidate = $db->prepare($queryUpdateCandidate);
                $stmtUpdateCandidate->bindParam(':value', $value);
                $stmtUpdateCandidate->bindParam(':id', $candidateID);
                $stmtUpdateCandidate->execute();
                
                // Si el candidato está inscrito (status = 2), también actualizar en student
                if ((int)$candidateData['status'] === 2) {
                    $queryUpdateStudent = 'UPDATE student SET program_id = :program_id WHERE user_id = :user_id';
                    $stmtUpdateStudent = $db->prepare($queryUpdateStudent);
                    $stmtUpdateStudent->bindParam(':program_id', $value);
                    $stmtUpdateStudent->bindParam(':user_id', $candidateData['user_id']);
                    $stmtUpdateStudent->execute();
                }
                
                $db->commit();
                return true;
            } catch (\Exception $e) {
                $db->rollBack();
                throw $e;
            }
        }
        
        // Actualizar en tabla candidate (para otros campos)
        $query = "UPDATE candidate SET $field = :value WHERE id = :id";
        $stmt = $db->prepare($query);
        
        // Si es un campo boolean, usar PDO::PARAM_BOOL
        if (in_array($field, $booleanFields)) {
            $stmt->bindParam(':value', $value, PDO::PARAM_BOOL);
        } else {
            $stmt->bindParam(':value', $value);
        }
        
        $stmt->bindParam(':id', $candidateID);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            return true;
        }
        
        ErrorList::add("No se actualizó ningún registro con ID: $candidateID");
        return false;
    } catch (\PDOException $e) {
        throw new \RuntimeException("Error al actualizar campo de candidato: {$e->getMessage()}");
    } catch (\Exception $e) {
        throw new \RuntimeException($e->getMessage());
    }
}

/**
 * @return bool
 */
function updateCandidateUserField($candidateID, string $field, $value): bool
{
    try {
        $db = getDatabaseConnection();
        
        // Lista de campos permitidos en la tabla user
        $allowedFields = ['first_name', 'last_name', 'email', 'ulsa_id'];
        
        if (!in_array($field, $allowedFields)) {
            throw new \RuntimeException("Campo no permitido: $field");
        }
        
        // Obtener el user_id del candidato
        $queryGetUser = 'SELECT user_id FROM candidate WHERE id = :id';
        $stmtGetUser = $db->prepare($queryGetUser);
        $stmtGetUser->bindParam(':id', $candidateID);
        $stmtGetUser->execute();
        $userData = $stmtGetUser->fetch(PDO::FETCH_ASSOC);
        
        if (!$userData) {
            throw new \RuntimeException("No se encontró candidato con ID: $candidateID");
        }
        
        $userID = $userData['user_id'];
        
        $query = "UPDATE public.user SET $field = :value WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':value', $value);
        $stmt->bindParam(':id', $userID);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            return true;
        }
        
        ErrorList::add("No se actualizó ningún registro de usuario");
        return false;
    } catch (\PDOException $e) {
        throw new \RuntimeException("Error al actualizar campo de usuario: {$e->getMessage()}");
    } catch (\Exception $e) {
        ErrorList::add("Error inesperado al actualizar campo: {$e->getMessage()}");
        return false;
    }
}

/**
 * @return bool
 */
function deleteCandidateByAdmissionFolio(string $folio): bool
{
    try {
        $db = getDatabaseConnection();
        $query = 'DELETE FROM public.user
                  WHERE id IN (SELECT user_id FROM candidate WHERE admission_folio = :folio)';
        $stmt = $db->prepare($query);
        $stmt->bindParam(':folio', $folio);
        $stmt->execute();
        
        if ($stmt->rowCount() === 0) {
            ErrorList::add("No se encontró candidato con folio: $folio");
            return false;
        }
        
        return true;
    } catch (\PDOException $e) {
        throw new \RuntimeException("Error al eliminar candidato: {$e->getMessage()}");
    } catch (\Exception $e) {
        ErrorList::add("Error inesperado al eliminar candidato: {$e->getMessage()}");
        return false;
    }
}

/**
 * @return array
 */
function getCandidateDescriptions(): array
{
    try {
        $db = getDatabaseConnection();
        $query = 'SELECT id, description FROM candidatedescription ORDER BY id';
        $stmt = $db->prepare($query);
        $stmt->execute();
        
        $descriptions = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $descriptions[] = [
                'id' => $row['id'],
                'description' => $row['description']
            ];
        }
        
        return $descriptions;
    } catch (\PDOException $e) {
        throw new \RuntimeException("Error al obtener descripciones de candidatos: {$e->getMessage()}");
    } catch (\Exception $e) {
        ErrorList::add("Error inesperado al obtener descripciones: {$e->getMessage()}");
        return [];
    }
}

/**
 * @return bool
 */
function insertCandidateEvidence($candidateID, $path): bool
{
    try {
        $db = getDatabaseConnection();
        $query = 'INSERT INTO evidence (directory_path, candidate_id) VALUES (:directory_path, :candidateID)';
        $stmt = $db->prepare($query);
        $stmt->bindParam(':directory_path', $path);
        $stmt->bindParam(':candidateID', $candidateID, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->rowCount() > 0;
    } catch (\PDOException $e) {
        throw new \RuntimeException('Error al insertar evidencia de candidato: ' . $e->getMessage());
    }
}

/**
 * @return array
 */
function getCandidateEvidence($candidateID): array
{
    try {
        $db = getDatabaseConnection();
        $query = 'SELECT e.id, e.directory_path AS path
                  FROM evidence e
                  WHERE e.candidate_id = :candidateID';
        $stmt = $db->prepare($query);
        $stmt->bindParam(':candidateID', $candidateID, PDO::PARAM_INT);
        $stmt->execute();

        $evidence = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            if ($row['path'] !== null) {
                $evidence[] = [
                    'id' => $row['id'],
                    'path' => $row['path'], 
                    'name' => basename($row['path'])
                ];
            }
        }

        return $evidence;
    } catch (\PDOException $e) {
        throw new \RuntimeException('Error al obtener evidencia de candidato: ' . $e->getMessage());
    }
}
