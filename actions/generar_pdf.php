<?php
// actions/generar_pdf.php
require_once '../config/database.php';

// Verificar si existe FPDF
if (!file_exists('../libs/fpdf/fpdf.php')) {
    die('Error: La librería FPDF no está instalada. Descárgala de http://www.fpdf.org');
}

require_once('../libs/fpdf/fpdf.php');

class PDF extends FPDF {
    // Cabecera de página
    function Header() {
        // Logo (opcional - si tienes uno)
        // $this->Image('../img/logo.png', 10, 10, 30);
        
        // Título del instituto
        $this->SetFont('Arial', 'B', 16);
        $this->Cell(0, 10, 'INSTITUTO NACIONAL "DON BARTOLO"', 0, 1, 'C');
        
        // Subtítulo
        $this->SetFont('Arial', 'I', 12);
        $this->Cell(0, 8, 'BOLETIN DE CALIFICACIONES', 0, 1, 'C');
        
        // Línea divisoria
        $this->Ln(5);
        $this->SetFont('Arial', '', 10);
        $this->Cell(0, 0, '', 'T', 1);
        $this->Ln(5);
    }

    // Pie de página
    function Footer() {
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 8);
        $this->Cell(0, 10, 'Página ' . $this->PageNo() . ' - Generado el ' . date('d/m/Y H:i'), 0, 0, 'C');
    }

    // Tabla mejorada con celdas multicolor
    function TablaCalificaciones($headers, $data, $promedios = null) {
        // Colores de encabezado
        $this->SetFillColor(38, 71, 184); // Azul institucional #2647B8
        $this->SetTextColor(255);
        $this->SetFont('Arial', 'B', 10);
        
        // Anchos de columna
        $w = [60, 20, 20, 20, 20, 25, 25];
        
        // Encabezados
        for($i = 0; $i < count($headers); $i++) {
            $this->Cell($w[$i], 10, $headers[$i], 1, 0, 'C', true);
        }
        $this->Ln();
        
        // Datos
        $this->SetTextColor(0);
        $this->SetFont('Arial', '', 9);
        $fill = false;
        
        foreach($data as $row) {
            $this->SetFillColor($fill ? 245 : 255, $fill ? 246 : 255, $fill ? 250 : 255);
            
            // Materia
            $this->Cell($w[0], 8, $row[0], 1, 0, 'L', $fill);
            
            // Períodos 1-4
            for($i = 1; $i <= 4; $i++) {
                $nota = $row[$i] !== null ? number_format($row[$i], 2) : '-';
                $this->Cell($w[$i], 8, $nota, 1, 0, 'C', $fill);
            }
            
            // Promedio
            $this->SetFont('Arial', 'B', 9);
            $this->SetTextColor(47, 111, 237); // Azul #2F6FED
            $this->Cell($w[5], 8, $row[5], 1, 0, 'C', $fill);
            
            // Estado
            $estadoColor = $row[6] === 'Aprobado' ? [220, 252, 231] : [254, 226, 226]; // Verde o Rojo claro
            $this->SetFillColor($estadoColor[0], $estadoColor[1], $estadoColor[2]);
            $this->SetTextColor($row[6] === 'Aprobado' ? 21 : 185); // Verde oscuro o Rojo oscuro
            $this->Cell($w[6], 8, $row[6], 1, 0, 'C', true);
            
            $this->SetFont('Arial', '', 9);
            $this->SetTextColor(0);
            $this->Ln();
            $fill = !$fill;
        }
    }
}

// ==========================================
// OBTENER DATOS DEL REPORTE
// ==========================================

$tipo_reporte = $_POST['tipo_reporte'] ?? 'general';
$materia_filtro = $_POST['materia_id'] ?? null;
$periodo_filtro = $_POST['periodo'] ?? null;

// Crear instancia de PDF
$pdf = new PDF();
$pdf->AddPage();
$pdf->SetFont('Arial', '', 10);

