# Nekoko Core — klasifikacija oglasa

CPT `oglas` (REST `/wp-json/wp/v2/oglasi`) koristi jednu hijerarhijsku taksonomiju `kategorija_oglasa` i nehijerarhijske `oglas_tag` tagove. Registracija je u `includes/content-model.php`.

## Klasifikacija

- Ponuda (`ponuda`) i Potražnja (`potraznja`) su root termini kategorija, ne posebna taksonomija ili post meta.
- `includes/classification.php` validira jednu granu, dodaje root i štiti REST, autosave i WordPress taxonomy API pozive. Prazni nedovršeni autosave nacrti su dozvoljeni, objavljivanje bez grane nije.
- Neispravni REST upisi dobijaju HTTP 400 pre promene podataka. Direktni WordPress taxonomy API nema pre-write filter: neispravna dodela/append vraća prethodni važeći skup, a API povratna vrednost sama nije validacioni WP_Error. Programatski pre upisa koristiti `nekoko_core_classify_categories()`.
- `includes/classification-admin.php` i `assets/classification-*` daju Gutenberg panel „Klasifikacija oglasa“, klasični nonce-zaštićen izbor, kolone Naslov/Autor/Tip/Kategorija/Datum i admin filtere po grani i kategoriji.
- Root kategorije se nalaze po slugu; nema fiksnih term ID vrednosti.
- Kategorije i podkategorije zadržavaju `nekoko_icon_id` i `nekoko_hero_id`; ova polja nisu menjana.

## Arhive

`/oglasi/`, `/oglas/{slug}/`, `/kategorije/{jedinstveni-slug}/`, `/oznake/{slug}/`.

Glavne grane: `/kategorije/ponuda/` i `/kategorije/potraznja/`. WordPress hijerarhijski taxonomy upit uključuje potomke. Podkategorije imaju različite slugove prema punoj grani. Zajednički template delovi ostaju u temi `template-parts/oglas/`; naslov kategorijske arhive prikazuje punu putanju.

Rewrite se soft-flushuje pri aktivaciji/deaktivaciji i jednom kroz izričito pokrenut lokalni reset, nikad na svakom requestu. Podešavanje permalinkova i postojeće stranice ostaju neizmenjeni.

## Lokalni demo podaci — 31. avgust 2026.

Lokalni reset/seed nije deo aktivnog plugina, aktivacije ili deploy procesa. Privremeni CLI alat zahtevao je tačnu lokalnu putanju, localhost home/siteurl i DB host, administratorski kontekst i nonce; uklonjen je nakon uspešnog izvršavanja i testiranja. Urađen je backup pre reseta. HTTP-zaštićen folder `.nekoko-local/` i `demo-users.local.txt` lokalno su isključeni kroz `.git/info/exclude`; nikad ih ne slati na produkciju.

Kreirano je 40 kategorija (20 u svakoj grani), 8 tagova, 5 izmišljenih subscriber naloga, 8 originalnih AI-generisanih WebP fotografija i 12 objavljenih oglasa. Slike su 1536 × 1024 i svaka se koristi najviše dva puta. Stari zajednički mediji sa test strane su sačuvani. Detaljan lokalni izveštaj i backup nalaze se u `.nekoko-local/`.

Obe grane imaju sledeće stablo:

- Podučavanje: Jezici, Muzika, Kreativne veštine.
- Čuvanje životinja: Psi i mačke, Egzotične životinje.
- Kuća i bašta: Popravke po kući, Travnjak i bašta, Organizovanje prostora.
- Izrada po meri: Šivenje i kostimi, Vajanje i skulpture, Dekoracije.
- Pomoć i događaji: Pomoć pri selidbi, Organizacija događaja, Neobični zadaci.

Ne dodaju se frontend forma, nove role/capabilities, moderacija, naplata ili frontend filteri.

## Centralna podešavanja sajta — NekoKO

Registracija je u `includes/settings.php`, koji učitava glavni fajl postojećeg `nekoko-core` plugina (projektni site-core sloj). Tema ne registruje options stranice.

- Glavni meni: NekoKO; naslov: NekoKO podešavanja; slug: `nekoko-settings`; ikonica: `dashicons-admin-generic`.
- Opšte: `nekoko-settings-general`.
- Header: `nekoko-settings-header`.
- Footer: `nekoko-settings-footer`.
- Sve stranice zahtevaju `manage_options` i koriste standardni ACF `post_id = options`.
- Glavni slug ostaje stabilan; uklonjen je samo automatski dupli podmeni roditelja.
- Provere `function_exists` omogućavaju bezbedno učitavanje plugina kada ACF Pro nije aktivan.

U trenutku implementacije nije bilo ACF field grupa, Local JSON grupa niti options stranica, uključujući header polja. Zbog toga nije bilo migracije ni menjanja field name/key vrednosti. Opšte, Header i Footer su namerno prazni; nisu izmišljena nova polja. Postojeći WordPress logo i navigacija nisu menjani.

### Konvencija za buduća podešavanja

- Nove globalne sekcije dodavati na `acf/init` pomoću `nekoko_core_add_settings_subpage( $slug, $menu_title, $page_title )`. Helper obavezno postavlja roditelja `nekoko-settings`; ne dodavati nove top-level menije.
- Field grupe definisati u PHP fajlovima ovog core plugina preko `acf_add_local_field_group`, uz proveru postojanja ACF funkcije. Fajlove uključivati iz plugina; struktura tako ostaje u repozitorijumu, bez oslanjanja na definicije samo u bazi.
- Location za header grupu: `options_page == nekoko-settings-header`, bez dodatnih OR lokacija na drugim ekranima. Za ostale sekcije koristiti njihove odgovarajuće slugove.
- Zadržavati stabilne field key i field name vrednosti. Tema samo čita potrebne vrednosti, npr. preko zaštićenog poziva `get_field( $field_name, 'option' )`.
- Ne menjati skladište `options` bez eksplicitne migracije podataka.

