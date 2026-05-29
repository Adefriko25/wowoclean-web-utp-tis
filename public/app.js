const api = axios.create({
    baseURL: 'http://127.0.0.1:8000/api/v1', // Diubah ke V1
    headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json'
    }
});

// Interceptor untuk menyisipkan Token JWT otomatis ke setiap request
api.interceptors.request.use(config => {
    const token = localStorage.getItem('jwt_token');
    if (token) {
        config.headers.Authorization = `Bearer ${token}`;
    }
    return config;
});

let containers = [];

// Fungsi Login
window.loginUser = async function(email, password) {
    try {
        const res = await api.post('/login', { email, password });
        localStorage.setItem('jwt_token', res.data.token);
        localStorage.setItem('role', res.data.user.role);
        alert('Login Sukses!');
        
        // Sembunyikan form login, tampilkan aplikasi utama
        document.getElementById('loginSection').style.display = 'none';
        document.getElementById('appSection').style.display = 'block';
        
        fetchContainers();
    } catch (e) {
        console.error(e);
        alert('Login gagal! Cek email atau password.');
    }
}

// Fungsi Logout
window.logoutUser = function() {
    localStorage.removeItem('jwt_token');
    localStorage.removeItem('role');
    document.getElementById('loginSection').style.display = 'block';
    document.getElementById('appSection').style.display = 'none';
    alert('Anda telah logout.');
}

// Fetch Data via API Gateway
async function fetchContainers() {
    try {
        console.log("-> Sedang mengambil data dari API Gateway...");
        const response = await api.get('/gateway/containers');
        console.log("-> Data diterima:", response.data);
        
        containers = response.data;
        renderContainers();
        calculateTotalWeight();
    } catch (error) {
        console.error("X Gagal mengambil data:", error);
        if (error.response && error.response.status === 401) {
            alert("Sesi habis atau belum login. Silakan login kembali.");
            window.logoutUser();
        } else {
            alert("Gagal konek ke API. Cek terminal Laravel.");
        }
    }
}

// Render HTML
function renderContainers() {
    const listDiv = document.getElementById('containerList');
    if (!listDiv) return; 

    listDiv.innerHTML = '';

    // Ambil role dari localStorage untuk pengecekan hak akses UI
    const role = localStorage.getItem('role');

    containers.forEach(item => {
        const statusClass = item.status === 'Archived' ? 'status-archived' : '';
        
        let actionButtons = '';
        
        // Hanya admin yang bisa melihat tombol aksi
        if (role === 'admin') {
            actionButtons = `
                ${item.status === 'Active' ? `<button class="btn btn-warning" onclick="window.doArchive('${item.container_id}')">Archive</button>` : ''}
                <button class="btn btn-danger" onclick="window.doDelete('${item.container_id}')">Hapus</button>
            `;
        } else {
            actionButtons = `<span style="font-size:12px;color:grey">Read-Only (Admin Only)</span>`;
        }

        listDiv.innerHTML += `
            <div class="card">
                <div class="card-info">
                    <h4>${item.container_id} <span class="status-badge ${statusClass}">${item.status}</span></h4>
                    <p style="margin:0; color:#555;">Type: ${item.waste_type} | Weight: ${item.weight_kg} kg</p>
                </div>
                <div class="card-actions">
                    ${actionButtons}
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
            await api.patch(`/gateway/containers/${id}/archive`);
            console.log("-> Sukses diarsipkan");
            fetchContainers();
        } catch (error) {
            console.error('X Gagal arsip:', error);
            if (error.response && error.response.status === 403) {
                alert("Akses ditolak: 403 Forbidden. Anda bukan Admin.");
            } else {
                alert("Gagal arsip. Cek Console.");
            }
        }
    }
}

window.doDelete = async function(id) {
    console.log("-> Tombol Delete diklik untuk ID:", id);
    if(confirm('Data akan dihapus permanen. Lanjutkan?')) {
        try {
            await api.delete(`/gateway/containers/${id}`);
            console.log("-> Sukses dihapus");
            fetchContainers();
        } catch (error) {
            console.error('X Gagal hapus:', error);
            if (error.response && error.response.status === 403) {
                alert("Akses ditolak: 403 Forbidden. Anda bukan Admin.");
            } else {
                alert("Gagal hapus. Cek Console.");
            }
        }
    }
}

// Mastiin script baru jalan setelah HTML selesai di-load
document.addEventListener('DOMContentLoaded', () => {

    // Cek apakah user sudah login sebelumnya
    if (localStorage.getItem('jwt_token')) {
        document.getElementById('loginSection').style.display = 'none';
        document.getElementById('appSection').style.display = 'block';
        fetchContainers();
    } else {
        document.getElementById('loginSection').style.display = 'block';
        document.getElementById('appSection').style.display = 'none';
    }

    const form = document.getElementById('containerForm');
    
    if (form) {
        form.addEventListener('submit', async function(e) {
            e.preventDefault(); 
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
                const response = await api.post('/gateway/containers', payload);
                console.log("-> Sukses simpan:", response.data);
                alert('Data berhasil ditambahkan!');
                form.reset();
                fetchContainers(); 
            } catch (error) {
                console.error("X Gagal simpan:", error.response);
                
                // Tangani error 403 Forbidden
                if (error.response && error.response.status === 403) {
                    alert("Akses ditolak: 403 Forbidden. Hanya Admin yang dapat menambah data.");
                } 
                // Tangani error 422 Unprocessable Entity (Validasi)
                else if (error.response && error.response.status === 422) {
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