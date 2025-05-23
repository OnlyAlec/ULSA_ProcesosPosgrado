<?php

require_once VENDOR_DIR . '/autoload.php';
require_once INCLUDES_DIR . '/models/program.php';
require_once INCLUDES_DIR . '/models/student.php';
require_once INCLUDES_DIR . '/models/professor.php';
require_once INCLUDES_DIR . '/models/subject.php';

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
