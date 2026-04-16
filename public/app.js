const api = axios.create({
    baseURL: 'http://127.0.0.1:8000/api', // Pastikan port sesuai terminal (8000/8001)
    headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json'
    }
});

let containers = [];

// Fetch Data
async function fetchContainers() {
    try {
        console.log("-> Sedang mengambil data dari API...");
        const response = await api.get('/containers');
        console.log("-> Data diterima:", response.data);
        
        containers = response.data;
        renderContainers();
        calculateTotalWeight();
    } catch (error) {
        console.error("X Gagal mengambil data:", error);
        alert("Gagal konek ke API. Cek terminal Laravel.");
    }
}

// Render HTML
function renderContainers() {
    const listDiv = document.getElementById('containerList');
    if (!listDiv) return; // Mencegah error kalau HTML hilang

    listDiv.innerHTML = '';

    containers.forEach(item => {
        const statusClass = item.status === 'Archived' ? 'status-archived' : '';
        
        // PENTING: onclick diganti dengan pemanggilan fungsi global window
        listDiv.innerHTML += `
            <div class="card">
                <div class="card-info">
                    <h4>${item.container_id} <span class="status-badge ${statusClass}">${item.status}</span></h4>
                    <p style="margin:0; color:#555;">Type: ${item.waste_type} | Weight: ${item.weight_kg} kg</p>
                </div>
                <div class="card-actions">
                    ${item.status === 'Active' ? `<button class="btn btn-warning" onclick="window.doArchive('${item.container_id}')">Archive</button>` : ''}
                    <button class="btn btn-danger" onclick="window.doDelete('${item.container_id}')">Hapus</button>
                </div>
            </div>
        `;
    });
}

// proses itung
function calculateTotalWeight() {
    const totalDisplay = document.getElementById('totalWeightDisplay');
    if (!totalDisplay) return;

    let total = 0;
    containers.forEach(item => {
        total += parseInt(item.weight_kg) || 0; 
    });

    totalDisplay.innerText = `Total Muatan: ${total} kg`;
}

window.doArchive = async function(id) {
    console.log("-> Tombol Archive diklik untuk ID:", id);
    if(confirm('Yakin ingin mengarsipkan kontainer ini?')) {
        try {
            await api.patch(`/containers/${id}/archive`);
            console.log("-> Sukses diarsipkan");
            fetchContainers();
        } catch (error) {
            console.error('X Gagal arsip:', error);
            alert("Gagal arsip. Cek Console.");
        }
    }
}

window.doDelete = async function(id) {
    console.log("-> Tombol Delete diklik untuk ID:", id);
    if(confirm('Data akan dihapus permanen. Lanjutkan?')) {
        try {
            await api.delete(`/containers/${id}`);
            console.log("-> Sukses dihapus");
            fetchContainers();
        } catch (error) {
            console.error('X Gagal hapus:', error);
            alert("Gagal hapus. Cek Console.");
        }
    }
}

// Mastiin script baru jalan setelah HTML selesai di-load
document.addEventListener('DOMContentLoaded', () => {

    fetchContainers();

    const form = document.getElementById('containerForm');
    
    if (form) {
        form.addEventListener('submit', async function(e) {
            e.preventDefault(); // Mencegah web reload saat disubmit
            console.log("-> Tombol Submit form diklik!");

            document.getElementById('err_container_id').innerText = '';
            document.getElementById('err_weight_kg').innerText = '';

            const payload = {
                container_id: document.getElementById('container_id').value,
                waste_type: document.getElementById('waste_type').value,
                weight_kg: document.getElementById('weight_kg').value
            };

            console.log("-> Payload yang mau dikirim:", payload);

            try {
                const response = await api.post('/containers', payload);
                console.log("-> Sukses simpan:", response.data);
                alert('Data berhasil ditambahkan!');
                form.reset();
                fetchContainers(); 
            } catch (error) {
                console.error("X Gagal simpan (Error Validasi):", error.response);
                
                // Tangani error 422
                if (error.response && error.response.status === 422) {
                    const errors = error.response.data.errors;
                    if (errors.container_id) document.getElementById('err_container_id').innerText = errors.container_id[0];
                    if (errors.weight_kg) document.getElementById('err_weight_kg').innerText = errors.weight_kg[0];
                } else {
                    alert("Terjadi kesalahan server. Cek console.");
                }
            }
        });
    } else {
        console.error("X Form dengan ID 'containerForm' tidak ditemukan di HTML!");
    }
});