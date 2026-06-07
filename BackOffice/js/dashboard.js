document.addEventListener('DOMContentLoaded', () => {
    const ctx = document.getElementById('usersGrowthChart').getContext('2d');
    const periodSelect = document.getElementById('periodSelect');

    let chart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: growthData.day.labels,
            datasets: [{
                label: 'Nouveaux utilisateurs',
                data: growthData.day.data,
                borderColor: '#006e1c',
                backgroundColor: 'rgba(0,110,28,0.08)',
                fill: true,
                tension: 0.2
            }]
        },
        options: {
            responsive: true,
            scales: { y: { beginAtZero: true } }
        }
    });

    periodSelect.addEventListener('change', () => {
        const p = periodSelect.value;
        chart.data.labels = growthData[p].labels;
        chart.data.datasets[0].data = growthData[p].data;
        chart.update();
    });
    // quick actions on dashboard table
    document.querySelectorAll('.btn-toggle').forEach(btn=>btn.addEventListener('click', ()=>{
        const id = btn.getAttribute('data-id');
        if (!confirm('Confirmer blocage/déblocage ?')) return;
        fetch('../../Controller/AdminController.php', { method:'POST', body: new URLSearchParams({ action:'toggle', id }) }).then(r=>r.json()).then(j=>{ if (j.ok) location.reload(); else alert('Erreur'); });
    }));
    document.querySelectorAll('.btn-delete').forEach(btn=>btn.addEventListener('click', ()=>{
        const id = btn.getAttribute('data-id');
        if (!confirm('Supprimer cet utilisateur ?')) return;
        fetch('../../Controller/AdminController.php', { method:'POST', body: new URLSearchParams({ action:'delete', id }) }).then(r=>r.json()).then(j=>{ if (j.ok) location.reload(); else alert('Erreur'); });
    }));
});
