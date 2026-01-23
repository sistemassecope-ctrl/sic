<!-- Modal Edición de Nota (Global para la página) -->
<div class="modal fade" id="modalEditNota" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content bg-dark text-white border-secondary">
            <form action="notas/update.php" method="POST" enctype="multipart/form-data">
                <div class="modal-header bg-dark text-white border-secondary">
                    <h5 class="modal-title">Editar Nota</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body bg-dark text-white">
                    <input type="hidden" name="nota_id" id="edit_nota_id">
                    <input type="hidden" name="vehiculo_id" id="edit_vehiculo_id">
                    <input type="hidden" name="redirect_to" id="edit_redirect_to">
                    
                    <div class="mb-3">
                        <label class="form-label">Contenido</label>
                        <textarea name="nota" id="edit_nota_text" class="form-control" rows="5" required></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Imagen Adjunta</label>
                        <div id="current_image_container" class="mb-2 d-none">
                            <span class="badge bg-secondary">Archivo actual: <span id="current_image_name"></span></span>
                            <div class="form-check mt-1">
                                <input class="form-check-input" type="checkbox" name="eliminar_imagen" value="1" id="checkEliminarImg">
                                <label class="form-check-label text-danger" for="checkEliminarImg">
                                    Eliminar imagen actual
                                </label>
                            </div>
                        </div>
                        <input type="file" name="imagen" class="form-control">
                        <div class="form-text text-white-50">Subir nueva imagen reemplazará la anterior.</div>
                    </div>
                </div>
                <div class="modal-footer bg-dark border-secondary">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar Cambios</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openEditNotaModal(nota, redirectTo) {
    document.getElementById('edit_nota_id').value = nota.id;
    document.getElementById('edit_vehiculo_id').value = nota.vehiculo_id;
    document.getElementById('edit_redirect_to').value = redirectTo;
    document.getElementById('edit_nota_text').value = nota.nota;
    
    // Reset check
    document.getElementById('checkEliminarImg').checked = false;
    
    const imgContainer = document.getElementById('current_image_container');
    if (nota.imagen_path) {
        imgContainer.classList.remove('d-none');
        document.getElementById('current_image_name').textContent = 'Sí (Click para ver en lista)';
    } else {
        imgContainer.classList.add('d-none');
    }
    
    var myModal = new bootstrap.Modal(document.getElementById('modalEditNota'));
    myModal.show();
}
</script>
