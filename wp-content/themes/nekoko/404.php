<?php
/** NekoKO 404 page. */
wp_enqueue_style('nekoko-404', get_theme_file_uri('assets/css/not-found.css'), array(), filemtime(get_theme_file_path('assets/css/not-found.css')));
get_header();
$search_url = home_url('/napredna-pretraga/');
?>
<main id="primary" class="site-main nk404">
<section class="nk404-panel" aria-labelledby="nk404-title">
<div class="nk404-art" aria-hidden="true"><span class="nk404-star">✦</span><div class="nk404-number">4<span class="nk404-lens"></span>4</div><span class="nk404-note">Ovde nema ničega… za sada.</span></div>
<div class="nk404-content">
<p class="nk404-eyebrow">GREŠKA 404 · STRANICA NIJE PRONAĐENA</p>
<h1 id="nk404-title">Ova stranica se malo zagubila.</h1>
<p class="nk404-description">Možda je link promenjen, a možda stranica više ne postoji. Ali neko ko vam treba verovatno je baš ovde.</p>
<form class="nk404-search" role="search" method="get" action="<?php echo esc_url($search_url); ?>">
<label for="nk404-query">Šta tražite?</label><div><input id="nk404-query" type="search" name="q" placeholder="Usluga, veština ili neobičan posao…" required><button type="submit" aria-label="Pretraži oglase"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="10" cy="10" r="6.5"/><path d="m15 15 6 6"/></svg></button></div>
</form>
<div class="nk404-actions"><a class="nk404-home" href="<?php echo esc_url(home_url('/')); ?>">Nazad na početnu <span aria-hidden="true">↗</span></a><a class="nk404-browse" href="<?php echo esc_url($search_url); ?>">Pogledajte sve oglase <span aria-hidden="true">→</span></a></div>
<p class="nk404-support">Nešto ne radi kako treba? <a href="mailto:info@nekoko.rs">Javite nam se.</a></p>
</div></section></main>
<?php get_footer(); ?>