// ==========================================
// CASO 1: ESTUDIANTE INDIVIDUAL
// ==========================================
if ($tipo_reporte === 'individual') {
    $id_estudiante = $_POST['estudiante_id'] ?? 0;
    
    // Obtener datos del estudiante
    $stmt = $pdo->prepare("SELECT 
                                e.id, e.nie, 
                                CONCAT(e.nombres, ' ', e.apellidos) as nombre_completo,
                                s.nombre as seccion,
                                c.anio
                           FROM estudiantes e
                           INNER JOIN calificaciones c ON e.id = c.id_estudiante
                           INNER JOIN secciones s ON c.id_seccion = s.id
                           WHERE e.id = ?
                           GROUP BY e.id
                           LIMIT 1");
    $stmt->execute([$id_estudiante]);
    $estudiante = $stmt->fetch();
    
    if (!$estudiante) {
        die('Estudiante no encontrado');
    }
    
    // Información del estudiante
    $pdf->SetFont('Arial', 'B', 11);
    $pdf->Cell(0, 8, 'DATOS DEL ESTUDIANTE', 0, 1);
    $pdf->SetFont('Arial', '', 10);
    $pdf->Cell(40, 6, 'Nombre:', 0, 0);
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell(0, 6, $estudiante['nombre_completo'], 0, 1);
    $pdf->SetFont('Arial', '', 10);
    $pdf->Cell(40, 6, 'NIE:', 0, 0);
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell(50, 6, $estudiante['nie'], 0, 0);
    $pdf->SetFont('Arial', '', 10);
    $pdf->Cell(30, 6, 'Seccion:', 0, 0);
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell(0, 6, $estudiante['seccion'], 0, 1);
    $pdf->SetFont('Arial', '', 10);
    $pdf->Cell(40, 6, 'Año Lectivo:', 0, 0);
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell(0, 6, $estudiante['anio'], 0, 1);
    
    $pdf->Ln(5);
    
    // Obtener calificaciones agrupadas por materia
    $query = "SELECT 
                  m.nombre as materia,
                  MAX(CASE WHEN c.periodo = 1 THEN c.nota END) as p1,
                  MAX(CASE WHEN c.periodo = 2 THEN c.nota END) as p2,
                  MAX(CASE WHEN c.periodo = 3 THEN c.nota END) as p3,
                  MAX(CASE WHEN c.periodo = 4 THEN c.nota END) as p4
              FROM calificaciones c
              INNER JOIN materias m ON c.id_materia = m.id
              WHERE c.id_estudiante = ?";
    
    $params = [$id_estudiante];
    
    if ($materia_filtro) {
        $query .= " AND c.id_materia = ?";
        $params[] = $materia_filtro;
    }
    
    if ($periodo_filtro) {
        $query .= " AND c.periodo = ?";
        $params[] = $periodo_filtro;
    }
    
    $query .= " GROUP BY m.id, m.nombre ORDER BY m.nombre";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $calificaciones = $stmt->fetchAll();
    
    // Calcular promedios y estado general
    $headers = ['Materia', 'P1', 'P2', 'P3', 'P4', 'Promedio', 'Estado'];
    $data = [];
    $sumaPromedios = 0;
    $totalMaterias = 0;
    
    foreach ($calificaciones as $cal) {
        $notas = [$cal['p1'], $cal['p2'], $cal['p3'], $cal['p4']];
        $notasValidas = array_filter($notas, fn($n) => $n !== null);
        $promedio = count($notasValidas) > 0 ? array_sum($notasValidas) / count($notasValidas) : 0;
        $estado = $promedio >= 6 ? 'Aprobado' : 'Reprobado';
        
        $data[] = [
            $cal['materia'],
            $cal['p1'],
            $cal['p2'],
            $cal['p3'],
            $cal['p4'],
            number_format($promedio, 2),
            $estado
        ];
        
        $sumaPromedios += $promedio;
        $totalMaterias++;
    }
    
    $promedioGeneral = $totalMaterias > 0 ? $sumaPromedios / $totalMaterias : 0;
    $estadoGeneral = $promedioGeneral >= 6 ? 'APROBADO' : 'REPROBADO';
    
    // Mostrar tabla
    $pdf->TablaCalificaciones($headers, $data);
    
    // Promedio y estado general
    $pdf->Ln(8);
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->SetFillColor(38, 71, 184);
    $pdf->SetTextColor(255);
    $pdf->Cell(100, 10, 'PROMEDIO GENERAL:', 1, 0, 'R', true);
    $pdf->SetFont('Arial', 'B', 14);
    $pdf->Cell(40, 10, number_format($promedioGeneral, 2), 1, 0, 'C', true);
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Cell(50, 10, 'ESTADO: ' . $estadoGeneral, 1, 0, 'C', $estadoGeneral === 'APROBADO' ? [220, 252, 231] : [254, 226, 226]);
    $pdf->SetTextColor(0);
}

// ==========================================
// CASO 2: MÚLTIPLES ESTUDIANTES
// ==========================================
elseif ($tipo_reporte === 'multiples') {
    $estudiantes_ids = $_POST['estudiantes'] ?? [];
    
    if (empty($estudiantes_ids)) {
        die('No se seleccionaron estudiantes');
    }
    
    $placeholders = implode(',', array_fill(0, count($estudiantes_ids), '?'));
    
    // Obtener calificaciones de los estudiantes seleccionados
    $query = "SELECT 
                  e.id as id_estudiante,
                  e.nie,
                  CONCAT(e.nombres, ' ', e.apellidos) as nombre_estudiante,
                  s.nombre as seccion,
                  m.nombre as materia,
                  c.periodo,
                  c.nota,
                  c.anio
              FROM calificaciones c
              INNER JOIN estudiantes e ON c.id_estudiante = e.id
              INNER JOIN materias m ON c.id_materia = m.id
              INNER JOIN secciones s ON c.id_seccion = s.id
              WHERE c.id_estudiante IN ($placeholders)";
    
    $params = $estudiantes_ids;
    
    if ($materia_filtro) {
        $query .= " AND c.id_materia = ?";
        $params[] = $materia_filtro;
    }
    
    if ($periodo_filtro) {
        $query .= " AND c.periodo = ?";
        $params[] = $periodo_filtro;
    }
    
    $query .= " ORDER BY e.nombres, m.nombre, c.periodo";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $calificaciones = $stmt->fetchAll();
    
    // Agrupar por estudiante
    $estudiantes = [];
    foreach ($calificaciones as $cal) {
        $id = $cal['id_estudiante'];
        if (!isset($estudiantes[$id])) {
            $estudiantes[$id] = [
                'nombre' => $cal['nombre_estudiante'],
                'nie' => $cal['nie'],
                'seccion' => $cal['seccion'],
                'anio' => $cal['anio'],
                'materias' => []
            ];
        }
        $estudiantes[$id]['materias'][$cal['materia']][$cal['periodo']] = $cal['nota'];
    }
    
    // Generar una página por estudiante
    $primerEstudiante = true;
    foreach ($estudiantes as $id => $est) {
        if (!$primerEstudiante) {
            $pdf->AddPage();
        }
        $primerEstudiante = false;
        
        // Datos del estudiante
        $pdf->SetFont('Arial', 'B', 11);
        $pdf->Cell(0, 8, 'DATOS DEL ESTUDIANTE', 0, 1);
        $pdf->SetFont('Arial', '', 10);
        $pdf->Cell(40, 6, 'Nombre:', 0, 0);
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell(0, 6, $est['nombre'], 0, 1);
        $pdf->SetFont('Arial', '', 10);
        $pdf->Cell(40, 6, 'NIE:', 0, 0);
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell(50, 6, $est['nie'], 0, 0);
        $pdf->SetFont('Arial', '', 10);
        $pdf->Cell(30, 6, 'Seccion:', 0, 0);
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell(0, 6, $est['seccion'], 0, 1);
        
        $pdf->Ln(5);
        
        // Construir tabla
        $headers = ['Materia', 'P1', 'P2', 'P3', 'P4', 'Promedio', 'Estado'];
        $data = [];
        $sumaPromedios = 0;
        $totalMaterias = 0;
        
        foreach ($est['materias'] as $materia => $periodos) {
            $notas = [$periodos[1] ?? null, $periodos[2] ?? null, $periodos[3] ?? null, $periodos[4] ?? null];
            $notasValidas = array_filter($notas, fn($n) => $n !== null);
            $promedio = count($notasValidas) > 0 ? array_sum($notasValidas) / count($notasValidas) : 0;
            $estado = $promedio >= 6 ? 'Aprobado' : 'Reprobado';
            
            $data[] = [
                $materia,
                $periodos[1] ?? null,
                $periodos[2] ?? null,
                $periodos[3] ?? null,
                $periodos[4] ?? null,
                number_format($promedio, 2),
                $estado
            ];
            
            $sumaPromedios += $promedio;
            $totalMaterias++;
        }
        
        $promedioGeneral = $totalMaterias > 0 ? $sumaPromedios / $totalMaterias : 0;
        
        $pdf->TablaCalificaciones($headers, $data);
        
        // Promedio general
        $pdf->Ln(5);
        $pdf->SetFont('Arial', 'B', 11);
        $pdf->SetTextColor(47, 111, 237);
        $pdf->Cell(0, 8, 'Promedio General: ' . number_format($promedioGeneral, 2), 0, 1);
        $pdf->SetTextColor(0);
    }
}

// ==========================================
// CASO 3: SECCIÓN COMPLETA
// ==========================================
elseif ($tipo_reporte === 'seccion') {
    $id_seccion = $_POST['seccion_id'] ?? 0;
    
    if (!$id_seccion) {
        die('No se seleccionó una sección');
    }
    
    // Obtener nombre de la sección
    $stmt = $pdo->prepare("SELECT nombre FROM secciones WHERE id = ?");
    $stmt->execute([$id_seccion]);
    $nombreSeccion = $stmt->fetchColumn();
    
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Cell(0, 10, 'Seccion: ' . $nombreSeccion, 0, 1, 'C');
    $pdf->Ln(3);
    
    // Obtener todos los estudiantes de la sección con sus calificaciones
    $query = "SELECT 
                  e.id as id_estudiante,
                  e.nie,
                  CONCAT(e.nombres, ' ', e.apellidos) as nombre_estudiante,
                  m.nombre as materia,
                  c.periodo,
                  c.nota,
                  c.anio
              FROM calificaciones c
              INNER JOIN estudiantes e ON c.id_estudiante = e.id
              INNER JOIN materias m ON c.id_materia = m.id
              WHERE c.id_seccion = ?";
    
    $params = [$id_seccion];
    
    if ($materia_filtro) {
        $query .= " AND c.id_materia = ?";
        $params[] = $materia_filtro;
    }
    
    if ($periodo_filtro) {
        $query .= " AND c.periodo = ?";
        $params[] = $periodo_filtro;
    }
    
    $query .= " ORDER BY e.nombres, m.nombre, c.periodo";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $calificaciones = $stmt->fetchAll();
    
    // Agrupar por estudiante
    $estudiantes = [];
    foreach ($calificaciones as $cal) {
        $id = $cal['id_estudiante'];
        if (!isset($estudiantes[$id])) {
            $estudiantes[$id] = [
                'nombre' => $cal['nombre_estudiante'],
                'nie' => $cal['nie'],
                'materias' => []
            ];
        }
        $estudiantes[$id]['materias'][$cal['materia']][$cal['periodo']] = $cal['nota'];
    }
    
    // Generar tabla general
    $headers = ['Estudiante', 'NIE', 'P1', 'P2', 'P3', 'P4', 'Promedio', 'Estado'];
    $data = [];
    
    foreach ($estudiantes as $est) {
        $todasNotas = [];
        foreach ($est['materias'] as $materia => $periodos) {
            $todasNotas = array_merge($todasNotas, array_values($periodos));
        }
        
        $notasValidas = array_filter($todasNotas, fn($n) => $n !== null);
        $promedio = count($notasValidas) > 0 ? array_sum($notasValidas) / count($notasValidas) : 0;
        $estado = $promedio >= 6 ? 'Aprobado' : 'Reprobado';
        
        // Calcular promedios por período
        $p1 = $p2 = $p3 = $p4 = null;
        $countP1 = $countP2 = $countP3 = $countP4 = 0;
        
        foreach ($est['materias'] as $materia => $periodos) {
            if (isset($periodos[1])) { $p1 += $periodos[1]; $countP1++; }
            if (isset($periodos[2])) { $p2 += $periodos[2]; $countP2++; }
            if (isset($periodos[3])) { $p3 += $periodos[3]; $countP3++; }
            if (isset($periodos[4])) { $p4 += $periodos[4]; $countP4++; }
        }
        
        $data[] = [
            $est['nombre'],
            $est['nie'],
            $countP1 > 0 ? round($p1 / $countP1, 2) : null,
            $countP2 > 0 ? round($p2 / $countP2, 2) : null,
            $countP3 > 0 ? round($p3 / $countP3, 2) : null,
            $countP4 > 0 ? round($p4 / $countP4, 2) : null,
            number_format($promedio, 2),
            $estado
        ];
    }
    
    // Ajustar anchos para esta tabla
    $w = [50, 25, 18, 18, 18, 18, 23, 20];
    
    // Encabezado
    $pdf->SetFillColor(38, 71, 184);
    $pdf->SetTextColor(255);
    $pdf->SetFont('Arial', 'B', 9);
    for($i = 0; $i < count($headers); $i++) {
        $pdf->Cell($w[$i], 10, $headers[$i], 1, 0, 'C', true);
    }
    $pdf->Ln();
    
    // Datos
    $pdf->SetTextColor(0);
    $pdf->SetFont('Arial', '', 8);
    $fill = false;
    
    foreach($data as $row) {
        $pdf->SetFillColor($fill ? 245 : 255, $fill ? 246 : 255, $fill ? 250 : 255);
        
        $pdf->Cell($w[0], 7, $row[0], 1, 0, 'L', $fill);
        $pdf->Cell($w[1], 7, $row[1], 1, 0, 'C', $fill);
        
        for($i = 2; $i <= 5; $i++) {
            $nota = $row[$i] !== null ? $row[$i] : '-';
            $pdf->Cell($w[$i], 7, $nota, 1, 0, 'C', $fill);
        }
        
        $pdf->SetFont('Arial', 'B', 8);
        $pdf->SetTextColor(47, 111, 237);
        $pdf->Cell($w[6], 7, $row[6], 1, 0, 'C', $fill);
        
        $estadoColor = $row[7] === 'Aprobado' ? [220, 252, 231] : [254, 226, 226];
        $pdf->SetFillColor($estadoColor[0], $estadoColor[1], $estadoColor[2]);
        $pdf->SetTextColor($row[7] === 'Aprobado' ? 21 : 185);
        $pdf->Cell($w[7], 7, $row[7], 1, 0, 'C', true);
        
        $pdf->SetFont('Arial', '', 8);
        $pdf->SetTextColor(0);
        $pdf->Ln();
        $fill = !$fill;
    }
}

// ==========================================
// CASO 4: REPORTE GENERAL
// ==========================================
elseif ($tipo_reporte === 'general') {
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Cell(0, 10, 'REPORTE GENERAL DE CALIFICACIONES', 0, 1, 'C');
    $pdf->SetFont('Arial', '', 10);
    $pdf->Cell(0, 8, 'Todas las secciones - Año Lectivo ' . date('Y'), 0, 1, 'C');
    $pdf->Ln(5);
    
    // Obtener estadísticas generales
    $query = "SELECT 
                  COUNT(DISTINCT c.id_estudiante) as total_estudiantes,
                  COUNT(DISTINCT c.id_materia) as total_materias,
                  COUNT(c.id) as total_calificaciones,
                  AVG(c.nota) as promedio_general
              FROM calificaciones c
              WHERE c.anio = YEAR(CURDATE())";
    
    $stmt = $pdo->query($query);
    $stats = $stmt->fetch();
    
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell(60, 8, 'Total Estudiantes:', 0, 0);
    $pdf->SetFont('Arial', '', 10);
    $pdf->Cell(40, 8, $stats['total_estudiantes'], 0, 1);
    
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell(60, 8, 'Total Materias:', 0, 0);
    $pdf->SetFont('Arial', '', 10);
    $pdf->Cell(40, 8, $stats['total_materias'], 0, 1);
    
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell(60, 8, 'Total Calificaciones:', 0, 0);
    $pdf->SetFont('Arial', '', 10);
    $pdf->Cell(40, 8, $stats['total_calificaciones'], 0, 1);
    
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell(60, 8, 'Promedio General:', 0, 0);
    $pdf->SetFont('Arial', '', 10);
    $pdf->SetTextColor(47, 111, 237);
    $pdf->Cell(40, 8, number_format($stats['promedio_general'], 2), 0, 1);
    $pdf->SetTextColor(0);
    
    $pdf->Ln(8);
    $pdf->SetFont('Arial', 'I', 9);
    $pdf->Cell(0, 8, 'Nota: Este es un reporte resumen. Para detalles individuales, use las otras opciones.', 0, 1);
}

// ==========================================
// GENERAR PDF
// ==========================================
$pdf->Output('I', 'Boletin_Calificaciones_' . date('Ymd_His') . '.pdf');
?>