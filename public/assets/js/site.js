document.addEventListener("DOMContentLoaded", function () {
  var ano = document.getElementById("ano-atual");
  if (ano) {
    ano.textContent = String(new Date().getFullYear());
  }

  var topo = document.getElementById("back-to-top");
  var rodape = document.querySelector(".site-footer");
  if (topo) {
    window.addEventListener("scroll", function () {
      var sobreRodape = rodape ? rodape.getBoundingClientRect().top < window.innerHeight - 60 : false;
      topo.classList.toggle("is-visible", window.scrollY > 420 && !sobreRodape);
    });
    topo.addEventListener("click", function () {
      window.scrollTo({ top: 0, behavior: "smooth" });
    });
  }

  var telefone = document.getElementById("telefone");
  if (telefone) {
    telefone.addEventListener("input", function () {
      var digitos = telefone.value.replace(/\D/g, "").slice(0, 11);
      if (digitos.length <= 10) {
        telefone.value = digitos.replace(/^(\d{2})(\d)/, "($1) $2").replace(/(\d{4})(\d)/, "$1-$2");
      } else {
        telefone.value = digitos.replace(/^(\d{2})(\d)/, "($1) $2").replace(/^(\d{2})\s(\d{5})(\d)/, "$1 $2-$3");
      }
    });
  }

  var lightbox = document.getElementById("galeriaLightbox");
  var lightboxImagem = document.getElementById("galeriaLightboxImagem");
  var lightboxTitulo = document.getElementById("galeriaLightboxTitulo");
  var botoesGaleria = document.querySelectorAll("[data-galeria-imagem]");

  function fecharLightbox() {
    if (lightbox) {
      lightbox.classList.remove("is-visible");
    }
    if (lightboxImagem) {
      lightboxImagem.hidden = true;
    }
  }

  botoesGaleria.forEach(function (botao) {
    botao.addEventListener("click", function () {
      if (!lightbox || !lightboxImagem || !lightboxTitulo) {
        return;
      }
      lightboxImagem.src = botao.getAttribute("data-galeria-imagem") || "";
      lightboxImagem.alt = botao.getAttribute("data-galeria-titulo") || "Imagem da galeria";
      lightboxImagem.hidden = false;
      lightboxTitulo.textContent = botao.getAttribute("data-galeria-titulo") || "";
      lightbox.classList.add("is-visible");
    });
  });

  if (lightbox) {
    lightbox.addEventListener("click", function (evento) {
      if (evento.target === lightbox || evento.target.closest("[data-lightbox-fechar]")) {
        fecharLightbox();
      }
    });
  }

  document.addEventListener("keydown", function (evento) {
    if (evento.key === "Escape") {
      fecharLightbox();
    }
  });
});
