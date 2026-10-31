export const site = {
  name: "KOVOCER.eu",
  owner: "Pavol Čerňanský",
  phone: "0911 782 251",
  phoneHref: "tel:+421911782251",
  email: "kovocer@gmail.com",
  street: "Prečín 447",
  city: "018 15 Prečín",
  mapEmbed: "https://www.google.com/maps?q=Kovocer,+Pre%C4%8D%C3%ADn+447&z=11&hl=sk&output=embed",
} as const;

/**
 * Ako funguje „Napíšte nám":
 *  - "link"   – obyčajný odkaz mailto: (netreba SMTP ani formulár),
 *  - "mailto" – okno s formulárom, ktoré po odoslaní otvorí e-mailového klienta s vyplnenou správou,
 *  - "smtp"   – okno s formulárom, ktoré odošle správu cez public/sendEmail.php
 *               (treba nastaviť smtp-config.php alebo SMTP_* premenné).
 */
export const formMode: "link" | "mailto" | "smtp" = "link";

export const button =
  "inline-flex items-center gap-3 rounded-md bg-linear-to-b from-[#D8C690] to-[#B39D60] font-display font-bold uppercase tracking-[0.14em] text-bg transition hover:-translate-y-0.5";
