<?php

function vfd_theme_asset(string $path): string
{
    return get_theme_file_uri('/assets/' . ltrim($path, '/'));
}

function vfd_esc_attr($value)
{
    return esc_attr($value);
}

function vfd_esc_html($value)
{
    return esc_html($value);
}
