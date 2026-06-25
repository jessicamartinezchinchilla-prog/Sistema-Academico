<?php
// includes/permisos.php

// ==========================================
// DEFINICIÓN DE ROLES Y PERMISOS
// ==========================================

define('ROL_ADMIN', 'administrador');
define('ROL_DIRECTOR', 'director');
define('ROL_SUBDIRECTOR', 'subdirector');
define('ROL_SECRETARIA', 'secretaria');
define('ROL_DOCENTE', 'docente');

// ==========================================
// FUNCIONES DE VERIFICACIÓN DE ROL
// ==========================================

/**
 * Verifica si el usuario actual tiene un rol específico
 */
function tieneRol($rol) {
    return isset($_SESSION['user_rol']) && $_SESSION['user_rol'] === $rol;
}

/**
 * Verifica si el usuario es administrador
 */
function esAdmin() {
    return tieneRol(ROL_ADMIN);
}

/**
 * Verifica si el usuario es director o subdirector
 */
function esDirectivo() {
    return tieneRol(ROL_DIRECTOR) || tieneRol(ROL_SUBDIRECTOR);
}

/**
 * Verifica si el usuario es secretaria
 */
function esSecretaria() {
    return tieneRol(ROL_SECRETARIA);
}

/**
 * Verifica si el usuario es docente
 */
function esDocente() {
    return tieneRol(ROL_DOCENTE);
}

// ==========================================
// FUNCIONES DE PERMISOS ESPECÍFICOS
// ==========================================

/**
 * Verifica si puede matricular estudiantes
 * - Admin: SÍ
 * - Director: NO
 * - Subdirector: NO
 * - Secretaria: SÍ
 * - Docente: NO
 */
function puedeMatricular() {
    return esAdmin() || esSecretaria();
}

/**
 * Verifica si puede ingresar/modificar calificaciones
 * - Admin: SÍ
 * - Director: NO
 * - Subdirector: NO
 * - Secretaria: SÍ
 * - Docente: SÍ (solo de sus estudiantes)
 */
function puedeModificarCalificaciones() {
    return esAdmin() || esSecretaria() || esDocente();
}

/**
 * Verifica si puede ver todos los paneles
 */
function tieneAccesoTotal() {
    return esAdmin() || esDirectivo() || esSecretaria();
}

/**
 * Verifica si puede ver un panel específico
 */
function puedeVerPanel($panel) {
    // Admin y Secretaria ven todo
    if (esAdmin() || esSecretaria()) {
        return true;
    }
    
    // Director y Subdirector ven todo excepto matrícula y calificaciones (solo lectura)
    if (esDirectivo()) {
        return true; // Pueden ver todos los paneles, pero con restricciones en acciones
    }
    
    // Docente solo ve paneles específicos
    if (esDocente()) {
        $panelesDocente = ['estudiantes', 'materias', 'calificaciones', 'historial_academico', 'secciones'];
        return in_array($panel, $panelesDocente);
    }
    
    return false;
}

/**
 * Obtiene el ID del usuario actual
 */
function getUserId() {
    return $_SESSION['user_id'] ?? 0;
}

/**
 * Obtiene el nombre de usuario actual
 */
function getUserName() {
    return $_SESSION['username'] ?? '';
}

/**
 * Obtiene el rol del usuario actual
 */
function getUserRol() {
    return $_SESSION['user_rol'] ?? '';
}

/**
 * Obtiene el nombre legible del rol
 */
function getRolNombre($rol) {
    $roles = [
        ROL_ADMIN => 'Administrador',
        ROL_DIRECTOR => 'Director',
        ROL_SUBDIRECTOR => 'Subdirector',
        ROL_SECRETARIA => 'Secretaria',
        ROL_DOCENTE => 'Docente'
    ];
    return $roles[$rol] ?? $rol;
}

// ==========================================
// FUNCIONES PARA DOCENTES
// ==========================================

/**
 * Obtiene el ID de profesor vinculado al usuario actual (solo docentes)
 */
function getIdProfesorUsuario() {
    if (!esDocente()) return null;
    return $_SESSION['id_profesor'] ?? null;
}

/**
 * Obtiene las secciones donde enseña el docente actual
 * Retorna array de IDs de secciones
 */
function getSeccionesDocente($pdo) {
    $idProfesor = getIdProfesorUsuario();
    if (!$idProfesor) return [];
    
    try {
        // ✅ SOLO usar tabla asignaciones (fuente de verdad desde Materias)
        $stmt = $pdo->prepare("
            SELECT DISTINCT id_seccion 
            FROM asignaciones 
            WHERE id_profesor = ?
        ");
        $stmt->execute([$idProfesor]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    } catch (PDOException $e) {
        error_log("Error obteniendo secciones del docente: " . $e->getMessage());
        return [];
    }
}

/**
 * Obtiene las materias que enseña el docente actual
 * Retorna array de IDs de materias
 */
function getMateriasDocente($pdo) {
    $idProfesor = getIdProfesorUsuario();
    if (!$idProfesor) return [];
    
    try {
        $stmt = $pdo->prepare("
            SELECT DISTINCT id_materia 
            FROM asignaciones 
            WHERE id_profesor = ?
        ");
        $stmt->execute([$idProfesor]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    } catch (PDOException $e) {
        error_log("Error obteniendo materias del docente: " . $e->getMessage());
        return [];
    }
}

/**
 * Verifica si el usuario es docente y tiene profesor vinculado
 */
function esDocenteConPerfil() {
    return esDocente() && getIdProfesorUsuario() !== null;
}
?>