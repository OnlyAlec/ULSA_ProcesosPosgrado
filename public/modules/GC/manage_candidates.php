<?php

require_once INCLUDES_DIR . '/utilities/database.php';
require_once INCLUDES_DIR . '/models/candidate.php';

/**
 * Inserta un candidato en la base de datos
 * @return bool
 */
function insertOneCandidate(
    string $folioAdmision,
    int $numeroBloque,
    string $nombre,
    string $apellidos,
    string $correo1,
    ?string $correo2,
    string $celular,
    int $programaAcademico,
    string $fechaSolicitudEntrevista,
    string $fechaHoraEntrevista,
    ?int $claveUlsa = null,
): bool {
    try {
        // Convertir fechas al formato esperado por PostgreSQL
        $fechaSolicitud = date('Y-m-d H:i:s', strtotime($fechaSolicitudEntrevista));
        $fechaEntrevista = date('Y-m-d H:i:s', strtotime($fechaHoraEntrevista));

        return insertCandidate(
            $nombre,
            $apellidos,
            $folioAdmision,
            $numeroBloque,
            $correo1,
            $correo2,
            $celular,
            $programaAcademico,
            $fechaSolicitud,
            $fechaEntrevista,
            $claveUlsa,
        );
    } catch (\Exception $e) {
        ErrorList::add("Error al insertar candidato: {$e->getMessage()}");
        return false;
    }
}
