<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';

function getAccessibleModulesDebug($userId)
{
    $pdo = getConnection();

    $sql = "
        SELECT DISTINCT m.* 
        FROM modulos m
        INNER JOIN usuario_modulo_permisos ump ON m.id = ump.id_modulo
        WHERE ump.id_usuario = ? AND m.estado = 1
        ORDER BY m.orden
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$userId]);

    $modulos = $stmt->fetchAll();

    $tree = [];
    $children = [];

    foreach ($modulos as $mod) {
        if ($mod['id_padre'] === null) {
            $tree[$mod['id']] = $mod;
            $tree[$mod['id']]['children'] = [];
        } else {
            $children[$mod['id_padre']][] = $mod;
        }
    }

    foreach ($children as $parentId => $kids) {
        if (isset($tree[$parentId])) {
            $tree[$parentId]['children'] = $kids;
        } else {
            $stmtP = $pdo->prepare("SELECT * FROM modulos WHERE id = ?");
            $stmtP->execute([$parentId]);
            $parent = $stmtP->fetch();
            if ($parent) {
                $tree[$parentId] = $parent;
                $tree[$parentId]['children'] = $kids;
            }
        }
    }

    uasort($tree, function ($a, $b) {
        return $a['orden'] - $b['orden'];
    });

    return $tree;
}

print_r(getAccessibleModulesDebug(10));
