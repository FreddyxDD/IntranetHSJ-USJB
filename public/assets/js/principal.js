// ======================================================
// VERIFICAR SESIÓN ACTIVA
// ======================================================
async function verificarSesion() {
    try {
        const res = await fetch("/me-ueei", {
            credentials: "include"
        });

        const data = await res.json();

        if (!data.ok) {
            window.location.href = "/";
        }

    } catch (error) {
        window.location.href = "/";
    }
}

verificarSesion();

// ======================================================
// AÑO AUTOMÁTICO
// ======================================================
const year = document.getElementById("year");

if (year) {
    year.textContent = new Date().getFullYear();
}

// ======================================================
// VARIABLES
// ======================================================
const hero = document.getElementById("hero");
const captionText = document.getElementById("captionText");
const heroTitle = document.getElementById("heroTitle");
const heroDesc = document.getElementById("heroDesc");
const menuToggle = document.getElementById("menuToggle");
const mainNav = document.getElementById("mainNav");
const dropdownMenu = document.getElementById("dropdownMenu");
const logoutBtn = document.getElementById("logoutBtn");
const searchInput = document.getElementById("searchInput");
const searchBtn = document.getElementById("searchBtn");
const searchSuggestions = document.getElementById("searchSuggestions");
const scrollTopBtn = document.getElementById("scrollTopBtn");
const navLinks = document.querySelectorAll(
    "#mainNav a[href^='#']"
);

// ======================================================
// IMÁGENES DEL HERO
// ======================================================
const fondos = [
    {
        img: "/assets/images/imaguen1.png",
        text: "Sistema de gestión hospitalaria",
        title: "Hospital San José",
        desc: "Accede a estadísticas, módulos internos, reportes y servicios digitales desde un solo lugar."
    },
    {
        img: "/assets/images/imaguen2.jpg",
        text: "Gestión rápida y centralizada",
        title: "Hospital San José",
        desc: "Unifica accesos a egresos, emergencias, reportes y servicios institucionales."
    },
    {
        img: "/assets/images/imaguen3.png",
        text: "Acceso a estadísticas y reportes",
        title: "Hospital San José",
        desc: "Consulta módulos clave de forma ágil y con una experiencia más moderna."
    }
];

// ======================================================
// BUSCADOR
// ======================================================
const modulosAutorizados = Array.isArray(window.UEEI_MODULOS)
    ? window.UEEI_MODULOS
    : [];

function aliasesModulo(modulo) {
    const codigo = String(modulo.codigo || "");
    const nombre = String(modulo.nombre || "");
    const aliases = [
        nombre,
        codigo.replaceAll("_", " ")
    ];

    if (codigo === "administracion") {
        aliases.push("admin", "usuarios", "permisos");
    }

    if (codigo === "informacion") {
        aliases.push("información", "reportes");
    }

    if (codigo === "produccion") {
        aliases.push("producción", "rendimiento");
    }

    if (codigo === "citas_admin") {
        aliases.push("citas", "citas admin");
    }

    if (codigo === "seguros_privados") {
        aliases.push("seguros", "seguros privados");
    }

    return aliases.filter(Boolean);
}

const accesos = modulosAutorizados.flatMap(modulo =>
    aliasesModulo(modulo).map(nombre => ({
        nombre,
        url: construirUrlModulo(modulo.ruta || "#")
    }))
);

function construirUrlModulo(ruta) {
    const path = String(ruta || "#");

    if (path === "#" || path.startsWith("http")) {
        return path;
    }

    const base = String(window.APP_BASE || "").replace(/\/$/, "");

    return `${base}/${path.replace(/^\/+/, "")}`;
}

// ======================================================
// SLIDER
// ======================================================
let indice = 0;
let sliderInterval = null;

function aplicarSlide(index) {
    const item = fondos[index];

    if (!hero || !captionText || !heroTitle || !heroDesc) return;

    hero.style.backgroundImage = `url('${item.img}')`;
    captionText.textContent = item.text;

    heroTitle.classList.remove("fade-up");
    heroDesc.classList.remove("fade-up");

    void heroTitle.offsetWidth;

    heroTitle.textContent = item.title;
    heroDesc.textContent = item.desc;

    heroTitle.classList.add("fade-up");
    heroDesc.classList.add("fade-up");
}

function cambiarFondo() {
    indice = (indice + 1) % fondos.length;
    aplicarSlide(indice);
}

function iniciarSlider() {
    if (sliderInterval) clearInterval(sliderInterval);
    sliderInterval = setInterval(cambiarFondo, 5000);
}

// ======================================================
// BOTÓN SUBIR
// ======================================================
if (scrollTopBtn) {
    window.addEventListener("scroll", () => {
        if (window.scrollY > 300) {
            scrollTopBtn.classList.add("show");
        } else {
            scrollTopBtn.classList.remove("show");
        }
    });

    scrollTopBtn.addEventListener("click", () => {
        window.scrollTo({
            top: 0,
            behavior: "smooth"
        });
    });
}

