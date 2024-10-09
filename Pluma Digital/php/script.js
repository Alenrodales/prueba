function showSection(sectionId) {
    const sections = document.querySelectorAll('.content-section');
    sections.forEach(section => {
        section.classList.remove('active');
    });
    document.getElementById(sectionId).classList.add('active');
}

function searchUser() {
    const input = document.getElementById('searchUser');
    const filter = input.value.toLowerCase();
    const table = document.getElementById('userTable');
    const tr = table.getElementsByTagName('tr');

    for (let i = 1; i < tr.length; i++) {
        let td = tr[i].getElementsByTagName('td')[2];
        if (td) {
            const textValue = td.textContent || td.innerText;
            if (textValue.toLowerCase().indexOf(filter) > -1) {
                tr[i].style.display = '';
            } else {
                tr[i].style.display = 'none';
            }
        }       
    }
}

function editUser(username) {
    alert('Editando usuario: ' + username);
}

function suspendUser(username) {
    alert('Suspendiendo usuario: ' + username);
}

function deleteUser(username) {
    alert('Eliminando usuario: ' + username);
}

document.addEventListener('DOMContentLoaded', () => {
    showSection('gestion-usuarios');
});

document.addEventListener('DOMContentLoaded', function() {
    var modal = document.getElementById("editModal");

    var btns = document.querySelectorAll('.edit-button');
    var span = document.getElementsByClassName("close")[0];

    btns.forEach(function(btn) {
        btn.onclick = function() {
            var userId = this.getAttribute('data-user-id');
            openModal(userId);
        }
    });

    span.onclick = function() {
        modal.style.display = "none";
    }

    window.onclick = function(event) {
        if (event.target == modal) {
            modal.style.display = "none";
        }
    }

    function openModal(userId) {
        document.getElementById('editUserId').value = userId;
        
        fetch('roles.php')
            .then(response => response.json())
            .then(data => {
                let select = document.getElementById('userRole');
                select.innerHTML = '';
                data.forEach(role => {
                    let option = document.createElement('option');
                    option.value = role.id;
                    option.textContent = role.nombre;
                    select.appendChild(option);
                });
            });

        modal.style.display = "block";
    }

    window.generateRandomPassword = function() {
        var length = 12;
        var charset = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
        var retVal = "";
        for (var i = 0, n = charset.length; i < length; ++i) {
            retVal += charset.charAt(Math.floor(Math.random() * n));
        }
        document.getElementById('newPassword').value = retVal;
    }
});

function openModal(userId) {
    document.getElementById('editUserId').value = userId;
    document.getElementById('editModal').style.display = 'block';
}

function closeModal() {
    document.getElementById('editModal').style.display = 'none';
}

window.onclick = function(event) {
    if (event.target == document.getElementById('editModal')) {
        closeModal();
    }
}

