<?php
// actions/configuracion_action.php
session_start();
require_once '../config/database.php';
require_once '../includes/audit.php';

// Verificar que sea POST
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: ../Vistas/configuracion.php");
    exit;
}

$categoria = $_POST['categoria'] ?? '';

// Mapa de campos permitidos por categoría (seguridad)
$campos_permitidos = [
    'institucion' => ['institucion_nombre', 'institucion_direccion', 'institucion_telefono', 'institucion_email'],
    'sistema'     => ['sistema_anio_lectivo', 'sistema_nota_minima', 'sistema_escala_maxima', 'sistema_cantidad_periodos', 'sistema_formato_fecha'],
    'seguridad'   => ['seguridad_tiempo_sesion', 'seguridad_intentos_maximos', 'seguridad_longitud_password', 'seguridad_requiere_mayusculas', 'seguridad_requiere_numeros'],
    'visual'      => ['visual_color_primario', 'visual_modo_oscuro'],
    'email'       => ['email_servidor', 'email_puerto', 'email_usuario', 'email_password', 'email_remitente']
];

if (!isset($campos_permitidos[$categoria])) {
    header("Location: ../Vistas/configuracion.php?error=invalido");
    exit;
}

// ==========================================
// ✅ VALIDACIONES ESPECÍFICAS POR CAMPO
// ==========================================

// 1. Validar Correos Electrónicos
$campos_email = ['institucion_email', 'email_usuario', 'email_remitente'];
foreach ($campos_email as $campo) {
    if (!empty($_POST[$campo]) && !filter_var($_POST[$campo], FILTER_VALIDATE_EMAIL)) {
        header("Location: ../Vistas/configuracion.php?error=email_invalido&campo={$campo}");
        exit;
    }
}

// 2. Validar Teléfono de la Institución (exactamente 8 dígitos)
if (!empty($_POST['institucion_telefono'])) {
    // Quitar guiones y espacios para validar solo los dígitos
    $telefono_limpio = str_replace(['-', ' '], '', $_POST['institucion_telefono']);
    
    // Debe tener exactamente 8 dígitos numéricos
    if (!preg_match('/^[0-9]{8}$/', $telefono_limpio)) {
        header("Location: ../Vistas/configuracion.php?error=telefono_invalido");
        exit;
    }
}

// 3. Validar Números del Sistema (Notas y Escalas)
if ($categoria === 'sistema') {
    $nota_minima = floatval($_POST['sistema_nota_minima'] ?? 0);
    $escala_maxima = floatval($_POST['sistema_escala_maxima'] ?? 0);
    
    if ($nota_minima < 0 || $nota_minima > 10) {
        header("Location: ../Vistas/configuracion.php?error=nota_minima_invalida");
        exit;
    }
    
    if ($escala_maxima <= 0 || $escala_maxima > 100) {
        header("Location: ../Vistas/configuracion.php?error=escala_maxima_invalida");
        exit;
    }

    if ($nota_minima >= $escala_maxima) {
        header("Location: ../Vistas/configuracion.php?error=nota_mayor_escala");
        exit;
    }
}

// 4. Validar Números de Seguridad
if ($categoria === 'seguridad') {
    if (isset($_POST['seguridad_tiempo_sesion']) && $_POST['seguridad_tiempo_sesion'] < 5) {
        header("Location: ../Vistas/configuracion.php?error=tiempo_sesion_invalido");
        exit;
    }
    if (isset($_POST['seguridad_intentos_maximos']) && $_POST['seguridad_intentos_maximos'] < 3) {
        header("Location: ../Vistas/configuracion.php?error=intentos_invalidos");
        exit;
    }
    if (isset($_POST['seguridad_longitud_password']) && $_POST['seguridad_longitud_password'] < 6) {
        header("Location: ../Vistas/configuracion.php?error=password_corta");
        exit;
    }
}

// 5. Validar Puerto SMTP
if (!empty($_POST['email_puerto'])) {
    $puerto = intval($_POST['email_puerto']);
    if ($puerto < 1 || $puerto > 65535) {
        header("Location: ../Vistas/configuracion.php?error=puerto_invalido");
        exit;
    }
}

// ==========================================
// ✅ PROCESAR Y GUARDAR EN LA BASE DE DATOS
// ==========================================

try {
    $pdo->beginTransaction();

    foreach ($campos_permitidos[$categoria] as $clave) {
        // Manejo especial para checkboxes
        if (in_array($clave, ['seguridad_requiere_mayusculas', 'seguridad_requiere_numeros', 'visual_modo_oscuro'])) {
            $valor = isset($_POST[$clave]) ? '1' : '0';
        } else {
            $valor = trim($_POST[$clave] ?? '');
        }

        $stmt = $pdo->prepare("UPDATE configuraciones SET valor = ? WHERE clave = ?");
        $stmt->execute([$valor, $clave]);
    }

    $pdo->commit();

    // ✅ AUDITORÍA
    registrarAuditoria($pdo, 'modificacion', 'configuracion', "Se actualizaron configuraciones de la categoría: {$categoria}");

    header("Location: ../Vistas/configuracion.php?success=1");
    exit;

} catch (PDOException $e) {
    $pdo->rollBack();
    header("Location: ../Vistas/configuracion.php?error=bd");
    exit;
}
?>