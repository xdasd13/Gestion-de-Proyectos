document.querySelectorAll('.kanban-card').forEach(card => {
    card.addEventListener('dragstart', e => {
        card.classList.add('dragging');
        e.dataTransfer.setData('text/plain', card.dataset.id);
    });
    card.addEventListener('dragend', () => card.classList.remove('dragging'));
});

document.querySelectorAll('.kanban-column').forEach(column => {
    column.addEventListener('dragover', e => {
        e.preventDefault();
        column.classList.add('drag-over');
    });
    column.addEventListener('dragleave', () => column.classList.remove('drag-over'));
    column.addEventListener('drop', e => {
        e.preventDefault();
        column.classList.remove('drag-over');
        const idProyecto = e.dataTransfer.getData('text/plain');
        const card = document.querySelector(`.kanban-card[data-id="${idProyecto}"]`);
        column.appendChild(card);

        // Llamada AJAX para actualizar en la BD
        fetch("", {
            method: "POST",
            headers: {"Content-Type": "application/x-www-form-urlencoded"},
            body: `cambiar_estado=1&id=${idProyecto}&nuevo_estado=${encodeURIComponent(column.dataset.estado)}`
        });
    });
});