// ======================================================
// FUNCIÓN BUSCAR
// ======================================================
function normalizarTexto(texto) {
    return texto
        .toLowerCase()
        .normalize("NFD")
        .replace(/[\u0300-\u036f]/g, "")
        .trim();
}

function buscarAcceso() {
    if (!searchInput) return;

    const texto = normalizarTexto(searchInput.value);

    if (!texto) return;

    const match = accesos.find(item =>
        normalizarTexto(item.nombre).includes(texto)
    );

    if (match) {
        window.location.href = match.url;
    } else {
        alert("No se encontró un módulo relacionado.");
    }
}

// ======================================================
// SUGERENCIAS
// ======================================================
function renderSuggestions(valor) {
    if (!searchSuggestions) return;

    const texto = normalizarTexto(valor);
    searchSuggestions.innerHTML = "";

    if (!texto) {
        searchSuggestions.style.display = "none";
        return;
    }

    const filtrados = accesos
        .filter(item => normalizarTexto(item.nombre).includes(texto))
        .slice(0, 5);

    if (!filtrados.length) {
        searchSuggestions.style.display = "none";
        return;
    }

    filtrados.forEach(item => {
        const btn = document.createElement("button");
        btn.type = "button";
        btn.textContent = item.nombre;

        btn.addEventListener("click", () => {
            window.location.href = item.url;
        });

        searchSuggestions.appendChild(btn);
    });

    searchSuggestions.style.display = "flex";
}

// ======================================================
// EVENTOS DEL BUSCADOR
// ======================================================
if (searchBtn) {
    searchBtn.addEventListener("click", buscarAcceso);
}

if (searchInput) {
    searchInput.addEventListener("keydown", (e) => {
        if (e.key === "Enter") buscarAcceso();
    });

    searchInput.addEventListener("input", (e) => {
        renderSuggestions(e.target.value);
    });
}

document.addEventListener("click", (e) => {
    if (
        searchSuggestions &&
        searchInput &&
        !searchSuggestions.contains(e.target) &&
        e.target !== searchInput
    ) {
        searchSuggestions.style.display = "none";
    }
});

// ======================================================
// MENÚ DESPLEGABLE
// ======================================================
if (menuToggle && dropdownMenu && mainNav) {
    menuToggle.addEventListener("click", (e) => {
        e.stopPropagation();
        dropdownMenu.classList.toggle("dropdown-menu--show");
        mainNav.classList.toggle("nav--open");
    });

    document.addEventListener("click", (e) => {
        if (!dropdownMenu.contains(e.target) && e.target !== menuToggle) {
            dropdownMenu.classList.remove("dropdown-menu--show");
        }

        if (!mainNav.contains(e.target) && e.target !== menuToggle) {
            mainNav.classList.remove("nav--open");
        }
    });
}

// ======================================================
// ENLACE ACTIVO DEL MENÚ
// ======================================================

const seccionesMenu = Array.from(navLinks)
    .map(link => {
        const selector = link.getAttribute("href");
        return document.querySelector(selector);
    })
    .filter(Boolean);

function actualizarEnlaceActivo() {
    const alturaTopbar =
        document.querySelector(".topbar")?.offsetHeight || 0;

    const posicionActual =
        window.scrollY + alturaTopbar + 80;

    let idActivo = "accesos";

    seccionesMenu.forEach(seccion => {
        if (posicionActual >= seccion.offsetTop) {
            idActivo = seccion.id;
        }
    });

    navLinks.forEach(link => {
        const esActivo =
            link.getAttribute("href") === `#${idActivo}`;

        link.classList.toggle("activo", esActivo);
    });
}

navLinks.forEach(link => {
    link.addEventListener("click", () => {
        navLinks.forEach(item => {
            item.classList.remove("activo");
        });

        link.classList.add("activo");
    });
});

window.addEventListener(
    "scroll",
    actualizarEnlaceActivo,
    { passive: true }
);

window.addEventListener(
    "load",
    actualizarEnlaceActivo
);

// ======================================================
// CERRAR SESIÓN
// ======================================================
if (logoutBtn) {
    logoutBtn.addEventListener("click", async () => {
        try {
            const res = await fetch("/logout-ueei", {
                method: "POST",
                credentials: "include"
            });

            const data = await res.json();

            if (data.ok) {
                window.location.href = "/";
            } else {
                alert("No se pudo cerrar la sesión.");
            }

        } catch (error) {
            console.error("Error al cerrar sesión:", error);
            alert("Error al cerrar sesión.");
        }
    });
}

// ======================================================
// ANIMACIÓN AL HACER SCROLL
// ======================================================
const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            entry.target.classList.add("reveal--visible");
        }
    });
}, {
    threshold: 0.15
});

document.querySelectorAll(".reveal").forEach(el => observer.observe(el));

// ======================================================
// INICIALIZACIÓN
// ======================================================
aplicarSlide(indice);
iniciarSlider();
