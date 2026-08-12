<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\HtmlString;

class CmsPage extends Model
{
    protected $fillable = [
        'parent_id',
        'created_by',
        'title',
        'title_ne',
        'slug',
        'status',
        'content_blocks',
        'content_blocks_ne',
        'content_html',
        'featured_image',
        'template',
        'meta_title',
        'meta_title_ne',
        'meta_description',
        'meta_description_ne',
        'meta_keywords',
        'meta_keywords_ne',
        'sort_order',
        'published_at',
    ];

    protected $casts = [
        'content_blocks' => 'array',
        'content_blocks_ne' => 'array',
        'published_at' => 'datetime',
    ];

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'published')
            ->where(function (Builder $q) {
                $q->whereNull('published_at')->orWhere('published_at', '<=', now());
            });
    }

    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('sort_order')->orderBy('title');
    }

    public function getUrlAttribute(): string
    {
        return route('cms.pages.show', $this->slug);
    }

    public function renderBlocks(): HtmlString
    {
        if ($this->content_html) {
            return new HtmlString($this->content_html);
        }

        $html = collect($this->localizedContentBlocks())
            ->map(fn ($block) => $this->renderBlock($block))
            ->implode("\n");

        return new HtmlString($html);
    }

    public function localizedTitle(): string
    {
        return $this->localizedValue('title');
    }

    public function localizedMetaTitle(): ?string
    {
        return $this->localizedValue('meta_title') ?: null;
    }

    public function localizedMetaDescription(): ?string
    {
        return $this->localizedValue('meta_description') ?: null;
    }

    public function localizedMetaKeywords(): ?string
    {
        return $this->localizedValue('meta_keywords') ?: null;
    }

    public function localizedContentBlocks(): array
    {
        if (app()->getLocale() === 'ne' && ! empty($this->content_blocks_ne)) {
            return $this->content_blocks_ne;
        }

        return $this->content_blocks ?? [];
    }

    private function localizedValue(string $field): string
    {
        $translated = app()->getLocale() === 'ne' ? $this->getAttribute($field.'_ne') : null;

        return (string) ($translated ?: $this->getAttribute($field) ?: '');
    }

    private function renderBlock(array $block): string
    {
        $type = $block['type'] ?? 'paragraph';
        $data = $block['data'] ?? [];

        return match ($type) {
            'row' => $this->renderRow($block),
            'heading' => $this->renderHeading($data),
            'image' => $this->renderImage($data),
            'media_text' => $this->renderMediaText($data),
            'video' => $this->renderVideo($data),
            'gallery' => $this->renderGallery($data),
            'table' => $this->renderTable($data),
            'button' => $this->renderButton($data),
            'stat' => $this->renderStat($data),
            'feature_card' => $this->renderFeatureCard($data),
            'testimonial' => $this->renderTestimonial($data),
            'html' => (string) ($data['html'] ?? ''),
            default => '<div class="'.$this->blockClasses($data).'"><p>'.$this->formatText($data['text'] ?? '').'</p></div>',
        };
    }

    private function renderRow(array $row): string
    {
        $columns = collect($row['columns'] ?? [])
            ->filter(fn ($column) => is_array($column))
            ->values();

        if ($columns->isEmpty()) {
            return '';
        }

        $data = $row['data'] ?? [];
        if (($data['section'] ?? 'normal') === 'hero') {
            return $this->renderHeroRow($columns, $data);
        }

        $count = min(max($columns->count(), 1), 4);
        $gap = in_array($data['gap'] ?? 'normal', ['compact', 'normal', 'large'], true) ? $data['gap'] : 'normal';
        $style = $this->rowStyle($data);

        $html = $columns->map(function ($column) {
            $blocks = collect($column['blocks'] ?? [])
                ->filter(fn ($block) => is_array($block))
                ->map(fn ($block) => $this->renderBlock($block))
                ->implode("\n");

            return '<div class="cms-column">'.$blocks.'</div>';
        })->implode("\n");

        return '<section class="'.$this->rowClasses($data).' cms-row--cols-'.$count.' cms-row--gap-'.$gap.'"'.$style.'>'.$this->renderSectionHeader($data).'<div class="cms-row-grid">'.$html.'</div></section>';
    }

    private function renderHeroRow($columns, array $data): string
    {
        $image = ! empty($data['image']) ? e($this->publicImageUrl((string) $data['image'])) : '';
        $heroLayout = $image ? ' cms-hero-section--with-media' : ' cms-hero-section--text-only';
        $focus = collect($columns)
            ->flatMap(fn ($column) => collect($column['blocks'] ?? []))
            ->firstWhere('type', 'feature_card');
        $focusData = $focus['data'] ?? [];
        $extraBlocks = collect($columns)
            ->flatMap(fn ($column) => collect($column['blocks'] ?? []))
            ->reject(fn ($block) => ($block['type'] ?? null) === 'feature_card')
            ->map(fn ($block) => $this->renderBlock($block))
            ->implode("\n");

        return '<section class="'.$this->rowClasses($data).' cms-hero-section'.$heroLayout.'"'.$this->rowStyle($data).'>'
            .'<div class="cms-hero-copy">'
            .'<nav class="cms-hero-breadcrumb"><a href="'.e(url('/')).'">'.e(__('site.common.home')).'</a><span>/</span><strong>'.e($data['eyebrow'] ?? $this->localizedTitle()).'</strong></nav>'
            .($data['badge'] ?? $data['eyebrow'] ?? '' ? '<p class="cms-hero-badge">'.e($data['badge'] ?? $data['eyebrow']).'</p>' : '')
            .(! empty($data['title']) ? '<h2>'.e($data['title']).'</h2>' : '')
            .(! empty($data['description']) ? '<p class="cms-section-desc">'.$this->formatText($data['description']).'</p>' : '')
            .$this->renderSectionButtons($data)
            .$extraBlocks
            .'</div>'
            .($image ? '<figure class="cms-hero-image"><img src="'.$image.'" alt="'.e($data['title'] ?? '').'"><figcaption><span>'.e($focusData['title'] ?? '').'</span><strong>'.e($focusData['text'] ?? '').'</strong></figcaption></figure>' : '')
            .'</section>';
    }

    private function renderSectionHeader(array $data): string
    {
        $section = $data['section'] ?? 'normal';
        if ($section === 'normal') {
            return '';
        }

        $eyebrow = $data['eyebrow'] ?? $data['badge'] ?? '';
        $title = $data['title'] ?? '';
        $description = $data['description'] ?? '';
        $buttons = $this->renderSectionButtons($data);

        if (! $eyebrow && ! $title && ! $description && ! $buttons) {
            return '';
        }

        return '<div class="cms-section-head">'
            .($eyebrow ? '<p class="cms-eyebrow">'.e($eyebrow).'</p>' : '')
            .($title ? '<h2>'.e($title).'</h2>' : '')
            .($description ? '<p class="cms-section-desc">'.$this->formatText($description).'</p>' : '')
            .$buttons
            .'</div>';
    }

    private function renderSectionButtons(array $data): string
    {
        $buttons = '';
        if (! empty($data['primary_label']) && ! empty($data['primary_url'])) {
            $buttons .= '<a class="cms-button" href="'.e($data['primary_url']).'">'.e($data['primary_label']).'</a>';
        }
        if (! empty($data['secondary_label']) && ! empty($data['secondary_url'])) {
            $buttons .= '<a class="cms-button cms-button--outline" href="'.e($data['secondary_url']).'">'.e($data['secondary_label']).'</a>';
        }

        return $buttons ? '<div class="cms-section-actions">'.$buttons.'</div>' : '';
    }

    private function renderHeading(array $data): string
    {
        $level = in_array((string) ($data['level'] ?? '2'), ['2', '3', '4'], true) ? (string) $data['level'] : '2';

        return '<div class="'.$this->blockClasses($data).'"><h'.$level.'>'.$this->formatText($data['text'] ?? '').'</h'.$level.'></div>';
    }

    private function renderImage(array $data): string
    {
        if (empty($data['url'])) {
            return '';
        }

        return '<figure class="'.$this->blockClasses($data).'"><img src="'.e($this->publicImageUrl((string) $data['url'])).'" alt="'.e($data['alt'] ?? '').'">'.(! empty($data['caption']) ? '<figcaption>'.e($data['caption']).'</figcaption>' : '').'</figure>';
    }

    private function renderMediaText(array $data): string
    {
        $layout = ($data['layout'] ?? 'image_left') === 'image_right' ? ' cms-media-text--reverse' : '';
        $image = ! empty($data['url'])
            ? '<figure><img src="'.e($data['url']).'" alt="'.e($data['alt'] ?? '').'"></figure>'
            : '<div></div>';
        $text = '<div class="cms-media-copy">'
            .(! empty($data['heading']) ? '<h2>'.$this->formatText($data['heading']).'</h2>' : '')
            .(! empty($data['text']) ? '<p>'.$this->formatText($data['text']).'</p>' : '')
            .(! empty($data['button_label']) && ! empty($data['button_url']) ? '<a class="cms-button" href="'.e($data['button_url']).'">'.e($data['button_label']).'</a>' : '')
            .'</div>';

        return '<section class="'.$this->blockClasses($data).' cms-media-text'.$layout.'">'.$image.$text.'</section>';
    }

    private function renderVideo(array $data): string
    {
        if (empty($data['url'])) {
            return '';
        }

        $url = $this->videoEmbedUrl((string) $data['url']);

        return '<div class="'.$this->blockClasses($data).'"><div class="cms-video"><iframe src="'.e($url).'" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" allowfullscreen loading="lazy"></iframe></div></div>';
    }

    private function videoEmbedUrl(string $url): string
    {
        $url = trim($url);
        $parts = parse_url($url);
        $host = strtolower($parts['host'] ?? '');
        $path = trim($parts['path'] ?? '', '/');

        if (str_contains($host, 'youtu.be') && $path !== '') {
            return 'https://www.youtube.com/embed/'.rawurlencode(explode('/', $path)[0]);
        }

        if (str_contains($host, 'youtube.com')) {
            parse_str($parts['query'] ?? '', $query);
            if (! empty($query['v'])) {
                return 'https://www.youtube.com/embed/'.rawurlencode((string) $query['v']);
            }

            if (str_starts_with($path, 'shorts/')) {
                return 'https://www.youtube.com/embed/'.rawurlencode(substr($path, 7));
            }

            if (str_starts_with($path, 'embed/')) {
                return $url;
            }
        }

        if (str_contains($host, 'vimeo.com') && $path !== '' && ! str_contains($host, 'player.vimeo.com')) {
            $id = explode('/', $path)[0] ?? '';
            if ($id !== '') {
                return 'https://player.vimeo.com/video/'.rawurlencode($id);
            }
        }

        return $url;
    }

    private function renderGallery(array $data): string
    {
        $images = array_filter(array_map('trim', explode("\n", (string) ($data['images'] ?? ''))));
        if (empty($images)) {
            return '';
        }

        return '<div class="'.$this->blockClasses($data).'"><div class="cms-gallery">'.collect($images)->map(fn ($url) => '<img src="'.e($this->publicImageUrl((string) $url)).'" alt="">')->implode('').'</div></div>';
    }

    private function renderTable(array $data): string
    {
        $rowsText = (string) ($data['rows'] ?? '');
        if ($rowsText === '') {
            return '';
        }

        $decoded = json_decode($rowsText, true);
        if (is_array($decoded) && isset($decoded['cells'])) {
            return $this->renderJsonTable($data, $decoded);
        }

        $rows = array_filter(array_map('trim', explode("\n", $rowsText)));
        if (empty($rows)) {
            return '';
        }

        return '<div class="'.$this->blockClasses($data).'"><div class="cms-table-wrap"><table>'.collect($rows)->map(function ($row, $index) {
            $tag = $index === 0 ? 'th' : 'td';

            return '<tr>'.collect(str_getcsv($row, ',', '"', '\\'))->map(fn ($cell) => '<'.$tag.'>'.e($cell).'</'.$tag.'>')->implode('').'</tr>';
        })->implode('').'</table></div></div>';
    }

    private function renderJsonTable(array $data, array $tableData): string
    {
        $cells = $tableData['cells'] ?? [];
        $hasBorder = ($tableData['border'] ?? true) !== false;
        $paddingKey = in_array($tableData['padding'] ?? 'md', ['xs', 'sm', 'md', 'lg'], true) ? ($tableData['padding'] ?? 'md') : 'md';
        $headerRow = ! empty($tableData['headerRow']);
        $paddingMap = ['xs' => '3px 5px', 'sm' => '5px 8px', 'md' => '8px 12px', 'lg' => '12px 18px'];
        $cellPadding = $paddingMap[$paddingKey];
        $borderAttr = $hasBorder ? '1px solid #d1d5db' : 'none';

        $html = '<table style="border-collapse:collapse;width:100%">';
        foreach ($cells as $rowIndex => $row) {
            $html .= '<tr>';
            $isHeaderRow = $headerRow && $rowIndex === 0;
            foreach ((array) $row as $cell) {
                if (! empty($cell['merged'])) {
                    continue;
                }
                $tag = $isHeaderRow ? 'th' : 'td';
                $rs = (int) ($cell['rowspan'] ?? 1);
                $cs = (int) ($cell['colspan'] ?? 1);
                $rsAttr = $rs > 1 ? " rowspan=\"{$rs}\"" : '';
                $csAttr = $cs > 1 ? " colspan=\"{$cs}\"" : '';
                $styles = ["padding:{$cellPadding}", "border:{$borderAttr}", 'vertical-align:middle'];
                if (! empty($cell['bg'])) {
                    $bg = preg_replace('/[^a-zA-Z0-9#(),. %]/', '', (string) $cell['bg']);
                    if ($bg !== '') {
                        $styles[] = 'background:'.$bg;
                    }
                }
                if (! empty($cell['color'])) {
                    $color = preg_replace('/[^a-zA-Z0-9#(),. %]/', '', (string) $cell['color']);
                    if ($color !== '') {
                        $styles[] = 'color:'.$color;
                    }
                }
                if (! empty($cell['bold'])) {
                    $styles[] = 'font-weight:bold';
                }
                $align = in_array($cell['align'] ?? '', ['left', 'center', 'right'], true) ? $cell['align'] : ($isHeaderRow ? 'center' : 'left');
                $styles[] = 'text-align:'.$align;
                if (isset($cell['wrap']) && $cell['wrap'] === false) {
                    $styles[] = 'white-space:nowrap';
                }
                $styleAttr = ' style="'.implode(';', $styles).'"';
                $html .= '<'.$tag.$rsAttr.$csAttr.$styleAttr.'>'.nl2br(e($cell['text'] ?? '')).'</'.$tag.'>';
            }
            $html .= '</tr>';
        }
        $html .= '</table>';

        return '<div class="'.$this->blockClasses($data).'"><div class="cms-table-wrap">'.$html.'</div></div>';
    }

    private function renderButton(array $data): string
    {
        if (empty($data['label']) || empty($data['url'])) {
            return '';
        }

        $rawStyle = $data['style'] ?? 'primary';
        $style = in_array($rawStyle, ['primary', 'outline', 'dark'], true) ? $rawStyle : 'primary';

        return '<p class="'.$this->blockClasses($data).'"><a class="cms-button cms-button--'.$style.'" href="'.e($data['url']).'">'.e($data['label']).'</a></p>';
    }

    private function renderStat(array $data): string
    {
        return '<div class="'.$this->blockClasses($data).' cms-stat"><p>'.e($data['label'] ?? '').'</p><strong>'.e($data['value'] ?? '').'</strong></div>';
    }

    private function renderFeatureCard(array $data): string
    {
        return '<article class="'.$this->blockClasses($data).' cms-feature-card">'
            .'<div class="cms-feature-icon">'.e($data['icon'] ?? '⭐').'</div>'
            .'<h3>'.e($data['title'] ?? '').'</h3>'
            .'<p>'.$this->formatText($data['text'] ?? '').'</p>'
            .'</article>';
    }

    private function renderTestimonial(array $data): string
    {
        return '<article class="'.$this->blockClasses($data).' cms-testimonial">'
            .'<p class="cms-quote">"'.$this->formatText($data['quote'] ?? '').'"</p>'
            .'<div><strong>'.e($data['name'] ?? '').'</strong>'.(! empty($data['role']) ? '<span>'.e($data['role']).'</span>' : '').'</div>'
            .'</article>';
    }

    private function blockClasses(array $data): string
    {
        $rawWidth = $data['width'] ?? 'normal';
        $rawAlign = $data['align'] ?? 'left';
        $width = in_array($rawWidth, ['normal', 'wide', 'narrow'], true) ? $rawWidth : 'normal';
        $align = in_array($rawAlign, ['left', 'center', 'right'], true) ? $rawAlign : 'left';

        return 'cms-block cms-block--'.$width.' cms-align--'.$align;
    }

    private function rowClasses(array $data): string
    {
        $rawWidth = $data['width'] ?? 'normal';
        $rawSection = $data['section'] ?? 'normal';
        $rawPalette = $data['palette'] ?? 'default';
        $rawPattern = $data['pattern'] ?? 'grid';
        $width = in_array($rawWidth, ['normal', 'wide', 'narrow'], true) ? $rawWidth : 'normal';
        $section = in_array($rawSection, ['normal', 'hero', 'stats', 'cards', 'dark', 'cta'], true) ? $rawSection : 'normal';
        $palette = in_array($rawPalette, ['default', 'light', 'dark', 'green', 'blue', 'amber', 'image'], true) ? $rawPalette : 'default';
        $pattern = in_array($rawPattern, ['none', 'grid', 'dots', 'diagonal'], true) ? $rawPattern : 'grid';

        return 'cms-row cms-row--'.$width.' cms-section--'.$section.' cms-palette--'.$palette.' cms-pattern--'.$pattern;
    }

    private function rowStyle(array $data): string
    {
        if (($data['palette'] ?? null) !== 'image' || empty($data['background_image'])) {
            return '';
        }

        return ' style="background-image:linear-gradient(180deg, rgba(11,36,21,.68), rgba(11,36,21,.82)), url(\''.e($this->publicImageUrl((string) $data['background_image'])).'\')"';
    }

    private function publicImageUrl(string $path): string
    {
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://') || str_starts_with($path, '/')) {
            return $path;
        }

        return asset($path);
    }

    private function formatText(?string $text): string
    {
        $html = nl2br(e($text ?? ''));
        $html = preg_replace('/\*\*(.+?)\*\*/s', '<strong>$1</strong>', $html);
        $html = preg_replace('/_(.+?)_/s', '<em>$1</em>', $html);

        return $html;
    }
}
