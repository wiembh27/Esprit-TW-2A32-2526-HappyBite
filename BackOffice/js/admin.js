// Confirmation avant suppression
document.querySelectorAll('.btn-delete').forEach(btn => {
    btn.addEventListener('click', (e) => {
        if (!confirm('Êtes-vous sûr de vouloir supprimer cet utilisateur ?')) {
            e.preventDefault();
        }
    });
});

// Confirmation avant blocage/déblocage
document.querySelectorAll('.btn-toggle').forEach(btn => {
    btn.addEventListener('click', (e) => {
        const action = btn.textContent.trim();
        if (!confirm(`Confirmer ${action} cet utilisateur ?`)) {
            e.preventDefault();
        }
    });
});

// Recherche dans le tableau
function searchTable() {
    const input = document.getElementById('searchInput');
    const filter = input.value.toLowerCase();
    const table = document.querySelector('table');
    const rows = table.getElementsByTagName('tr');

    for (let i = 1; i < rows.length; i++) {
        const cells = rows[i].getElementsByTagName('td');
        let found = false;
        for (let j = 0; j < cells.length - 1; j++) {
            if (cells[j] && cells[j].textContent.toLowerCase().indexOf(filter) > -1) {
                found = true;
                break;
            }
        }
        rows[i].style.display = found ? '' : 'none';
    }
}

// Ajouter une barre de recherche
document.addEventListener('DOMContentLoaded', () => {
    const usersCard = document.querySelector('.users-card');
    if (usersCard) {
        const searchDiv = document.createElement('div');
        searchDiv.style.marginBottom = '1rem';
        searchDiv.innerHTML = `
            <input type="text" id="searchInput" placeholder="🔍 Rechercher un utilisateur..." 
                   style="width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 0.5rem;">
        `;
        usersCard.insertBefore(searchDiv, usersCard.firstChild);
        document.getElementById('searchInput').addEventListener('keyup', searchTable);
    }
});