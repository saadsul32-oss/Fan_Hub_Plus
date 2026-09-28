<?php
/**
 * Admin CRUD configuration. Each entity maps an admin tab to a table and its editable fields.
 * Field types: text, textarea, longtext, number, date, color, select, checkbox, image, password
 */
function cat_options(): array { $o = []; foreach (categories() as $c) $o[$c['category_id']] = $c['name']; return $o; }

function admin_entities(): array
{
    $cats = cat_options();
    return [
        'categories' => [
            'table' => 'categories', 'pk' => 'category_id', 'label' => 'Category', 'order' => 'sort_order, category_id',
            'columns' => ['name' => 'Name', 'tagline' => 'Tagline', 'stat1_value' => 'Series Tracked', 'color' => 'Color'],
            'fields' => [
                'name' => ['text', 'Name', true], 'slug' => ['text', 'Slug (url key)', true], 'tagline' => ['text', 'Tagline', true],
                'description' => ['textarea', 'Description', true], 'image' => ['image', 'Cover image', true],
                'color' => ['color', 'Accent color', true], 'glow' => ['text', 'Glow (rgba)', true],
                'stat1_label' => ['text', 'Stat 1 label'], 'stat1_value' => ['text', 'Stat 1 value'],
                'stat2_label' => ['text', 'Stat 2 label'], 'stat2_value' => ['text', 'Stat 2 value'], 'sort_order' => ['number', 'Sort order'],
            ],
        ],
        'content' => [
            'table' => 'contents', 'pk' => 'content_id', 'label' => 'Content', 'where' => "status <> 'pending'", 'order' => 'release_date DESC, content_id DESC',
            'columns' => ['title' => 'Title', 'category_id' => 'Category', 'type' => 'Type', 'release_date' => 'Date', 'views' => 'Views', 'status' => 'Status'],
            'fields' => [
                'title' => ['text', 'Title', true], 'category_id' => ['select', 'Category', true, $cats],
                'type' => ['select', 'Type', true, ['article' => 'Article', 'video' => 'Video', 'trailer' => 'Trailer', 'audio' => 'Audio']],
                'genre' => ['text', 'Genre (tag)'], 'image' => ['image', 'Thumbnail / cover', true],
                'excerpt' => ['textarea', 'Excerpt / short description'], 'body' => ['longtext', 'Article body (blank line between paragraphs)'],
                'media_url' => ['text', 'Media URL (mp4 / mp3 / YouTube / Vimeo)'], 'meta' => ['text', 'Meta line (e.g. "Podcast · 58 min")'],
                'read_time' => ['text', 'Read time'], 'release_date' => ['date', 'Release / publish date'], 'popularity' => ['number', 'Popularity (0-100)'],
                'is_featured' => ['checkbox', 'Featured (hero article / featured trailer)'], 'is_trending' => ['checkbox', 'Show in Trending'],
                'in_gallery' => ['checkbox', 'Show in Watch & Listen gallery'],
                'status' => ['select', 'Status', true, ['published' => 'Published', 'pending' => 'Pending', 'rejected' => 'Rejected']],
            ],
        ],
        'characters' => [
            'table' => 'characters', 'pk' => 'character_id', 'label' => 'Character', 'order' => 'character_id',
            'columns' => ['name' => 'Name', 'fandom_label' => 'Fandom', 'role' => 'Role', 'views' => 'Views'],
            'fields' => [
                'name' => ['text', 'Name', true], 'category_id' => ['select', 'Category', true, $cats], 'fandom_label' => ['text', 'Fandom label (e.g. "Anime · Ashen Requiem")', true],
                'role' => ['text', 'Role'], 'image' => ['image', 'Portrait', true], 'short_desc' => ['text', 'Short description'],
                'bio' => ['textarea', 'Biography', true], 'timeline' => ['textarea', 'Timeline (one per line: Label | Event)'],
                'related' => ['textarea', 'Related media (one per line)'], 'in_carousel' => ['checkbox', 'Show in 3D carousel'],
            ],
        ],
        'merch' => [
            'table' => 'merchandise', 'pk' => 'item_id', 'label' => 'Product', 'order' => 'item_id',
            'columns' => ['name' => 'Name', 'fandom_label' => 'Fandom', 'item_type' => 'Category', 'price' => 'Price', 'tag' => 'Tag', 'views' => 'Views'],
            'fields' => [
                'name' => ['text', 'Name', true], 'category_id' => ['select', 'Fandom category', true, $cats], 'fandom_label' => ['text', 'Fandom label', true],
                'item_type' => ['text', 'Item type (Figure, Apparel…)', true], 'image' => ['image', 'Image', true], 'description' => ['textarea', 'Description', true],
                'price' => ['text', 'Display price'], 'tag' => ['select', 'Tag', false, ['' => '— none —', 'Limited Edition' => 'Limited Edition', 'Pre-Order' => 'Pre-Order', 'Collectible' => 'Collectible']],
                'is_upcoming' => ['checkbox', 'Upcoming drop'],
            ],
        ],
        'releases' => [
            'table' => 'releases', 'pk' => 'release_id', 'label' => 'Release', 'order' => 'release_date',
            'columns' => ['title' => 'Title', 'kind' => 'Kind', 'category_id' => 'Category', 'release_date' => 'Date', 'tag' => 'Tag'],
            'fields' => [
                'title' => ['text', 'Title', true], 'category_id' => ['select', 'Category', true, $cats],
                'kind' => ['select', 'Kind', true, array_combine($k = ['Anime', 'Game', 'Movie', 'Show', 'Comic', 'Manga', 'Album', 'Merch'], $k)],
                'release_date' => ['date', 'Release date', true], 'image' => ['image', 'Image'], 'description' => ['text', 'Description'], 'tag' => ['text', 'Tag'],
            ],
        ],
        'events' => [
            'table' => 'events', 'pk' => 'event_id', 'label' => 'Event', 'order' => 'event_date',
            'columns' => ['title' => 'Title', 'event_date' => 'Date', 'city' => 'City', 'venue' => 'Venue', 'category_id' => 'Category'],
            'fields' => [
                'title' => ['text', 'Title', true], 'category_id' => ['select', 'Category', true, $cats], 'event_date' => ['date', 'Date', true],
                'city' => ['text', 'City', true], 'venue' => ['text', 'Venue / location', true],
                'latitude' => ['number', 'Latitude (map)'], 'longitude' => ['number', 'Longitude (map)'],
                'description' => ['textarea', 'Description', true], 'ticket_url' => ['text', 'Ticket link'],
            ],
        ],
        'users' => [
            'table' => 'users', 'pk' => 'user_id', 'label' => 'User', 'order' => 'user_id',
            'columns' => ['name' => 'Name', 'username' => 'Username', 'email' => 'Email', 'role' => 'Role', 'status' => 'Status', 'last_login' => 'Last login'],
            'fields' => [
                'name' => ['text', 'Full name', true], 'username' => ['text', 'Username', true], 'email' => ['text', 'Email', true],
                'role' => ['select', 'Role', true, ['user' => 'Registered user', 'admin' => 'Administrator']],
                'status' => ['select', 'Status', true, ['active' => 'Active', 'blocked' => 'Blocked']],
                'email_verified' => ['checkbox', 'Email verified'], 'password' => ['password', 'New password (leave blank to keep)'],
            ],
        ],
        'faq' => [
            'table' => 'chatbot_faq', 'pk' => 'faq_id', 'label' => 'Knowledge Entry', 'order' => 'sort_order, faq_id',
            'columns' => ['keywords' => 'Keywords', 'answer' => 'Response', 'is_suggestion' => 'Suggested'],
            'fields' => [
                'question' => ['text', 'Suggested question (shown as a chip)'], 'keywords' => ['text', 'Keywords (comma separated)', true],
                'answer' => ['textarea', 'Response', true], 'is_suggestion' => ['checkbox', 'Show as suggestion chip'], 'sort_order' => ['number', 'Sort order'],
            ],
        ],
    ];
}
