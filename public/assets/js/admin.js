document.addEventListener("DOMContentLoaded", function () {
  var botao = document.getElementById("sidebarToggle");
  var sidebar = document.getElementById("adminSidebar");

  if (botao && sidebar) {
    botao.addEventListener("click", function () {
      sidebar.classList.toggle("is-open");
    });
  }

  document.querySelectorAll(".admin-nav-link").forEach(function (link) {
    link.addEventListener("click", function () {
      if (window.innerWidth < 992 && sidebar) {
        sidebar.classList.remove("is-open");
      }
    });
  });

  var ano = document.getElementById("ano-admin");
  if (ano) {
    ano.textContent = String(new Date().getFullYear());
  }
});
