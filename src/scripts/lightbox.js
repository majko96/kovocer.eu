import PhotoSwipeLightbox from "photoswipe/lightbox";
import "photoswipe/style.css";

const items = [...document.querySelectorAll("[data-lightbox]")];

if (items.length) {
  // Popisky sa zámerne nezobrazujú; alt ostáva len pre čítačky obrazovky.
  const lightbox = new PhotoSwipeLightbox({
    dataSource: items.map((el) => ({
      src: el.getAttribute("src"),
      width: Number(el.getAttribute("width")) || el.naturalWidth,
      height: Number(el.getAttribute("height")) || el.naturalHeight,
      alt: el.getAttribute("alt") || "",
      element: el,
    })),
    pswpModule: () => import("photoswipe"),
    bgOpacity: 0.96,
    padding: { top: 56, bottom: 24, left: 8, right: 8 },
    wheelToZoom: true,
    closeTitle: "Zavrieť",
    zoomTitle: "Priblížiť",
    arrowPrevTitle: "Predchádzajúca fotografia",
    arrowNextTitle: "Ďalšia fotografia",
    errorMsg: "Fotografiu sa nepodarilo načítať.",
  });
  lightbox.init();

  items.forEach((el, i) => {
    el.style.cursor = "zoom-in";
    el.setAttribute("tabindex", "0");
    el.setAttribute("role", "button");
    el.addEventListener("click", (e) => {
      e.preventDefault();
      lightbox.loadAndOpen(i);
    });
    el.addEventListener("keydown", (e) => {
      if (e.key === "Enter" || e.key === " ") {
        e.preventDefault();
        lightbox.loadAndOpen(i);
      }
    });
  });
}
