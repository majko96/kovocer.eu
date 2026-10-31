const items = [...document.querySelectorAll("[data-reveal]")];

if (items.length) {
  const still = window.matchMedia("(prefers-reduced-motion: reduce)").matches;

  // Asked for motion and given none: show everything at once rather than leave the page blank.
  if (still || !("IntersectionObserver" in window)) {
    items.forEach((el) => el.setAttribute("data-revealed", ""));
  } else {
    // Injected from JS so the content stays visible when scripts are off.
    const style = document.createElement("style");
    style.textContent =
      "[data-reveal]{opacity:0;transform:translateY(1rem);" +
      "transition:opacity .6s cubic-bezier(.16,1,.3,1),transform .6s cubic-bezier(.16,1,.3,1)}" +
      "[data-reveal][data-revealed]{opacity:1;transform:none}";
    document.head.appendChild(style);

    const io = new IntersectionObserver(
      (entries) => {
        entries.forEach((entry) => {
          if (!entry.isIntersecting) return;
          entry.target.setAttribute("data-revealed", "");
          io.unobserve(entry.target);
        });
      },
      { rootMargin: "0px 0px -10% 0px", threshold: 0.1 },
    );

    items.forEach((el) => io.observe(el));
  }
}
