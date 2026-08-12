@extends('layouts.app')

@section('title', $page->localizedMetaTitle() ?: $page->localizedTitle())
@section('meta_description', $page->localizedMetaDescription() ?: str($page->localizedTitle())->limit(150))
@section('meta_keywords', $page->localizedMetaKeywords())
@section('og_image', $page->featured_image ? asset($page->featured_image) : $siteSettings->logoUrl())

@section('content')
@php
    $featuredImageUrl = null;
    if ($page->featured_image) {
        $featuredImageUrl = str_starts_with($page->featured_image, 'http')
            ? $page->featured_image
            : (\Illuminate\Support\Facades\File::exists(public_path($page->featured_image)) ? asset($page->featured_image) : null);
    }
    $localizedBlocks = $page->localizedContentBlocks();
    $startsWithHero = ($localizedBlocks[0]['type'] ?? null) === 'row'
        && ($localizedBlocks[0]['data']['section'] ?? null) === 'hero';
@endphp
<main class="{{ $startsWithHero ? 'pt-0' : 'pt-32' }} pb-16">
    <article class="cms-page-shell {{ $startsWithHero ? 'cms-page-shell--starts-hero w-full' : ($page->template === 'wide' ? 'max-w-350' : 'max-w-5xl').' mx-auto px-4 sm:px-6 lg:px-8' }}">
        <header class="{{ $startsWithHero ? 'sr-only' : 'mb-8 border-b border-gray-200 pb-6' }}">
            @if($page->status !== 'published')
                <div class="mb-4 inline-flex rounded-full bg-amber-50 px-3 py-1 text-xs font-extrabold uppercase tracking-widest text-amber-700 ring-1 ring-amber-200">
                    Draft preview
                </div>
            @endif
            @if($page->parent)
                <a href="{{ $page->parent->url }}" class="text-sm font-bold text-[#1a5632] hover:underline">{{ $page->parent->localizedTitle() }}</a>
            @endif
            <h1 class="mt-2 text-3xl sm:text-5xl font-extrabold tracking-tight text-gray-950">{{ $page->localizedTitle() }}</h1>
            @if($featuredImageUrl)
                <img src="{{ $featuredImageUrl }}" alt="{{ $page->localizedTitle() }}" class="mt-6 aspect-[16/7] w-full rounded-2xl object-cover">
            @endif
        </header>

        <div class="cms-content">
            {!! $page->renderBlocks() !!}
        </div>
    </article>
