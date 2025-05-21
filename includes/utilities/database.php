<?php

require_once VENDOR_DIR . '/autoload.php';
require_once INCLUDES_DIR . '/models/program.php';
require_once INCLUDES_DIR . '/models/student.php';

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
        $db->exec('DELETE FROM student');
        $db->exec('DELETE FROM name');
        $db->exec('DELETE FROM program');
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
