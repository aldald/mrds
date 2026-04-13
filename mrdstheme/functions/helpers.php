<?php

/**
 * Limiter le texte à un certain nombre de caractères (environ 3 lignes)
 */
function mrds_limit_text($text, $limit = 120)
{
    $text = strip_tags($text);
    if (strlen($text) <= $limit) {
        return $text;
    }
    $text = substr($text, 0, $limit);
    $text = substr($text, 0, strrpos($text, ' '));
    return $text . '...';
}
