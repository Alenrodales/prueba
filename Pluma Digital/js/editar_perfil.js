document.addEventListener('DOMContentLoaded', function() {
    var editProfileModal = document.getElementById("editProfileModal");
    var editarFotoBtn = document.getElementById("editarFotoBtn");
    var editarDescripcionBtn = document.getElementById("editarDescripcionBtn");
    var editarFotoModal = document.getElementById("editarFotoModal");
    var editarDescripcionModal = document.getElementById("editarDescripcionModal");

    editarFotoBtn.onclick = function() {
        editProfileModal.style.display = "none";
        editarFotoModal.style.display = "block";
    }

    editarDescripcionBtn.onclick = function() {
        editProfileModal.style.display = "none";
        editarDescripcionModal.style.display = "block";
    }

    var closeBtns = document.getElementsByClassName("close");
    for (var i = 0; i < closeBtns.length; i++) {
        closeBtns[i].onclick = function() {
            editProfileModal.style.display = "block";
            editarFotoModal.style.display = "none";
            editarDescripcionModal.style.display = "none";
        }
    }

    var modal = document.getElementById("editProfileModal");
    var btn = document.querySelector(".editar");
    var span = document.getElementsByClassName("close")[0];

    btn.onclick = function() {
        modal.style.display = "block";
    }

    span.onclick = function() {
        modal.style.display = "none";
    }

    window.onclick = function(event) {
        if (event.target == modal) {
            modal.style.display = "none";
        }
    }
});