</main>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&family=DM+Sans:opsz,wght@9..40,400;9..40,500;9..40,700;9..40,800&display=swap" rel="stylesheet">
<style>
    /* ── TOKENS ─────────────────────────────────────────────────── */
    .cms-content{
        --cg:var(--theme-primary);--cg-d:var(--theme-dark);--cg-l:var(--theme-primary-soft);
        --gold:var(--theme-secondary);--gold-l:var(--theme-secondary-soft);--gold-v:var(--theme-secondary-light);
        --ink:#111827;--mist:#64748b;--ash:var(--theme-muted-surface);
        --surface:var(--theme-surface);--line:var(--theme-border);
        --r:.9rem;--r-lg:1.15rem;
        --sh:0 10px 30px rgba(11,36,21,.08);
        --sh-lg:0 22px 54px rgba(11,36,21,.14);
        font-family:var(--ff-body);
        font-size:.97rem;line-height:1.75;color:var(--theme-text);
    }
    .cms-page-shell--starts-hero{margin-top:0}
    /* ── LAYOUT ─────────────────────────────────────────────────── */
    .cms-content .cms-row{margin:0;align-items:start}
    .cms-content > .cms-row:not(.cms-hero-section):not(.cms-section--timeline){margin:3.75rem auto;width:min(100% - 2rem,1400px)}
    .cms-content > .cms-row:not(.cms-hero-section){width:min(100% - 2rem, 1400px);margin-left:auto;margin-right:auto}
    @media(min-width:640px){.cms-content > .cms-row:not(.cms-hero-section):not(.cms-section--timeline){width:min(100% - 3rem,1400px)}}
    @media(min-width:1024px){.cms-content > .cms-row:not(.cms-hero-section):not(.cms-section--timeline){width:min(100% - 10rem,1400px)}}
    .cms-content .cms-row-grid{display:grid;grid-template-columns:1fr;gap:1.5rem}
    .cms-content .cms-row--gap-compact .cms-row-grid{gap:.85rem}
    .cms-content .cms-row--gap-normal .cms-row-grid{gap:1.5rem}
    .cms-content .cms-row--gap-large .cms-row-grid{gap:2.25rem}
    .cms-content .cms-row--narrow{max-width:48rem;margin-left:auto;margin-right:auto}
    .cms-content .cms-column{min-width:0}
    .cms-content .cms-column > .cms-block:first-child,.cms-content .cms-column > figure.cms-block:first-child{margin-top:0}
    .cms-content .cms-column > .cms-block:last-child,.cms-content .cms-column > figure.cms-block:last-child{margin-bottom:0}
    .cms-content .cms-block{margin:1.25rem 0}
    .cms-content .cms-block--narrow{max-width:48rem;margin-left:auto;margin-right:auto}
    .cms-content .cms-block--wide{max-width:100%}
    .cms-content .cms-align--center{text-align:center}
    .cms-content .cms-align--right{text-align:right}
    /* ── TYPOGRAPHY ─────────────────────────────────── */
    .cms-content h2{font-family:var(--ff-head);font-size:clamp(1.75rem,3vw,2.65rem);font-weight:900;line-height:1.16;color:var(--ink);margin:0 0 .75rem}
    .cms-content h3{margin:0 0 .5rem;font-size:1.08rem;line-height:1.35;font-weight:800;color:var(--ink)}
    .cms-content h4{margin:0 0 .5rem;font-size:.95rem;font-weight:800;color:var(--ink)}
    .cms-content p{margin:0 0 1rem;text-align:justify;text-wrap:pretty}
    .cms-content strong{font-weight:800;color:var(--ink)}
    .cms-content figure{margin:1.5rem 0}
    .cms-content img{max-width:100%;border-radius:var(--r)}
    .cms-content figcaption{margin-top:.5rem;text-align:center;font-size:.8rem;color:var(--mist)}
    /* ── EYEBROW + HEAD ─────────────────────────────── */
    .cms-content .cms-eyebrow{display:inline-flex;align-items:center;gap:.5rem;margin:0 0 .65rem;font-size:.66rem;font-weight:900;letter-spacing:.18em;text-transform:uppercase;color:var(--cg)}
    .cms-content .cms-eyebrow::before{content:'';display:inline-block;width:1.4rem;height:2px;background:var(--gold);border-radius:2px}
    .cms-content .cms-section-head{margin-bottom:2rem;max-width:48rem}
    .cms-content .cms-section-desc{margin:.4rem 0 0;color:var(--mist);line-height:1.75}
    .cms-content .cms-section-actions{display:flex;flex-wrap:wrap;gap:.75rem;margin-top:1.25rem}
    /* ── PALETTES ───────────────────────────────────── */
    .cms-content .cms-palette--light{background:var(--ash);color:var(--ink)}
    .cms-content .cms-palette--dark{background:var(--theme-cta-gradient);color:#fff}
    .cms-content .cms-palette--green{background:var(--cg-d);color:#fff}
    .cms-content .cms-palette--blue{background:var(--theme-hero-gradient);color:#fff}
    .cms-content .cms-palette--amber{background:var(--gold-l);color:var(--ink)}
    .cms-content .cms-palette--image{background-size:cover;background-position:center;color:#fff}
    .cms-content .cms-palette--dark h2,.cms-content .cms-palette--green h2,
    .cms-content .cms-palette--blue h2,.cms-content .cms-palette--image h2{color:#fff}
    .cms-content .cms-palette--dark .cms-section-desc,.cms-content .cms-palette--green .cms-section-desc,
    .cms-content .cms-palette--blue .cms-section-desc{color:rgba(255,255,255,.76)}
    .cms-content .cms-palette--dark .cms-eyebrow,.cms-content .cms-palette--green .cms-eyebrow,
    .cms-content .cms-palette--blue .cms-eyebrow{color:var(--gold-v)}
    .cms-content .cms-palette--dark .cms-eyebrow::before,.cms-content .cms-palette--green .cms-eyebrow::before,
    .cms-content .cms-palette--blue .cms-eyebrow::before{background:var(--gold)}
    /* ── PATTERNS ───────────────────────────────────── */
    .cms-content .cms-pattern--grid{background-image:linear-gradient(90deg,rgba(255,255,255,.08) 1px,transparent 1px),linear-gradient(180deg,rgba(255,255,255,.08) 1px,transparent 1px);background-size:44px 44px}
    .cms-content .cms-pattern--dots{background-image:radial-gradient(rgba(255,255,255,.16) 1px,transparent 1.2px);background-size:22px 22px}
    .cms-content .cms-pattern--diagonal{background-image:repeating-linear-gradient(135deg,rgba(255,255,255,.09) 0 1px,transparent 1px 18px)}
    .cms-content .cms-pattern--none{background-image:none}
    .cms-content .cms-palette--light.cms-pattern--grid,.cms-content .cms-palette--amber.cms-pattern--grid{background-image:linear-gradient(90deg,rgba(15,23,42,.05) 1px,transparent 1px),linear-gradient(180deg,rgba(15,23,42,.05) 1px,transparent 1px);background-size:44px 44px}
    .cms-content .cms-palette--light.cms-pattern--dots,.cms-content .cms-palette--amber.cms-pattern--dots{background-image:radial-gradient(rgba(15,23,42,.10) 1px,transparent 1.2px);background-size:22px 22px}
    /* ── HERO ───────────────────────────────────────── */
    .cms-content .cms-section--dark{border-radius:var(--r);background:linear-gradient(135deg,var(--theme-primary) 0%,var(--theme-dark) 100%);color:#fff;padding:2rem;overflow:hidden}
    .cms-content .cms-hero-section{
        position:relative;display:grid;gap:2.5rem;align-items:start;
        border-radius:0;margin:-1px 0 0;width:100%;
        min-height:min(560px,68svh);
        padding:2.5rem max(1.25rem,calc((100vw - 1500px)/2)) 4rem;
        background:var(--theme-hero-gradient);
        overflow:hidden;
    }
    .cms-content .cms-hero-section::before{content:'';position:absolute;inset:0;background:linear-gradient(90deg,rgba(255,255,255,.05) 1px,transparent 1px),linear-gradient(180deg,rgba(255,255,255,.05) 1px,transparent 1px);background-size:54px 54px;mask-image:linear-gradient(90deg,#000,transparent 72%);pointer-events:none}
    .cms-content .cms-hero-copy{position:relative;z-index:1;max-width:44rem}
    .cms-content .cms-hero-section--text-only{grid-template-columns:minmax(0,1fr)}
    .cms-content .cms-hero-section--text-only .cms-hero-copy{max-width:min(100%,78rem)}
    .cms-content .cms-hero-breadcrumb{display:flex;gap:.5rem;margin-bottom:1.25rem;font-size:.84rem;font-weight:800;color:rgba(255,255,255,.55)}
    .cms-content .cms-hero-breadcrumb a{color:rgba(255,255,255,.55);text-decoration:none}
    .cms-content .cms-hero-breadcrumb strong{color:#fff}
    .cms-content .cms-hero-badge{display:inline-flex;max-width:100%;margin:0 0 1.4rem;border-radius:999px;background:linear-gradient(135deg,var(--gold),var(--gold-v));padding:.5rem 1.1rem;color:var(--cg-d);font-size:.66rem;font-weight:900;letter-spacing:.12em;text-transform:uppercase;box-shadow:0 4px 18px color-mix(in srgb, var(--gold) 34%, transparent)}
    .cms-content .cms-hero-section h2{font-family:var(--ff-head);max-width:42rem;color:#fff;font-size:clamp(2.2rem,4vw,4rem);line-height:1.1;font-weight:900;margin-bottom:1.2rem}
    .cms-content .cms-hero-section .cms-section-desc{max-width:40rem;color:rgba(255,255,255,.78);font-size:1rem;line-height:1.8}
    .cms-content .cms-hero-section--text-only h2{max-width:72rem}
    .cms-content .cms-hero-section--text-only .cms-section-desc{max-width:64rem}
    .cms-content .cms-hero-image{position:relative;margin:0;min-height:24rem;overflow:hidden;border-radius:1.15rem;box-shadow:0 28px 64px rgba(0,0,0,.35);border:1px solid rgba(255,255,255,.16)}
    .cms-content .cms-hero-image img{height:100%;min-height:24rem;width:100%;object-fit:cover;border-radius:0}
    .cms-content .cms-hero-image figcaption{position:absolute;left:1rem;right:1rem;bottom:1rem;border-radius:.9rem;background:rgba(255,255,255,.96);padding:.85rem 1rem;backdrop-filter:blur(8px);box-shadow:0 16px 32px rgba(15,23,42,.2)}
    .cms-content .cms-hero-image figcaption span{display:block;color:var(--cg);font-size:.64rem;font-weight:900;letter-spacing:.15em;text-transform:uppercase}
    .cms-content .cms-hero-image figcaption strong{display:block;margin-top:.3rem;color:var(--theme-text);font-size:.86rem;line-height:1.5}
    .cms-content .cms-hero-section .cms-button{background:var(--gold);color:var(--cg-d);font-weight:900;box-shadow:0 4px 18px color-mix(in srgb, var(--gold) 38%, transparent)}
    .cms-content .cms-hero-section .cms-button:hover{background:var(--gold-v);transform:translateY(-2px)}
    .cms-content .cms-hero-section .cms-button--outline{background:rgba(255,255,255,.1);color:#fff;border:1.5px solid rgba(255,255,255,.3)}
    /* ── STATS ──────────────────────────────────────── */
    .cms-content .cms-section--stats{
        position:relative;z-index:2;margin-top:-4.5rem;
        border-radius:1.5rem;background:var(--surface);
        box-shadow:0 28px 64px rgba(11,36,21,.14);overflow:hidden;
    }
    .cms-content .cms-section--stats .cms-row-grid{gap:0}
    .cms-content .cms-section--stats .cms-column{border-right:1px solid var(--line)}
    .cms-content .cms-section--stats .cms-column:last-child{border-right:none}
    .cms-content .cms-section--stats .cms-stat{padding:1.5rem 1.25rem;text-align:center;position:relative}
    .cms-content .cms-section--stats .cms-stat::before{content:'';position:absolute;top:0;left:0;right:0;height:3px}
    .cms-content .cms-section--stats .cms-column:nth-child(1) .cms-stat::before{background:var(--cg)}
    .cms-content .cms-section--stats .cms-column:nth-child(2) .cms-stat::before{background:var(--gold)}
    .cms-content .cms-section--stats .cms-column:nth-child(3) .cms-stat::before{background:var(--theme-primary-light)}
    .cms-content .cms-section--stats .cms-column:nth-child(4) .cms-stat::before{background:var(--theme-dark)}
    .cms-content .cms-section--stats .cms-stat:hover{background:var(--ash)}
    .cms-content .cms-stat{margin:0}
    .cms-content .cms-stat p{margin:0;font-size:.62rem;font-weight:900;letter-spacing:.15em;text-transform:uppercase;color:var(--mist)}
    .cms-content .cms-stat strong{display:block;margin-top:.4rem;font-family:var(--ff-head);font-size:clamp(1.1rem,2vw,1.5rem);line-height:1.25;color:var(--ink);font-weight:900}
    /* ── SECTION TYPES ──────────────────────────────── */
    .cms-content .cms-section--cards{border-radius:var(--r-lg);background:var(--ash);padding:2rem}
    .cms-content .cms-section--cards .cms-section-head{max-width:100%}
    .cms-content .cms-section--cta{
        border-radius:var(--r-lg);overflow:hidden;position:relative;
        background:var(--theme-cta-gradient);
        color:#fff;padding:2.5rem 2rem;
    }
    .cms-content .cms-section--cta::before{content:'';position:absolute;inset:0;background:radial-gradient(ellipse 75% 55% at 92% 50%,rgba(226,160,36,.16),transparent);pointer-events:none}
    .cms-content .cms-section--cta h2{font-family:var(--ff-head);color:#fff;font-size:clamp(1.5rem,2.5vw,2.25rem);position:relative}
    .cms-content .cms-section--cta .cms-section-desc{color:rgba(255,255,255,.78)}
    .cms-content .cms-section--cta .cms-eyebrow{color:var(--gold-v)}
    .cms-content .cms-section--cta .cms-eyebrow::before{background:var(--gold)}
    .cms-content .cms-section--cta .cms-button{background:var(--gold);color:var(--cg-d);font-weight:900;box-shadow:0 4px 18px color-mix(in srgb, var(--gold) 42%, transparent)}
    .cms-content .cms-section--cta .cms-button:hover{background:var(--gold-v);transform:translateY(-2px)}
    .cms-content .cms-section--cta .cms-button--outline{background:rgba(255,255,255,.12);color:#fff;border:1.5px solid rgba(255,255,255,.32)}
    .cms-content .cms-section--cta .cms-section-head{position:relative}
    .cms-content .cms-section--normal.cms-palette--light{border-radius:var(--r-lg);padding:2.5rem}
    .cms-content .cms-section--normal.cms-palette--green{border-radius:var(--r-lg);padding:2.5rem}
    .cms-content .cms-section--normal.cms-palette--green h3{color:#fff}
    .cms-content .cms-section--normal.cms-palette--green p{color:rgba(255,255,255,.82)}
    /* ── FEATURE CARDS ──────────────────────────────── */
    .cms-content .cms-feature-card{
        margin:0;border:1px solid var(--line);border-radius:var(--r);
        background:var(--surface);padding:1.4rem 1.25rem;box-shadow:var(--sh);
        display:flex;flex-direction:column;gap:.5rem;height:auto;
        transition:transform .3s cubic-bezier(.22,.68,0,1.2),box-shadow .3s,border-color .3s;
    }
    .cms-content .cms-column > .cms-feature-card:only-child{height:100%}
    .cms-content .cms-feature-card:hover{transform:translateY(-6px);box-shadow:var(--sh-lg);border-color:rgba(26,86,50,.2)}
    .cms-content .cms-feature-icon{display:flex;width:3rem;height:3rem;align-items:center;justify-content:center;border-radius:.8rem;background:linear-gradient(135deg,var(--cg-l),color-mix(in srgb,var(--cg) 18%,white));font-size:1.3rem;flex-shrink:0}
    .cms-content .cms-column:nth-child(1) .cms-feature-icon{background:linear-gradient(135deg,var(--cg-l),color-mix(in srgb,var(--cg) 18%,white))}
    .cms-content .cms-column:nth-child(2) .cms-feature-icon{background:linear-gradient(135deg,var(--gold-l),color-mix(in srgb,var(--gold) 28%,white))}
    .cms-content .cms-column:nth-child(3) .cms-feature-icon{background:linear-gradient(135deg,var(--cg-l),color-mix(in srgb,var(--theme-primary-light) 22%,white))}
    .cms-content .cms-column:nth-child(4) .cms-feature-icon{background:linear-gradient(135deg,var(--gold-l),color-mix(in srgb,var(--theme-dark) 14%,white))}
    .cms-content .cms-feature-card h3{margin:.4rem 0 .3rem;font-size:1rem}
    .cms-content .cms-feature-card p{margin:0;font-size:.89rem;line-height:1.7;color:var(--mist)}
    .cms-content .cms-palette--green .cms-feature-card{background:rgba(255,255,255,.09);border-color:rgba(255,255,255,.14);box-shadow:none}
    .cms-content .cms-palette--green .cms-feature-card:hover{background:rgba(255,255,255,.16);border-color:rgba(255,255,255,.26);transform:translateY(-4px)}
    .cms-content .cms-palette--green .cms-feature-card h3{color:#fff}
    .cms-content .cms-palette--green .cms-feature-card p{color:rgba(255,255,255,.72)}
    .cms-content .cms-palette--green .cms-feature-icon{background:rgba(255,255,255,.16)}
    /* ── TESTIMONIAL ────────────────────────────────── */
    .cms-content .cms-testimonial{
        margin:0;border:none;border-radius:var(--r-lg);
        background:var(--surface);padding:2.75rem 2.25rem;
        box-shadow:var(--sh-lg);position:relative;overflow:hidden;
    }
    .cms-content .cms-testimonial::before{
        content:'\201C';
        position:absolute;top:-1.5rem;left:1.25rem;
        font-family:var(--ff-head);
        font-size:11rem;line-height:1;font-weight:900;
        color:var(--cg-l);opacity:.65;pointer-events:none;
    }
    .cms-content .cms-testimonial .cms-quote{position:relative;z-index:1;font-size:1.06rem;line-height:1.82;color:var(--theme-text);font-style:italic;margin-bottom:1.75rem}
    .cms-content .cms-testimonial > div{display:flex;align-items:center;gap:1rem;border-top:1px solid var(--line);padding-top:1.25rem}
    .cms-content .cms-testimonial > div::before{content:'';display:flex;flex-shrink:0;width:3.25rem;height:3.25rem;border-radius:50%;background:linear-gradient(135deg,var(--cg),var(--theme-primary-light))}
    .cms-content .cms-testimonial strong{display:block;font-size:.95rem;color:var(--ink)}
    .cms-content .cms-testimonial span{display:block;font-size:.8rem;color:var(--mist);margin-top:.15rem}
    /* ── BUTTONS ────────────────────────────────────── */
    .cms-content .cms-button{display:inline-flex;align-items:center;border-radius:.75rem;background:var(--cg);padding:.7rem 1.25rem;font-size:.88rem;font-weight:800;color:#fff;text-decoration:none;transition:transform .22s,box-shadow .22s,background .2s;box-shadow:0 4px 14px rgba(26,86,50,.22)}
    .cms-content .cms-button:hover{transform:translateY(-2px);box-shadow:0 8px 26px rgba(26,86,50,.32);background:var(--cg-d)}
    .cms-content .cms-button--outline{background:transparent;color:var(--cg);border:2px solid var(--cg);box-shadow:none}
    .cms-content .cms-button--outline:hover{background:var(--cg);color:#fff}
    .cms-content .cms-button--dark{background:var(--ink);color:#fff}
    /* ── TABLE ──────────────────────────────────────── */
    .cms-content .cms-table-wrap{overflow-x:auto;border:1px solid var(--line);border-radius:var(--r);background:var(--surface);box-shadow:var(--sh)}
    .cms-content table{width:100%;border-collapse:separate;border-spacing:0;font-size:.86rem}
    .cms-content th{background:var(--cg-l);color:var(--cg-d);font-weight:800;text-align:left}
    .cms-content th,.cms-content td{border-right:1px solid var(--line);border-bottom:1px solid var(--line);padding:.85rem 1rem;vertical-align:middle}
    .cms-content th:last-child,.cms-content td:last-child{border-right:0}
    .cms-content tr:last-child td{border-bottom:0}
    /* ── GALLERY / VIDEO / MEDIA ────────────────────── */
    .cms-content .cms-gallery{display:grid;grid-template-columns:repeat(auto-fit,minmax(12rem,1fr));gap:1rem;margin:1.5rem 0}
    .cms-content .cms-video{position:relative;aspect-ratio:16/9;margin:1.5rem 0;overflow:hidden;border-radius:var(--r);background:var(--ink)}
    .cms-content .cms-video iframe{position:absolute;inset:0;height:100%;width:100%;border:0}
    .cms-content .cms-media-text{display:grid;gap:1.5rem;align-items:center}
    .cms-content .cms-media-text figure{margin:0}
    .cms-content .cms-media-copy{min-width:0}
    /* ── TIMELINE ───────────────────────────────────── */
    .cms-content .cms-section--timeline{background:none;margin:4rem 0}
    .cms-timeline-inner{width:min(100% - 2rem,1400px);margin:0 auto;padding:3.5rem 0}
    @media(min-width:640px){.cms-timeline-inner{width:min(100% - 3rem,1400px)}}
    @media(min-width:1024px){.cms-timeline-inner{width:min(100% - 10rem,1400px)}}
    .cms-content .cms-timeline-header{margin-bottom:2.5rem;max-width:52rem}
    .cms-content .cms-timeline-header h2{font-family:var(--ff-head)}
    /* single-column spine — line on the left, cards on the right */
    .cms-content .cms-timeline-track{position:relative;padding-left:2.75rem;max-width:58rem}
    .cms-content .cms-timeline-track::before{
        content:'';position:absolute;left:.85rem;top:.5rem;bottom:.5rem;
        width:2px;background:linear-gradient(180deg,var(--cg) 0%,var(--gold) 50%,var(--cg) 100%);
        border-radius:2px;
    }
    .cms-content .cms-timeline-entry{position:relative;margin-bottom:1.5rem}
    .cms-content .cms-timeline-entry:last-child{margin-bottom:0}
    .cms-content .cms-timeline-dot{
        position:absolute;left:-2.75rem;top:1.1rem;
        width:1.7rem;height:1.7rem;border-radius:50%;
        background:var(--cg);border:3px solid #fff;
        box-shadow:0 0 0 2px var(--cg),0 4px 14px rgba(26,86,50,.28);
    }
    .cms-content .cms-timeline-card .cms-block{margin:0}
    .cms-content .cms-timeline-card .cms-feature-card{height:auto;border-left:3px solid transparent}
    .cms-content .cms-timeline-entry:hover .cms-timeline-dot{background:var(--gold);box-shadow:0 0 0 2px var(--gold),0 4px 14px rgba(226,160,36,.35)}
    .cms-content .cms-timeline-entry:hover .cms-feature-card{border-left-color:var(--cg);box-shadow:var(--sh-lg)}
    /* ── RESPONSIVE GRIDS ───────────────────────────── */
    @media(min-width:540px){
        .cms-content .cms-section--stats .cms-row-grid{grid-template-columns:repeat(2,1fr)}
        .cms-content .cms-row--cols-2 .cms-row-grid{grid-template-columns:repeat(2,1fr)}
    }
    @media(min-width:768px){
        .cms-content .cms-hero-section{grid-template-columns:minmax(0,1fr) minmax(22rem,.64fr);padding-top:2.5rem;padding-bottom:4rem}
        .cms-content .cms-hero-section--text-only{grid-template-columns:minmax(0,1fr)}
        .cms-content .cms-row--cols-2 .cms-row-grid{grid-template-columns:repeat(2,1fr)}
        .cms-content .cms-row--cols-3 .cms-row-grid{grid-template-columns:repeat(3,1fr)}
        .cms-content .cms-row--cols-4 .cms-row-grid{grid-template-columns:repeat(2,1fr)}
        .cms-content .cms-section--cta{display:flex;align-items:center;justify-content:space-between;gap:2rem}
        .cms-content .cms-section--cta .cms-section-head{margin-bottom:0;max-width:60%}
        .cms-content .cms-media-text{grid-template-columns:1fr 1fr}
        .cms-content .cms-media-text--reverse figure{order:2}
        .cms-content .cms-media-text--reverse .cms-media-copy{order:1}
    }
    @media(min-width:1024px){
        .cms-content .cms-row--cols-4 .cms-row-grid{grid-template-columns:repeat(4,1fr)}
        .cms-content .cms-section--cards,.cms-content .cms-section--dark,.cms-content .cms-section--cta{padding:2.75rem 3rem}
        .cms-content .cms-section--normal.cms-palette--light,.cms-content .cms-section--normal.cms-palette--green{padding:3rem 3.5rem}
        .cms-content .cms-timeline-track{max-width:none;display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:1.15rem 2rem;padding-left:0}
        .cms-content .cms-timeline-track::before{left:50%;transform:translateX(-50%)}
        .cms-content .cms-timeline-entry{margin-bottom:0}
        .cms-content .cms-timeline-entry:nth-child(odd){padding-right:2rem}
        .cms-content .cms-timeline-entry:nth-child(even){padding-left:2rem;transform:translateY(3rem)}
        .cms-content .cms-timeline-dot{left:auto;right:-.85rem}
        .cms-content .cms-timeline-entry:nth-child(even) .cms-timeline-dot{right:auto;left:-.85rem}
    }
    @media(max-width:767px){
        .cms-content{font-size:.94rem}
        .cms-content .cms-hero-section{min-height:auto;padding:2.25rem 1rem 3.5rem}
        .cms-content .cms-hero-section h2{font-size:clamp(2rem,12vw,2.75rem)}
        .cms-content .cms-hero-badge{white-space:normal;border-radius:.85rem;line-height:1.45}
        .cms-content .cms-hero-image{min-height:17rem}
        .cms-content .cms-hero-image img{min-height:17rem}
        .cms-content .cms-section--stats{margin-top:-3rem;border-radius:1rem}
        .cms-content .cms-section--stats .cms-column{border-right:0;border-bottom:1px solid var(--line)}
        .cms-content .cms-section--stats .cms-column:last-child{border-bottom:0}
        .cms-content .cms-section--stats .cms-stat{padding:1.75rem 1rem}
        .cms-content .cms-section--cards,.cms-content .cms-section--dark,.cms-content .cms-section--cta,
        .cms-content .cms-section--normal.cms-palette--light,.cms-content .cms-section--normal.cms-palette--green{padding:1.5rem}
        .cms-content .cms-testimonial{padding:2rem 1.35rem}
        .cms-content .cms-section-actions{align-items:stretch;flex-direction:column}
        .cms-content .cms-button{justify-content:center;width:100%}
    }
    /* ── STAT REVEAL ────────────────────────────────── */
    @keyframes cms-fadeup{from{opacity:0;transform:translateY(14px)}to{opacity:1;transform:translateY(0)}}
    .cms-stat-anim{animation:cms-fadeup .55s cubic-bezier(.22,.68,0,1.2) both}
</style>
<script>
(function(){
    if (!('IntersectionObserver' in window)) return;
    var io = new IntersectionObserver(function(entries){
        entries.forEach(function(e){
            if(!e.isIntersecting) return;
            e.target.classList.add('cms-stat-anim');
            io.unobserve(e.target);
        });
    },{threshold:.3});
    document.querySelectorAll('.cms-stat').forEach(function(el){io.observe(el)});
})();
</script>
@endsection
