document.addEventListener("DOMContentLoaded", () => {
    const barCards = document.querySelectorAll(".animate-bars");
    const donutCards = document.querySelectorAll(".animate-donut");
    const lineCards = document.querySelectorAll(".animate-line");

    function resetBar(bar) {
        bar.style.transition = "none";
        bar.style.transform = "scaleY(0)";
        bar.style.opacity = "0.35";
    }

    function activateBars(card) {
        const bars = card.querySelectorAll(".bar");
        if (!bars.length) return;

        bars.forEach(resetBar);

        void card.offsetHeight;

        bars.forEach((bar, index) => {
            bar.style.transition =
                "transform 0.85s cubic-bezier(0.22, 1, 0.36, 1), opacity 0.5s ease, filter 0.3s ease";
            bar.style.transitionDelay = `${0.05 + index * 0.07}s`;
            bar.style.transform = "scaleY(1)";
            bar.style.opacity = "1";
        });
    }

    function animateDonut(card) {
        const donut = card.querySelector(".fake-donut");
        const center = card.querySelector(".donut-center");

        if (!donut || !center) return;

        const target = Number(donut.dataset.percent) || 68;
        let current = 0;

        donut.style.setProperty("--percent", 0);
        center.textContent = "0%";

        if (donut._interval) {
            clearInterval(donut._interval);
        }

        donut._interval = setInterval(() => {
            current++;

            donut.style.setProperty("--percent", current);
            center.textContent = `${current}%`;

            if (current >= target) {
                clearInterval(donut._interval);
            }
        }, 18);
    }

    function activateLine(card) {
        const path = card.querySelector(".line-path");
        if (!path) return;

        const length = path.getTotalLength();

        path.style.transition = "none";
        path.style.strokeDasharray = length;
        path.style.strokeDashoffset = length;

        void path.getBoundingClientRect();

        path.style.transition =
            "stroke-dashoffset 1.4s cubic-bezier(0.22, 1, 0.36, 1)";
        path.style.strokeDashoffset = "0";
    }

    function initAnimations() {
        barCards.forEach(activateBars);
        donutCards.forEach(animateDonut);
        lineCards.forEach(activateLine);
    }

    initAnimations();

    barCards.forEach(card => {
        card.addEventListener("mouseenter", () => {
            activateBars(card);
        });
    });

    donutCards.forEach(card => {
        card.addEventListener("mouseenter", () => {
            animateDonut(card);
        });
    });

    lineCards.forEach(card => {
        card.addEventListener("mouseenter", () => {
            activateLine(card);
        });
    });
});