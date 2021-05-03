<?php declare(strict_types=1);
require __DIR__ . "/../vendor/autoload.php";
use Nette\Utils\{Strings, Paginator};

const basedir = "/shara-and-gers";

function paginator(int $page, int $perPage, int $itemCount): Paginator
{
    $paginator = new Paginator;
    $paginator->setPage($page);
    $paginator->setItemsPerPage($perPage);
    $paginator->setItemCount($itemCount);
    
    return $paginator;
}

function createTemplateEngine(): PHPTAL
{
    $phptal = new PHPTAL;
    $phptal->setPhpCodeDestination(__DIR__ . "/../tmp");
    
    return $phptal;
}

function render(string $template, string $destination, $data): void
{
    $engine = createTemplateEngine();
    foreach($data as $key => $value)
        $engine->{$key} = $value;
    
    $engine->setTemplate(__DIR__ . "/../templates/" . strtolower($template) . ".xml");
    file_put_contents(__DIR__ . "/../dist/$destination.html", $engine->execute());
}

function tagName(string $tag): string
{
    return ([
        "AW" => "Accidental Wetting",
        "FD" => "Female Desperation",
        "SP" => "Spanking",
        "GS" => "Golden Showers",
        "BO" => "Bondage",
        "FP" => "Foreplay",
        "FW" => "Female Wetting",
        "HU" => "Humiliation",
        "MB" => "Masturbation",
        "DI" => "Diapers",
        "DO" => "Domination",
        "SE" => "Sex",
        "MD" => "Male Desperation",
        "DW" => "Deliberate Wetting",
        "FE" => "Fear Wetting",
        "BW" => "Bed Wetting",
        "MW" => "Male Wetting",
        "EX" => "Exhibitionism",
    ])[trim((string) $tag)] ?? $tag;
}

function storyPreview($story): string
{
    $text = file_get_contents(
        __DIR__ .
        "/../../stories/" .
        (string) $story->Author->attributes()->slug .
        "/" .
        (string) $story->Title->attributes()->slug .
        "/eng.textile"
    );
    
    return str_replace("�", "'", Strings::truncate($text, 720));
}

function storyUrl($story): string
{
    if(!$story->Author)
        exit(json_encode($story));
        
    return (
        "/~" .
        (string) $story->Author->attributes()->slug .
        "/" .
        date("o/m/d", strtotime((string) $story->Date)) .
        "/" .
        (string) $story->Title->attributes()->slug
    );
}

function storiesArray($input): array
{
    $stories   = [];
    foreach($input as $story) {
        $tags = [];
        foreach($story->attributes() as $tag => $tag) {
            $tags[] = [
                "id"   => (string) $tag,
                "name" => tagName((string) $tag),
            ];
        }
        
        $stories[] = (object) [
            "title"  => (string) $story->Title,
            "link"   => basedir . storyUrl($story),
            "author" => [
                "name" => (string) $story->Author,
                "slug" => (string) $story->Author->attributes()->slug,
            ],
            "preview" => storyPreview($story),
            "created" => date("d M o, l", strtotime((string) $story->Date)),
            "tags"    => $tags,
        ];
    }
    
    return $stories;
}