Provereni su stvarni WordPress admin menu/submenu registri i ACF renderovanje sve četiri rute. Nema duplih stavki ni field grupa. Simulacija jednog PHP procesa bez učitanog ACF-a prošla je bez fatal greške i bez trajne deaktivacije ACF plugina. Sačuvani sadržaj, korisnici, theme mods i postojeća ACF options podešavanja ostali su neizmenjeni.

## Header povezan sa ACF podešavanjima

Naknadno je dodata jedna PHP grupa `group_nekoko_header_settings`, naslov **Header podešavanja**, u `includes/header-fields.php`. Glavni plugin učitava taj fajl. Grupa se registruje na `acf/init` i vezuje samo za stvarnu postojeću options stranicu `nekoko-settings-header`. Nisu dodate options stranice niti menjana postojeća registracija menija.

Admin: [NekoKO → Header](http://localhost/nekoko/wp-admin/admin.php?page=nekoko-settings-header).

| Field name | Tip | Podrazumevano ponašanje |
| --- | --- | --- |
| header_login_label | Text | Prijava |
| header_login_link | Link | admin_url() |
| header_logout_label | Text | Odjava |
| header_logout_redirect | Page Link | Početna strana nakon odjave |
| header_post_ad_label | Text | Postavite oglas |
| header_post_ad_link | Link | home_url('/postavite-oglas/') |

Polja ranije nisu postojala. Opšte i Footer ostaju bez grupa; samo Header sada prikazuje šest polja. Skladište ostaje `options`. ACF može biti isključen: registracija se bezbedno preskače, a tema koristi iste fallback vrednosti.

Tema čita vrednosti u `functions.php`. Gost vidi Prijava; prijavljeni korisnik vidi Odjava sa dinamičkim `wp_logout_url()` i nonce zaštitom. Destinacija nakon odjave prolazi kroz `wp_validate_redirect()`. Link target se ograničava na isti ili novi tab; novi tab dobija `noopener noreferrer`. Nazivi i URL-ovi se escapuju u template-u.

Header ima jedan zajednički DOM za glavni meni i akcije: postojeći logo levo, meni u sredini, žuto dugme Postavite oglas i zatim Plavi okvir za Prijava/Odjava desno. Koriste se postojeće `is-style-nekoko-secondary` i `is-style-nekoko-outline` varijante iz neizmenjenog `assets/css/button-styles.css`, koji se sada učitava i na stranicama bez Gutenberg Button blokova.

Do širine 64rem isti panel postaje mobilni meni, zatvoren pri učitavanju, sa punom širinom oba dugmeta. Hamburger ima aria-expanded/aria-controls/aria-label, animaciju u X i vidljiv fokus. Escape zatvara panel i vraća fokus na dugme; promena breakpoint-a resetuje stanje; reduced-motion isključuje animaciju. Nije uveden jQuery niti menjano sticky ponašanje.

Postojeća objavljena stranica Postavite oglas (ID 69) zadržana je bez promene sadržaja. U postojeći core URL registar dodato je samo pravilo za `/postavite-oglas/`, potrebno zbog nasleđenog index.php permalink podešavanja. Pravila su osvežena preko aktivacije core plugina.

### Fajlovi ovog ažuriranja

- Dodato: `wp-content/plugins/nekoko-core/includes/header-fields.php`.
- Izmenjeno: `wp-content/plugins/nekoko-core/nekoko-core.php`, `includes/content-model.php` i ovaj README.
- Izmenjeno u temi Nekoko: `functions.php`, `header.php`, `style.css`, `assets/js/theme.js`.

### Provere headera

- ACF admin render: šest polja, svako jednom, samo na Header stranici; nema poruke o nedostajućim grupama na Header-u.
- Stvarni desktop prikaz na 1440 i 1025 px, mobilni prikaz na 375 i 320 px: bez horizontalnog skrola.
- Provereni redosled logotipa, menija i dva dugmeta; postojeći logo ima alt i link ka početnoj.
- Proverene normal/hover/focus boje, 2px okvir, puna širina mobilnih dugmadi, animacija u X, Tab redosled, Escape, ponovno zatvaranje, resize reset i reduced-motion. Nema JavaScript grešaka.
- ACF upis/čitanje naziva, oba linka i stranice nakon odjave provereni privremenim vrednostima preko API-ja; sve originalne vrednosti vraćene u istom procesu.
- Server render za gosta i prijavljenog korisnika; validan logout nonce i odbijanje izmenjenog nonce-a. Nije odjavljivana korisnikova stvarna browser sesija.
- Provereni prazni i nebezbedni URL-ovi, HTML escaping i fallback rad bez učitanog ACF-a.
- /postavite-oglas/ vraća HTTP 200. Naziv sajta, tagline, logo, postojeći primarni meni i sadržaj stranice 69 nisu menjani.
- Browser alat nije mogao da pokrene sesiju; vizuelne provere završene su u izolovanom lokalnom headless Chrome-u, bez korisničkog profila.
- Regenerisani su samo keširani CSS/JS fajlovi teme: standardni WP Rocket purge nije uklonio te fajlove na Windowsu, pa su četiri tačno određena generisana CSS/JS/gzip fajla uklonjena. Izvorni fajlovi i mediji nisu brisani.
