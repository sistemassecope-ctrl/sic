<?php
$pageTitle = 'Dependencias - SIC';
$breadcrumb = [
    ['url' => '../../modulos/rh/empleados.php', 'text' => 'Inicio'],
    ['url' => 'areas.php', 'text' => 'Dependencias']
];
require_once '../../includes/header.php';

$pdo = conectarDB();
$areas = obtenerDependencias($pdo);

// Función para mostrar la estructura jerárquica
function mostrarEstructura($areas, $padreId = null, $nivel = 0) {
    $html = '';
    
    foreach ($areas as $dep) {
        $depPadre = $dep['area_padre_id'];
        $esRaiz = ($padreId === null) && (empty($depPadre) && $depPadre !== '0' ? true : ($depPadre === null || $depPadre === '' || $depPadre === 0 || $depPadre === '0'));
        if ($esRaiz || $depPadre == $padreId) {
            $html .= '<div class="dependencia-item" style="margin-left: ' . ($nivel * 20) . 'px;">';
            $html .= '<div class="dependencia-header">';
            $html .= '<span class="tipo-badge tipo-' . mb_strtolower(str_replace(' ', '-', $dep['tipo']), 'UTF-8') . '">' . htmlspecialchars($dep['tipo']) . '</span>';
            $html .= '<span class="nombre-dependencia">' . htmlspecialchars($dep['nombre']) . '</span>';
            $html .= '<div class="acciones">';
            $html .= '<a href="editar_area.php?id=' . $dep['id'] . '" class="btn btn-sm btn-primary"><i class="fas fa-edit"></i> Editar</a>';
            $html .= '<a href="duplicar_area.php?id=' . $dep['id'] . '" class="btn btn-sm btn-secondary" onclick="return confirm(\'¿Duplicar esta area al mismo nivel?\')"><i class="fas fa-copy"></i> Duplicar</a>';
            $html .= '<a href="agregar_area.php?padre=' . $dep['id'] . '" class="btn btn-sm btn-success"><i class="fas fa-plus"></i> Agregar aquí</a>';
            $html .= '<a href="eliminar_area.php?id=' . $dep['id'] . '" class="btn btn-sm btn-danger" onclick="return confirm(\'¿Estás seguro de que deseas eliminar esta area?\')"><i class="fas fa-trash"></i> Eliminar</a>';
            $html .= '</div>';
            $html .= '</div>';
            
            // Mostrar dependencias hijas
            $hijos = mostrarEstructura($areas, $dep['id'], $nivel + 1);
            if ($hijos) {
                $html .= '<div class="dependencias-hijas">' . $hijos . '</div>';
            }
            
            $html .= '</div>';
        }
    }
    
    return $html;
}
?>

<div class="row mb-4">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1 class="h3 mb-0">Gestión de Dependencias</h1>
                <p class="text-muted">Administra la estructura organizacional de SECOPE</p>
            </div>
            <a href="agregar_area.php" class="btn btn-primary">
                <i class="fas fa-plus"></i> Nueva Area
            </a>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-sitemap"></i> Estructura Organizacional
                </h5>
            </div>
            <div class="card-body">
                <?php if (empty($areas)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-sitemap fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">No hay dependencias registradas</h5>
                        <p class="text-muted">Comienza agregando la primera dependencia</p>
                        <a href="agregar_area.php" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Agregar Area
                        </a>
                    </div>
                <?php else: ?>
                    <div class="estructura-dependencias">
                        <?php echo mostrarEstructura($areas); ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<style>
.estructura-dependencias {
    padding: 20px 0;
}

.dependencia-item {
    background: var(--color-white);
    border: 1px solid #e9ecef;
    border-radius: var(--border-radius);
    margin-bottom: 15px;
    box-shadow: var(--shadow-light);
    transition: all 0.3s ease;
}

.dependencia-item:hover {
    box-shadow: var(--shadow-medium);
    transform: translateY(-2px);
}

.dependencia-header {
    display: flex;
    align-items: center;
    padding: 15px;
    gap: 15px;
}

.tipo-badge {
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 600;
    color: var(--color-white);
    min-width: 100px;
    text-align: center;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.tipo-secretaria { background: linear-gradient(135deg, #dc3545, #c82333); }
.tipo-subsecretaria { background: linear-gradient(135deg, #fd7e14, #e55a00); }
.tipo-secretaria-técnica { background: linear-gradient(135deg, #e83e8c, #d63384); }
.tipo-direccion { background: linear-gradient(135deg, #ffc107, #e0a800); color: #212529; }
.tipo-subdireccion { background: linear-gradient(135deg, #20c997, #1ea085); }
.tipo-área { background: linear-gradient(135deg, #17a2b8, #138496); }
.tipo-jefatura { background: linear-gradient(135deg, #6f42c1, #5a32a3); }

.nombre-dependencia {
    flex: 1;
    font-weight: 600;
    font-size: 1.1rem;
    color: var(--color-gray-dark);
}

.acciones {
    display: flex;
    gap: 8px;
}

.acciones .btn {
    border-radius: 6px;
    font-size: 0.8rem;
    padding: 6px 12px;
}

.dependencias-hijas {
    margin-left: 30px;
    border-left: 3px solid var(--color-gray-light);
    padding-left: 20px;
    margin-top: 10px;
}

@media (max-width: 768px) {
    .dependencia-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 10px;
    }
    
    .acciones {
        width: 100%;
        justify-content: flex-end;
    }
    
    .dependencias-hijas {
        margin-left: 15px;
        padding-left: 15px;
    }
}
</style>

<?php require_once '../../includes/footer.php'; ?>
