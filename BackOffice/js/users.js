document.addEventListener('DOMContentLoaded', () => {
    const ctx = document.getElementById('usersGrowthChart').getContext('2d');
    const periodSelect = document.getElementById('periodSelect');

    const gridColor = 'rgba(0,0,0,0.06)';
    let chart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: growthData.day.labels,
            datasets: [{
                label: 'Nouveaux utilisateurs',
                data: growthData.day.data,
                borderColor: '#22c55e',
                backgroundColor: 'rgba(134,239,172,0.35)',
                fill: true,
                tension: 0.3,
                pointRadius: 4,
                pointBackgroundColor: '#22c55e',
                pointBorderColor: '#fff',
                pointBorderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            plugins: { legend: { position: 'top' } },
            scales: {
                x: { grid: { color: gridColor } },
                y: { beginAtZero: true, grid: { color: gridColor } }
            }
        }
    });

    periodSelect.addEventListener('change', () => {
        const p = periodSelect.value;
        chart.data.labels = growthData[p].labels;
        chart.data.datasets[0].data = growthData[p].data;
        chart.update();
    });

    // delegate clicks for row actions (modify = link to edit_user.php)
    document.getElementById('usersCard')?.addEventListener('click', (e) => {
        if (e.target.closest('a.bo-img-link')) {
            return;
        }
        const row = e.target.closest('.user-row');
        if (!row) return;
        const id = row.getAttribute('data-id');
        if (!id) return;

        const toggleBtn = e.target.closest('.btn-toggle');
        if (toggleBtn) {
            const blocked = toggleBtn.getAttribute('data-blocked') === '1';
            const msg = blocked
                ? 'Débloquer cet utilisateur ? Il pourra à nouveau se connecter.'
                : 'Bloquer cet utilisateur ? Il ne pourra plus se connecter.';
            if (!confirm(msg)) return;
            fetch('users.php', { method: 'POST', body: new URLSearchParams({ action: 'toggle', id }) })
                .then(r => r.json())
                .then(j => {
                    if (j.ok) location.reload();
                    else alert(j.error === 'toggle_failed' ? 'Impossible de changer le statut (admin, compte introuvable, ou colonne statut absente).' : 'Erreur lors du changement de statut.');
                })
                .catch(() => alert('Erreur réseau.'));
            return;
        }

        if (e.target.closest('.btn-delete')) {
            if (!confirm('Supprimer définitivement cet utilisateur ? Cette action est irréversible.')) return;
            fetch('users.php', { method: 'POST', body: new URLSearchParams({ action: 'delete', id }) })
                .then(r => r.json())
                .then(j => {
                    if (j.ok) location.reload();
                    else alert(j.error === 'delete_failed' ? 'Suppression impossible (contrainte base de données, compte admin, ou vous supprimez votre propre compte).' : 'Erreur lors de la suppression.');
                })
                .catch(() => alert('Erreur réseau.'));
        }
    });

    document.getElementById('closeModal').addEventListener('click', () => {
        document.getElementById('userModal').style.display = 'none';
    });
    
        // New user button
        document.getElementById('btnNewUser')?.addEventListener('click', () => {
            const content = document.getElementById('modalContent');
            content.innerHTML = `
                <h3>Créer un nouvel utilisateur</h3>
                <form id="createForm">
                    <input name="prenom" placeholder="Prénom" required><input name="nom" placeholder="Nom" required><br>
                    <input name="email" placeholder="Email" required><br>
                    <label>Rôle<select name="role"><option value="client">client</option><option value="nutritionniste">nutritionniste</option></select></label>
                    <br><button class="btn btn-save" type="submit">Créer</button>
                </form>
            `;
            document.getElementById('userModal').style.display = 'flex';
            document.getElementById('createForm').addEventListener('submit', (ev) => {
                ev.preventDefault();
                const fd = new FormData(ev.target); const data = {}; fd.forEach((v,k)=>data[k]=v);
                fetch('../../Controller/AdminController.php', { method:'POST', body: new URLSearchParams({ action:'create', 'data': JSON.stringify(data) }) }).then(r=>r.json()).then(j=>{ if (j.ok) location.reload(); else alert('Erreur création'); });
            });
        });
    
        // Search/filter
        document.getElementById('searchInput')?.addEventListener('input', (e)=>{
            const q = e.target.value.toLowerCase();
            document.querySelectorAll('#usersTable tbody tr').forEach(tr=>{
                const txt = tr.innerText.toLowerCase(); tr.style.display = txt.indexOf(q) > -1 ? '' : 'none';
            });
        });
    
        // Export PDF (print)
        document.getElementById('btnExport')?.addEventListener('click', ()=>{
            const w = window.open('', '_blank');
            w.document.write('<html><head><title>Export Users</title><link rel="stylesheet" href="css/style.css"></head><body>');
            w.document.write(document.getElementById('usersCard').innerHTML);
            w.document.write('</body></html>');
            w.document.close();
            w.print();
        });
    
        // Simple client-side sorting
        document.querySelectorAll('#usersTable thead th[data-key]').forEach(th=>{
            th.style.cursor='pointer'; let asc=true;
            th.addEventListener('click', ()=>{
                const key = th.getAttribute('data-key');
                const rows = Array.from(document.querySelectorAll('#usersTable tbody tr'));
                rows.sort((a,b)=>{
                    const av = a.querySelector('.col-'+key)?.innerText || a.innerText;
                    const bv = b.querySelector('.col-'+key)?.innerText || b.innerText;
                    if (!isNaN(av) && !isNaN(bv)) return asc ? av - bv : bv - av;
                    return asc ? av.localeCompare(bv) : bv.localeCompare(av);
                });
                asc = !asc;
                const tbody = document.querySelector('#usersTable tbody'); tbody.innerHTML=''; rows.forEach(r=>tbody.appendChild(r));
            });
        });
});
